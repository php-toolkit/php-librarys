<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/1/26
 * Use : 管理事件的监听、调度、触发
 * File: Dispatcher.php
 * @From : [windwalker framework](https://github.com/ventoviro/windwalker)
 */

namespace inhere\librarys\event;

use inhere\librarys\StdBase;

class Dispatcher extends StdBase implements InterfaceDispatcher
{
    /**
     * 1.事件存储
     * @var array EventInterface[]
     * [
     *     'event name' => (object)EventInterface -- event description
     * ]
     */
    protected $_events = [];

    /**
     * 2.监听器存储
     * @var ListenersQueue[] array
     */
    protected $_listeners = [];

    /**
     * @explain :
     *
     * 1. 事件名(eventName) 和 相关参数存储到 实现 EventInterface 的实例(e.g. Event)中
     * 2. 事件名(eventName) 和 对应的 监听器实例(e.g. XxListener)或者匿名函数(\Closure) 存储到 ListenersQueue
     *  - 监听器实例(XxListener) 中有对应 事件名的(eventName) 方法
     *  - 触发事件时 会调用监听器中的 对应事件方法，参数为 对应的事件实例(Event)[可在其中得到对应的相关参数...]
     *  - 一个事件可以添加多个 监听器，并可以设置优先级；同样一个监听器也能添加到 多个 事件中
     *
     */

/////////////////////////////////////////////// Event ///////////////////////////////////////////////

    /**
     * 添加一个不存在的事件
     * @param Event|string $event | event name
     * @param array $args
     * @return $this
     */
    public function addEvent($event, array $args = [])
    {
        if ( is_string($event) ) {
            $event = new Event(trim($event), $args );
        }

        /**
         * @var $event Event
         */
        if ( ($event instanceof InterfaceEvent) && !isset($this->_events[$event->name])) {
            $this->_events[$event->name] = $event;
        }

        return $this;
    }

    /**
     * 设定一个事件处理
     * @param $event InterfaceEvent
     * @param array $args
     * @return $this
     */
    public function setEvent($event, array $args = [])
    {
        if ( is_string($event) ) {
            $event = new Event(trim($event), $args );
        }

        /**
         * @var $event Event
         */
        if ( $event instanceof InterfaceEvent ) {
            $this->_events[$event->name] = $event;
        }

        return $this;
    }

    public function getEvent($name, $default=null)
    {
        return isset($this->_events[$name]) ? $this->_events[$name] : $default;
    }


    public function removeEvent($event)
    {
        if ($event instanceof InterfaceEvent) {
            $event = $event->getName();
        }

        if (isset($this->_events[$event])) {
            unset($this->_events[$event]);
        }

        return $this;
    }

    /**
     * @param $event
     * @return bool
     */
    public function hasEvent($event)
    {
        if ($event instanceof InterfaceEvent) {
            $event = $event->getName();
        }

        return isset($this->_events[$event]);
    }

    /**
     * @param $event
     * @return bool
     */
    public function existsEvent($event)
    {
        return $this->hasEvent($event);
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        return $this->_events;
    }

    /**
     * @param $events
     */
    public function setEvents($events)
    {
        foreach ($events as $key => $event) {
            $this->setEvent($event);
        }
    }

    /**
     * @return int
     */
    public function countEvents()
    {
        return count($this->_events);
    }


///////////////////////////////////////////// Listener /////////////////////////////////////////////

    /**
     * 添加监听器 并关联到 某一个(多个)事件
     * @param object|\Closure|callback $listener 监听器
     * @param array|string|int $definition 事件名，优先级设置
     * @return $this
     * @throws \InvalidArgumentException
     * @example
     *     $definition = [
     *        'event name' => int level
     *     ]
     * OR
     *     $definition = 'event name'
     * OR
     *     $definition = 1 // The priority of the listener 监听器的优先级
     */
    public function addListener($listener, $definition = [])
    {
        if (!is_object($listener)) {
            throw new \InvalidArgumentException('The given listener must is an object or a Closure.');
        }

        $defaultLevel = ListenerLevel::NORMAL;

        if (is_numeric($definition)) {
            $defaultLevel = (int)$definition;
            $definition = null;
        } elseif (is_string($definition)) { // 仅是个 事件名称
            $definition = [ $definition => $defaultLevel];
        } elseif ( $definition instanceof Event) { // 仅是个 事件对象,取出名称
            $definition = [ $definition->name => $defaultLevel];
        }

        // 1. is a Closure or callback(String|Array)
        if ( is_callable($listener) ) {
            if (!$definition) {
                throw new \InvalidArgumentException('请设置要将监听器关联到什么事件。');
            }

            // 循环: 将 监听器 关联到 各个事件
            foreach ($definition as $eventName => $level) {
                $eventName = trim($eventName);

                if ( !isset($this->_listeners[$eventName]) ) {
                    $this->_listeners[$eventName] = new ListenersQueue;
                }

                $this->_listeners[$eventName]->add($listener, (int)$level);
            }

            return $this;
        }

        // 2. is a Object.

        // 得到要绑定的监听器中所有方法名
        $methods = get_class_methods($listener);
        $eventNames = [];

        /**
         * @var $definition array 取出所有方法列表中 需要关联的事件(方法)名
         */
        if ($definition) {
            $eventNames = array_intersect($methods, array_keys($definition));
        }

        // 循环: 将 监听器 关联到 各个事件
        foreach ($eventNames as $name) {
            if ( !isset($this->_listeners[$name]) ) {
                $this->_listeners[$name] = new ListenersQueue;
            }

            $level = isset($definition[$name]) ? $definition[$name]: $defaultLevel;

            $this->_listeners[$name]->add($listener, (int)$level);
        }

        return $this;
    }

    /**
     * 是否存在 对事件的 监听队列
     * @param  object|string  $event
     * @return boolean
     */
    public function hasListenerQueue($event)
    {
        if ($event instanceof InterfaceEvent) {
            $event = $event->getName();
        }

        return isset($this->_listeners[$event]);
    }

    /**
     * @see self::hasListenerQueue() alias method
     * @param  object|string  $event
     * @return boolean
     */
    public function hasListeners($event)
    {
        return $this->hasListenerQueue($event);
    }

    /**
     * 是否存在(对事件的)监听器
     * @param $listener
     * @param  object|string $event
     * @return bool
     */
    public function hasListener($listener, $event = null)
    {
        if ($event) {
            if ($event instanceof InterfaceEvent) {
                $event = $event->getName();
            }

            // 存在对这个事件的监听队列
            if (isset($this->_listeners[$event])) {
                return $this->_listeners[$event]->has($listener);
            }

        } else {
            foreach ($this->_listeners as $queue) {
                if ( $queue->has($listener) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 获取事件的一个监听器的优先级别
     * @param $listener
     * @param  string|object $event
     * @return int|null
     */
    public function getListenerLevel($listener, $event)
    {
        if ($event instanceof InterfaceEvent) {
            $event = $event->getName();
        }

        // 存在对这个事件的监听队列
        if (isset($this->_listeners[$event])) {
            return $this->_listeners[$event]->getLevel($listener);
        }

        return null;
    }

    /**
     * 获取事件的所有监听器
     * @param  string|object $event
     * @return array ListenersQueue[]
     */
    public function getListeners($event)
    {
        if ($event instanceof InterfaceEvent) {
            $event = $event->getName();
        }

        // 存在对这个事件的监听队列
        if (isset($this->_listeners[$event])) {
            return $this->_listeners[$event]->getAll();
        }

        return [];
    }

    /**
     * 统计获取事件的监听器数量
     * @param  string|object $event
     * @return int
     */
    public function countListeners($event)
    {
        if ($event instanceof InterfaceEvent) {
            $event = $event->getName();
        }

        return isset($this->_listeners[$event]) ? count($this->_listeners[$event]) : 0;
    }

    /**
     * 移除对某个事件的监听
     * @param $listener
     * @param null|string|object $event
     * $event 为空时，移除监听者队列中所有名为 $listener 的监听者
     * 否则，则移除对事件 $event 的监听者
     * @return $this
     */
    public function removeListener($listener, $event=null)
    {
        if ($event) {
            if ($event instanceof InterfaceEvent) {
                $event = $event->getName();
            }

            // 存在对这个事件的监听队列
            if (isset($this->_listeners[$event])) {
                $this->_listeners[$event]->remove($listener);
            }

        } else {
            foreach ($this->_listeners as $queue) {
                /**
                 * @var $queue ListenersQueue
                 */
                $queue->remove($listener);
            }
        }

        return $this;
    }

    /**
     * 清除(对事件的)所有监听器
     * @param  null|string|object $event
     * @return self
     */
    public function clearListeners($event=null)
    {
        if ($event) {
            if ($event instanceof InterfaceEvent) {
                $event = $event->getName();
            }

            // 存在对这个事件的监听队列
            if (isset($this->_listeners[$event])) {
                unset($this->_listeners[$event]);
            }

        } else {
            $this->_listeners = [];
        }

        return $this;
    }

    public function removeListeners($event=null)
    {
        return $this->clearListeners($event);
    }

    /**
     * @param string|InterfaceEvent $event 事件名或者事件实例
     * @param array $args
     * @return bool|mixed
     */
    public function triggerEvent($event, array $args=[])
    {
        if ( !($event instanceof InterfaceEvent) ) {
            if ( isset($this->_events[$event]) ) {
                $event = $this->_events[$event];
            } else {
                $event = new Event($event);
            }
        }

        $arguments = array_merge($event->getArguments(), $args);
        $event->setArguments($arguments);

        if ( isset($this->_listeners[$event->name]) ) {

            // 循环调用监听器，处理事件
            foreach ($this->_listeners[$event->name] as $listener) {
                if ($event->isStopped()) {
                    break;
                }

                /**
                 * @var object|\Closure|Callback $listener
                 */
                if ( $listener instanceof \StdClass ) {
                    call_user_func($listener->callback, $event);
                } elseif ( is_callable($listener) ) {
                    call_user_func($listener, $event);
                } else {
                    call_user_func([$listener, $event->name], $event);
                }
            }
        }

        return $event;
    }

    public function trigger($event, array $args=[])
    {
        return $this->triggerEvent($event, $args);
    }

}// end class Dispatcher