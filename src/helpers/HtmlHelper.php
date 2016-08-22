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
