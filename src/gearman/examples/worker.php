<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 下午9:26
 */

use \inhere\library\gearman\WorkerManager;

require dirname(__DIR__) . '/../../../../autoload.php';

$config = [
    'log_level' => WorkerManager::LOG_DEBUG,
];

$worker = new WorkerManager($config);

$worker->addHandler('reverse_string', function ($string, \GearmanJob $job)
{
    echo "Received job: " . $job->handle() . "\n";
    echo "Workload: $string\n";

    $result = strrev($string);

    echo "Result: $result\n";

    return $result;
});

$worker->start();