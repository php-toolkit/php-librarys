<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-04-27
 * Time: 16:06
 */

namespace inhere\library\gearman;

/**
 * Class Job
 * @package inhere\library\gearman
 */
abstract class Job implements JobInterface
{
    /**
     * @var mixed
     */
    protected $context;

    /**
     * do the job
     * @param string $workload
     * @param WorkerManager $manger
     * @param \GearmanJob $job
     * @return mixed
     */
    abstract public function run($workload, WorkerManager $manger, \GearmanJob $job);

    /**
     * @param mixed $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }
}