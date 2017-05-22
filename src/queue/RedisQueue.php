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
    public function push($data, $priority = self::PRIORITY_NORM)
    {
        try {
            $data = serialize($data);
            $channels = array_values($this->getChannels());

            if (isset($channels[$priority])) {
                return $this->redis->lPush($channels[$priority], $data);
            }

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
        $data = null;

        try {
            foreach ($this->getChannels() as $channel) {
                if ($data = $this->redis->rPop($channel)) {
                    $data = unserialize($data);
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->errCode = $e->getCode() > 0 ? $e->getCode() : -__LINE__;
            $this->errMsg = $e->getMessage();
        }

        return $data;
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
