<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/1/30
 * Use : ...
 * File: TraitDateHelper.php
 */

namespace inhere\tools\traits;


trait TraitDateHelper
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
     * @param  string  $format  [description]
     * @return boolean | string datetime
     */
    static public function isDate($strTime,$format= 'Y/m/d')
    {
        if (empty($strTime) || (!is_string($strTime) && !is_numeric($strTime)) ) {
            return false;
        }

        $strTime = trim($strTime);

        //@example timestamp 1325347200
        if ( is_integer($strTime) ) {
            $date = date($format,$strTime);

            return $date ? $date : false;

            //@example date 2015/04/05
        } else {
            $time = strtotime($strTime);

            if (!$time) {
                return false;
            }

            return date($format,$time);
        }
    }

    //获取指定日期所在月的第一天和最后一天
    static public function getTheMonth($date)
    {
        $firstDay = date("Y-m-01",strtotime($date));
        $lastDay  = date("Y-m-d",strtotime("$firstDay +1 month -1 day"));

        return array($firstDay,$lastDay);
    }

    //获取指定日期上个月的第一天和最后一天
    static public function getPurMonth($date)
    {
        $time     = strtotime($date);
        $firstDay = date('Y-m-01',strtotime(date('Y',$time).'-'.(date('m',$time)-1).'-01'));
        $lastDay  = date('Y-m-d',strtotime("$firstDay +1 month -1 day"));

        return array($firstDay,$lastDay);
    }

    //获取指定日期下个月的第一天和最后一天
    static public function getNextMonth($date)
    {

        $arr = getdate();

        if ($arr['mon'] == 12) {
            $year       = $arr['year'] +1;
            $month      = $arr['mon'] -11;
            $day        = $arr['mday'];

            if ($day < 10) {
                $mday       = '0'.$day;
            } else {
                $mday       = $day;
            }

            $firstday   = $year.'-0'.$month.'-01';
            $lastday    = $year.'-0'.$month.'-'.$mday;
        } else {
            $time     = strtotime($date);
            $firstday = date('Y-m-01',strtotime(date('Y',$time).'-'.(date('m',$time)+1).'-01'));
            $lastday  = date('Y-m-d',strtotime("$firstday +1 month -1 day"));
        }

        return array($firstday,$lastday);
    }

    /**
     * 获得几天前，几小时前，几月前
     */
    static public function before($time,$unit=null)
    {
        if (!is_int($time)) {
            return false;
        }

        $unit    = is_null($unit) ?
            array("年","月","星期","日","小时","分钟","秒") :
            $unit;
        $nowTime = time();

        switch(true)
        {
            case $time<($nowTime - 31536000):
                return floor(($nowTime - $time)/31536000).$unit[0];
            case $time<($nowTime - 2592000):
                return floor(($nowTime - $time)/2592000).$unit[1];
            case $time<($nowTime - 604800):
                return floor(($nowTime - $time)/604800).$unit[2];
            case $time<($nowTime - 86400):
                return floor(($nowTime - $time)/86400).$unit[3];
            case $time<($nowTime - 3600):
                return floor(($nowTime - $time)/3600).$unit[4];
            case $time<($nowTime - 60):
                return floor(($nowTime - $time)/60).$unit[5];
            default:
                return floor($nowTime - $time).$unit[6];
        }
    }

}