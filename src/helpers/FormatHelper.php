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
     * Replaces &amp; with & for XHTML compliance
     * @param   string $text Text to process
     * @return  string  Processed string.
     */
    static public function ampReplace($text)
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
    static public function cleanText(&$text)
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
     * @param int $size
     * @return string
     * ```
     * Helper::formatMemory(memory_get_usage(true));
     * ```
     */
    public static function formatSize($size)
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
}// end class FormatHelper
