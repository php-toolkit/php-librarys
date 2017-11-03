<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-11-03
 * Time: 10:11
 */


use Inhere\Library\Components\DatabaseClient;

require __DIR__ . '/s-autoload.php';

$db = DatabaseClient::make([
    'user' => 'root',
    'password' => 'root',
]);

$db->on(DatabaseClient::CONNECT, function ($db) {
    echo "connect database success\n";
});
$db->on(DatabaseClient::DISCONNECT, function ($db) {
    echo "disconnect database success\n";
});

$ret = $db->fetchAll('show tables');

dump_vars($ret);