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
class Service
{
    /**
     * @var callable
     */
    protected $callback;

    protected $instance;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * $locked 锁定服务，一旦注册就不允许重载 | 重置reset (锁定并不等于共享，仍可以设置是否共享)
     * @var array
     */
    protected $locked = false;

    /**
     * 共享的服务，设置后若没有重载(置)，获取的总是第一次激活的服务实例
     * @var array
     */
    protected $shared = false;

    public function __construct($callback, array $arguments=[], $shared=false, $locked=false)
    {
        $this->setCallback($callback);
        $this->arguments = $arguments;
        $this->shared    = $shared;
        $this->locked    = $locked;
    }

    /**
     * @param Container $container
     * @param bool $getNew
     * @return mixed|null
     */
    public function get( Container $container, $getNew=false )
    {
        if ($this->shared) {
            if (!$this->instance || $getNew) {
                $this->instance = call_user_func($this->callback, $container);
            }

            return $this->instance;
        }

        // 总是获取新的实例，就不在存储到 $this->instance
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
     * @return $this
     */
    public function setCallback($callback)
    {
        if (!is_callable($callback)) {
            $callback = function () use ($callback) {
                return $callback;
            };
        }

        // 第二次进入时...
        if($this->locked) {
            return $this;
        }

        $this->callback = $callback;

        return $this;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * 给服务设置参数，在获取服务实例前
     * @param array $params 设置参数
     * 通常无key值，按默认顺序传入服务回调中
     * 当 $bindType = REPLACE_PARAM
     * [
     * // pos => args
     *  0 => arg1,
     *  1 => arg2,
     *  3 => arg3,
     * ]
     * @param int $bindType 绑定参数方式
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setArguments( array $params, $bindType=Container::OVERLOAD_PARAM)
    {
        if ( ! $this->arguments ) {
            $this->arguments = (array) $params;
        } else {
            $oldParams = $this->arguments;

            switch (trim($bindType)) {
                case Container::REPLACE_PARAM:
                    $nowParams = array_replace((array) $oldParams, (array) $params);
                    break;
                case Container::APPEND_PARAM: {
                    $nowParams = (array) $oldParams;

                    foreach ($params as $param) {
                        $nowParams[] = $param;
                    }

                    break;
                }
                default:
                    $nowParams = (array) $params;
                    break;
            }

            $this->arguments = (array) $nowParams;
        }

        return $this;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function shared($value=true)
    {
        $this->shared = (bool)$value;

        return $this;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function lock($value = true)
    {
        $this->locked = (bool)$value;

        return $this;
    }

    public function isLocked()
    {
        return $this->locked;
    }

    public function isShared()
    {
        return $this->shared;
    }
}
