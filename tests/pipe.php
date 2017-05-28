<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/27
 * Time: 下午9:47
 */
// 定义管道路径,与创建管道
$pipe_path = __DIR__ . '/test.pipe';

if (!file_exists($pipe_path)) {
    if (!posix_mkfifo($pipe_path, 0664)) {
        exit("create pipe error!\n");
    }
}

$pid = pcntl_fork();

if ($pid == 0) {
    // 子进程,向管道写数据
    $file = fopen($pipe_path, 'w');

    while (true) {
        fwrite($file, "send: hello world\n");
        $rand = rand(1, 3);
        sleep($rand);
    }

    exit('child end!');
} else {
    // 父进程,从管道读数据
    $file = fopen($pipe_path, 'r');

    while (true) {
        $rel = fread($file, 20);
        echo "received: {$rel}\n";
        $rand = rand(1, 2);
        sleep($rand);
    }
}
