<?php

/**
 * app local config read
 * @Notice require define constant 'PROJECT_PATH' in application entry file.
 */
class Config
{
    /**
     * app local env config
     * @var array
     */
    private static $_local = [
        '__loaded' => false,
        'started' => false,
    ];

    const CONF_FILE = '.env';

    /**********************************************************
     * app local env config
     **********************************************************/

    /**
     * get local env config
     * @param  string $name
     * @param  mixed $default
     * @return mixed
     */
    public static function local($name = null, $default=null)
    {
        // init loading ...
        if ( self::$_local['__loaded'] === false ) {
            $file = PROJECT_PATH .'/'. self::CONF_FILE;
            self::$_local['__loaded'] = true;

            if ( is_file($file) ) {
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
}
