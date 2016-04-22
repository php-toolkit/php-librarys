<?php
/**
*
*/
namespace inhere\tools\helpers;


class PhpHelper
{

    /**
     * 检查扩展是否加载
     * @param array $extensions
     * @param  boolean $totalReport true: 检查完后报告结果(array). false: 立即报告出错的;若全部加载了，返回 true
     * @return array|bool
     */
    static public function extensionIsLoaded($extensions=[], $totalReport=false)
    {
        $allTotal = [];

        foreach ((array)$extensions as $extension)
        {
            $bool = extension_loaded($extension);

            if (!$totalReport && !$bool){
                return false;
            }

            if (!$bool) {
                # 没有加载此扩展，记录
                $allTotal['no'][] = $extension;
            } else {
                $allTotal['yes'][] = $extension;
            }

        }

        return $totalReport ? $allTotal : true;
    }

    /**
     * 返回加载的扩展
     * @param bool $zend_extensions
     * @return array
     */
    static public function getLoadedExtension($zend_extensions = false)
    {
        return get_loaded_extensions($zend_extensions);
    }

    /**
     * 根据服务器设置得到文件上传大小的最大值
     *
     * @param int $max_size optional max file size
     * @return int max file size in bytes
     */
    static public function getMaxUploadSize($max_size = 0)
    {
        $post_max_size = StrHelper::convertBytes(ini_get('post_max_size'));
        $upload_max_filesize = StrHelper::convertBytes(ini_get('upload_max_filesize'));

        if ($max_size > 0)
            $result = min($post_max_size, $upload_max_filesize, $max_size);
        else
            $result = min($post_max_size, $upload_max_filesize);

        return $result;
    }

    /**
     * Get PHP version
     * @return string
     */
    static public function getVersion()
    {
        if ( defined('HHVM_VERSION') )
        {
            return HHVM_VERSION;
        }
        else
        {
            return PHP_VERSION;
        }
    }

    /**
     * setStrict
     * @return  void
     */
    static public function setStrict()
    {
        error_reporting(32767);
    }

    /**
     * setMuted
     * @return  void
     */
    static public function setMuted()
    {
        error_reporting(0);
    }

///////////////////////////////////// system info /////////////////////////////////////
///

    /**
     * @return bool
     */
    static public function isWin()
    {
        return strstr(PHP_OS, 'WIN') ? true : false;
    }

    /**
     * @return bool
     */
    static public function isCgi()
    {
        return strpos(PHP_SAPI, 'cgi') !== false ? true : false;   #  cgi环境
    }

    /**
     * isWeb
     * @return  boolean
     */
    static public function isWeb()
    {
        return in_array(
            PHP_SAPI,
            array(
                'apache',
                'cgi',
                'fast-cgi',
                'cgi-fcgi',
                'fpm-fcgi',
                'srv'
            )
        );
    }

    /**
     * isCli
     * @return  boolean
     */
    static public function isCli()
    {
        return in_array(
            PHP_SAPI,
            array(
                'cli',
                'cli-server'
            )
        );
    }

    /**
     * isHHVM
     * @return  boolean
     */
    static public function isHHVM()
    {
        return defined('HHVM_VERSION');
    }

    /**
     * isPHP
     * @return  boolean
     */
    static public function isPHP()
    {
        return !static::isHHVM();
    }

    /**
     * isEmbed
     * @return  boolean
     */
    static public function isEmbed()
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
    static public function hasXdebug()
    {
        return static::isPHP() && extension_loaded('xdebug');
    }

    /**
     * supportPcntl
     * @return  boolean
     */
    static public function hasPcntl()
    {
        return extension_loaded('PCNTL');
    }

    /**
     * supportCurl
     * @return  boolean
     */
    static public function hasCurl()
    {
        return function_exists('curl_init');
    }

    /**
     * supportMcrypt
     * @return  boolean
     */
    static public function hasMcrypt()
    {
        return extension_loaded('mcrypt');
    }

    //检查 Apache mod_rewrite 是否开启
    static public function checkApacheRewriteMode()
    {
        if ( function_exists('apache_get_modules') )
        {
            return in_array( 'mod_rewrite', apache_get_modules() );
        }
        // de(\apache_get_version() ,\apache_get_modules());

        return true;
        // return false;
    }
}
