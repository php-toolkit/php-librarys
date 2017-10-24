<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/7 0007
 * Time: 21:12
 */

namespace Inhere\Library\Helpers;

/**
 * Class Str
 *  alias of the StringHelper
 * @package Inhere\Library\Helpers
 */
class Str extends StringHelper
{
    /**
     * @param string $string
     * @param string $prefix
     * @param string $suffix
     * @return string
     */
    public static function optional(string $string, string $prefix = ' ', string $suffix = ''): string
    {
        if (empty($string)) {
            return '';
        }

        return $prefix . $string . $suffix;
    }

    /**
     * @param string $needle
     * @param string|array $string
     * @return bool
     */
    public static function contains(string $needle, $string)
    {
        return self::has($needle, $string);
    }

    /**
     * @param string $needle
     * @param string|array  $string
     * @return bool
     */
    public static function has(string $needle, $string)
    {
        if (is_string($string)) {
            return stripos($string, $needle) !== false;
        }

        if (is_array($string)) {
            foreach ($string as $item) {
                if (stripos($item, $needle) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}
