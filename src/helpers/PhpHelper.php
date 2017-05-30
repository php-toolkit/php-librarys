<?php
/**
 *
 */

namespace inhere\library\helpers;

use inhere\exceptions\ExtensionMissException;

/**
 * Class PhpHelper
 * @package inhere\library\helpers
 */
class PhpHelper
{
    /**
     * @param $name
     * @param bool|false $throwException
     * @return bool
     * @throws ExtensionMissException
     */
    public static function extIsLoaded($name, $throwException = false)
    {
        $result = extension_loaded($name);

        if (!$result && $throwException) {
            throw new ExtensionMissException("Extension [$name] is not loaded.");
        }

        return $result;
    }

    /**
     * 检查多个扩展加载情况
     * @param array $extensions
     * @return array|bool
     */
    public static function checkExtList(array $extensions = array())
    {
        $allTotal = [];

        foreach ((array)$extensions as $extension) {
            if (!extension_loaded($extension)) {
                # 没有加载此扩展，记录
                $allTotal['no'][] = $extension;
            } else {
                $allTotal['yes'][] = $extension;
            }
        }

        return $allTotal;
    }

    /**
     * 返回加载的扩展
     * @param bool $zend_extensions
     * @return array
     */
    public static function getLoadedExtension($zend_extensions = false)
    {
        return get_loaded_extensions($zend_extensions);
    }

    /**
     * 根据服务器设置得到文件上传大小的最大值
     *
     * @param int $max_size optional max file size
     * @return int max file size in bytes
     */
    public static function getMaxUploadSize($max_size = 0)
    {
        $post_max_size = FormatHelper::convertBytes(ini_get('post_max_size'));
        $upload_max_fileSize = FormatHelper::convertBytes(ini_get('upload_max_filesize'));

        if ($max_size > 0) {
            $result = min($post_max_size, $upload_max_fileSize, $max_size);
        } else {
            $result = min($post_max_size, $upload_max_fileSize);
        }

        return $result;
    }

    /**
     * Get PHP version
     * @return string
     */
    public static function getVersion()
    {
        return defined('HHVM_VERSION') ? HHVM_VERSION : PHP_VERSION;
    }

    /**
     * setStrict
     * @return  void
     */
    public static function setStrict()
    {
        error_reporting(32767);
    }

    /**
     * setMuted
     * @return  void
     */
    public static function setMuted()
    {
        error_reporting(0);
    }

///////////////////////////////////// system info /////////////////////////////////////
///

    /**
     * @return bool
     */
    public static function isUnix()
    {
        $uNames = array('CYG', 'DAR', 'FRE', 'HP-', 'IRI', 'LIN', 'NET', 'OPE', 'SUN', 'UNI');

        return in_array(strtoupper(substr(PHP_OS, 0, 3)), $uNames, true);
    }

    /**
     * @return bool
     */
    public static function isLinux()
    {
        return stripos(PHP_OS, 'LIN') !== false;
    }

    /**
     * @return bool
     */
    public static function isWin()
    {
        return stripos(PHP_OS, 'WIN') !== false;
    }

    /**
     * @return bool
     */
    public static function isMac()
    {
        return stripos(PHP_OS, 'Darwin') !== false;
    }

    /**
     * @return bool
     */
    public static function isCgi()
    {
        return stripos(PHP_SAPI, 'cgi') !== false;   #  cgi环境
    }

    /**
     * is Cli
     * @return  boolean
     */
    public static function isCli()
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * is Build In Server
     * run server use like: `php -S 127.0.0.1:8085`
     * @return  boolean
     */
    public static function isBuiltInServer()
    {
        return PHP_SAPI === 'cli-server';
    }

    /**
     * isWeb
     * @return  boolean
     */
    public static function isWeb()
    {
        return in_array(PHP_SAPI, [
            'apache',
            'cgi',
            'fast-cgi',
            'cgi-fcgi',
            'fpm-fcgi',
            'srv',
            'cli-server'
        ], true );
    }

    /**
     * isHHVM
     * @return  boolean
     */
    public static function isHHVM()
    {
        return defined('HHVM_VERSION');
    }

    /**
     * isPHP
     * @return  boolean
     */
    public static function isPHP()
    {
        return !static::isHHVM();
    }

    /**
     * isEmbed
     * @return  boolean
     */
    public static function isEmbed()
    {
        return 'embed' === PHP_SAPI;
    }

    /**
     * Returns true when the runtime used is PHP and Xdebug is loaded.
     * @return boolean
     */
    public static function hasXdebug()
    {
        return static::isPHP() && extension_loaded('xdebug');
    }

    /**
     * Converts an exception into a simple string.
     * @param \Exception|\Throwable $exp the exception being converted
     * @param string $br
     * @param bool $getTrace
     * @return string the string representation of the exception.
     */
    public static function convertExceptionToString($exp, $br = "\n", $getTrace = false)
    {
        if (!$getTrace) {
            $message = "Error: {$exp->getMessage()}";
        } else {
            if ($exp instanceof \ErrorException) {
                $message = 'Error';
            } else {
                $message = 'Exception';
            }

            $message .= " '" . get_class($exp) . "' with message '{$exp->getMessage()}' \n\nin "
                . $exp->getFile() . ':' . $exp->getLine() . "\n\n"
                . "Stack trace:\n" . $exp->getTraceAsString();

            if ($br !== "\n") {
                $message = str_replace("\n", $br, $message);
            }
        }

        return $message;
    }

    /**
     * dump vars
     * @param array ...$args
     * @return string
     */
    public static function dumpVar(...$args)
    {
        ob_start();
        var_dump(...$args);
        $string = ob_get_clean();

        return preg_replace("/=>\n\s+/", '=> ', $string);
    }

    /**
     * print vars
     * @param array ...$args
     * @return string
     */
    public static function printVar(...$args)
    {
        ob_start();

        foreach ($args as $arg) {
            print_r($arg);
        }

        $string = ob_get_clean();

        return preg_replace("/Array\n\s+\(/", 'Array (', $string);
    }
}
