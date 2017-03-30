<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/27
 * Time: 下午12:34
 * reference windwalker https://github.com/ventoviro/windwalker
 * 用于存储一个事件
 */

namespace inhere\library\event;

use inhere\library\StdBase;

/**
 * Class Event
 * @package inhere\library\event
 */
class Event extends StdBase implements InterfaceEvent, \ArrayAccess, \Serializable,\Countable
{
    /**
     * @var string 当前的事件名称
     */
    protected $name;

    /**
     * 参数
     * @var array
     */
    protected $arguments = [];

    /**
     * 停止事件关联的监听器队列的执行
     * @var boolean
     */
    protected $stopped = false;

    /**
     * @param $name
     * @param array $arguments
     */
    public function __construct($name, array $arguments = [])
    {
        $this->name      = trim($name);
        $this->arguments = $arguments;
    }

    /**
     * Get the event name.
     * @return  string  The event name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Method to set property name
     * @param   string $name
     * @return  static  Return self to support chaining.
     */
    public function setName($name)
    {
        $this->name = trim($name);

        return $this;
    }

    /**
     * set all arguments
     * @param array $arguments
     * @return $this
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * get all arguments
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * clear all arguments
     */
    public function clearArguments()
    {
        $old = $this->arguments;
        $this->arguments = [];

        return $old;
    }

    /**
     * add a argument
     * @param $name
     * @param $value
     * @return $this
     */
    public function addArgument($name, $value)
    {
        if (!isset($this->arguments[$name])) {
            $this->setArgument($name, $value);
        }

        return $this;
    }

    /**
     * set a argument
     * @param $name
     * @param $value
     * @throws  \InvalidArgumentException  If the argument name is null.
     * @return $this
     */
    public function setArgument($name, $value)
    {
        if (is_null($name)) {
            throw new \InvalidArgumentException('The argument name cannot be null.');
        }

        $this->arguments[$name] = $value;

        return $this;
    }

    /**
     * @param $name
     * @param null $default
     * @return null
     */
    public function getArgument($name, $default=null)
    {
        return isset($this->arguments[$name]) ? $this->arguments[$name] : $default;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasArgument($name)
    {
        return isset($this->arguments[$name]);
    }

    /**
     * @param $name
     */
    public function removeArgument($name)
    {
        if ( isset($this->arguments[$name])) {
            unset($this->arguments[$name]);
        }
    }

    /**
     * @return bool
     */
    public function isStopped()
    {
        return (bool) $this->stopped;
    }

    /**
     * @return bool
     */
    public function getStopped()
    {
        return (bool) $this->stopped;
    }

    /**
     * @param $value
     */
    public function setStopped($value)
    {
        $this->stopped = (bool) $value;
    }

    /**
     * stopped the event
     */
    public function stopped()
    {
        $this->stopped = true;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array($this->name, $this->arguments, $this->stopped));
    }

    /**
     * Unserialize the event.
     * @param   string  $serialized  The serialized event.
     * @return  void
     */
    public function unserialize($serialized)
    {
        list($this->name, $this->arguments, $this->stopped) = unserialize($serialized);
    }

    /**
     * Tell if the given event argument exists.
     * @param   string  $name  The argument name.
     * @return  boolean  True if it exists, false otherwise.
     */
    public function offsetExists($name)
    {
        return $this->hasArgument($name);
    }

    /**
     * Get an event argument value.
     * @param   string  $name  The argument name.
     * @return  mixed  The argument value or null if not existing.
     */
    public function offsetGet($name)
    {
        return $this->getArgument($name);
    }

    /**
     * Set the value of an event argument.
     * @param   string  $name   The argument name.
     * @param   mixed   $value  The argument value.
     * @return  void
     */
    public function offsetSet($name, $value)
    {
        $this->setArgument($name, $value);
    }

    /**
     * Remove an event argument.
     * @param   string  $name  The argument name.
     * @return  void
     */
    public function offsetUnset($name)
    {
        $this->removeArgument($name);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->arguments);
    }

    public function __get($name)
    {
        if ( isset($this->arguments[$name]) ) {
            return $this->arguments[$name];
        }

        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if ( isset($this->arguments[$name]) ) {
            $this->setArgument($name, $value);
        } else {
            parent::__set($name, $value);
        }
    }
}
