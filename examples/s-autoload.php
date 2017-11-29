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
require dirname(__DIR__) . '/src/exceptions.php';

$inhereDir = dirname(__DIR__, 2);
$vendorDir = dirname(__DIR__, 3);
$map = [
    'Inhere\Library\Examples\\' => __DIR__,
    'Inhere\Library\Tests\\' => dirname(__DIR__) . '/tests',
    'Inhere\Library\\' => dirname(__DIR__) . '/src',
    'Inhere\Queue\\' => $inhereDir . '/queue/src',
    'Psr\Log\\' => $vendorDir . '/psr/log/Psr/Log',
];

spl_autoload_register(function($class) use ($map)
{
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
