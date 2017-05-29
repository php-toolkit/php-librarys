<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/21
 * Time: 上午1:45
 */

namespace inhere\library\queue;

/**
 * Class PhpQueue
 * @package inhere\library\queue
 */
class PhpQueue extends BaseQueue
{
    /**
     * @var \SplQueue[]
     */
    private $queues = [];

    /**
     * PhpQueue constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        // ...
        $this->id = $config['id'] ?? 'php';

        // create queues
        foreach ($this->getPriorities() as $level) {
            $this->queues[$level] = new \SplQueue();
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function doPush($data, $priority = self::PRIORITY_NORM)
    {
        if (isset($this->queues[$priority])) {
            $this->queues[$priority]->enqueue($data); // can use push().
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function doPop()
    {
        $data = null;

        foreach ($this->queues as $queue) {
            // can use shift().
            if ($data = $queue->dequeue()) {
                break;
            }
        }

        return $data;
    }

    /**
     * close
     */
    public function close()
    {
        parent::close();

        foreach ($this->getPriorities() as $p) {
            $this->queues[$p] = null;
        }
    }
}
