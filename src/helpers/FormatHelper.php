<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/1/29
 * Use : ...
 * File: FormatHelper.php
 */

namespace inhere\librarys\helpers;


use inhere\librarys\traits\TraitJsonFormat;

class FormatHelper
{
    use TraitJsonFormat;


    /**
     * Replaces &amp; with & for XHTML compliance
     *
     * @param   string  $text  Text to process
     *
     * @return  string  Processed string.
     */
    static public function ampReplace($text)
    {
        $text = str_replace('&&', '*--*', $text);
        $text = str_replace('&#', '*-*', $text);
        $text = str_replace('&amp;', '&', $text);
        $text = preg_replace('|&(?![\w]+;)|', '&amp;', $text);
        $text = str_replace('*-*', '&#', $text);
        $text = str_replace('*--*', '&&', $text);

        return $text;
    }

    /**
     * Cleans text of all formatting and scripting code
     *
     * @param   string  &$text  Text to clean
     *
     * @return  string  Cleaned text.
     */
    static public function cleanText(&$text)
    {
        $text = preg_replace("'<script[^>]*>.*?</script>'si", '', $text);
        $text = preg_replace('/<a\s+.*?href="([^"]+)"[^>]*>([^<]+)<\/a>/is', '\2 (\1)', $text);
        $text = preg_replace('/<!--.+?-->/', '', $text);
        $text = preg_replace('/{.+?}/', '', $text);
        $text = preg_replace('/&nbsp;/', ' ', $text);
        $text = preg_replace('/&amp;/', ' ', $text);
        $text = preg_replace('/&quot;/', ' ', $text);
        $text = strip_tags($text);
        $text = htmlspecialchars($text, ENT_COMPAT, 'UTF-8');

        return $text;
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
}// end class FormatHelper