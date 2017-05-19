<?php

namespace inhere\library\process;

use inhere\library\traits\TraitSimpleConfig;

/**
 * Class TaskManager
 * @package inhere\library\process
 */
class TaskManager
{
    use TraitSimpleConfig;
    use ProcessControlTrait;

    /**
     * @var MsgQueue
     */
    protected $queue;

    /**
     * @var int
     */
    protected $id = 0;

    /**
     * The PID of the parent(master) process, when running in the forked helper,worker.
     * @var int
     */
    protected $masterPid = 0;

    /**
     * @var bool
     */
    protected $isMaster = false;

    /**
     * @var bool
     */
    protected $isWorker = false;

    /**
     * taskHandler
     * @var callable
     */
    protected $taskHandler;

    /**
     * workers
     * @var array
     * [
     *  pid => [
     *      'jobs' => [],
     *      'start_time' => int
     *  ]
     * ]
     */
    protected $workers = [];

    /**
     * @var array
     */
    protected $config = [
        'daemon' => false,
        'server' => '0.0.0.0:9999',
        'workerNum' => 2,
        'bufferSize' => 8192,
        'queue' => [
            'msgType' => 2,
            'bufferSize' => 8192,
        ],
    ];

    /**
     * TaskManager constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * run
     */
    public function run()
    {
        if ($this->config['daemon']) {
            $this->runAsDaemon();
        }

        $this->isMaster = true;
        $this->pid = getmypid();

        $this->queue = new MsgQueue($this->config['queue']);

        echo "create queue msgId = {$this->queue->getMsgId()}\n";

        $this->workers = $this->startWorkers($this->config['workerNum']);

        $this->startMaster();

        exit("manager exited.\n");
    }

    /**
     * @param int $workerNum
     * @return array
     */
    public function startWorkers($workerNum)
    {
        $workers = [];

        for ($i = 0; $i < $workerNum; $i++) {
            $pid = pcntl_fork();

            // 主进程
            if ($pid > 0) {
                $workers[$pid] = [
                    'id' => $i,
                    'start_time' => time(),
                ];
                echo "create worker $i.pid = $pid\n";
                continue;
            } elseif ($pid == 0) { // 子进程
                $this->runWorker($i);
                exit("worker #$i exited.\n");
            } else {
                echo "fork fail\n";
                exit;
            }
        }

        return $workers;
    }

    /**
     * @param int $id
     */
    public function runWorker($id)
    {
        $this->isMaster = false;
        $this->isWorker = true;
        $this->masterPid = $this->pid;
        $this->pid = getmypid();

        while (true) {
            if($pkt = $this->queue->pop()) {
                echo "[Worker $id] ". $pkt;
            } else {
                echo "ERROR: queue errNo={$this->queue->getErrCode()}\n";
            }

            usleep(50000);
        }
    }

    /**
     * @param $data
     */
    public function handleTask($data)
    {
        if ($cb = $this->taskHandler) {
            call_user_func($cb, $data);
        }
    }

    /**
     * startMaster
     */
    public function startMaster()
    {
        $bind = "udp://{$this->config['server']}";

        // 建立一个UDP服务器接收请求
        $socket = stream_socket_server($bind, $errNo, $errStr, STREAM_SERVER_BIND);

        if (!$socket) {
            die("$errStr ($errNo)");
        }

        stream_set_blocking($socket, 1);

        echo "stream_socket_server bind=$bind\n";

        while (true) {
            $peer = '';
            $pkt = stream_socket_recvfrom($socket, $this->config['bufferSize'], 0, $peer);

            if ($pkt == false) {
                echo "udp error\n";
            }

            // 如果队列满了，这里会阻塞
            if ($this->queue->push($pkt)) {
                $out = "OK\n";
            } else {
                $out = "ER\n";
            }

            stream_socket_sendto($socket, $out, 0, $peer);
        }
    }

    /**
     * @param callable $cb
     */
    public function setTaskHandler(callable $cb)
    {
        $this->taskHandler = $cb;
    }
}
