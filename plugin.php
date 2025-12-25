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

/*************** Code IS Poetry **************/
return (function($_) {

    return $_(__FILE__);
})(
    require __DIR__.'/boot/app.php',
    
    require __DIR__.'/vendor/autoload.php'
);
/************ Built With WPFluent *************/
