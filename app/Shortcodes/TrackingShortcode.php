<?php

namespace FluentShipment\App\Shortcodes;

use FluentShipment\App\Models\Shipment;
use FluentShipment\App\Models\ShipmentTrackingEvent;

class TrackingShortcode
{
    /**
     * Register the shortcode
     */
    public function register()
    {
        add_shortcode('fluent-shipment', [$this, 'render']);
        
        // Register AJAX handlers
        add_action('wp_ajax_fluent_shipment_track', [$this, 'ajaxTrackingLookup']);
        add_action('wp_ajax_nopriv_fluent_shipment_track', [$this, 'ajaxTrackingLookup']);
    }

    /**
     * Render the tracking shortcode
     * 
     * @param array $atts
     * @return string
     */
    public function render($atts = [])
    {
        try {
            // Parse shortcode attributes
            $atts = shortcode_atts([
                'title' => 'Track Your Shipment',
                'placeholder' => 'Enter your tracking number',
                'button_text' => 'Track Shipment',
                'show_search' => 'yes',
            ], $atts, 'fluent-shipment');

            // Enqueue public assets
            $this->enqueuePublicAssets();

            // Start output buffering
            ob_start();
            
            // Get tracking number from URL or form
            $trackingNumber = $this->getTrackingNumber();
            $shipment = null;
            $trackingEvents = [];

            if ($trackingNumber) {
                $shipment = $this->findShipmentByTracking($trackingNumber);
                if ($shipment) {
                    $trackingEvents = $this->getTrackingEvents($shipment->id);
                    // Ensure we have a collection or empty array
                    if (!$trackingEvents) {
                        $trackingEvents = [];
                    }
                }
            }

            // Get template path
            $templatePath = $this->getTemplatePath('public-tracking.php');
            
            // Check if template exists
            if (!file_exists($templatePath)) {
                // Debug path for troubleshooting
                $debugInfo = "Template file not found. Looking for: " . $templatePath;
                error_log('FluentShipment Template Error: ' . $debugInfo);
                return '<div class="fluent-shipment-error">Template file not found.<br><small>Path: ' . esc_html($templatePath) . '</small></div>';
            }

            // Include the template
            include $templatePath;
            
            return ob_get_clean();
            
        } catch (Exception $e) {
            // Log error and return safe fallback
            error_log('FluentShipment Shortcode Error: ' . $e->getMessage());
            return '<div class="fluent-shipment-error">Unable to load tracking form. Please try again later.</div>';
        }
    }

    /**
     * AJAX endpoint for tracking lookup
     * 
     * @return void
     */
    public function ajaxTrackingLookup()
    {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'fluent_shipment_tracking')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }

        $trackingNumber = sanitize_text_field($_POST['tracking_number'] ?? '');
        
        if (empty($trackingNumber)) {
            wp_send_json_error(['message' => 'Please enter a tracking number']);
        }

        $shipment = $this->findShipmentByTracking($trackingNumber);
        
        if (!$shipment) {
            wp_send_json_error(['message' => 'No shipment found with this tracking number']);
        }

        $trackingEvents = $this->getTrackingEvents($shipment->id);

        // Convert events to array format
        $events = [];
        if ($trackingEvents) {
            foreach ($trackingEvents as $event) {
                $events[] = [
                    'status' => $event->event_status,
                    'description' => $event->event_description,
                    'location' => $event->event_location,
                    'date' => $event->event_date,
                    'is_milestone' => $event->is_milestone,
                    'formatted_date' => date('M j, Y g:i A', strtotime($event->event_date)),
                ];
            }
        }

        wp_send_json_success([
            'shipment' => [
                'id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'current_status' => $shipment->current_status,
                'carrier' => $shipment->carrier,
                'estimated_delivery' => $shipment->estimated_delivery,
                'created_at' => $shipment->created_at,
                'delivery_address' => $this->formatAddress($shipment->delivery_address),
                'rider' => $shipment->rider ? [
                    'id' => $shipment->rider->id,
                    'rider_name' => $shipment->rider->rider_name,
                    'phone' => $shipment->rider->phone,
                    'vehicle_type' => $shipment->rider->vehicle_type,
                    'avatar_url' => $shipment->rider->avatar_url,
                    'rating' => $shipment->rider->rating,
                ] : null,
            ],
            'events' => $events,
        ]);
    }

    /**
     * Get tracking number from various sources
     * 
     * @return string|null
     */
    private function getTrackingNumber()
    {
        // Try GET parameter first (for direct links)
        if (!empty($_GET['tracking'])) {
            return sanitize_text_field($_GET['tracking']);
        }

        // Try POST parameter (for form submissions)
        if (!empty($_POST['tracking_number'])) {
            return sanitize_text_field($_POST['tracking_number']);
        }

        return null;
    }

    /**
     * Find shipment by tracking number
     * 
     * @param string $trackingNumber
     * @return Shipment|null
     */
    private function findShipmentByTracking($trackingNumber)
    {
        return Shipment::where('tracking_number', $trackingNumber)->with('rider')->first();
    }

    /**
     * Get tracking events for a shipment
     * 
     * @param int $shipmentId
     * @return \Illuminate\Support\Collection
     */
    private function getTrackingEvents($shipmentId)
    {
        return ShipmentTrackingEvent::forShipment($shipmentId)
            ->orderBy('event_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Format address for display
     * 
     * @param mixed $address
     * @return string
     */
    private function formatAddress($address)
    {
        if (is_string($address)) {
            return $address;
        }
        
        if (is_array($address) || is_object($address)) {
            $addr = (array) $address;
            $parts = array_filter([
                $addr['address_1'] ?? '',
                $addr['city'] ?? '',
                $addr['state'] ?? '',
                $addr['postcode'] ?? '',
                $addr['country'] ?? '',
            ]);
            
            return implode(', ', $parts);
        }
        
        return 'N/A';
    }

    /**
     * Get template file path
     * 
     * @param string $template
     * @return string
     */
    private function getTemplatePath($template)
    {
        // Get the plugin root directory - more reliable approach
        // Current file: /app/Shortcodes/TrackingShortcode.php 
        // Need to go up 3 levels to get to plugin root
        $currentDir = __DIR__;  // /app/Shortcodes/
        $appDir = dirname($currentDir);  // /app/
        $pluginRoot = dirname($appDir);  // plugin root
        $pluginPath = $pluginRoot . '/resources/views/' . $template;
        
        // Allow theme overrides
        $themeTemplate = locate_template(['fluent-shipment/' . $template]);
        
        if ($themeTemplate) {
            return $themeTemplate;
        }
        
        return $pluginPath;
    }

    /**
     * Enqueue public assets
     * 
     * @return void
     */
    private function enqueuePublicAssets()
    {
        // Get plugin URL using the same method as template path
        $currentDir = __DIR__;
        $appDir = dirname($currentDir);
        $pluginRoot = dirname($appDir);
        $pluginUrl = plugins_url('', $pluginRoot . '/fluent-shipment.php');
        
        $version = defined('FLUENT_SHIPMENT_VERSION') ? FLUENT_SHIPMENT_VERSION : '1.0.0';

        wp_enqueue_style(
            'fluent-shipment-public',
            $pluginUrl . '/public/css/public-tracking.css',
            [],
            $version
        );

        wp_enqueue_script(
            'fluent-shipment-public',
            $pluginUrl . '/public/js/public-tracking.js',
            ['jquery'],
            $version,
            true
        );

        // Localize script with AJAX data
        wp_localize_script('fluent-shipment-public', 'fluentShipmentPublic', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fluent_shipment_tracking'),
            'strings' => [
                'searching' => 'Searching...',
                'error' => 'An error occurred. Please try again.',
                'no_results' => 'No shipment found with this tracking number.',
            ]
        ]);
    }

    /**
     * Get status display label
     * 
     * @param string $status
     * @return string
     */
    public static function getStatusLabel($status)
    {
        $labels = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'in_transit' => 'In Transit',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'failed' => 'Delivery Failed',
            'cancelled' => 'Cancelled',
            'returned' => 'Returned',
            'exception' => 'Exception',
        ];

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Get status CSS class
     * 
     * @param string $status
     * @return string
     */
    public static function getStatusClass($status)
    {
        $classes = [
            'pending' => 'status-pending',
            'processing' => 'status-processing',
            'shipped' => 'status-shipped',
            'in_transit' => 'status-transit',
            'out_for_delivery' => 'status-delivery',
            'delivered' => 'status-delivered',
            'failed' => 'status-failed',
            'cancelled' => 'status-cancelled',
            'returned' => 'status-returned',
            'exception' => 'status-exception',
        ];

        return $classes[$status] ?? 'status-default';
    }
}