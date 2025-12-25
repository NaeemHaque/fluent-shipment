<?php defined('ABSPATH') or die(__FILE__);

/*
Plugin Name: Fluent Shipment
Description: Fluent Shipment WordPress Plugin
Version: 1.0.0
Author: 
Author URI: 
Plugin URI: 
License: GPLv2 or later
Text Domain: fluent-shipment
Domain Path: /language
*/

define('FLUENTSHIPMENT_PLUGIN_VERSION', '1.0.0');
define('FLUENTSHIPMENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FLUENTSHIPMENT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FLUENTSHIPMENT_DIR_FILE', __FILE__);

/*************** Code IS Poetry **************/
return (function($_) {

    return $_(__FILE__);
})(
    require __DIR__.'/boot/app.php',
    
    require __DIR__.'/vendor/autoload.php'
);
/************ Built With WPFluent *************/
