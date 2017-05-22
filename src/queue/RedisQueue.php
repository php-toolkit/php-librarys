<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/21
 * Time: 上午1:45
 */

namespace inhere\library\queue;

/**
 * Class RedisQueue
 * @package inhere\library\queue
 */
class RedisQueue extends BaseQueue
{
    /**
     * redis
     * @var \Redis
     */
    private $redis;

    /**
     * {@inheritDoc}
     */
    public function push($data)
    {
        try {
            return $this->redis->lPush($this->id, serialize($data));
        } catch (\Exception $e) {
            $this->errCode = $e->getCode() > 0 ? $e->getCode() : -__LINE__;
            $this->errMsg = $e->getMessage();
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function pop()
    {
        try {
            $data = $this->redis->rPop($this->id);

            return unserialize($data);
        } catch (\Exception $e) {
            $this->errCode = $e->getCode() > 0 ? $e->getCode() : -__LINE__;
            $this->errMsg = $e->getMessage();
        }

        return null;
    }

    /**
     * @return \Redis
     */
    public function getRedis(): \Redis
    {
        return $this->redis;
    }

    /**
     * @param \Redis $redis
     */
    public function setRedis(\Redis $redis)
    {
        $this->redis = $redis;
    }
}
