<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/20
 * Time: 下午11:04
 */

namespace inhere\library\task\server;

use inhere\library\process\ProcessLogger;
use inhere\library\queue\SysVQueue;
use inhere\library\queue\QueueInterface;
use inhere\library\task\Base;

/**
 * Class Server - task server
 *
 * @package inhere\library\task\server
 */
class Server extends Base
{
    use OptionAndConfigTrait;

    /**
     * @var array
     */
    protected $config = [
        'name' => '',
        'daemon' => false,

        'server'    => '0.0.0.0:9998',
        'serverType' => 'udp',

        // the master process pid save file
        'pidFile' => 'task-svr.pid',

        'queue' => [
            'driver' => 'sysv',
            'msgType' => 2,
            'bufferSize' => 8192,
        ],

        // log
        'logger' => [
            'level' => ProcessLogger::WORKER_INFO,
            // 'day' 'hour', if is empty, not split.
            'splitType' => ProcessLogger::SPLIT_DAY,
            // log file
            'file' => 'task-server.log',
            // will write log by `syslog()`
            'toSyslog' => false,
        ],
    ];

    /**
     * TaskManager constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);

        $this->init();
    }

    /**
     * init
     */
    protected function init()
    {
        $this->parseCommandAndConfig();

        // checkEnvironment
        $this->checkEnvironment();

        $this->dispatchCommand($this->command);
    }

    /**
     * run
     */
    public function run()
    {
        $this->pid = getmypid();

        $this->beforeRun();

        $this->stopWork = false;
        $this->stat['startTime'] = time();
        $this->setProcessTitle(sprintf("php-ts: master process%s (%s)", $this->getShowName(), getcwd() . '/' . $this->fullScript));

        $this->prepare();

        $this->beforeStart();

        $this->startTaskServer();

        $this->afterRun();
    }

    protected function beforeRun()
    {
        // ... ...
    }

    /**
     * prepare start
     */
    protected function prepare()
    {
        $this->pid = getmypid();

        // If we want run as daemon, fork here and exit
        if ($this->config['daemon']) {
            $this->stdout('Run the worker manager in the background');
            $this->runAsDaemon();
        }

        $this->queue = new SysVQueue($this->config['queue']);
        $this->stdout("Create queue msgId = {$this->queue->getId()}");

        // save Pid File
        $this->savePidFile();

        // open Log File
        $this->lgr = new ProcessLogger($this->config['logger']);

//        if ($username = $this->config['user']) {
//            $this->changeScriptOwner($username, $this->config['group']);
//        }
    }

    protected function beforeStart()
    {
        // ... ...
    }


    /**
     * afterRun
     */
    protected function afterRun()
    {
        // delPidFile
        $this->delPidFile();

        // close logFileHandle

        $this->log("Manager stopped\n", ProcessLogger::PROC_INFO);
        $this->quit();
    }

    ////////////////////////////////////////////////////////////////
    /// task server
    ////////////////////////////////////////////////////////////////

    /**
     * 建立一个UDP服务器接收请求
     * runTaskServer
     * @return int
     */
    public function startTaskServer()
    {
        $bind = "udp://{$this->config['server']}";
        $socket = stream_socket_server($bind, $errNo, $errStr, STREAM_SERVER_BIND);

        if (!$socket) {
            $this->log("$errStr ($errNo)", ProcessLogger::ERROR);
            $this->stopWork();
            return -50;
        }

        stream_set_blocking($socket, 1);

        $this->log("start server by stream_socket_server(), bind=$bind");

        while (!$this->stopWork) {
            $this->dispatchSignals();

            $peer = '';
            $pkt = stream_socket_recvfrom($socket, $this->config['bufferSize'], 0, $peer);

            if ($pkt == false) {
                $this->log("udp error", ProcessLogger::ERROR);
            }

            // 如果队列满了，这里会阻塞
            $ret = $this->queue->push($pkt) ? "OK\n" : "ER\n";

            stream_socket_sendto($socket, $ret, 0, $peer);
            usleep(50000);
        }

        return 0;
    }

    public function installSignals()
    {
        // ignore
        pcntl_signal(SIGPIPE, SIG_IGN, false);
        pcntl_signal(SIGTERM, [$this, 'signalHandler'], false);
    }

    /**
     * Handles signals
     * @param int $sigNo
     */
    public function signalHandler($sigNo)
    {
        switch ($sigNo) {
            case SIGINT: // Ctrl + C
            case SIGTERM:
                $sigText = $sigNo === SIGINT ? 'SIGINT' : 'SIGTERM';
                $this->log("Shutting down(signal:$sigText)...", ProcessLogger::PROC_INFO);
                $this->stopWork();
                break;
            default:
                // handle all other signals
        }
    }

}
