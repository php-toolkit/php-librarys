<?php
/**
 *
 */
namespace inhere\librarys\helpers;

use inhere\librarys\traits\TraitStringFormat;

/**
 * Class StrHelper
 * @package inhere\librarys\helpers
 */
class StrHelper
{
    use TraitStringFormat;

    /**
     * 使用正则验证数据
     * @access public
     * @param string $value  要验证的数据
     * @param string $rule 验证规则 require email url currency number integer english
     * @return boolean
     */
    public static function regexVerify($value,$rule)
    {
        $value    = trim($value);
        $validate = array(
            'require'   =>  '/\S+/',
            'email'     =>  '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            // 'url'       =>  '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
            'url'       =>  '/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i',
            'currency'  =>  '/^\d+(\.\d+)?$/', # 货币
            'number'    =>  '/^\d+$/',
            'zip'       =>  '/^\d{6}$/',
            'integer'   =>  '/^[-\+]?\d+$/',
            'double'    =>  '/^[-\+]?\d+(\.\d+)?$/',
            'english'   =>  '/^[A-Za-z]+$/',
        );

        // 检查是否有内置的正则表达式
        if (isset($validate[strtolower($rule)])){
            $rule       =   $validate[strtolower($rule)];
        }

        return preg_match($rule,$value)===1;
    }

    /**
     * 检查字符串是否是正确的变量名
     * @param $string
     * @return bool
     */
    public static function isVarName($string)
    {
        return preg_match('@^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*@i', $string)===1;
    }

    /**
     * 计算字符长度
     * @param  [type] $str
     * @return int|string [type]
     */
    public static function length($str)
    {
        if (empty($str)){
          return '0';
        }

        if ((string)$str=='0'){
          return '1';
        }

        if (function_exists('mb_strlen')){
            return mb_strlen($str,'utf-8');
        } else {
            preg_match_all("/./u", $str, $arr);

            return count($arr[0]);
        }
    }

    /**
     * @from web
     * 可以统计中文字符串长度的函数
     * @param string $str 要计算长度的字符串
     * @internal param bool $type 计算长度类型，0(默认)表示一个中文算一个字符，1表示一个中文算两个字符
     * @return int
     */
    public static function abs_length($str)
    {
        if (empty($str)){
            return 0;
        }

        if (function_exists('mb_strwidth')){
            return mb_strwidth($str,'utf-8');
        } else if (function_exists('mb_strlen')){
            return mb_strlen($str,'utf-8');
        } else {
            preg_match_all("/./u", $str, $ar);
            return count($ar[0]);
        }
    }

    /**
     * @from web
     *  utf-8编码下截取中文字符串,参数可以参照substr函数
     * @param string $str 要进行截取的字符串
     * @param int $start 要进行截取的开始位置，负数为反向截取
     * @param int $end 要进行截取的长度
     * @return string
     */
    public static function utf8_substr($str,$start=0,$end=null)
    {
        if (empty($str)){
            return false;
        }

        if (function_exists('mb_substr')){
            if (func_num_args() >= 3) {
                $end = func_get_arg(2);

                return mb_substr($str,$start,$end,'utf-8');
            } else {
                mb_internal_encoding("UTF-8");

                return mb_substr($str,$start);
            }

        } else {
            $null = "";
            preg_match_all("/./u", $str, $ar);

            if (func_num_args() >= 3) {
                $end = func_get_arg(2);

                return join($null, array_slice($ar[0],$start,$end));
            } else {
                return join($null, array_slice($ar[0],$start));
            }
        }
    }


    /**
     * @from web
     * 中文截取，支持gb2312,gbk,utf-8,big5   *
     * @param string $str 要截取的字串
     * @param int $start 截取起始位置
     * @param int $length 截取长度
     * @param string $charset utf-8|gb2312|gbk|big5 编码
     * @param bool $suffix 是否加尾缀
     * @return string
     */
    public static function zhSubstr($str, $start=0, $length, $charset="utf-8", $suffix=true)
    {
        if (function_exists("mb_substr"))
        {
            if (mb_strlen($str, $charset) <= $length) {
                return $str;
            }

            $slice = mb_substr($str, $start, $length, $charset);
        } else {
            $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312']  = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk']     = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5']    = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";

            preg_match_all($re[$charset], $str, $match);
            if (count($match[0]) <= $length) {
                return $str;
            }

            $slice = implode("",array_slice($match[0], $start, $length));
        }

        return (bool)$suffix ? $slice."…" : $slice;
    }


    /**
     * ********************** 生成一定长度的随机字符串函数 **********************
     * @param $length - 随机字符串长度
     * @param array|string $param -
     * @internal param string $chars
     * @return string
     */
    public static function randStr($length, array $param=array())
    {
        $param = array_merge(
            array(
                'prefix' => '',
                'suffix' => '',
                'chars'  => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'
                ),
            $param
            );

        $chars = $param['chars'];
        $max = strlen($chars) - 1;   //strlen($chars) 计算字符串的长度
        $str = '';

        for($i = 0; $i < $length; $i++){
            $str .= $chars[rand(0, $max)];
        }

        return $param['prefix'].$str.$param['suffix'];
    }

    /**
     *
     * @param  mixed  $string
     * @param  string $sep
     * @return array
     */
    public static function toArray( $string, $sep=',')
    {
        $array = [];

        if ( is_string($string) ) {
            $array = strpos($string,$sep)!==false ? array_map('trim', explode(',', $string)) : [ trim($string)];
        }

        return $array;
    }

    // var_dump(string2array('34,56,678, 678, 89, '));
    public static function string2array($string)
    {
        if (!$string) {
            return array();
        }

        return preg_split('/\s*,\s*/',trim($string), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
    * Truncate strings
    *
    * @param string $str
    * @param int $max_length Max length
    * @param string $suffix Suffix optional
    * @return string $str truncated
    */
    /* CAUTION : Use it only on module hookEvents.
    ** For other purposes use the smarty function instead */
    public static function truncate($str, $max_length, $suffix = '...')
    {
        if (self::strlen($str) <= $max_length)
            return $str;
        $str = utf8_decode($str);
        return (utf8_encode(substr($str, 0, $max_length - self::strlen($suffix)).$suffix));
    }

    // 字符截断输出
    public static function truncate_two($string,$start,$length='')
    {
        if ($length=='') {
          $length = $start;
          $start  = 0;
        }

        if (strlen($string) <= $length) {
            return $string;
        }

        if (function_exists('mb_substr')) {
            $string = mb_substr(strip_tags($string),$start,$length,'utf-8');
        } else {
            $string = substr($string, $start,$length).'...';
        }

        return $string;
    }

    /*Copied from CakePHP String utility file*/
    public static function truncateString($text, $length = 120, $options = array())
    {
        $default = array(
            'ellipsis' => '...', 'exact' => true, 'html' => true
        );

        $options = array_merge($default, $options);
        extract($options);
        /**
         * @var string $ellipsis
         * @var bool   $exact
         * @var bool   $html
         */

        if ($html) {
            if (self::strlen(preg_replace('/<.*?>/', '', $text)) <= $length)
                return $text;

            $total_length = self::strlen(strip_tags($ellipsis));
            $open_tags = array();
            $truncate = '';
            preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);

            foreach ($tags as $tag)
            {
                if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2]))
                {
                    if (preg_match('/<[\w]+[^>]*>/s', $tag[0]))
                        array_unshift($open_tags, $tag[2]);
                    elseif (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $close_tag))
                    {
                        $pos = array_search($close_tag[1], $open_tags);
                        if ($pos !== false)
                            array_splice($open_tags, $pos, 1);
                    }
                }
                $truncate .= $tag[1];
                $content_length = self::strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));

                if ($content_length + $total_length > $length)
                {
                    $left = $length - $total_length;
                    $entities_length = 0;

                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE))
                    {
                        foreach ($entities[0] as $entity)
                        {
                            if ($entity[1] + 1 - $entities_length <= $left)
                            {
                                $left--;
                                $entities_length += self::strlen($entity[0]);
                            }
                            else
                                break;
                        }
                    }

                    $truncate .= self::substr($tag[3], 0, $left + $entities_length);
                    break;
                }
                else
                {
                    $truncate .= $tag[3];
                    $total_length += $content_length;
                }

                if ($total_length >= $length)
                    break;
            }
        }
        else
        {
            if (self::strlen($text) <= $length)
                return $text;

            $truncate = self::substr($text, 0, $length - self::strlen($ellipsis));
        }

        if (!$exact)
        {
            $spacepos = self::strrpos($truncate, ' ');
            if ($html)
            {
                $truncate_check = self::substr($truncate, 0, $spacepos);
                $last_open_tag = self::strrpos($truncate_check, '<');
                $last_close_tag = self::strrpos($truncate_check, '>');

                if ($last_open_tag > $last_close_tag)
                {
                    preg_match_all('/<[\w]+[^>]*>/s', $truncate, $last_tag_matches);
                    $last_tag = array_pop($last_tag_matches[0]);
                    $spacepos = self::strrpos($truncate, $last_tag) + self::strlen($last_tag);
                }

                $bits = self::substr($truncate, $spacepos);
                preg_match_all('/<\/([a-z]+)>/', $bits, $dropped_tags, PREG_SET_ORDER);

                if (!empty($dropped_tags))
                {
                    if (!empty($open_tags))
                    {
                        foreach ($dropped_tags as $closing_tag)
                            if (!in_array($closing_tag[1], $open_tags))
                                array_unshift($open_tags, $closing_tag[1]);
                    }
                    else
                    {
                        foreach ($dropped_tags as $closing_tag)
                            $open_tags[] = $closing_tag[1];
                    }
                }
            }

            $truncate = self::substr($truncate, 0, $spacepos);
        }

        $truncate .= $ellipsis;

        if ($html && isset($open_tags))
            foreach ($open_tags as $tag)
                $truncate .= '</'.$tag.'>';

        return $truncate;
    }


    /**
     * @param $str
     * @return bool|string
     */
    public static function strtolower($str)
    {
        if (is_array($str))
            return false;
        if (function_exists('mb_strtolower'))
            return mb_strtolower($str, 'utf-8');
        return strtolower($str);
    }

    /**
     * @param $str
     * @param string $encoding
     * @return bool|int
     */
    public static function strlen($str, $encoding = 'UTF-8')
    {
        if (is_array($str))
            return false;
        $str = html_entity_decode($str, ENT_COMPAT, 'UTF-8');
        if (function_exists('mb_strlen'))
            return mb_strlen($str, $encoding);
        return strlen($str);
    }

    /**
     * @param $str
     * @return bool|string
     */
    public static function strtoupper($str)
    {
        if (is_array($str))
            return false;
        if (function_exists('mb_strtoupper'))
            return mb_strtoupper($str, 'utf-8');
        return strtoupper($str);
    }

    /**
     * @param $str
     * @param $start
     * @param bool|false $length
     * @param string $encoding
     * @return bool|string
     */
    public static function substr($str, $start, $length = false, $encoding = 'utf-8')
    {
        if (is_array($str))
            return false;
        if (function_exists('mb_substr'))
            return mb_substr($str, (int)$start, ($length === false ? self::strlen($str) : (int)$length), $encoding);
        return substr($str, $start, ($length === false ? self::strlen($str) : (int)$length));
    }

    /**
     * @param $str
     * @param $find
     * @param int $offset
     * @param string $encoding
     * @return bool|int
     */
    public static function strpos($str, $find, $offset = 0, $encoding = 'UTF-8')
    {
        if (function_exists('mb_strpos'))
            return mb_strpos($str, $find, $offset, $encoding);
        return strpos($str, $find, $offset);
    }

    /**
     * @param $str
     * @param $find
     * @param int $offset
     * @param string $encoding
     * @return bool|int
     */
    public static function strrpos($str, $find, $offset = 0, $encoding = 'utf-8')
    {
        if (function_exists('mb_strrpos'))
            return mb_strrpos($str, $find, $offset, $encoding);
        return strrpos($str, $find, $offset);
    }

    /**
     * @param $str
     * @return string
     */
    public static function ucfirst($str)
    {
        return self::strtoupper(self::substr($str, 0, 1)).self::substr($str, 1);
    }

    /**
     * @param $str
     * @return string
     */
    public static function ucwords($str)
    {
        if (function_exists('mb_convert_case'))
            return mb_convert_case($str, MB_CASE_TITLE);

        return ucwords(self::strtolower($str));
    }

    /**
     * Translates a string with underscores into camel case (e.g. first_name -> firstName)
     * @prototype string public static function toCamelCase(string $str[, bool $capitalise_first_char = false])
     * @param $str
     * @param bool $upper_case_first_char
     * @return mixed
     */
    public static function toCamelCase($str, $upper_case_first_char = false)
    {
        $str = self::strtolower($str);

        if ($upper_case_first_char)
            $str = self::ucfirst($str);

        return preg_replace_callback('/_+([a-z])/', function($c){ return strtoupper($c[1]);}, $str);
    }

    /**
     * Transform a CamelCase string to underscore_case string
     *
     * @param string $string
     * @param string $sep
     * @return string
     */
    public static function toUnderscoreCase($string, $sep='_')
    {
        // 'CMSCategories' => 'cms_categories'
        // 'RangePrice' => 'range_price'
        return self::strtolower(trim(preg_replace('/([A-Z][a-z])/', $sep . '$1', $string), $sep));
    }

    /**
     * Convert a shorthand byte value from a PHP configuration directive to an integer value
     * @param string $value value to convert
     * @return int
     */
    public static function convertBytes($value)
    {
        if (is_numeric($value))
            return $value;
        else {
            $value_length = strlen($value);
            $qty = (int)substr($value, 0, $value_length - 1 );
            $unit = self::strtolower(substr($value, $value_length - 1));
            switch ($unit)
            {
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

        $base     = log($size) / log(1024);
        $suffixes = array('b', 'k', 'M', 'G', 'T');
        $floorBase = floor($base);

        return round(pow(1024, $base - $floorBase), $precision).$suffixes[(int)$floorBase];
    }
}
