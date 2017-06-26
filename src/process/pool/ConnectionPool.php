<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/25
 * Time: 上午12:54
 */

namespace inhere\library\process\pool;

/**
 * Class ConnectionPool
 * @package inhere\library\process\pool
 */
class ConnectionPool
{
    /**
     * 使用中的资源队列
     * @var \SplQueue
     */
    private $occupiedPool;

    /**
     * 空闲中的资源队列
     * @var \SplQueue
     */
    private $freePool;

}
