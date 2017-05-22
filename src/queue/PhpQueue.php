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
     * @var \SplQueue
     */
    private $queue;

    /**
     * {@inheritDoc}
     */
    public function push($data)
    {
        $this->queue->push($data);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function pop()
    {
        return $this->queue->pop();
    }
}
