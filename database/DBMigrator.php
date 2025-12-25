<?php

namespace FluentShipment\Database;

use FluentShipment\Framework\Database\Schema;
use FluentShipment\Database\Migrations\ShipmentMigrator;

class DBMigrator
{
    private static $migrations = [
        ShipmentMigrator::class
    ];
    public static function run($network_wide = false)
    {
        return static::migrateUp($network_wide);
    }

    public static function migrateUp($network_wide = false)
    {
        if ( ! function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }

        foreach (static::getMigrations() as $migration) {
            $migration::migrate();
        }
    }

    public static function migrateDown()
    {
        foreach (array_keys(static::getMigrations()) as $table) {
            Schema::dropTableIfExists($table);
        }
    }

    public static function getMigrations()
    {
        return static::$migrations;
    }
}
