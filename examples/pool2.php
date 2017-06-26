<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/25
 * Time: 上午10:53
 */
require __DIR__ . '/s-autoload.php';

use inhere\library\process\pool\SimpleObjectPool;

class TestObj implements \inhere\library\process\pool\PoolObjectInterface
{
    public function create()
    {
        $obj = new \stdClass();
        $obj->name = 'test';

        return $obj;
    }

    public function release($obj)
    {
        echo "release() method.\n";
    }
}

$spl = new SimpleObjectPool(new TestObj());

$obj1 = $spl->get();
$obj2 = $spl->get();

var_dump($obj1, $obj2);

$spl->put($obj1);
$spl->put($obj2);

var_dump($spl);
