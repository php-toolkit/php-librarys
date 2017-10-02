<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/1
 * Time: 下午12:46
 */

use Inhere\Library\Utils\LiteLogger;

require __DIR__ . '/s-autoload.php';

$lgr = LiteLogger::make([
    'splitType' => 'hour',
    'basePath' => __DIR__,
], 'test');

$lgr->trace('a traced message');
$lgr->debug('a debug message', [
    'name' => 'value',
]);
$lgr->debug('a debug message');
$lgr->info('a info message');
$lgr->notice('a notice message');
$lgr->warning('a warning message');
$lgr->error('a error message');
$lgr->ex(new \RuntimeException('a exception message'));

//$lgr->flush();

print_r($lgr);
