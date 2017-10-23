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
}
