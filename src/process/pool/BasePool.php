<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/2
 * Time: 下午1:43
 */

namespace inhere\library\process\pool;

use inhere\library\StdObject;
use inhere\queue\PhpQueue;
use inhere\queue\QueueInterface;

/**
 * Class BasePool
 * @package inhere\library\process\pool
 */
abstract class BasePool extends StdObject implements PoolInterface
{
    /**
     * (空闲)可用的资源队列
     * @var QueueInterface
     */
    private $pool;

    /**
     * pool Driver class
     * @var string
     */
    private $poolDriver = PhpQueue::class;

    /**
     * @var array
     */
    public $driverOptions = [];

    /**
     * default 30 seconds
     * @var int
     */
    public $expireTime = 30;

    /**
     * 初始化池大小
     * @var int
     */
    private $initSize = 0;

    /**
     * 扩大的增量(当资源不够时，一次增加资源的数量)
     * @var int
     */
    private $stepSize = 1;

    /**
     * 池最大资源数大小
     * @var int
     */
    private $maxSize = 100;

    /**
     * @var bool
     */
    private $blocking = false;

    /**
     * the blocking timeout(ms) when get resource
     * @var int
     */
    private $timeout = 1000;

    /**
     * 已创建的资源数
     * @var int
     */
    private $createdNumber = 0;

    /**
     * 使用中的资源数
     * @var int
     */
    private $occupiedNumber = 0;

    /**
     * @var bool
     */
    //private $locking = false;

    /**
     * init
     */
    protected function init()
    {
        $class = $this->poolDriver;
        $this->pool = new $class($this->driverOptions);

        // fix mixSize
        if ($this->initSize > $this->maxSize) {
            $this->maxSize = $this->initSize;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($blocking = false)
    {
        // 还有可用资源
        if ($res = $this->pool->pop()) {
            $this->occupiedNumber++;

            return $res;
        }

        $created = $this->createdNumber;

        // 资源池已满，并且无可用资源
        // $blocking = true 等待空闲连接.
        // $blocking = false 返回 null
        if ($created === $this->maxSize && $created === $this->occupiedNumber) {
            if (!$blocking) {
                return null;
            }

            // if blocking
            $timer = 0;
            $interval = 50;
            $uSleep = $interval * 1000;

            while ($timer <= $this->timeout) {
                // 等到了可用的空闲资源
                if ($res = $this->get()) {
                    return $res;
                }

                $timer += $interval;
                usleep($uSleep);
            }
        }

        // 未满，无可用资源 创建新的资源
        $this->prepare($this->stepSize);
        $this->occupiedNumber++;

        return $this->pool->pop();
    }

    /**
     * {@inheritdoc}
     */
    public function put($resource)
    {
        if ($this->occupiedNumber > 0) {
            $this->occupiedNumber--;
        }

        $this->pool->push($resource);
    }

    /**
     * call resource(will auto get and put resource)
     * @param \Closure $closure
     * @return mixed
     */
    public function call(\Closure $closure)
    {
        $resource = $this->get();
        $result = $closure($resource);
        $this->put($resource);

        return $result;
    }

    /**
     * @param $resource
     */
//    public function del($resource)
//    {
//        $this->pool->push($resource);
//    }

    /**
     * (创建)准备资源
     * @param int $size
     * @return int
     */
    abstract public function prepare($size);

    /**
     * @return int
     */
    public function count()
    {
        return $this->pool->count();
    }

    /**
     * release pool
     */
    public function clear()
    {
        $this->pool = null;
    }

    /**
     * release pool
     */
    public function __destruct()
    {
        $this->clear();
    }

    /**
     * @return int
     */
    public function getExpireTime(): int
    {
        return $this->expireTime;
    }

    /**
     * @param int $expireTime
     */
    public function setExpireTime(int $expireTime)
    {
        $this->expireTime = $expireTime;
    }

    /**
     * @return int
     */
    public function getInitSize(): int
    {
        return $this->initSize;
    }

    /**
     * @param int $initSize
     */
    public function setInitSize(int $initSize)
    {
        $this->initSize = $initSize < 0 ? 0 : $initSize;
    }

    /**
     * @return int
     */
    public function getStepSize(): int
    {
        return $this->stepSize;
    }

    /**
     * @param int $stepSize
     */
    public function setStepSize(int $stepSize)
    {
        $this->stepSize = $stepSize < 1 ? 1 : $stepSize;
    }

    /**
     * @return int
     */
    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * @param int $maxSize
     * @throws \InvalidArgumentException
     */
    public function setMaxSize(int $maxSize)
    {
        if ($maxSize < 1) {
            throw new \InvalidArgumentException('The resource pool max size cannot lt 1');
        }

        $this->maxSize = $maxSize;
    }

    /**
     * @return QueueInterface
     */
    public function getPool(): QueueInterface
    {
        return $this->pool;
    }

    /**
     * @param QueueInterface $pool
     */
    public function setPool(QueueInterface $pool)
    {
        $this->pool = $pool;
    }

    /**
     * increment CreatedNumber
     */
    protected function incrementCreatedNumber()
    {
        $this->createdNumber++;
    }

    /**
     * @return int
     */
    public function getCreatedNumber(): int
    {
        return $this->createdNumber;
    }

    /**
     * @return int
     */
    public function getOccupiedNumber(): int
    {
        return $this->occupiedNumber;
    }

    /**
     * @return bool
     */
    public function hasFree()
    {
        return $this->getFreeNumber() > 0;
    }

    /**
     * @return int
     */
    public function getFreeNumber()
    {
        return $this->createdNumber - $this->occupiedNumber;
    }

    /**
     * @return bool
     */
    public function isBlocking(): bool
    {
        return $this->blocking;
    }

    /**
     * @param bool $blocking
     */
    public function setBlocking($blocking)
    {
        $this->blocking = (bool)$blocking;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return string
     */
    public function getPoolDriver(): string
    {
        return $this->poolDriver;
    }

    /**
     * @param string $poolDriver
     */
    public function setPoolDriver(string $poolDriver)
    {
        $this->poolDriver = $poolDriver;
    }
}
