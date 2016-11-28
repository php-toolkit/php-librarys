<?php
/**
 *
 */
namespace inhere\librarys\helpers;

use inhere\librarys\exceptions\ExtensionMissException;

/**
 * Class PhpHelper
 * @package inhere\librarys\helpers
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

        if ( !$result && $throwException) {
            throw new ExtensionMissException("Extension [$name] is not loaded.");
        }

        return $result;
    }

    /**
     * 检查多个扩展加载情况
     * @param array $extensions
     * @return array|bool
     */
    public static function checkExtList($extensions=[])
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
        $post_max_size = StrHelper::convertBytes(ini_get('post_max_size'));
        $upload_max_filesize = StrHelper::convertBytes(ini_get('upload_max_filesize'));

        if ($max_size > 0) {
            $result = min($post_max_size, $upload_max_filesize, $max_size);
        } else {
            $result = min($post_max_size, $upload_max_filesize);
        }

        return $result;
    }

    /**
     * Get PHP version
     * @return string
     */
    public static function getVersion()
    {
        if ( defined('HHVM_VERSION') ) {
            return HHVM_VERSION;
        } else {
            return PHP_VERSION;
        }
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
        $unames = array('CYG', 'DAR', 'FRE', 'HP-', 'IRI', 'LIN', 'NET', 'OPE', 'SUN', 'UNI');

        return in_array( strtoupper(substr(PHP_OS, 0, 3)) , $unames);
    }

    /**
     * @return bool
     */
    public static function isLinux()
    {
        return strstr(PHP_OS, 'LIN') ? true : false;
    }

    /**
     * @return bool
     */
    public static function isWin()
    {
        return strstr(PHP_OS, 'WIN') ? true : false;
    }

    /**
     * @return bool
     */
    public static function isCgi()
    {
        return strpos(PHP_SAPI, 'cgi') !== false ? true : false;   #  cgi环境
    }

    /**
     * isWeb
     * @return  boolean
     */
    public static function isWeb()
    {
        return in_array(
            PHP_SAPI,
            array(
                'apache',
                'cgi',
                'fast-cgi',
                'cgi-fcgi',
                'fpm-fcgi',
                'srv',
                'cli-server'
            )
        );
    }

    /**
     * isCli
     * @return  boolean
     */
    public static function isCli()
    {
        return in_array(
            PHP_SAPI,
            array(
                'cli',
            )
        );
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
        return in_array(
            PHP_SAPI,
            array(
                'embed',
            )
        );
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
     * supportPcntl
     * @return  boolean
     */
    public static function hasPcntl()
    {
        return extension_loaded('PCNTL');
    }

    /**
     * supportCurl
     * @return  boolean
     */
    public static function hasCurl()
    {
        return function_exists('curl_init');
    }

    /**
     * supportMcrypt
     * @return  boolean
     */
    public static function hasMcrypt()
    {
        return extension_loaded('mcrypt');
    }

    //检查 Apache mod_rewrite 是否开启
    public static function checkApacheRewriteMode()
    {
        if ( function_exists('apache_get_modules') )
        {
            return in_array( 'mod_rewrite', apache_get_modules() );
        }
        // de(\apache_get_version() ,\apache_get_modules());

        return true;
        // return false;
    }

    /**
     * Method to execute a command in the terminal
     * Uses :
     * 1. system
     * 2. passthru
     * 3. exec
     * 4. shell_exec
     * @param $command
     * @return array
     */
    public static function terminal($command)
    {
        $return_var = 1;

        //system
        if (function_exists('system')) {
            ob_start();
            system($command , $return_var);
            $output = ob_get_contents();
            ob_end_clean();

        // passthru
        } elseif (function_exists('passthru')) {
            ob_start();
            passthru($command , $return_var);
            $output = ob_get_contents();
            ob_end_clean();
        //exec
        } else if (function_exists('exec')) {
            exec($command , $output , $return_var);
            $output = implode("\n" , $output);

        //shell_exec
        } else if (function_exists('shell_exec')) {
            $output = shell_exec($command) ;
        } else {
            $output = 'Command execution not possible on this system';
            $return_var = 0;
        }

        return array('output' => $output , 'status' => $return_var);
    }
}
