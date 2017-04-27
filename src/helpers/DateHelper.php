<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 14-9-25
 * Time: 10:35
 * Uesd: 主要功能是 hi
 */

namespace inhere\library\helpers;

/**
 * Class DateHelper
 * @package inhere\library\helpers
 */
class DateHelper
{
    /*
$tomorrow  = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
$lastmonth = mktime(0, 0, 0, date("m")-1, date("d"),   date("Y"));
$nextyear  = mktime(0, 0, 0, date("m"),   date("d"),   date("Y")+1);

echo strtotime("now"), "\n";
echo strtotime("10 September 2000"), "\n";
echo strtotime("+1 day"), "\n";
echo strtotime("+1 week"), "\n";
echo strtotime("+1 week 2 days 4 hours 2 seconds"), "\n";
echo strtotime("next Thursday"), "\n";
echo strtotime("last Monday"), "\n";

 */
    /**
     * [isDate 判断给定的 字符串 是否是个 日期时间 ]
     * @param  [type]  $strTime 时间戳 | 日期格式的字符串
     * @param  string $format [description]
     * @return boolean | string datetime
     */
    public static function isDate($strTime, $format = 'Y/m/d')
    {
        if (!$strTime || (!is_string($strTime) && !is_numeric($strTime))) {
            return false;
        }

        $strTime = trim($strTime);

        //@example timestamp 1325347200
        if (is_int($strTime)) {
            $date = date($format, $strTime);

            return $date ?: false;

            //@example date 2015/04/05
        }

        $time = strtotime($strTime);

        return $time ? date($format, $time) : false;
    }

    //获取指定日期所在月的第一天和最后一天
    public static function getTheMonth($date)
    {
        $firstDay = date('Y-m-01', strtotime($date));
        $lastDay = date('Y-m-d', strtotime("$firstDay +1 month -1 day"));

        return array($firstDay, $lastDay);
    }

    //获取指定日期上个月的第一天和最后一天
    public static function getPurMonth($date)
    {
        $time = strtotime($date);
        $firstDay = date('Y-m-01', strtotime(date('Y', $time) . '-' . (date('m', $time) - 1) . '-01'));
        $lastDay = date('Y-m-d', strtotime("$firstDay +1 month -1 day"));

        return array($firstDay, $lastDay);
    }

    //获取指定日期下个月的第一天和最后一天
    public static function getNextMonth($date)
    {
        $arr = getdate();

        if ($arr['mon'] === 12) {
            $year = $arr['year'] + 1;
            $month = $arr['mon'] - 11;
            $day = $arr['mday'];

            $mday = $day < 10 ? '0' . $day : $day;

            $firstDay = $year . '-0' . $month . '-01';
            $lastDay = $year . '-0' . $month . '-' . $mday;
        } else {
            $time = strtotime($date);
            $firstDay = date('Y-m-01', strtotime(date('Y', $time) . '-' . (date('m', $time) + 1) . '-01'));
            $lastDay = date('Y-m-d', strtotime("$firstDay +1 month -1 day"));
        }

        return [$firstDay, $lastDay];
    }

    /**
     * 获得几天前，几小时前，几月前
     * @param $time
     * @param null|array $unit
     * @return string
     */
    public static function before($time, $unit = null)
    {
        if (!is_int($time)) {
            return false;
        }

        $unit = $unit ?: ['年', '月', '星期', '日', '小时', '分钟', '秒'];
        $nowTime = time();
        $diffTime = $nowTime - $time;

        switch (true) {
            case $time < ($nowTime - 31536000):
                return floor($diffTime / 31536000) . $unit[0];
            case $time < ($nowTime - 2592000):
                return floor($diffTime / 2592000) . $unit[1];
            case $time < ($nowTime - 604800):
                return floor($diffTime / 604800) . $unit[2];
            case $time < ($nowTime - 86400):
                return floor($diffTime / 86400) . $unit[3];
            case $time < ($nowTime - 3600):
                return floor($diffTime / 3600) . $unit[4];
            case $time < ($nowTime - 60):
                return floor($diffTime / 60) . $unit[5];
            default:
                return floor($diffTime) . $unit[6];
        }
    }

}
