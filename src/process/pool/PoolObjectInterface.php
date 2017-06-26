<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/24
 * Time: 下午7:16
 */

namespace inhere\library\process\pool;

/**
 * Class PoolObjectInterface
 * @package inhere\library\process\pool
 */
interface PoolObjectInterface
{
    public function create();

    public function release($obj);
}
