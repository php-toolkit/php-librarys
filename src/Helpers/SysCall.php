<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/27
 * Time: 下午8:18
 */

namespace Inhere\Library\Helpers;

/**
 * Class SysCall
 * @package Inhere\Library\Helpers
 */
class SysCall
{
    /**
     * @param string $program
     * @return int|string
     */
    public static function getCpuUsage($program)
    {
        if (!$program) {
            return -1;
        }

        $info = exec('ps aux | grep ' . $program . ' | grep -v grep | grep -v su | awk {"print $3"}');

        return $info;
    }

    public static function getMemUsage($program)
    {
        if (!$program) {
            return -1;
        }

        $info = exec('ps aux | grep ' . $program . ' | grep -v grep | grep -v su | awk {"print $4"}');

        return $info;
    }
}
