<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-06-26
 * Time: 11:57
 */

use inhere\library\di\Container;
use inhere\library\utils\LiteLogger;

require __DIR__ . '/s-autoload.php';

$di = new Container([
    'logger' => LiteLogger::make([], 'test'),
    'logger2' => [
        'target' => LiteLogger::class . '::make',
        ['name' => 'test2']// first arg
    ]
]);

var_dump($di);

var_dump($di->get('logger2'));