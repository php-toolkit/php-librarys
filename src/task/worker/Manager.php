<?php

namespace inhere\library\task\worker;

use inhere\library\helpers\CliHelper;
use inhere\library\helpers\PhpHelper;
use inhere\library\process\ProcessLogger;
use inhere\library\queue\SysVQueue;
use inhere\library\task\Base;
use inhere\library\task\ProcessControlTrait;

/**
 * Class Manager - task workers manager
 * @package inhere\library\task\worker
 */
class Manager extends Base
{
    use OptionAndConfigTrait;
    use ProcessControlTrait;
    use ProcessManageTrait;


    /**
     * some MIN values
     */
    const MIN_LIFETIME = 1800;
    const MIN_RUN_TASKS = 200;
    const MIN_TASK_TIMEOUT = 10;
    const MIN_WATCH_INTERVAL = 120;

    /**
     * some default values
     */
    const WORKER_NUM = 1;
    const TASK_TIMEOUT = 300;
    const MAX_LIFETIME = 3600;
    const MAX_RUN_TASKS = 3000;
    const RESTART_SPLAY = 600;
    const WATCH_INTERVAL = 300;

    /**
     * process exit status code.
     */
    const CODE_MANUAL_KILLED = -500;
    const CODE_NORMAL_EXITED = 0;
    const CODE_CONNECT_ERROR = 170;
    const CODE_NO_HANDLERS = 171;
    const CODE_UNKNOWN_ERROR = 180;

    /**
     * taskHandler
     * @var callable
     */
    protected $taskHandler;

    /**
     * @var array
     */
    protected $config = [
        'daemon' => false,
        'name' => '',

        'server' => '0.0.0.0:9999',
        'serverType' => 'udp',

        'workerNum' => 2,
        'bufferSize' => 8192,

        // the master process pid save file
        'pidFile' => 'task-mgr.pid',

        'queue' => [
            'msgType' => 2,
            'bufferSize' => 8192,
        ],

        // log
        'logger' => [
            'level' => ProcessLogger::WORKER_INFO,
            // 'day' 'hour', if is empty, not split.
            'splitType' => ProcessLogger::SPLIT_DAY,
            // log file
            'file' => 'task-worker-mgr.log',
            // will write log by `syslog()`
            'toSyslog' => false,
        ],
    ];

    protected $workerNum = 2;

    protected $maxLifetime = 3600;

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
        $this->beforeRun();

        $this->isMaster = true;
        $this->stopWork = false;
        $this->stat['startTime'] = time();
        $this->setProcessTitle(sprintf("php-twm: master process%s (%s)", $this->getShowName(), getcwd() . '/' . $this->fullScript));

        $this->prepare();


        $this->beforeStart();

        $this->workers = $this->startWorkers($this->config['workerNum']);

        $this->startManager();

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

        $this->stdout("Create queue msgId = {$this->queue->getId()}");

        $this->queue = new SysVQueue($this->config['queue']);

        // save Pid File
        $this->savePidFile();

        $this->config['logger']['toConsole'] = $this->config['daemon'];

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
     * showVersion
     */
    protected function showVersion()
    {
        printf("Gearman worker manager script tool. Version %s\n", CliHelper::color(self::VERSION, 'green'));

        $this->quit();
    }

    /**
     * Shows the scripts help info with optional error message
     * @param string $msg
     * @param int $code The exit code
     */
    protected function showHelp($msg = '', $code = 0)
    {
        $usage = CliHelper::color('USAGE:', 'brown');
        $commands = CliHelper::color('COMMANDS:', 'brown');
        $sOptions = CliHelper::color('SPECIAL OPTIONS:', 'brown');
        $pOptions = CliHelper::color('PUBLIC OPTIONS:', 'brown');
        $version = CliHelper::color(self::VERSION, 'green');
        $script = $this->getScript();

        if ($msg) {
            $code = $code ?: self::CODE_UNKNOWN_ERROR;
            echo CliHelper::color('ERROR:', 'light_red') . "\n  " . wordwrap($msg, 108, "\n  ") . "\n\n";
        }

        echo <<<EOF
Gearman worker manager(gwm) script tool. Version $version(lite)

$usage
  $script {COMMAND} -c CONFIG [-v LEVEL] [-l LOG_FILE] [-d] [-w] [-p PID_FILE]
  $script -h
  $script -D

$commands
  start             Start gearman worker manager(default)
  stop              Stop running's gearman worker manager
  restart           Restart running's gearman worker manager
  reload            Reload all running workers of the manager
  status            Get gearman worker manager runtime status

$sOptions
  start/restart
    -d,--daemon        Daemon, detach and run in the background
       --tasks         Only register the assigned tasks, multi task name separated by commas(',')
       --no-test       Not add test handler, when task name prefix is 'test'.(eg: test_task)

  status
    --cmd COMMAND      Send command when connect to the task server. allow:status,workers.(default:status)
    --watch-status     Watch status command, will auto refresh status.

$pOptions
  -c CONFIG          Load a custom worker manager configuration file
  -s HOST[:PORT]     Connect to server HOST and optional PORT, multi server separated by commas(',')

  -n NUMBER          Start NUMBER workers that do all tasks

  -l LOG_FILE        Log output to LOG_FILE or use keyword 'syslog' for syslog support
  -p PID_FILE        File to write master process ID out to

  -r NUMBER          Maximum run task iterations per worker
  -x SECONDS         Maximum seconds for a worker to live
  -t SECONDS         Number of seconds gearmand server should wait for a worker to complete work before timing out

  -v [LEVEL]         Increase verbosity level by one. eg: -v vv | -v vvv

  -h,--help          Shows this help information
  -V,--version       Display the version of the manager
  -D,--dump [all]    Parse the command line and config file then dump it to the screen and exit.\n\n
EOF;
        $this->quit($code);
    }

    /**
     * show Status
     * @param string $command
     * @param bool $watch
     */
    protected function showStatus($command, $watch = false)
    {
        $this->stdout("un-completed! $command, $watch", true, 0);
    }

    /**
     * dumpInfo
     * @param bool $allInfo
     */
    protected function dumpInfo($allInfo = false)
    {
        if ($allInfo) {
            $this->stdout("There are all information of the manager:\n" . PhpHelper::printVar($this));
        } else {
            $this->stdout("There are configure information:\n" . PhpHelper::printVar($this->config));
        }

        $this->quit();
    }

    /**
     * {@inheritDoc}
     */
    public function installSignals($isMaster = true)
    {
        // ignore
        pcntl_signal(SIGPIPE, SIG_IGN, false);

        if ($isMaster) {
            $this->log('Registering signal handlers for master(parent) process', ProcessLogger::DEBUG);

            pcntl_signal(SIGTERM, [$this, 'signalHandler'], false);
            pcntl_signal(SIGINT, [$this, 'signalHandler'], false);
            pcntl_signal(SIGUSR1, [$this, 'signalHandler'], false);
            pcntl_signal(SIGUSR2, [$this, 'signalHandler'], false);

            pcntl_signal(SIGHUP, [$this, 'signalHandler'], false);

            pcntl_signal(SIGCHLD, [$this, 'signalHandler'], false);

        } else {
            $this->log("Registering signal handlers for current worker process", ProcessLogger::DEBUG);

            pcntl_signal(SIGTERM, [$this, 'signalHandler'], false);
        }
    }

    /**
     * Handles signals
     * @param int $sigNo
     */
    public function signalHandler($sigNo)
    {
        if ($this->isMaster) {
            static $stopCount = 0;

            switch ($sigNo) {
                case SIGINT: // Ctrl + C
                case SIGTERM:
                    $sigText = $sigNo === SIGINT ? 'SIGINT' : 'SIGTERM';
                    $this->log("Shutting down(signal:$sigText)...", ProcessLogger::PROC_INFO);
                    $this->stopWork();
                    $stopCount++;

                    if ($stopCount < 5) {
                        $this->stopWorkers();
                    } else {
                        $this->log('Stop workers failed by(signal:SIGTERM), force kill workers by(signal:SIGKILL)', ProcessLogger::PROC_INFO);
                        $this->stopWorkers(SIGKILL);
                    }
                    break;
                case SIGHUP:
                    $this->log('Restarting workers(signal:SIGHUP)', ProcessLogger::PROC_INFO);
                    $this->stopWorkers();
                    break;
                case SIGUSR1: // reload workers and reload handlers
//                    $this->log('Reloading workers and handlers(signal:SIGUSR1)', ProcessLogger::PROC_INFO);
//                    $this->stopWork();
//                    $this->start();
                    break;
                case SIGUSR2:
                    break;
                default:
                    // handle all other signals
            }

        } else {
            $this->stopWork();
            $this->log("Received 'stopWork' signal(signal:SIGTERM), will be exiting.", ProcessLogger::PROC_INFO);
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
