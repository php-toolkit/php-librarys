<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/1/29
 * Use : ...
 * File: FormatHelper.php
 */

namespace inhere\library\helpers;

/**
 * Class FormatHelper
 * @package inhere\library\helpers
 */
class FormatHelper
{
    /**
     * formatTime
     * @param  int $secs
     * @return string
     */
    public static function formatTime($secs)
    {
        static $timeFormats = [
            [0, '< 1 sec'],
            [1, '1 sec'],
            [2, 'secs', 1],
            [60, '1 min'],
            [120, 'mins', 60],
            [3600, '1 hr'],
            [7200, 'hrs', 3600],
            [86400, '1 day'],
            [172800, 'days', 86400],
        ];

        foreach ($timeFormats as $index => $format) {
            if ($secs >= $format[0]) {
                if ((isset($timeFormats[$index + 1]) && $secs < $timeFormats[$index + 1][0])
                    || $index == count($timeFormats) - 1
                ) {
                    if (2 == count($format)) {
                        return $format[1];
                    }

                    return floor($secs / $format[2]).' '.$format[1];
                }
            }
        }

        return date('Y-m-d H:i:s', $secs);
    }

    /**
     * @param string $mTime value is microtime(1)
     * @return string
     */
    public static function microTime($mTime = null)
    {
        if (!$mTime) {
            $mTime = microtime(true);
        }

        list($ts, $ms) = explode('.', sprintf('%.4f', $mTime));

        return date('Y/m/d H:i:s', $ts) . '.' . $ms;
    }

    /**
     * @param $memory
     * @return string
     * ```
     * Helper::memory(memory_get_usage(true));
     * ```
     */
    public static function memory($memory)
    {
        if ($memory >= 1024 * 1024 * 1024) {
            return sprintf('%.1f GiB', $memory / 1024 / 1024 / 1024);
        }

        if ($memory >= 1024 * 1024) {
            return sprintf('%.1f MiB', $memory / 1024 / 1024);
        }

        if ($memory >= 1024) {
            return sprintf('%d KiB', $memory / 1024);
        }

        return sprintf('%d B', $memory);
    }

    /**
     * @param int $size
     * @return string
     * ```
     * Helper::size(memory_get_usage(true));
     * ```
     */
    public static function size($size)
    {
        if ($size >= 1024 * 1024 * 1024) {
            return sprintf('%.1f Gb', $size / 1024 / 1024 / 1024);
        }

        if ($size >= 1024 * 1024) {
            return sprintf('%.1f Mb', $size / 1024 / 1024);
        }

        if ($size >= 1024) {
            return sprintf('%d Kb', $size / 1024);
        }

        return sprintf('%d b', $size);
    }

    /**
     * Format a number into a human readable format
     * e.g. 24962496 => 23.81M
     * @param     $size
     * @param int $precision
     * @return string
     */
    public static function formatBytes($size, $precision = 2)
    {
        if (!$size) {
            return '0';
        }

        $base = log($size) / log(1024);
        $suffixes = array('b', 'k', 'M', 'G', 'T');
        $floorBase = floor($base);

        return round(1024 ** ($base - $floorBase), $precision) . $suffixes[(int)$floorBase];
    }

    /**
     * Convert a shorthand byte value from a PHP configuration directive to an integer value
     * @param string $value value to convert
     * @return int
     */
    public static function convertBytes($value)
    {
        if (is_numeric($value)) {
            return $value;
        }

        $value_length = strlen($value);
        $qty = (int)substr($value, 0, $value_length - 1);
        $unit = StringHelper::strtolower(substr($value, $value_length - 1));
        switch ($unit) {
            case 'k':
                $qty *= 1024;
                break;
            case 'm':
                $qty *= 1048576;
                break;
            case 'g':
                $qty *= 1073741824;
                break;
        }

        return $qty;
    }

    /**
     * Replaces &amp; with & for XHTML compliance
     * @param   string $text Text to process
     * @return  string  Processed string.
     */
    public static function ampReplace($text)
    {
        $text = str_replace('&&', '*--*', $text);
        $text = str_replace('&#', '*-*', $text);
        $text = str_replace('&amp;', '&', $text);
        $text = str_replace('*-*', '&#', $text);
        $text = str_replace('*--*', '&&', $text);
        $text = preg_replace('/|&(?![\w]+;)|/', '&amp;', $text);

        return $text;
    }

    /**
     * Cleans text of all formatting and scripting code
     *
     * @param   string &$text Text to clean
     *
     * @return  string  Cleaned text.
     */
    public static function cleanText(&$text)
    {
        $text = preg_replace('/<script[^>]*>.*?</script>/si', '', $text);
        $text = preg_replace('/<a\s+.*?href="([^"]+)"[^>]*>([^<]+)<\/a>/is', '\2 (\1)', $text);
        $text = preg_replace('/<!--.+?-->/', '', $text);
        $text = preg_replace('/{.+?}/', '', $text);
        $text = preg_replace('/&nbsp;/', ' ', $text);
        $text = preg_replace('/&amp;/', ' ', $text);
        $text = preg_replace('/&quot;/', ' ', $text);
        $text = strip_tags($text);
        $text = htmlspecialchars($text, ENT_COMPAT, 'UTF-8');

        return $text;
    }

}// end class FormatHelper
