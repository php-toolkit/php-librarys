<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/21
 * Time: 上午1:26
 */

namespace inhere\library\queue;

/**
 * Interface QueueInterface
 * @package inhere\library\queue
 */
interface QueueInterface
{
    /**
     * push data
     * @param mixed $data
     * @return bool
     */
    public function push($data);

    /**
     * pop data
     * @return mixed
     */
    public function pop();

    public function getMsgId();

    public function getErrCode();
}