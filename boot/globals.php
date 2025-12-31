<?php

/**
 ***** DO NOT CALL ANY FUNCTIONS DIRECTLY FROM THIS FILE ******
 *
 * This file will be loaded even before the framework is loaded
 * so the $app is not available here, only declare functions here.
 */

if ($app->config->get('app.env') == 'dev') {

    $globalsDevFile = __DIR__ . '/../dev/globals.php';
    
    is_readable($globalsDevFile) && include $globalsDevFile;
}

if (!function_exists('wpf_float_val')) {
    /**
     * PHP floatval doesn't always show decimal places for integers.
     * This ensures a float with fixed precision.
     *
     * @param  int|float $val
     * @param  int       $frac
     * @return float|string
     */
    function wpf_float_val($val = 0, $frac = 2) {
        $val = floatval($val);

        if (strpos((string)$val, '.') === false) {
            return sprintf("%.{$frac}f", $val);
        }

        return $val;
    }
}
if (!function_exists('fluentShipmentSanitizeArray')) {
    function fluentShipmentSanitizeArray(array $array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = fluentShipmentSanitizeArray($value);
            } else {
                $array[$key] = wp_kses_post($value);
            }
        }

        return $array;
    }
}

if (!function_exists('fluentShipmentSanitizeIds')) {
    function fluentShipmentSanitizeIds(array $ids)
    {
        return array_map('intval', $ids);
    }
}
