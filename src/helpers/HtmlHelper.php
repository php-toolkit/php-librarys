<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/8/10 0010
 * Time: 00:44
 */

namespace inhere\librarys\helpers;

/**
 * Class HtmlHelper
 * @package inhere\librarys\helpers
 */
class HtmlHelper
{
    /**
     * Encodes special characters into HTML entities.
     * @param string $text data to be encoded
     * @param string $charset
     * @return string the encoded data
     * @see http://www.php.net/manual/en/function.htmlspecialchars.php
     */
    public static function encode($text, $charset= 'utf-8')
    {
        return htmlspecialchars($text,ENT_QUOTES, 'utf-8');
    }

    /**
     * This is the opposite of {@link encode()}.
     * @param string $text data to be decoded
     * @return string the decoded data
     * @see http://www.php.net/manual/en/function.htmlspecialchars-decode.php
     */
    public static function decode($text)
    {
        return htmlspecialchars_decode($text,ENT_QUOTES);
    }

    /**
     * @form yii1
     * @param array $data data to be encoded
     * @param string $charset
     * @return array the encoded data
     * @see http://www.php.net/manual/en/function.htmlspecialchars.php
     */
    public static function encodeArray($data, $charset= 'utf-8')
    {
        $d = [];

        foreach($data as $key=>$value) {
            if (is_string($key)) {
                $key = htmlspecialchars($key,ENT_QUOTES,$charset);
            }

            if (is_string($value)) {
                $value = htmlspecialchars($value,ENT_QUOTES,$charset);
            } elseif (is_array($value)) {
                $value = static::encodeArray($value);
            }

            $d[$key] = $value;
        }

        return $d;
    }

    /**
     * Strip img-tags from string
     *
     * @param   string  $string  Sting to be cleaned.
     *
     * @return  string  Cleaned string
     */
    static public function stripImages($string)
    {
        return preg_replace('#(<[/]?img.*>)#U', '', $string);
    }

    /**
     * Strip iframe-tags from string
     *
     * @param   string  $string  Sting to be cleaned.
     *
     * @return  string  Cleaned string
     */
    static public function stripIframes($string)
    {
        return preg_replace('#(<[/]?iframe.*>)#U', '', $string);
    }

    /**
     * stripScript
     *
     * @param string $string
     *
     * @return  mixed
     */
    static public function stripScript($string)
    {
        return preg_replace("'<script[^>]*>.*?</script>'si", '', $string);
    }

    /**
     * stripStyle
     *
     * @param string $string
     *
     * @return  mixed
     */
    static public function stripStyle($string)
    {
        return preg_replace("'<style[^>]*>.*?</style>'si", '', $string);
    }

    /**
     * @param $html
     * @param bool|true $onlySrc
     * @return array
     */
    public function fetchImgSrc($html, $onlySrc=true)
    {
        // $preg = '/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*>/i';
        $preg = '/<img.+src=\"(:?.+.+\.(?:jpg|gif|bmp|bnp|png)\"?).+>/i';

        preg_match_all($preg, trim($html), $imgArr);

        if ( !$imgArr ) {
            return [];
        }

        if ( $onlySrc ) {
            return array_key_exists(1, $imgArr) ? $imgArr[1] : [];
        }

        return $imgArr;
    }
}
