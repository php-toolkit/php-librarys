<?php

namespace inhere\library\task\handlers;

use inhere\library\task\server\TaskWrapper;

/**
 * Class Task
 * @package inhere\library\task\handlers
 */
abstract class Task implements TaskInterface
{
    /**
     * the task id
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var mixed
     */
    protected $context;

    /**
     * Task constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
        // some init ...
    }

    /**
     * do the job
     * @param string $workload
     * @param TaskWrapper $task
     * @return mixed
     */
    public function run($workload, TaskWrapper $task)
    {
        $result = false;
        $this->id = $task->getId();
        $this->name = $task->getName();

        try {
            if (false !== $this->beforeRun($workload, $task)) {
                $result = $this->doRun($workload, $task);

                $this->afterRun($result);
            }
        } catch (\Exception $e) {
            $this->onException($e);
        }

        return $result;
    }

    /**
     * beforeRun
     * @param $workload
     * @param TaskWrapper $task
     */
    protected function beforeRun($workload, TaskWrapper $task)
    {}

    /**
     * doRun
     * @param $workload
     * @param TaskWrapper $task
     * @return mixed
     */
    abstract protected function doRun($workload, TaskWrapper $task);

    /**
     * afterRun
     * @param mixed $result
     */
    protected function afterRun($result)
    {
    }

    /**
     * @param \Exception $e
     */
    protected function onException(\Exception $e)
    {
        // error
    }

    /**
     * @param mixed $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }
}

