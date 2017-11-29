<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/30
 * Time: 下午12:04
 */
$parentPid = posix_getpid();
echo "parent progress pid:{$parentPid}\n";

$childList = array();
// 创建消息队列,以及定义消息类型(类似于数据库中的库)
$id = ftok(__FILE__, 'm');
$msgQueue = msg_get_queue($id);
const MSG_TYPE = 2;

// 生产者
function producer()
{
    global $msgQueue;
    $pid = posix_getpid();
    $repeatNum = 5;

    for ($i = 1; $i <= $repeatNum; $i++) {
        $str = "({$pid})progress create! {$i}";

        msg_send($msgQueue, MSG_TYPE, $str);

        $rand = rand(1, 3);

        sleep($rand);
    }
}

// 消费者
function consumer()
{
    global $msgQueue;

    $pid = posix_getpid();
    $repeatNum = 8;

    for ($i = 1; $i <= $repeatNum; $i++) {
        $rel = msg_receive($msgQueue, MSG_TYPE, $msgType, 1024, $message);

        echo "{$message} | msgType $msgType | consumer({$pid}) destroy \n";

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

// 2个读进程
for ($i = 0; $i < 2; $i++) {
    $pid = createProgress('consumer');
    $childList[$pid] = 1;

    echo "create consumer child progress: {$pid} \n";
}

// 等待所有子进程结束
while (!empty($childList)) {
    $childPid = pcntl_wait($status);

    if ($childPid > 0) {
        unset($childList[$childPid]);
    }
}
echo "({$parentPid})main progress end!\n";
