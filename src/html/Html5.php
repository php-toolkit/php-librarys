<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/26
 * Time: 下午3:57
 */

namespace inhere\library\html;

/**
 * Class Html5
 * @package inhere\library\html
 */
class Html5 extends Html
{
    /**
     * style tag
     * @param  string $content
     * @param array $attrs
     * @return string
     */
    public static function style($content, array $attrs = [])
    {
        // $attrs = array_merge( ['type' =>"text/css"], $attrs );

        return static::tag('style', PHP_EOL . trim($content) . PHP_EOL, $attrs);
    }

    /**
     * @param $href
     * @param array $attrs
     * @return string
     */
    public static function css($href, array $attrs = [])
    {
        $attrs = array_merge([
            'href' => $href,
        ], $attrs);

        return static::tag('link', null, $attrs);
    }

    /**
     * javascript tag
     * @param  string $content
     * @param array $attrs
     * @return string
     */
    public static function scriptCode($content = null, array $attrs = [])
    {
        return static::tag('script', PHP_EOL . trim($content) . PHP_EOL, $attrs);
    }

    /**
     * javascript tag
     * @param  string $src
     * @param array $attrs
     * @return string
     */
    public static function script($src, array $attrs = [])
    {
        $attrs = array_merge([
            'src' => $src
        ], $attrs);

        return static::tag('script', null, $attrs);
    }
}
