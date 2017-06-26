<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 15-1-18
 * Time: 10:35
 * Used: 存放单个服务的相关信息
 * Service.php
 */

namespace inhere\library\di;

/**
 * Class Service
 * @package inhere\library\di
 */
final class Service
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @var mixed
     */
    private $instance;

    /**
     * @var array
     */
    private $arguments;

    /**
     * $locked 锁定服务，一旦注册就不允许重载 | 重置reset (锁定并不等于共享，仍可以设置是否共享)
     * @var bool
     */
    private $locked;

    /**
     * 共享的服务，设置后若没有重载(置)，获取的总是第一次激活的服务实例
     * @var bool
     */
    private $shared;

    /**
     * Service constructor.
     * @param $callback
     * @param array $arguments
     * @param bool $shared
     * @param bool $locked
     */
    public function __construct($callback, array $arguments = [], $shared = false, $locked = false)
    {
        $this->arguments = $arguments;

        $this->shared = (bool)$shared;
        $this->locked = (bool)$locked;

        $this->setCallback($callback);
    }

    /**
     * @param Container $container
     * @param bool $forceNew
     * @return mixed|null
     */
    public function get(Container $container, $forceNew = false)
    {
        if ($this->shared) {
            if (!$this->instance || $forceNew) {
                $this->instance = call_user_func($this->callback, $container);
            }

            // 激活后就锁定，不允许再覆盖设置服务
            $this->locked = true;

            return $this->instance;
        }

        // 总是获取新的实例，就不再存储
        return call_user_func($this->callback, $container);
    }

    /**
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param $callback
     */
    public function setCallback($callback)
    {
        if (!method_exists($callback, '__invoke')) {
            $this->instance = $callback;

            $callback = function () use ($callback) {
                return $callback;
            };
        }

        $this->callback = $callback;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * 给服务设置参数，在获取服务实例前
     * @param array $params 设置参数
     * @throws \InvalidArgumentException
     */
    public function setArguments(array $params)
    {
        $this->arguments = $params;
    }

    /**
     * @return mixed
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @param bool $locked
     */
    public function setLocked($locked = true)
    {
        $this->locked = (bool)$locked;
    }

    /**
     * @return bool
     */
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * @param bool $shared
     */
    public function setShared($shared = true)
    {
        $this->shared = (bool)$shared;
    }
}
