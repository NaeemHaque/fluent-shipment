<?php

namespace FluentShipment\App\Helpers;

class DateTimeHelper
{
    /**
     * Get current timestamp in MySQL format
     * 
     * @return string
     */
    public static function now()
    {
        return current_time('mysql');
    }

    /**
     * Get current timestamp in UTC MySQL format
     * 
     * @return string
     */
    public static function utcNow()
    {
        return current_time('mysql', true);
    }

    /**
     * Format date for display
     * 
     * @param string $date
     * @param string $format
     * @return string
     */
    public static function format($date, $format = 'Y-m-d H:i:s')
    {
        if (!$date) {
            return '';
        }
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, $timestamp);
    }

    /**
     * Get date X days ago
     * 
     * @param int $days
     * @return string
     */
    public static function daysAgo($days)
    {
        return date('Y-m-d H:i:s', strtotime("-{$days} days"));
    }

    /**
     * Get date X days from now
     * 
     * @param int $days
     * @return string
     */
    public static function daysFromNow($days)
    {
        return date('Y-m-d H:i:s', strtotime("+{$days} days"));
    }

    /**
     * Check if date is in the future
     * 
     * @param string $date
     * @return bool
     */
    public static function isFuture($date)
    {
        return strtotime($date) > time();
    }

    /**
     * Check if date is in the past
     * 
     * @param string $date
     * @return bool
     */
    public static function isPast($date)
    {
        return strtotime($date) < time();
    }
}