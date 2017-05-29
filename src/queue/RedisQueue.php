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
     * RedisQueue constructor.
     * @param \Redis|null $redis
     * @param array $config
     */
    public function __construct(\Redis $redis = null, array $config = [])
    {
        $this->redis = $redis;

        $this->id = $config['id'] ?? 'redis';
    }

    /**
     * {@inheritDoc}
     */
    protected function doPush($data, $priority = self::PRIORITY_NORM)
    {
        $data = serialize($data);
        $channels = array_values($this->getChannels());

        if (isset($channels[$priority])) {
            return $this->redis->lPush($channels[$priority], $data);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function doPop()
    {
        $data = null;

        foreach ($this->getChannels() as $channel) {
            if ($data = $this->redis->rPop($channel)) {
                $data = unserialize($data);
                break;
            }
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
