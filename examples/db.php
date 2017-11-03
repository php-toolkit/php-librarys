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
    'debug' => 1,
    'user' => 'root',
    'password' => 'root',
]);

$db->on(DatabaseClient::CONNECT, function ($db) {
    echo "connect database success\n";
});
$db->on(DatabaseClient::BEFORE_EXECUTE, function ($sql) {
    echo "Will run SQL: $sql\n";
});
$db->on(DatabaseClient::DISCONNECT, function ($db) {
    echo "disconnect database success\n";
});

//$ret = $db->fetchAll('show tables');
//dump_vars($ret);
//
//$ret = $db->fetchAll('select * from user');
//dump_vars($ret);

// find one
// SQL: SELECT * FROM `user` WHERE `id`= ? LIMIT 1
//$ret = $db->find('user', ['id' => 3], '*', [
//    'fetchType' => 'assoc'
//]);
//dump_vars($ret);

// find all
// SQL: SELECT * FROM `user` WHERE `username` like ? LIMIT 1000
$ret = $db->findAll('user', [ ['username', 'like', '%tes%'] ], '*', [
    'fetchType' => 'assoc',
    'limit' => 10
]);
dump_vars($ret);

// find all
// SQL: SELECT * FROM `user` WHERE `id` > ? ORDER BY createdAt ASC LIMIT 1000
$ret = $db->findAll('user', [['id', '>', 3]], '*', [
    'fetchType' => 'assoc',
    'order' => 'createdAt ASC',
]);
dump_vars($ret);

dump_vars($db->getQueryLog());
