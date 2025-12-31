<?php

namespace FluentShipment\App\Shortcodes;

use FluentShipment\App\App;
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

    public function render($atts = [])
    {
        try {
            $atts = shortcode_atts([
                'title'       => 'Track Your Shipment',
                'placeholder' => 'Enter your tracking number',
                'button_text' => 'Track Shipment',
                'show_search' => 'yes',
            ], $atts, 'fluent-shipment');

            $this->enqueuePublicAssets();

            $trackingNumber = $this->getTrackingNumber();
            $shipment       = null;
            $trackingEvents = [];

            if ($trackingNumber) {
                $shipment = $this->findShipmentByTracking($trackingNumber);
                if ($shipment) {
                    $trackingEvents = $this->getTrackingEvents($shipment->id);
                    if ( ! $trackingEvents) {
                        $trackingEvents = [];
                    }
                }
            }
            $view = App::make('view');
            $view->render('public-tracking', compact('atts', 'trackingNumber', 'shipment', 'trackingEvents'));

        } catch (\Exception $e) {
            return '<div class="fluent-shipment-error">Unable to load tracking form. Please try again later.</div>';
        }
    }

    public function ajaxTrackingLookup()
    {
        if ( ! wp_verify_nonce($_POST['nonce'] ?? '', 'fluent_shipment_tracking')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }

        $trackingNumber = sanitize_text_field($_POST['tracking_number'] ?? '');

        if (empty($trackingNumber)) {
            wp_send_json_error(['message' => 'Please enter a tracking number']);
        }

        $shipment = $this->findShipmentByTracking($trackingNumber);

        if ( ! $shipment) {
            wp_send_json_error(['message' => 'No shipment found with this tracking number']);
        }

        $trackingEvents = $this->getTrackingEvents($shipment->id);

        $events = [];
        if ($trackingEvents) {
            foreach ($trackingEvents as $event) {
                $events[] = [
                    'status'         => $event->event_status,
                    'description'    => $event->event_description,
                    'location'       => $event->event_location,
                    'date'           => $event->event_date,
                    'is_milestone'   => $event->is_milestone,
                    'formatted_date' => date('M j, Y g:i A', strtotime($event->event_date)),
                ];
            }
        }

        wp_send_json_success([
            'shipment' => [
                'id'                 => $shipment->id,
                'tracking_number'    => $shipment->tracking_number,
                'current_status'     => $shipment->current_status,
                'carrier'            => $shipment->carrier,
                'estimated_delivery' => $shipment->estimated_delivery,
                'created_at'         => $shipment->created_at,
                'delivery_address'   => $this->formatAddress($shipment->delivery_address),
                'rider'              => $shipment->rider ? [
                    'id'           => $shipment->rider->id,
                    'rider_name'   => $shipment->rider->rider_name,
                    'phone'        => $shipment->rider->phone,
                    'vehicle_type' => $shipment->rider->vehicle_type,
                    'avatar_url'   => $shipment->rider->avatar_url,
                    'rating'       => $shipment->rider->rating,
                ] : null,
            ],
            'events'   => $events,
        ]);
    }

    private function getTrackingNumber()
    {
        if ( ! empty($_GET['tracking'])) {
            return sanitize_text_field($_GET['tracking']);
        }

        if ( ! empty($_POST['tracking_number'])) {
            return sanitize_text_field($_POST['tracking_number']);
        }

        return null;
    }

    private function findShipmentByTracking($trackingNumber)
    {
        return Shipment::where('tracking_number', $trackingNumber)->with('rider')->first();
    }

    private function getTrackingEvents($shipmentId)
    {
        return ShipmentTrackingEvent::forShipment($shipmentId)
                                    ->orderBy('event_date', 'desc')
                                    ->orderBy('created_at', 'desc')
                                    ->get();
    }

    private function formatAddress($address)
    {
        if (is_string($address)) {
            return $address;
        }

        if (is_array($address) || is_object($address)) {
            $addr  = (array)$address;
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

    private function enqueuePublicAssets()
    {
        $currentDir = __DIR__;
        $appDir     = dirname($currentDir);
        $pluginRoot = dirname($appDir);
        $pluginUrl  = plugins_url('', $pluginRoot . '/fluent-shipment.php');

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
            'nonce'   => wp_create_nonce('fluent_shipment_tracking'),
            'strings' => [
                'searching'  => 'Searching...',
                'error'      => 'An error occurred. Please try again.',
                'no_results' => 'No shipment found with this tracking number.',
            ]
        ]);
    }

    public static function getStatusLabel($status)
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
            'returned'         => 'Returned',
            'exception'        => 'Exception',
        ];

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public static function getStatusClass($status)
    {
        $classes = [
            'pending'          => 'status-pending',
            'processing'       => 'status-processing',
            'shipped'          => 'status-shipped',
            'in_transit'       => 'status-transit',
            'out_for_delivery' => 'status-delivery',
            'delivered'        => 'status-delivered',
            'failed'           => 'status-failed',
            'cancelled'        => 'status-cancelled',
            'returned'         => 'status-returned',
            'exception'        => 'status-exception',
        ];

        return $classes[$status] ?? 'status-default';
    }
}
