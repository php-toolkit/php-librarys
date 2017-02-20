<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/12/14
 * Time: 19:44
 */

namespace inhere\librarys\collections;

/**
 * app local config read
 *
 * in local env file(must is 'ini' file):
 *
 * ```
 * env=dev
 * debug=true
 * ... ...
 * ```
 * in code:
 *
 * ```
 * $debug = Local::env('debug', false);// can also use function: local_env('debug', false)
 * $env = Local::env('env', 'pdt');
 * ```
 */
class Local
{
    /**
     * app local env config
     * @var array
     */
    private static $_local;

    /**
     * config File
     * @var string
     */
    private static $configFile = '';

    /**********************************************************
     * app local env config
     **********************************************************/

    /**
     * get local env config
     * @param  string $name
     * @param  mixed $default
     * @return mixed
     */
    public static function env($name = null, $default = null)
    {
        // init loading ...
        if ( self::$_local === null ) {
            self::$_local = [];

            if ( ($file = self::getConfigFile()) && is_file($file) ) {
                self::$_local = array_merge(self::$_local, parse_ini_file($file, true));
            }
        }

        // get all
        if ( null === $name ) {
            return self::$_local;
        }

        // get one by key name
        return isset(self::$_local[$name]) ? self::$_local[$name] : $default;
    }

    /**
     * setConfigFile
     * @param string $file
     */
    public static function setConfigFile($file)
    {
        if ( $file ) {
            self::$configFile = $file;
        }
    }

    /**
     * getConfigFile
     * @return string
     */
    public static function getConfigFile()
    {
        if ( !self::$configFile && defined('PROJECT_PATH')) {
            self::$configFile = PROJECT_PATH . '/.env';
        }

        return self::$configFile;
    }
}
