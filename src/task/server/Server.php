<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/20
 * Time: 下午11:04
 */

namespace inhere\library\task\server;

use inhere\library\queue\MsgQueue;
use inhere\library\queue\QueueInterface;
use inhere\library\task\ProcessLogInterface;
use inhere\library\task\ProcessLogTrait;
use inhere\library\task\ProcessControlTrait;
use inhere\library\traits\TraitSimpleConfig;

/**
 * Class Server - task server
 *
 * @package inhere\library\task\server
 */
class Server implements ProcessLogInterface
{
    use OptionAndConfigTrait;
    use ProcessLogTrait;
    use ProcessControlTrait;
    use TraitSimpleConfig;


    /**
     * @var string
     */
    protected $name;

    /**
     * @var QueueInterface
     */
    protected $queue;

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
            'msgType' => 2,
            'bufferSize' => 8192,
        ],

        // log
        'logLevel' => 4,
        // 'day' 'hour', if is empty, not split.
        'logSplit' => 'day',
        // will write log by `syslog()`
        'logSyslog' => false,
        'logFile' => 'task-mgr.log',
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

        $this->queue = new MsgQueue($this->config['queue']);
        $this->stdout("Create queue msgId = {$this->queue->getMsgId()}");

        // save Pid File
        $this->savePidFile();

        // open Log File
        $this->openLogFile();

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
        if ($this->logFileHandle) {
            fclose($this->logFileHandle);

            $this->logFileHandle = null;
        }

        $this->log("Manager stopped\n", self::LOG_PROC_INFO);
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
            $this->log("$errStr ($errNo)", self::LOG_ERROR);
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
                $this->log("udp error", self::LOG_ERROR);
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
                $this->log("Shutting down(signal:$sigText)...", self::LOG_PROC_INFO);
                $this->stopWork();
                break;
            default:
                // handle all other signals
        }
    }

    /**
     * @return QueueInterface
     */
    public function getQueue(): QueueInterface
    {
        return $this->queue;
    }

    /**
     * @param QueueInterface $queue
     */
    public function setQueue(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getShowName()
    {
        return $this->name ? "({$this->name})" : '';
    }

    /**
     * @return bool
     */
    public function isDaemon()
    {
        return $this->config['daemon'];
    }
}