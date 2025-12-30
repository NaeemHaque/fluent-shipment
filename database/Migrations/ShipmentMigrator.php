<?php

namespace FluentShipment\Database\Migrations;

class ShipmentMigrator
{
    public static function migrate()
    {
        global $wpdb;

        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }

        $charsetCollate = $wpdb->get_charset_collate();
        $table          = $wpdb->prefix . 'fluent_shipments';
        $indexPrefix    = $wpdb->prefix . 'fs_';

        $sql = "CREATE TABLE $table (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                order_id BIGINT UNSIGNED NOT NULL,
                order_source VARCHAR(50) NOT NULL DEFAULT 'fluent-cart',
                order_hash VARCHAR(100) NULL,
                tracking_number VARCHAR(100) NOT NULL,
                carrier VARCHAR(50) NULL,
                carrier_service VARCHAR(100) NULL,
                current_status VARCHAR(20) NOT NULL DEFAULT 'pending',
                shipping_address JSON NULL,
                delivery_address JSON NULL,
                package_info JSON NULL,
                estimated_delivery DATE NULL,
                shipped_at DATETIME NULL,
                delivered_at DATETIME NULL,
                customer_id BIGINT UNSIGNED NULL,
                customer_email VARCHAR(100) NULL,
                customer_phone VARCHAR(20) NULL,
                rider_id BIGINT UNSIGNED NULL,
                weight_total DECIMAL(10,3) NULL,
                dimensions JSON NULL,
                shipping_cost BIGINT UNSIGNED DEFAULT 0,
                currency VARCHAR(10) DEFAULT 'USD',
                tracking_url TEXT NULL,
                delivery_confirmation TEXT NULL,
                special_instructions TEXT NULL,
                meta JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX `{$indexPrefix}order_id` (order_id),
                INDEX `{$indexPrefix}order_source` (order_source),
                INDEX `{$indexPrefix}tracking_number` (tracking_number),
                INDEX `{$indexPrefix}current_status` (current_status),
                INDEX `{$indexPrefix}customer_id` (customer_id),
                INDEX `{$indexPrefix}customer_email` (customer_email),
                INDEX `{$indexPrefix}rider_id` (rider_id),
                INDEX `{$indexPrefix}carrier` (carrier),
                INDEX `{$indexPrefix}created_at` (created_at),
                UNIQUE KEY `{$indexPrefix}unique_order_shipment` (order_id, order_source)
            ) $charsetCollate;";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        // Capture output to prevent activation errors
        ob_start();
        dbDelta($sql);
        ob_end_clean();

        // Create tracking events table
        static::createTrackingEventsTable();
        
        // Create riders table
        static::createRidersTable();
    }

    public static function createTrackingEventsTable()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $table          = $wpdb->prefix . 'fluent_shipment_tracking_events';
        $indexPrefix    = $wpdb->prefix . 'fste_';

        $sql = "CREATE TABLE $table (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                shipment_id BIGINT UNSIGNED NOT NULL,
                event_status VARCHAR(50) NOT NULL,
                event_description TEXT NULL,
                event_location VARCHAR(255) NULL,
                event_date DATETIME NOT NULL,
                carrier_data JSON NULL,
                is_milestone BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX `{$indexPrefix}shipment_id` (shipment_id),
                INDEX `{$indexPrefix}event_status` (event_status),
                INDEX `{$indexPrefix}event_date` (event_date),
                FOREIGN KEY (shipment_id) REFERENCES {$wpdb->prefix}fluent_shipments(id) ON DELETE CASCADE
            ) $charsetCollate;";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        // Capture output to prevent activation errors
        ob_start();
        dbDelta($sql);
        ob_end_clean();
    }

    public static function createRidersTable()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'fluent_shipment_riders';
        $indexPrefix = $wpdb->prefix . 'fsr_';

        $sql = "CREATE TABLE $table (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                rider_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NULL,
                license_number VARCHAR(50) NULL,
                vehicle_type VARCHAR(50) NULL,
                vehicle_number VARCHAR(50) NULL,
                status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                address JSON NULL,
                emergency_contact JSON NULL,
                documents JSON NULL,
                rating DECIMAL(3,2) DEFAULT 0.00,
                total_deliveries INT UNSIGNED DEFAULT 0,
                successful_deliveries INT UNSIGNED DEFAULT 0,
                avatar_url TEXT NULL,
                notes TEXT NULL,
                joining_date DATE NULL,
                meta JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY `{$indexPrefix}email` (email),
                INDEX `{$indexPrefix}rider_name` (rider_name),
                INDEX `{$indexPrefix}phone` (phone),
                INDEX `{$indexPrefix}status` (status),
                INDEX `{$indexPrefix}vehicle_type` (vehicle_type),
                INDEX `{$indexPrefix}rating` (rating),
                INDEX `{$indexPrefix}created_at` (created_at)
            ) $charsetCollate;";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        // Capture output to prevent activation errors
        ob_start();
        dbDelta($sql);
        ob_end_clean();
    }

    public static function dropTable()
    {
        global $wpdb;

        $shipmentsTable = $wpdb->prefix . 'fluent_shipments';
        $eventsTable    = $wpdb->prefix . 'fluent_shipment_tracking_events';
        $ridersTable    = $wpdb->prefix . 'fluent_shipment_riders';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query("DROP TABLE IF EXISTS $eventsTable");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query("DROP TABLE IF EXISTS $ridersTable");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query("DROP TABLE IF EXISTS $shipmentsTable");
    }
}
