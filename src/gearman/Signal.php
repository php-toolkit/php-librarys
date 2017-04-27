<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-04-27
 * Time: 14:28
 */

namespace inhere\library\gearman;

/**
 * Class Signal - Process Signal Helper
 * @package inhere\library\gearman
 */
class Signal
{
    private static $exit = false;

    /**
     * Checks exit signals
     * @return bool
     */
    public static function isExit()
    {
        if (function_exists('pcntl_signal')) {
            // Installs a signal handler
            static $handled = false;

            if (!$handled) {
                foreach ([SIGTERM, SIGINT, SIGHUP] as $signal) {
                    pcntl_signal($signal, function () {
                        static::$exit = true;
                    });
                }

                $handled = true;
            }

            // Checks signal
            if (!static::$exit) {
                pcntl_signal_dispatch();
            }
        }

        return static::$exit;
    }
}