<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-6-5
 * Time: 下午11:39
 *  数据操作 转码 序列化
 */

namespace inhere\library\helpers;

/**
 * Class DataHelper
 * @package inhere\library\helpers
 */
abstract class DataHelper
{
    /**
     * Get a value from $_POST / $_GET
     * if unavailable, take a default value
     *
     * @param string $key Value key
     * @param mixed $default_value (optional)
     * @return mixed Value
     */
    public static function getValue($key, $default_value = false)
    {
        if (!$key || !is_string($key)) {
            return false;
        }

        $ret = $_POST[$key] ?? $_GET[$key] ?? $default_value;

        if (is_string($ret)) {
            return stripslashes(urldecode(preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($ret))));
        }

        return $ret;
    }


    /**
     * Get all values from $_POST/$_GET
     * @return mixed
     */
    public static function getAllValues()
    {
        return $_POST + $_GET;
    }

    /**
     * @param $key
     * @return bool
     */
    public static function hasKey($key)
    {
        if (!$key || !is_string($key)) {
            return false;
        }

        return isset($_POST[$key]) ? true : isset($_GET[$key]);
    }

    public static function safePostVars()
    {
        if (!$_POST || !is_array($_POST)) {
            $_POST = array();
        } else {
            $_POST = array_map(array(__CLASS__, 'htmlentitiesUTF8'), $_POST);
        }
    }

    /**
     * Sanitize a string
     *
     * @param string $string String to sanitize
     * @param bool $clearTag clear html tag
     * @return string Sanitized string
     */
    public static function safeOutput($string, $clearTag = false)
    {
        if (!$clearTag) {
            $string = strip_tags($string);
        }

        return @self::htmlentitiesUTF8($string, ENT_QUOTES);
    }

    public static function htmlentitiesUTF8($string, $type = ENT_QUOTES)
    {
        if (is_array($string)) {
            return array_map(array(__CLASS__, 'htmlentitiesUTF8'), $string);
        }

        return htmlentities((string)$string, $type, 'utf-8');
    }

    public static function htmlentitiesDecodeUTF8($string)
    {
        if (is_array($string)) {
            $string = array_map(array(__CLASS__, 'htmlentitiesDecodeUTF8'), $string);
            return (string)array_shift($string);
        }

        return html_entity_decode((string)$string, ENT_QUOTES, 'utf-8');
    }


    /**
     * Convert \n and \r\n and \r to <br />
     *
     * @param string $str String to transform
     * @return string New string
     */
    public static function nl2br($str)
    {
        return str_replace(array("\r\n", "\r", "\n"), '<br />', $str);
    }


    public static function argvToGET($argc, $argv)
    {
        if ($argc <= 1) {
            return null;
        }

        // get the first argument and parse it like a query string
        parse_str($argv[1], $args);
        if (!is_array($args) || !count($args)) {
            return null;
        }

        $_GET = array_merge($args, $_GET);
        $_SERVER['QUERY_STRING'] = $argv[1];
    }

    /**
     * 清理数据的空白
     * @param $data array|string
     * @return array|string
     */
    public static function trim($data)
    {
        if (is_scalar($data)) {
            return trim($data);
        }

        array_walk_recursive($data, function (&$value) {
            $value = trim($value);
        });

        return $data;
    }

    /**
     * @param $data
     * @param $separator
     * @example
     *      /status/active/id/12
     *  =>
     *    [
     *     'status' => 'active',
     *     'id'     => '12',
     *    ]
     * @return array
     */
    public static function buildQueryParams($data, $separator = '/')
    {
        $arrData = is_string($data) ? explode($separator, $data) : $data;
        $arrData = array_values(array_filter($arrData));
        $newArr = [];
        $count = count($arrData); #统计

        // $arrData 中的 奇数位--变为键，偶数位---变为前一个奇数 键的值 array('前一个奇数'=>'偶数位')
        for ($i = 0; $i < $count; $i += 2) {
            $newArr[$arrData[$i]] = $arrData[$i + 1] ?? '';
        }

        unset($arrData);

        return $newArr;
    }

    /*
     * strip_tags — 从字符串中去除 HTML 和 PHP 标记
     * 由于 strip_tags() 无法实际验证 HTML，不完整或者破损标签将导致更多的数据被删除。
     * $allow_tags 允许的标记,多个以空格隔开
     **/
    public static function stripTags($data, $allow_tags = null)
    {
        if (is_array($data)) {

            foreach ($data as $k => $v) {
                $data[$k] = self::stripTags($v, $allow_tags);
            }

            return $data;
        }
        if (is_string($data) || is_numeric($data)) {
            return strip_tags($data, $allow_tags);
        }

        return false;
    }

    /*
     * html代码转义
     * htmlspecialchars 只转化这几个html [ & ' " < > ] 代码 --> [ &amp; &quot;  ]，
     * 而 htmlentities 却会转化所有的html代码，连同里面的它无法识别的中文字符也会转化。
     * 一般使用 htmlspecialchars 就足够了，要使用 htmlentities 时，要注意为第三个参数传递正确的编码。
     * htmlentities() <--> html_entity_decode() — 将特殊的 HTML 实体转换回普通字符
     * htmlspecialchars() <--> htmlspecialchars_decode() — 将特殊的 HTML 实体转换回普通字符
     * ENT_COMPAT ENT_QUOTES ENT_NOQUOTES ENT_HTML401 ENT_XML1 ENT_XHTML ENT_HTML5
     * */
    public static function htmlEscape($data, $type = 0, $encoding = 'UTF-8')
    {
        if (is_array($data)) {

            foreach ($data as $k => $v) {
                $data[$k] = self::htmlEscape($data, $type, $encoding);
            }

        } else {

            if (!$type) {//默认使用  htmlspecialchars()
                $data = htmlspecialchars($data, ENT_QUOTES, $encoding);
            } else {
                $data = htmlentities($data, ENT_QUOTES, $encoding);
            }

            //如‘&#x5FD7;’这样的16进制的html字符，为了防止这样的字符被错误转译，使用正则进行匹配，把这样的字符又转换回来。
            if (strpos($data, '&#')) {
                $data = preg_replace('/&((#(\d{3,5}|x[a-fA-F0-9]{4}));)/',
                    '&\\1', $data);
            }
        }

        return $data;
    }

    //去掉html转义
    public static function htmlUnescap($data, $type = 0, $encoding = 'UTF-8')
    {
        if (is_array($data)) {

            foreach ($data as $k => $v) {
                $data[$k] = self::htmlUnescap($data, $type, $encoding);
            }

        } else {
            if (!$type) {//默认使用  htmlspecialchars_decode()
                $data = htmlspecialchars_decode($data, ENT_QUOTES);
            } else {
                $data = html_entity_decode($data, ENT_QUOTES, $encoding);
            }

        }

        return $data;
    }

    /**
     * 对数组或字符串进行加斜杠\转义处理 去除转义
     *
     * 去除转义返回一个去除反斜线后的字符串（\' 转换为 ' 等等）。双反斜线（\\）被转换为单个反斜线（\）。
     * @param array|string $data 数据可以是字符串或数组
     * @param int $escape 进行转义 true 转义处理 false 去除转义
     * @param int $level 增强
     * @return array|string
     */
    public static function slashes($data, $escape = 1, $level = 0)
    {
        if (is_array($data)) {
            foreach ((array)$data as $key => $value) {
                $data[$key] = self::slashes($value, $escape, $level);
            }

            return $data;
        }

        $data = trim($data);

        if (!$escape) {
            return stripslashes($data);
        }

        $data = addslashes($data);

        if ($level) {
            // 两个str_replace替换转义目的是防止黑客转换SQL编码进行攻击。
            $data = str_replace(['_', '%'], ["\_", "\%"], $data);    // 转义掉_ %
        }

        return $data;
    }

    public static function escape_query($str)
    {
        return strtr($str, array(
            "\0" => '',
            "'" => '&#39;',
            '"' => '&#34;',
            "\\" => '&#92;',
            // more secure
            '<' => '&lt;',
            '>' => '&gt;',
        ));
    }

    /**
     * 对数据进行字符集转换处理，数据可以是字符串或数组及对象
     * @param array $data
     * @param $in_charset
     * @param $out_charset
     * @return array|string
     */
    public static function changeEncode($data, $in_charset = 'GBK', $out_charset = 'UTF-8')
    {
        if (is_array($data)) {

            foreach ($data as $key => $value) {
                $data[$key] = self::changeEncode($value, $in_charset, $out_charset);
            }

            return $data;
        }

        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($data, $out_charset, $in_charset);
        }

        return iconv($in_charset, $out_charset . '/' . '/IGNORE', $data);
    }

}
