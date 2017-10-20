<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/9
 * Time: 下午10:06
 */

namespace Inhere\Library\Helpers;

/**
 * Class EnvHelper
 * @package Inhere\Library\Helpers
 */
class EnvHelper
{
////////////////////////////////////////
///  system env
////////////////////////////////////////

    /**
     * @return bool
     */
    public static function isUnix(): bool
    {
        $uNames = ['CYG', 'DAR', 'FRE', 'HP-', 'IRI', 'LIN', 'NET', 'OPE', 'SUN', 'UNI'];

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
        return self::isWindows();
    }
    public static function isWindows(): bool
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

    /**
     * @return bool
     */
    public static function isRoot(): bool
    {
        return posix_getuid() === 0;
    }

    /**
     * @return string
     */
    public static function getHostname()
    {
        return php_uname('n');
    }

    /**
     * @return string
     */
    public static function getNullDevice()
    {
        if (self::isUnix()) {
            return '/dev/null';
        }

        return 'NUL';
    }

    /**
     * Returns true if STDOUT supports colorization.
     * This code has been copied and adapted from
     * \Symfony\Component\Console\Output\OutputStream.
     * @return boolean
     */
    public static function isSupportColor()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return
                '10.0.10586' === PHP_WINDOWS_VERSION_MAJOR . '.' . PHP_WINDOWS_VERSION_MINOR . '.' . PHP_WINDOWS_VERSION_BUILD
                || false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM')// || 'cygwin' === getenv('TERM')
                ;
        }

        if (!defined('STDOUT')) {
            return false;
        }

        return self::isInteractive(STDOUT);
    }

    /**
     * Returns if the file descriptor is an interactive terminal or not.
     * @param  int|resource $fileDescriptor
     * @return boolean
     */
    public static function isInteractive($fileDescriptor)
    {
        return function_exists('posix_isatty') && @posix_isatty($fileDescriptor);
    }

////////////////////////////////////////
///  php env
////////////////////////////////////////

    /**
     * Get PHP version
     * @return string
     */
    public static function getVersion(): string
    {
        return defined('HHVM_VERSION') ? HHVM_VERSION : PHP_VERSION;
    }

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

    /**
     * setStrict
     * @return  void
     */
    public static function setStrict(): void
    {
        error_reporting(32767);
    }

    /**
     * setMuted
     * @return  void
     */
    public static function setMuted(): void
    {
        error_reporting(0);
    }

    /**
     * Returns true when the runtime used is PHP and Xdebug is loaded.
     * @return boolean
     */
    public static function hasXDebug(): bool
    {
        return static::isPHP() && extension_loaded('xdebug');
    }
}
