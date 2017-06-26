<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/24
 * Time: 下午7:16
 */

namespace inhere\library\process\pool;

/**
 * Class BasePoolObject
 * @package inhere\library\process\pool
 */
abstract class BasePoolObject implements PoolObjectInterface
{
    abstract public function create();

    abstract public function release($obj);
}
