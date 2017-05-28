<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/28
 * Time: 下午10:35
 */

use inhere\library\process\ProcessLogger;

require __DIR__ . '/s-autoload.php';

$plg = new ProcessLogger([
    'file' => __DIR__ . '/process.log',
    'spiltType' => ProcessLogger::SPLIT_HOUR,
]);

$plg->log('message');
$plg->log('message', $plg::ERROR);
$plg->ex(new \RuntimeException('test exception'), 'message');
