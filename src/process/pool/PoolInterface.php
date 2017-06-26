<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/24
 * Time: 下午7:16
 */

namespace inhere\library\process\pool;

/**
 * Class PoolInterface
 * @package inhere\library\process\pool
 */
interface PoolInterface
{
    /**
     * 获取资源
     * @param bool $blocking 是否阻塞，当没有资源可用时
     * @return mixed
     */
    public function get($blocking = false);

    /**
     * 返还资源到资源池
     * @param mixed $resource
     */
    public function put($resource);
}
