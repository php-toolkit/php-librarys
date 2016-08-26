<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/27
 * Time: 上午12:33
 */

namespace inhere\librarys\asset;

/**
 * Class AssetHelper
 * @package inhere\librarys\asset
 */
class AssetHelper
{
    /**
     * @var array
     */
    public static $patterns = [
        'cssJs' => '/.\.(css|js)$/i',
        'css' => '/.\.css$/i',
        'js' => '/.\.js$/i',
        'min' => '/.[-.]min\.(css|js)$/i',

        'font' => '/.\.(ttf|svg|eot|woff|woff2)$/i',
        'img' => '/.\.(png|jpg|jpeg|gif|ico)$/i',
    ];

    /**
     * @param $file
     * @return bool
     */
    public static function isCss($file)
    {
        return 1 === preg_match(static::$patterns['css'], trim($file));
    }

    /**
     * @param $file
     * @return bool
     */
    public static function isJs($file)
    {
        return 1 === preg_match(static::$patterns['js'], trim($file));
    }

    /**
     * @param $file
     * @return bool
     */
    public static function isCssOrJs($file)
    {
        return 1 === preg_match(static::$patterns['cssJs'], trim($file));
    }

    /**
     * @param $file
     * @return bool
     */
    public static function isMinCssOrJs($file)
    {
        return 1 === preg_match(static::$patterns['min'], trim($file));
    }

    /**
     * @param $file
     * @return bool
     */
    public static function isFont($file)
    {
        return 1 === preg_match(static::$patterns['font'], trim($file));
    }

    /**
     * @param $file
     * @return bool
     */
    public static function isImage($file)
    {
        return 1 === preg_match(static::$patterns['img'], trim($file));
    }

}