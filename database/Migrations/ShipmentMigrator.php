<?php

namespace FluentShipment\Database\Migrations;

class ShipmentMigrator
{
    public static function migrate()
    {
        global $wpdb;

        // Ensure dbDelta function is available
        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }

        $charsetCollate = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . 'fluent_shipments';

        $sql = "CREATE TABLE $table (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                order_id BIGINT UNSIGNED NOT NULL,
                order_source VARCHAR(255) NOT NULL,
                tracking_number VARCHAR(255) NOT NULL,
                current_status VARCHAR(255) NOT NULL,
                delivery_address TEXT NOT NULL,
                estimated_delivery DATE NULL,
                delivered_at DATE NULL,
                customer_id BIGINT UNSIGNED NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY order_id (order_id),
                KEY customer_id (customer_id),
                KEY tracking_number (tracking_number)
            ) $charsetCollate;";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        dbDelta($sql);
    }
}
