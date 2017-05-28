<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/27
 * Time: 下午9:16
 */

$parentPid = posix_getpid();
echo "parent progress pid:{$parentPid}\n";

$childList = array();

// 创建共享内存,创建信号量,定义共享key
$shm_id = ftok(__FILE__, 'm');
$sem_id = ftok(__FILE__, 's');

$shareMemory = shm_attach($shm_id);
$signal = sem_get($sem_id);

const SHARE_KEY = 1;

// 生产者
function producer()
{
    global $shareMemory;
    global $signal;

    $pid = posix_getpid();
    $repeatNum = 5;

    for ($i = 1; $i <= $repeatNum; $i++) {
        // 获得信号量
        sem_acquire($signal);

        if (shm_has_var($shareMemory, SHARE_KEY)) {
            // 有值,加一
            $count = shm_get_var($shareMemory, SHARE_KEY);
            $count++;
            shm_put_var($shareMemory, SHARE_KEY, $count);
            echo "({$pid}) count: {$count}\n";
        } else {
            // 无值,初始化
            shm_put_var($shareMemory, SHARE_KEY, 0);
            echo "({$pid}) count: 0\n";
        }

        // 用完释放
        sem_release($signal);

        $rand = rand(1, 3);
        sleep($rand);
    }
}

function createProgress($callback)
{
    $pid = pcntl_fork();
    if ($pid == -1) {
        // 创建失败
        exit("fork progress error!\n");
    } else if ($pid == 0) {
        // 子进程执行程序
        $pid = posix_getpid();
        $callback();
        exit("({$pid})child progress end!\n");
    } else {
        // 父进程执行程序
        return $pid;
    }
}

// 3个写进程
for ($i = 0; $i < 3; $i++) {
    $pid = createProgress('producer');
    $childList[$pid] = 1;
    echo "create producer child progress: {$pid} \n";
}

// 等待所有子进程结束
while (!empty($childList)) {
    $childPid = pcntl_wait($status);
    if ($childPid > 0) {
        unset($childList[$childPid]);
    }
}

// 释放共享内存与信号量
shm_remove($shareMemory);
sem_remove($signal);

echo "({$parentPid})main progress end!\n";
