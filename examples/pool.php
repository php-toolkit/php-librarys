<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/25
 * Time: 上午10:53
 */
require __DIR__ . '/s-autoload.php';

use inhere\library\process\pool\ResourcePool;


$rpl = new ResourcePool([
 'maxSize' => 2,
]);

$rpl->setResourceCreator(function () {
    $obj = new \stdClass();
    $obj->name = 'test';

    return $obj;
})
->setResourceReleaser(function ($obj) {
    echo "call resourceReleaser.\n";
});

$obj1 = $rpl->get();
$obj2 = $rpl->get();
$obj3 = $rpl->get();

var_dump($obj1, $obj2, $obj3,$rpl);

$rpl->put($obj1);
$rpl->put($obj2);

var_dump($rpl);

$rpl->call(function ($obj) {
   echo " $obj->name\n";
});

var_dump($rpl);
