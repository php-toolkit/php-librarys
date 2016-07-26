<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/1/30
 * Use : ...
 * File: TraitUrlHelper.php
 */

namespace inhere\librarys\traits;

/**
 * Class TraitUrlHelper
 * @package inhere\librarys\traits
 */
trait TraitUrlHelper
{
    static public function isUrl($str)
    {
        $rule = '/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i';

        return preg_match($rule,$str)===1;
    }

    // Build arrays of values we need to decode before parsing
    protected static  $entities       = array(
        '%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26',
        '%3D', '%24', '%2C', '%2F', '%3F', '%23', '%5B', '%5D'
    );

    protected static $replacements   = array(
        '!'  , '*'  , "'"  ,  '(' ,  ')' ,  ';' ,  ':' ,  '@' ,  '&' ,
        '='  , '$'  , ','  ,  '/' ,  '?' ,  '#' ,  '[' ,  ']'
    );

    static public function parseUrl($url)
    {
        $result         = [];

        // Create encoded URL with special URL characters decoded so it can be parsed
        // All other characters will be encoded
        $encodedURL     = str_replace(self::$entities, self::$replacements, urlencode($url));

        // Parse the encoded URL
        $encodedParts   = parse_url($encodedURL);

        // Now, decode each value of the resulting array
        if ($encodedParts) {
            foreach ($encodedParts as $key => $value) {
                $result[$key] = urldecode(str_replace(self::$replacements, self::$entities, $value));
            }
        }

        return $result;
    }

    /**
     * url_encode form urlencode(),但是 : / ? & = ...... 几个符号不会被转码为 %3A %2F %3F %26 %3D ......
     * $url="ftp://ud03:password@www.xxx.net/中文/中文.rar";
     * $url1 =  url_encode1($url);
     * //ftp://ud03:password@www.xxx.net/%E4%B8%AD%E6%96%87/%E4%B8%AD%E6%96%87.rar
     * $url2 =  urldecode($url);
     * echo $url1.PHP_EOL.$url2.PHP_EOL;
     * @param $url
     * @return mixed|string [type] [description]
     */
    static public function encode($url)
    {
        $url        = trim($url);

        if (empty($url) || !is_string($url) ) {
            return $url;
        }

        // 若已被编码的url，将被解码，再继续重新编码
        $url       = urldecode($url);
        $encodeUrl = urlencode($url);
        $encodeUrl = str_replace( self::$entities, self::$replacements, $encodeUrl);

        return $encodeUrl;
    }

    /**
     * [urlEncode 会先转换编码]
     * $url="ftp://ud03:password@www.xxx.net/中文/中文.rar";
     * $url1 =  url_encode($url);
     * //ftp://ud03:password@www.xxx.net/%C3%A4%C2%B8%C2%AD%C3%A6%C2%96%C2%87/%C3%A4%C2%B8%C2%AD%C3%A6%C2%96%C2%87.rar
     * $url2 =  urldecode($url);
     * echo $url1.PHP_EOL.$url2;
     * @param  string $url [description]
     * @return mixed|string [type]      [description]
     */
    static public function encode2($url)
    {
        $url         = trim($url);

        if (!$url || !is_string($url) ) {
            return $url;
        }

        // 若已被编码的url，将被解码，再继续重新编码
        $url         = urldecode($url);
        $encodeUrl   = rawurlencode(mb_convert_encoding($url, 'utf-8'));

        // $url  = rawurlencode($url);

        $encodeUrl = str_replace( self::$entities, self::$replacements, $encodeUrl);

        return $encodeUrl;
    }
}
