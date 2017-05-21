<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/21
 * Time: 上午1:52
 */

namespace inhere\library\task;

use inhere\library\queue\QueueInterface;
use inhere\library\traits\TraitSimpleConfig;

/**
 * Class Base
 * @package inhere\library\task
 */
abstract class Base
{
    use TraitSimpleConfig;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var QueueInterface
     */
    protected $queue;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * run
     */
    abstract public function run();

    /**
     * Install Signals
     */
    abstract public function installSignals();

    /**
     * Handle signals
     * @param int $sigNo
     */
    abstract public function signalHandler($sigNo);

    /**
     * @return QueueInterface
     */
    public function getQueue(): QueueInterface
    {
        return $this->queue;
    }

    /**
     * @param QueueInterface $queue
     */
    public function setQueue(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getShowName()
    {
        return $this->name ? "({$this->name})" : '';
    }

    /**
     * @return bool
     */
    public function isDaemon()
    {
        return $this->config['daemon'];
    }
}