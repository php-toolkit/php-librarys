<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/29
 * Time: 上午10:12
 */

namespace inhere\library\task\worker;

/**
 * Class TaskWrapper - Task Data Wrapper
 * @package inhere\library\task\server
 */
final class TaskWrapper
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $workload;

    /**
     * @var callable
     */
    private $handler;

    /**
     * TaskWrapper constructor.
     * @param string $id
     * @param string $name
     * @param string $workload
     * @param callable $handler
     */
    public function __construct($id, $name, $workload, callable $handler)
    {
        $this->id = $id;
        $this->name = $name;
        $this->workload = $workload;
        $this->handler = $handler;
    }

    /**
     * @param int $index
     * @return string
     */
    protected function genId($index)
    {
        return sprintf('T:%s:%s', gethostname(), $index);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getWorkload(): string
    {
        return $this->workload;
    }

    /**
     * @param string $workload
     */
    public function setWorkload(string $workload)
    {
        $this->workload = $workload;
    }

    /**
     * @return callable
     */
    public function getHandler(): callable
    {
        return $this->handler;
    }

    /**
     * @param callable $handler
     */
    public function setHandler(callable $handler)
    {
        $this->handler = $handler;
    }

}
