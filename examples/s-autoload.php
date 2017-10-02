<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/28
 * Time: 下午10:36
 */

ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

require dirname(__DIR__) . '/src/functions.php';
require dirname(__DIR__) . '/src/some_exception.php';

spl_autoload_register(function($class)
{
    $inhereDir = dirname(__DIR__, 2);
    $map = [
        'Inhere\Library\examples\\' => __DIR__,
        'Inhere\Library\\' => dirname(__DIR__) . '/src',
        'inhere\queue\\' => $inhereDir . '/queue/src',
    ];

    foreach ($map as $np => $dir) {
        if (0 === strpos($class, $np)) {
            $path = str_replace('\\', '/', substr($class, strlen($np)));
            $file = $dir . "/{$path}.php";

            if (is_file($file)) {
                include_file($file);
            }
        }
    }
});

function include_file($file) {
    include $file;
}
