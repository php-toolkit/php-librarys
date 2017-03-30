<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/2/12
 * Use : 监听器队列存储管理类
 * @From : [windwalker framework](https://github.com/ventoviro/windwalker)
 * File: ListenersQueue.php
 */

namespace inhere\library\event;

/**
 * Class ListenersQueue
 * @package inhere\library\event
 */
class ListenersQueue implements \IteratorAggregate, \Countable
{
    /**
     * 对象存储器
     * @var \SplObjectStorage
     */
    protected $store;

    /**
     * 优先级队列
     * @var \SplPriorityQueue
     */
    protected $queue;

    /**
     * 计数器
     * 设定最大值为 PHP_INT_MAX == 300
     * @var int
     */
    private $counter = PHP_INT_MAX;

    public function __construct()
    {
        $this->store = new \SplObjectStorage();
        $this->queue = new \SplPriorityQueue();
    }

    /**
     * 添加一个监听器, 增加了添加 callback(string|array)
     * @param $listener \Closure|callback 监听器
     * @param $priority integer 优先级
     * @return $this
     */
    public function add($listener, $priority)
    {
        if ( !$this->has($listener) ) {
            // Compute the internal priority as an array.计算内部优先级为一个数组。
            $priority = array($priority, $this->counter--);

            // a Callback(string|array)
            if ( !is_object($listener) && is_callable($listener) ) {
                $callback = $listener;
                $listener = new \StdClass;
                $listener->callback = $callback;
            }

            $this->store->attach($listener, $priority);
            $this->queue->insert($listener, $priority);
        }

        return $this;
    }

    /**
     * 删除一个监听器
     * @param $listener
     * @return $this
     */
    public function remove($listener)
    {
        if ($this->has($listener)) {
            $this->store->detach($listener);
            $this->store->rewind();

            $queue = new \SplPriorityQueue();

            foreach ($this->store as $listener) {
                // 优先级
                $priority = $this->store->getInfo();
                $queue->insert($listener, $priority);
            }

            $this->queue = $queue;
        }

        return $this;
    }

    /**
     * Get the priority of the given listener. 得到指定监听器的优先级
     * @param   mixed  $listener  The listener.
     * @param   mixed  $default   The default value to return if the listener doesn't exist.
     * @return  mixed  The listener priority if it exists, null otherwise.
     */
    public function getPriority($listener, $default = null)
    {
        if ($this->store->contains($listener)) {
            return $this->store[$listener][0];
        }

        return $default;
    }

    /**
     * getPriority() alias method
     * @param $listener
     * @param null $default
     * @return mixed
     */
    public function getLevel($listener, $default = null)
    {
        return $this->getPriority($listener, $default);
    }

    /**
     * Get all listeners contained in this queue, sorted according to their priority.
     * @return  object[]  An array of listeners.
     */
    public function getAll()
    {
        $listeners = array();

        // Get a clone of the queue.
        $queue = $this->getIterator();

        foreach ($queue as $listener) {
            $listeners[] = $listener;
        }

        unset($queue);

        return $listeners;
    }

    public function has($listener)
    {
        return $this->store->contains($listener);
    }

    public function exists($listener)
    {
        return $this->store->contains($listener);
    }


    /**
     * Get the inner queue with its cursor on top of the heap.
     * @return  \SplPriorityQueue  The inner queue.
     */
    public function getIterator()
    {
        // SplPriorityQueue queue is a heap.
        $queue = clone $this->queue;

        if (!$queue->isEmpty()) {
            $queue->top();
        }

        return $queue;
    }

    public function count()
    {
        return count($this->queue);
    }

}// end class ListenersQueue
