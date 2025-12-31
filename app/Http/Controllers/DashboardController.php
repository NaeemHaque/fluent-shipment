<?php

namespace FluentShipment\App\Http\Controllers;

use FluentShipment\App\Models\Shipment;
use FluentShipment\App\Models\Rider;
use FluentShipment\App\Models\ShipmentTrackingEvent;
use FluentShipment\App\Helpers\DateTimeHelper;

class DashboardController extends Controller
{
    public function index()
    {
        $totalShipments = Shipment::count();
        $activeRiders   = Rider::active()->count();
        $inProcessing   = Shipment::whereIn('current_status', [
            Shipment::STATUS_PENDING,
            Shipment::STATUS_PROCESSING
        ])->count();
        $delivered      = Shipment::ofStatus(Shipment::STATUS_DELIVERED)->count();
        $failed         = Shipment::whereIn('current_status', [
            Shipment::STATUS_FAILED,
            Shipment::STATUS_CANCELLED
        ])->count();

        $last30Days = date('Y-m-d', strtotime('-30 days'));
        $last60Days = date('Y-m-d', strtotime('-60 days'));

        $recentShipments   = Shipment::where('created_at', '>=', $last30Days)->count();
        $previousShipments = Shipment::whereBetween('created_at', [$last60Days, $last30Days])->count();
        $shipmentsTrend    = $previousShipments > 0 ? round(
            (($recentShipments - $previousShipments) / $previousShipments) * 100,
            1
        ) : 0;

        $recentDelivered   = Shipment::where('delivered_at', '>=', $last30Days)->count();
        $previousDelivered = Shipment::whereBetween('delivered_at', [$last60Days, $last30Days])->count();
        $deliveredTrend    = $previousDelivered > 0 ? round(
            (($recentDelivered - $previousDelivered) / $previousDelivered) * 100,
            1
        ) : 0;

        $stats = [
            [
                'id'        => 1,
                'label'     => 'Total Shipments',
                'value'     => $totalShipments,
                'type'      => 'shipments',
                'icon'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#1FC16B"><path d="M19 7H16V6C16 4.9 15.1 4 14 4H10C8.9 4 8 4.9 8 6V7H5C3.9 7 3 7.9 3 9V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V9C21 7.9 20.1 7 19 7ZM10 6H14V7H10V6ZM19 19H5V9H8V10C8 10.6 8.4 11 9 11S10 10.6 10 10V9H14V10C14 10.6 14.4 11 15 11S16 10.6 16 10V9H19V19Z"></path></svg>',
                'color'     => '#E0FAEC',
            ],
            [
                'id'        => 2,
                'label'     => 'Active Riders',
                'value'     => $activeRiders,
                'type'      => 'riders',
                'icon'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FB4BA3"><path d="M12 3C10.9 3 10 3.9 10 5S10.9 7 12 7 14 6.1 14 5 13.1 3 12 3ZM19 13V21H17V15H13L15 13H19ZM7 13V21H9V15H13L11 13H7ZM12 8C9.8 8 8 9.8 8 12H16C16 9.8 14.2 8 12 8ZM13 14V17H11V14H13Z"></path></svg>',
                'color'     => '#FFEBF4',

            ],
            [
                'id'        => 3,
                'label'     => 'In Processing',
                'value'     => $inProcessing,
                'type'      => 'processing',
                'icon'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FA7319"><path d="M12 2C6.48 2 2 6.48 2 12S6.48 22 12 22 22 17.52 22 12 17.52 2 12 2ZM17 13H11V7H13V11H17V13Z"></path></svg>',
                'color'     => '#FFF3EB',
            ],
            [
                'id'        => 4,
                'label'     => 'Delivered',
                'value'     => $delivered,
                'type'      => 'delivered',
                'icon'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#335CFF"><path d="M9 16.17L4.83 12L3.41 13.41L9 19L21 7L19.59 5.59L9 16.17Z"></path></svg>',
                'color'     => '#EBF1FF',
            ],
            [
                'id'        => 5,
                'label'     => 'Failed/Cancelled',
                'value'     => $failed,
                'type'      => 'failed',
                'icon'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#7D52F4"><path d="M12 2C6.48 2 2 6.48 2 12S6.48 22 12 22 22 17.52 22 12 17.52 2 12 2ZM15.5 16.5L12 13L8.5 16.5L7 15L10.5 11.5L7 8L8.5 6.5L12 10L15.5 6.5L17 8L13.5 11.5L17 15L15.5 16.5Z"></path></svg>',
                'color'     => '#EFEBFF',
            ],
        ];

        $chartData          = $this->getChartData();
        $recentActivities   = $this->getRecentActivities();
        $performanceMetrics = $this->getPerformanceMetrics();

        return [
            'stats'              => $stats,
            'chartData'          => $chartData,
            'recentActivities'   => $recentActivities,
            'performanceMetrics' => $performanceMetrics,
            'lastUpdated'        => date('Y-m-d H:i:s')
        ];
    }

    private function getChartData($days = 30)
    {
        global $wpdb;

        $table    = $wpdb->prefix . 'fluent_shipments';
        $fromDate = date('Y-m-d', strtotime("-{$days} days"));

        $query = $wpdb->prepare(
            "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as shipments,
                SUM(CASE WHEN current_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN current_status IN ('shipped', 'in_transit', 'out_for_delivery') THEN 1 ELSE 0 END) as in_transit,
                SUM(CASE WHEN current_status IN ('failed', 'cancelled') THEN 1 ELSE 0 END) as failed
            FROM {$table} 
            WHERE created_at >= %s 
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ",
            $fromDate
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        $chartData = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date   = date('M j', strtotime("-{$i} days"));
            $dbDate = date('Y-m-d', strtotime("-{$i} days"));

            $dayData = array_filter($results, function ($row) use ($dbDate) {
                return $row['date'] === $dbDate;
            });

            if ( ! empty($dayData)) {
                $dayData     = reset($dayData);
                $chartData[] = [
                    'date'       => $date,
                    'shipments'  => (int)$dayData['shipments'],
                    'orders'     => (int)$dayData['shipments'], // For compatibility with existing chart
                    'delivered'  => (int)$dayData['delivered'],
                    'in_transit' => (int)$dayData['in_transit'],
                    'failed'     => (int)$dayData['failed']
                ];
            } else {
                $chartData[] = [
                    'date'       => $date,
                    'shipments'  => 0,
                    'orders'     => 0,
                    'delivered'  => 0,
                    'in_transit' => 0,
                    'failed'     => 0
                ];
            }
        }

        return $chartData;
    }

    private function getRecentActivities($limit = 8)
    {
        $events = ShipmentTrackingEvent::with(['shipment'])
                                       ->orderBy('event_date', 'desc')
                                       ->limit($limit)
                                       ->get();

        $activities = [];
        foreach ($events as $event) {
            $shipment = $event->shipment;
            if ( ! $shipment) {
                continue;
            }

            $type = $this->mapEventToActivityType($event->event_status);

            $activities[] = [
                'id'          => $event->id,
                'type'        => $type,
                'title'       => $this->getActivityTitle($event->event_status, $shipment),
                'description' => $event->event_description ?: $this->getActivityDescription($event, $shipment),
                'created_at'  => $event->event_date->format('Y-m-d H:i:s'),
                'created_by'  => $this->getEventCreatedBy($event, $shipment)
            ];
        }

        return $activities;
    }

    private function mapEventToActivityType($status)
    {
        $mapping = [
            Shipment::STATUS_PENDING          => 'shipment_created',
            Shipment::STATUS_PROCESSING       => 'shipment_created',
            Shipment::STATUS_SHIPPED          => 'order_shipped',
            Shipment::STATUS_IN_TRANSIT       => 'tracking_updated',
            Shipment::STATUS_OUT_FOR_DELIVERY => 'tracking_updated',
            Shipment::STATUS_DELIVERED        => 'shipment_delivered',
            Shipment::STATUS_FAILED           => 'shipment_cancelled',
            Shipment::STATUS_CANCELLED        => 'shipment_cancelled',
            'status_updated'                  => 'shipment_updated'
        ];

        return $mapping[$status] ?? 'tracking_updated';
    }

    private function getActivityTitle($status, $shipment)
    {
        $titles = [
            Shipment::STATUS_PENDING          => 'Shipment created',
            Shipment::STATUS_PROCESSING       => 'Shipment processing started',
            Shipment::STATUS_SHIPPED          => 'Package shipped',
            Shipment::STATUS_IN_TRANSIT       => 'Package in transit',
            Shipment::STATUS_OUT_FOR_DELIVERY => 'Out for delivery',
            Shipment::STATUS_DELIVERED        => 'Package delivered successfully',
            Shipment::STATUS_FAILED           => 'Delivery failed',
            Shipment::STATUS_CANCELLED        => 'Shipment cancelled'
        ];

        return $titles[$status] ?? 'Status updated';
    }

    private function getActivityDescription($event, $shipment)
    {
        $trackingNumber = $shipment->tracking_number;
        $orderId        = $shipment->order_id ? "#" . $shipment->order_id : "Manual";

        $descriptions = [
            Shipment::STATUS_PENDING          => "New shipment {$trackingNumber} created for order {$orderId}",
            Shipment::STATUS_PROCESSING       => "Shipment {$trackingNumber} is being prepared",
            Shipment::STATUS_SHIPPED          => "Package {$trackingNumber} has been shipped",
            Shipment::STATUS_IN_TRANSIT       => "Package {$trackingNumber} is on its way",
            Shipment::STATUS_OUT_FOR_DELIVERY => "Package {$trackingNumber} is out for delivery",
            Shipment::STATUS_DELIVERED        => "Package {$trackingNumber} delivered successfully",
            Shipment::STATUS_FAILED           => "Delivery attempt failed for {$trackingNumber}",
            Shipment::STATUS_CANCELLED        => "Shipment {$trackingNumber} has been cancelled"
        ];

        return $descriptions[$event->event_status] ?? "Status updated for shipment {$trackingNumber}";
    }

    private function getEventCreatedBy($event, $shipment)
    {
        if ($event->carrier_data && isset($event->carrier_data['created_by'])) {
            return $event->carrier_data['created_by'];
        }

        if ($event->carrier_data && isset($event->carrier_data['user_id'])) {
            $user = get_user_by('ID', $event->carrier_data['user_id']);
            if ($user) {
                return $user->display_name ?: $user->user_login;
            }
        }

        if ($shipment->meta && isset($shipment->meta['created_by'])) {
            $userId = $shipment->meta['created_by'];
            $user   = get_user_by('ID', $userId);
            if ($user) {
                return $user->display_name ?: $user->user_login;
            }
        }

        if ($shipment->rider) {
            return $shipment->rider->rider_name;
        }

        if (in_array($event->event_status, [
            Shipment::STATUS_DELIVERED,
            Shipment::STATUS_IN_TRANSIT,
            Shipment::STATUS_OUT_FOR_DELIVERY
        ])) {
            return 'Carrier System';
        }

        if ($event->created_at) {
            $eventTime   = strtotime($event->created_at);
            $currentTime = time();
            $hoursDiff   = ($currentTime - $eventTime) / 3600;

            if ($hoursDiff < 1) {
                $currentUser = wp_get_current_user();
                if ($currentUser && $currentUser->ID) {
                    return $currentUser->display_name ?: $currentUser->user_login;
                }
            }
        }

        global $current_user;
        if ($current_user && $current_user->ID) {
            return $current_user->display_name ?: $current_user->user_login;
        }

        return 'System';
    }

    private function getPerformanceMetrics()
    {
        $totalShipments     = Shipment::count();
        $deliveredShipments = Shipment::ofStatus(Shipment::STATUS_DELIVERED)->count();

        global $wpdb;
        $table = $wpdb->prefix . 'fluent_shipments';

        $avgQuery = $wpdb->prepare(
            "
            SELECT AVG(DATEDIFF(delivered_at, shipped_at)) as avg_days
            FROM {$table}
            WHERE delivered_at IS NOT NULL 
            AND shipped_at IS NOT NULL
        "
        );

        $avgResult = $wpdb->get_var($avgQuery);

        $onTimeQuery = $wpdb->prepare(
            "
            SELECT 
                COUNT(*) as total_with_estimate,
                SUM(CASE WHEN delivered_at <= estimated_delivery THEN 1 ELSE 0 END) as on_time
            FROM {$table}
            WHERE delivered_at IS NOT NULL 
            AND estimated_delivery IS NOT NULL
        "
        );

        $onTimeResult = $wpdb->get_row($onTimeQuery);
        $onTimeRate   = $onTimeResult && $onTimeResult->total_with_estimate > 0
            ? round(($onTimeResult->on_time / $onTimeResult->total_with_estimate) * 100, 1)
            : 0;

        // Average cost
        $avgCostQuery = $wpdb->prepare(
            "
            SELECT AVG(shipping_cost) as avg_cost
            FROM {$table}
            WHERE shipping_cost > 0
        "
        );

        $avgCost       = $wpdb->get_var($avgCostQuery);
        $formattedCost = $avgCost ? '$' . number_format($avgCost / 100, 2) : '$0.00';

        return [
            'delivery_rate'         => $totalShipments > 0 ? round(
                ($deliveredShipments / $totalShipments) * 100,
                1
            ) : 0,
            'avg_delivery_time'     => $avgResult ? round($avgResult, 1) . ' days' : 'N/A',
            'customer_satisfaction' => 4.6,
            'on_time_delivery'      => $onTimeRate,
            'cost_per_shipment'     => $formattedCost
        ];
    }
}
