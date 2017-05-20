<?php

namespace inhere\library\process;

use inhere\library\helpers\CliHelper;
use inhere\library\helpers\PhpHelper;
use inhere\library\traits\TraitSimpleConfig;

/**
 * Class TaskManager
 * @package inhere\library\process
 */
class TaskManager implements ProcessLogInterface
{
    use TraitSimpleConfig;
    use OptionAndConfigTrait;
    use ProcessControlTrait;
    use ProcessLogTrait;
    use ProcessManageTrait;
    
    const VERSION = '0.1.0';

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
     * @var string
     */
    protected $name;

    /**
     * @var MsgQueue
     */
    protected $queue;

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
        'workerNum' => 2,
        'bufferSize' => 8192,

        // the master process pid save file
        'pidFile' => 'task-mgr.pid',

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
        $this->stat['start_time'] = time();
        $this->setProcessTitle(sprintf("php-gwm: master process%s (%s)", $this->getShowName(), getcwd() . '/' . $this->fullScript));

        if ($this->config['daemon']) {
            $this->runAsDaemon();
        }

        $this->queue = new MsgQueue($this->config['queue']);

        $this->stdout("Create queue msgId = {$this->queue->getMsgId()}");

        $this->beforeStart();

        $this->startManager();

        $this->runTaskServer();

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
        $this->stdout("un-completed!", true, 0);
    }

    /**
     * dumpInfo
     * @param bool $allInfo
     */
    protected function dumpInfo($allInfo = false)
    {
        if ($allInfo) {
            $this->stdout("There are all information of the manager:\n" . PhpHelper::printR($this));
        } else {
            $this->stdout("There are configure information:\n" . PhpHelper::printR($this->config));
        }

        $this->quit();
    }


    /**
     * @param callable $cb
     */
    public function setTaskHandler(callable $cb)
    {
        $this->taskHandler = $cb;
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
