<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-11-03
 * Time: 10:11
 */


use Inhere\Library\Components\RedisClient;

require __DIR__ . '/s-autoload.php';

$redis = new RedisClient([

]);

$redis->on(RedisClient::CONNECT, function ($r) {
    echo "connect redis success\n";
});
$redis->on(RedisClient::DISCONNECT, function ($r) {
    echo "disconnect redis success\n";
});

$suc = $redis->set('key1', 'value');
$ret = $redis->get('key1');

dump_vars($suc, $ret);

$ret = $redis->getStats('STATS');

dump_vars($ret);