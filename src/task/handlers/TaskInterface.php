<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/29
 * Time: 上午10:25
 */

namespace inhere\library\task\handlers;

use inhere\library\task\server\TaskWrapper;

/**
 * Interface TaskInterface
 * @package inhere\library\task\handlers
 */
interface TaskInterface
{
    /**
     * do the task
     * @param string $workload
     * @param TaskWrapper $task
     * @return mixed
     */
    public function run($workload, TaskWrapper $task);
}
