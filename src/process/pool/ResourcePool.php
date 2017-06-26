<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/24
 * Time: 上午9:31
 */

namespace inhere\library\process\pool;

use inhere\library\StdObject;

/**
 * Class ResourcePool - 资源池
 *
 * ```php
 *
 * $rpl = new ResourcePool([
 *  'maxSize' => 50,
 * ]);
 *
 * $rpl->setResourceCreator(function () {
 *  return new \Db(...);
 * );
 *
 * $rpl->setResourceCreator(function ($db) {
 *   $db->close();
 * );
 *
 * // use
 * $db = $rpl->get();
 *
 * $rows = $db->query('select * from table limit 10');
 *
 * $rpl->put($db);
 *
 * ```
 *
 * @package inhere\library\process
 */
class ResourcePool extends StdObject implements PoolInterface
{
    // 1 预准备 2 动态创建
    const MODE_READY = 1;
    const MODE_DYNAMIC = 2;

    /**
     * (空闲)可用的资源队列
     * @var \SplQueue
     */
    private $pool;

    /**
     * @var int
     */
    protected $mode = self::MODE_DYNAMIC;

    /**
     * default 30 seconds
     * @var int
     */
    public $expireTime = 30;

    /**
     * 初始化池大小
     * @var int
     */
    public $initSize = 0;

    /**
     * @var int
     */
    public $fixSize = 10;

    /**
     * 扩大的增量(当资源不够时，一次增加资源的数量)
     * @var int
     */
    public $stepSize = 1;

    /**
     * 最大资源大小
     * @var int
     */
    public $maxSize = 100;

    /**
     * @var callable
     */
    private $resourceCreator;

    /**
     * @var callable
     */
    private $resourceReleaser;

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
    private $resourceNumber = 0;

    /**
     * 使用中的资源数
     * @var int
     */
    private $occupiedNumber = 0;

    /**
     * init
     */
    public function init()
    {
        $this->pool = new \SplQueue();
        $this->pool->setIteratorMode(\SplQueue::IT_MODE_DELETE);

        // fix mixSize
        if ($this->initSize > $this->maxSize) {
            $this->maxSize = $this->initSize;
        }

        // 预准备资源
        $this->prepare($this->initSize);
    }

    /**
     * {@inheritdoc}
     */
    public function get($blocking = false)
    {
        // 还有可用资源
        if (!$this->pool->isEmpty()) {
            $this->occupiedNumber++;

            return $this->pool->pop();
        }

        $created = $this->resourceNumber;

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
    public function del($resource)
    {
        $this->pool->push($resource);
    }

    /**
     * (创建)准备资源
     * @param int $size
     * @return int
     */
    public function prepare($size)
    {
        if ($size <= 0) {
            return 0;
        }

        $cb = $this->resourceCreator;

        for ($i = 0; $i < $size; $i++) {
            $this->resourceNumber++;
            $this->pool->push($cb());
        }

        return $size;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->pool->count();
    }

    /**
     * @return callable
     */
    public function getResourceCreator(): callable
    {
        return $this->resourceCreator;
    }

    /**
     * @param callable $resourceCreator
     * @return $this
     */
    public function setResourceCreator(callable $resourceCreator)
    {
        $this->resourceCreator = $resourceCreator;

        return $this;
    }

    /**
     * @return callable
     */
    public function getResourceReleaser(): callable
    {
        return $this->resourceReleaser;
    }

    /**
     * @param callable $resourceReleaser
     * @return $this
     */
    public function setResourceReleaser(callable $resourceReleaser)
    {
        $this->resourceReleaser = $resourceReleaser;

        return $this;
    }

    /**
     * @return int
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * @param int $mode
     */
    public function setMode(int $mode)
    {
        $this->mode = $mode;
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
    public function getFixSize(): int
    {
        return $this->fixSize;
    }

    /**
     * @param int $fixSize
     */
    public function setFixSize(int $fixSize)
    {
        $this->fixSize = $fixSize;
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
     */
    public function setMaxSize(int $maxSize)
    {
        if ($maxSize < 1) {
            throw new \InvalidArgumentException('The resource pool max size cannot lt 1');
        }

        $this->maxSize = $maxSize;
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
     * release pool
     */
    public function __destruct()
    {
        if ($cb = $this->resourceReleaser) {
            foreach ($this->pool as $obj) {
                $cb($obj);
            }
        }

        $this->pool = null;
    }
}
