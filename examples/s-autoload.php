<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/28
 * Time: 下午10:36
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

require dirname(__DIR__) . '/src/functions.php';
require dirname(__DIR__) . '/src/some_exception.php';

spl_autoload_register(function($class)
{
    if (0 === strpos($class,'inhere\library\examples\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('inhere\library\examples\\')));
        $file =__DIR__ . "/{$path}.php";

        if (is_file($file)) {
            include $file;
        }

    } elseif (0 === strpos($class,'inhere\library\\')) {
        $path = str_replace('\\', '/', substr($class, strlen('inhere\library\\')));
        $file = dirname(__DIR__) . "/src/{$path}.php";

        if (is_file($file)) {
            include $file;
        }
    }
});
