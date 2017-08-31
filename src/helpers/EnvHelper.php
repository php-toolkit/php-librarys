<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/9
 * Time: 下午10:06
 */

namespace inhere\library\helpers;

/**
 * Class EnvHelper
 * @package inhere\library\helpers
 */
class EnvHelper
{
    /**
     * Get PHP version
     * @return string
     */
    public static function getVersion(): string
    {
        return defined('HHVM_VERSION') ? HHVM_VERSION : PHP_VERSION;
    }

////////////////////////////////////////
///  system env
////////////////////////////////////////


    /**
     * @return bool
     */
    public static function isUnix(): bool
    {
        $uNames = array('CYG', 'DAR', 'FRE', 'HP-', 'IRI', 'LIN', 'NET', 'OPE', 'SUN', 'UNI');

        return in_array(strtoupper(substr(PHP_OS, 0, 3)), $uNames, true);
    }

    /**
     * @return bool
     */
    public static function isLinux(): bool
    {
        return stripos(PHP_OS, 'LIN') !== false;
    }

    /**
     * @return bool
     */
    public static function isWin(): bool
    {
        return stripos(PHP_OS, 'WIN') !== false;
    }

    /**
     * @return bool
     */
    public static function isMac(): bool
    {
        return stripos(PHP_OS, 'Darwin') !== false;
    }

////////////////////////////////////////
///  php env
////////////////////////////////////////

    /**
     * isEmbed
     * @return  boolean
     */
    public static function isEmbed(): bool
    {
        return 'embed' === PHP_SAPI;
    }

    /**
     * @return bool
     */
    public static function isCgi(): bool
    {
        return stripos(PHP_SAPI, 'cgi') !== false;   #  cgi环境
    }

    /**
     * is Cli
     * @return  boolean
     */
    public static function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * is Build In Server
     * run server use like: `php -S 127.0.0.1:8085`
     * @return  boolean
     */
    public static function isBuiltInServer(): bool
    {
        return PHP_SAPI === 'cli-server';
    }

    /**
     * isWeb
     * @return  boolean
     */
    public static function isWeb(): bool
    {
        return in_array(PHP_SAPI, [
            'apache',
            'cgi',
            'fast-cgi',
            'cgi-fcgi',
            'fpm-fcgi',
            'srv',
            'cli-server'
        ], true);
    }

    /**
     * isHHVM
     * @return  boolean
     */
    public static function isHHVM(): bool
    {
        return defined('HHVM_VERSION');
    }

    /**
     * isPHP
     * @return  boolean
     */
    public static function isPHP(): bool
    {
        return !static::isHHVM();
    }


}
