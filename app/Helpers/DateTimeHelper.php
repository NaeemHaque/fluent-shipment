<?php

namespace FluentShipment\App\Helpers;

class DateTimeHelper
{
    // Get current timestamp in MySQL format
    public static function now()
    {
        return current_time('mysql');
    }

    // Get current timestamp in UTC MySQL format
    public static function utcNow()
    {
        return current_time('mysql', true);
    }

    // Format date for display
    public static function format($date, $format = 'Y-m-d H:i:s')
    {
        if ( ! $date) {
            return '';
        }

        $timestamp = is_numeric($date) ? $date : strtotime($date);

        return date($format, $timestamp);
    }

    // Get date X days ago
    public static function daysAgo($days)
    {
        return date('Y-m-d H:i:s', strtotime("-{$days} days"));
    }

    // Get date X days from now
    public static function daysFromNow($days)
    {
        return date('Y-m-d H:i:s', strtotime("+{$days} days"));
    }

    // Check if date is in the future
    public static function isFuture($date)
    {
        return strtotime($date) > time();
    }

    // Check if date is in the past
    public static function isPast($date)
    {
        return strtotime($date) < time();
    }
}
