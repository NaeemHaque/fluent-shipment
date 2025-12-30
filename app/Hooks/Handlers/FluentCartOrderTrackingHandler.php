<?php

namespace FluentShipment\App\Hooks\Handlers;

use FluentShipment\App\Models\Shipment;
use FluentShipment\App\Models\ShipmentTrackingEvent;

class FluentCartOrderTrackingHandler
{
    /**
     * Register the hooks
     */
    public static function register()
    {
        add_filter('fluent_cart/customer/order_details_section_parts', [__CLASS__, 'addOrderTrackingSection'], 10, 2);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueueTrackingAssets']);
    }

    /**
     * Add tracking section to FluentCart order details
     * 
     * @param array $sections
     * @param array $data
     * @return array
     */
    public static function addOrderTrackingSection($sections, $data)
    {
        $order = $data['order'] ?? null;
        
        if (!$order || $order->fulfillment_type !== 'physical') {
            return $sections;
        }

        // Get shipment for this order
        $shipment = Shipment::where('order_id', $order->id)
            ->where('order_source', Shipment::SOURCE_FLUENT_CART)
            ->with('rider')
            ->first();

        if (!$shipment) {
            return $sections;
        }

        // Ensure CSS is enqueued since we're displaying tracking content
        self::enqueueTrackingCss();

        // Get tracking events
        $trackingEvents = ShipmentTrackingEvent::forShipment($shipment->id)
            ->orderBy('event_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Generate the tracking section HTML
        $trackingHtml = self::generateTrackingHtml($shipment, $trackingEvents);

        // Add to the after_transactions section
        $sections['after_transactions'] = ($sections['after_transactions'] ?? '') . $trackingHtml;

        return $sections;
    }

    /**
     * Generate tracking section HTML
     * 
     * @param \FluentShipment\App\Models\Shipment $shipment
     * @param \Illuminate\Database\Eloquent\Collection $trackingEvents
     * @return string
     */
    private static function generateTrackingHtml($shipment, $trackingEvents)
    {
        $trackingNumber     = esc_html($shipment->tracking_number);
        $currentStatus      = esc_html(self::getStatusLabel($shipment->current_status));
        $currentStatusClass = esc_attr(self::getStatusClass($shipment->current_status));


        $html = '<div class="fct-shipment-tracking-wrapper">';
        $html .= '<article class="fct-single-order-box fct-shipment-tracking" role="region" aria-labelledby="tracking-title">';
        $html .= '<header class="fct-single-order-header">';
        $html .= '<h2 id="tracking-title" class="title">Order Status</h2>';
        $html .= '</header>';
        $html .= '<div class="fct-shipment-tracking-content">';

        // Current Status Header
        $html .= '<div class="fct-tracking-header">';
        $html .= '<div class="fct-tracking-info">';
        $html .= '<h3 class="fct-tracking-number">Tracking Number: <span>' . $trackingNumber . '</span></h3>';
        $html .= '<div class="fct-current-status">';
        $html .= '<span class="fct-status-badge ' . $currentStatusClass . '">' . $currentStatus . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Tracking Timeline
        if (!$trackingEvents->isEmpty()) {
            $html .= '<div class="fct-tracking-timeline">';
            $html .= '<h4 class="fct-timeline-title">Tracking History</h4>';
            
            $html .= '<div class="fct-timeline">';
            foreach ($trackingEvents as $index => $event) {
                $isFirst = $index === 0;
                $isLast = $index === count($trackingEvents) - 1;
                $isMilestone = $event->is_milestone;
                
                $eventStatus = esc_html(self::getStatusLabel($event->event_status));
                $eventStatusClass = esc_attr(self::getStatusClass($event->event_status));
                $eventDate = $event->event_date ? esc_html(date('M j, Y g:i A', strtotime($event->event_date))) : '';
                $eventDescription = $event->event_description ? esc_html($event->event_description) : '';
                $eventLocation = $event->event_location ? esc_html($event->event_location) : '';

                $itemClasses = ['fct-timeline-item'];
                if ($isFirst) $itemClasses[] = 'fct-timeline-current';
                if ($isMilestone) $itemClasses[] = 'fct-timeline-milestone';

                $html .= '<div class="' . implode(' ', $itemClasses) . '">';
                $html .= '<div class="fct-timeline-marker">';
                $html .= '<div class="fct-timeline-dot"></div>';
                if (!$isLast) {
                    $html .= '<div class="fct-timeline-line"></div>';
                }
                $html .= '</div>';
                
                $html .= '<div class="fct-timeline-content">';
                $html .= '<div class="fct-timeline-header">';
                $html .= '<span class="fct-timeline-status ' . $eventStatusClass . '">' . $eventStatus . '</span>';
                $html .= '<span class="fct-timeline-date">' . $eventDate . '</span>';
                $html .= '</div>';
                
                if ($eventDescription) {
                    $html .= '<div class="fct-timeline-description">' . $eventDescription . '</div>';
                }
                
                if ($eventLocation) {
                    $html .= '<div class="fct-timeline-location">📍 ' . $eventLocation . '</div>';
                }
                
                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }

        // Rider Information (for out_for_delivery status)
        if ( ($shipment->current_status === 'out_for_delivery' || $shipment->current_status === 'delivered') && $shipment->rider) {
            $rider = $shipment->rider;
            $html .= '<div class="fct-rider-info-section">';
            $html .= '<h4 class="fct-rider-title">Delivery Person</h4>';
            $html .= '<div class="fct-rider-details">';

            if ($rider->avatar_url) {
                $html .= '<div class="fct-rider-avatar">';
                $html .= '<img src="' . esc_url($rider->avatar_url) . '" alt="' . esc_attr($rider->rider_name) . '" />';
                $html .= '</div>';
            }

            $html .= '<div class="fct-rider-info-content">';
            $html .= '<div class="fct-rider-name">' . esc_html($rider->rider_name) . '</div>';

            if ($rider->phone) {
                $html .= '<div class="fct-rider-contact">📞 ' . esc_html($rider->phone) . '</div>';
            }

            if ($rider->vehicle_type) {
                $html .= '<div class="fct-rider-vehicle">🚐 ' . esc_html(ucfirst($rider->vehicle_type)) . '</div>';
            }

            if ($rider->rating > 0) {
                $html .= '<div class="fct-rider-rating">⭐ ' . esc_html(number_format($rider->rating, 1)) . '/5.0</div>';
            }

            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</article>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Enqueue tracking assets for FluentCart pages
     */
    public static function enqueueTrackingAssets()
    {
        // Check if this is a FluentCart page in multiple ways
        if (!self::isFluentCartCustomerPage()) {
            return;
        }

        self::enqueueTrackingCss();
    }

    /**
     * Enqueue tracking CSS - can be called directly when needed
     */
    public static function enqueueTrackingCss()
    {
        // Prevent duplicate enqueuing
        if (wp_style_is('fluent-shipment-cart-tracking', 'enqueued') || wp_style_is('fluent-shipment-cart-tracking', 'done')) {
            return;
        }

        $currentDir = dirname(__DIR__, 2);
        $pluginRoot = dirname($currentDir);
        $pluginUrl  = plugins_url('', $pluginRoot . '/fluent-shipment.php');
        $version    = defined('FLUENTSHIPMENT_PLUGIN_VERSION') ? FLUENTSHIPMENT_PLUGIN_VERSION : '1.0.0';

        wp_enqueue_style(
            'fluent-shipment-cart-tracking',
            $pluginUrl . '/public/css/fluent-cart-tracking.css',
            [],
            $version
        );
    }

    /**
     * Check if this is a FluentCart customer profile page
     * 
     * @return bool
     */
    private static function isFluentCartCustomerPage()
    {
        // Check if FluentCart is active
        if (!function_exists('fluent_cart') && !defined('FLUENTCART_VERSION')) {
            return false;
        }

        // Check if we're on frontend
        if (is_admin()) {
            return false;
        }

        global $post;

        // Multiple detection methods
        $isFluentCartPage = false;

        if ($post && has_shortcode($post->post_content, 'fluent_cart_customer_profile')) {
            $isFluentCartPage = true;
        }

        if (isset($_GET['fluent-cart']) || isset($_GET['fct_page'])) {
            $isFluentCartPage = true;
        }

        $currentUrl = sanitize_text_field($_SERVER['REQUEST_URI'] ?? '');
        if (strpos($currentUrl, 'fluent-cart') !== false || strpos($currentUrl, 'customer-profile') !== false) {
            $isFluentCartPage = true;
        }

        if (function_exists('fluent_cart') && method_exists(fluent_cart(), 'isCustomerProfilePage')) {
            $isFluentCartPage = fluent_cart()->isCustomerProfilePage();
        }

        return $isFluentCartPage;
    }

    /**
     * Get status display label
     * 
     * @param string $status
     * @return string
     */
    private static function getStatusLabel($status)
    {
        $labels = [
            'pending'          => 'Pending',
            'processing'       => 'Processing',
            'shipped'          => 'Shipped',
            'in_transit'       => 'In Transit',
            'out_for_delivery' => 'Out for Delivery',
            'delivered'        => 'Delivered',
            'failed'           => 'Delivery Failed',
            'cancelled'        => 'Cancelled',
        ];

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Get status CSS class
     * 
     * @param string $status
     * @return string
     */
    private static function getStatusClass($status)
    {
        $classes = [
            'pending' => 'fct-status-pending',
            'processing' => 'fct-status-processing',
            'shipped' => 'fct-status-shipped',
            'in_transit' => 'fct-status-transit',
            'out_for_delivery' => 'fct-status-delivery',
            'delivered' => 'fct-status-delivered', 
            'failed' => 'fct-status-failed',
            'cancelled' => 'fct-status-cancelled',
        ];

        return $classes[$status] ?? 'fct-status-default';
    }
}
