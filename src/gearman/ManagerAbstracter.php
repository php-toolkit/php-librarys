<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 下午9:30
 */

namespace inhere\library\gearman;

/**
 * Class ManagerAbstracter
 * @package inhere\library\gearman
 */
abstract class ManagerAbstracter
{
    const VERSION = '0.1.0';

    /**
     * Events list
     */
    const EVENT_BEFORE_PUSH = 'beforePush';
    const EVENT_AFTER_PUSH = 'afterPush';
    const EVENT_BEFORE_WORK = 'beforeWork';
    const EVENT_AFTER_WORK = 'afterWork';
    const EVENT_AFTER_ERROR = 'afterError';

    /**
     * handler types
     */
    const HANDLER_FUNC = 'func';
    const HANDLER_CLOSURE = 'closure';
    const HANDLER_JOB = 'job';

    /**
     * Log levels can be enabled from the command line with -v, -vv, -vvv
     */
    const LOG_EMERG  = -8;
    const LOG_ERROR  = -6;
    const LOG_WARN   = -4;
    const LOG_NOTICE = -2;
    const LOG_INFO   = 0;
    const LOG_PROC_INFO   = 2;
    const LOG_WORKER_INFO = 4;
    const LOG_DEBUG = 6;
    const LOG_CRAZY = 8;

    /**
     * Logging levels
     * @var array $levels Logging levels
     */
    protected static $levels = array(
        self::LOG_EMERG     => 'EMERGENCY',
        self::LOG_ERROR     => 'ERROR',
        self::LOG_WARN      => 'WARNING',
        self::LOG_INFO      => 'INFO',
        self::LOG_PROC_INFO   => 'PROC_INFO',
        self::LOG_WORKER_INFO => 'WORKER_INFO',
        self::LOG_DEBUG  => 'DEBUG',
        self::LOG_CRAZY  => 'CRAZY',
    );

    const DO_ALL = 'all';

    const MIN_HANDLE = 10;

    /**
     * @var string
     */
    protected $scriptName;

    /**
     * Verbosity level for the running script. Set via -v option
     * @var int
     */
    protected $verbose = 0;

    /**
     * The worker id
     * @var int
     */
    protected $id = 0;

    /**
     * Holds the resource for the log file
     * @var resource
     */
    protected $logFileHandle;

    ///////// process control //////////

    /**
     * @var bool
     */
    protected $isParent = true;

    /**
     * @var bool
     */
    protected $daemon = true;

    /**
     * The PID of the running process. Set for parent and child processes
     */
    protected $pid = 0;

    /**
     * @var string
     */
    protected $pidFile;

    /**
     * The PID of the parent process, when running in the forked helper.
     */
    protected $parentPid = 0;

    /**
     * children
     * @var array
     * [
     *  pid => [
     *      'jobs' => [],
     *      'start_time' => int
     *  ]
     * ]
     */
    protected $children = [];

    /**
     * Workers will only live for 1 hour
     * @var integer
     */
    protected $maxLifetime = 3600;

    /**
     * the worker max handle 2000 job. after will restart.
     * @var integer
     */
    protected $maxHandleJob = 2000;

    /**
     * allow multi process of the current environment
     * @var boolean
     */
    protected $multiProcess = true;

    /**
     * @var bool
     */
    private $waitForSignal = false;

    ///////// jobs //////////

    /**
     * Number of workers that do all jobs
     * @var int
     */
    protected $doAllWorkers = 0;

    /**
     * Number of times this worker has run a job
     * @var int
     */
    protected $jobExecCount = 0;

    /**
     * The array of jobs that have workers running
     * @var string[]
     */
    protected $running = [];

    /**
     * There are jobs config
     * @var array
     */
    protected $jobsOpts = [
        // job name => job config
        'reverse_string' => [
            // 至少需要 3 个 worker 去处理这个 job (可能会超过 3 个，会在它和 $doAllWorkers 取最大值), 可以同时做其他的 job
            'worker_num' => 3,
        ],
        'fetch_url' => [
            'worker_num' => 5
        ],
        'sum' => [
            // 需要 5 个 worker 处理这个 job
            'worker_num' => 5,
            // 当设置 dedicated = true, 这些 worker 将专注这一个job
            'dedicated' => true, // true | false
            // job 执行超时时间 秒
            'timeout' => 100,
        ],
    ];

    /**
     * List of job handlers(functions) available for work
     * @var array
     */
    protected $handlers = [
        // job name  => job handler(allow:string,closure,class,object),
        // 'reverse_string' => 'my_reverse_string',
    ];

    ///////// other //////////

    /**
     * Holds the last timestamp of when the code was checked for updates
     * @var int
     */
    protected $lastCheckTime = 0;

    /**
     * When true, workers will stop look for jobs and the parent process will kill off all running children
     * @var boolean
     */
    protected $stopWork = false;

    /**
     * @var bool
     */
    protected $restartWork = false;

    /**
     * @var int
     */
    protected $stopTime = 0;

    /**
     * @var array
     */
    private $_events = [];

    ///////// config //////////

    /**
     * the workers config
     * @var array
     */
    protected $config = [
        'servers' => '127.0.0.1:4730',

        // the jobs config, @see $jobs property
        // 'jobs' => [],

        'conf_file' => '',

        // user and group
        'user'  => '',
        'group' => '',

        'as_daemon' => false,

        'pid_file' => './job_worker.pid',

        // 需要 4 个 worker 处理所有的 job, 随机处理。
        'worker_num' => 4,

        'auto_reload' => 1,

        // job handle timeout seconds
        'timeout' => 300,

        // Workers will only live for 1 hour
        'max_lifetime' => 3600,

        // now, max_lifetime is <= 3600 and <= 4200
        'restart_splay' => 600,

        // max run 2000 job of each worker. after will auto restart. // todo ...
        'max_run_job' => 2000,

        // log
        'log_level'  => 0,
        'log_split'  => 'day', // 'day' 'hour', if is empty, not split
        'log_syslog' => false,
        'log_file'   => './job_worker.log',
    ];

    /**
     * ManagerAbstracter constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->pid = getmypid();
        $this->setConfig($config);
        $this->bootstrap();
    }

    /**
     * bootstrap
     */
    protected function bootstrap()
    {
        // checkEnvironment
        $this->checkEnvironment();

        // parseCliOption
        $this->handleCliCommand();

        // parseCliOption
        $this->parseCliOption();

        // $this->debug("Start gearman worker, connection to the gearman server {$host}:{$port}");
    }

    /**
     * do start run manager
     */
    public function start()
    {
        $this->log("Started with pid {$this->pid}, Current script owner: " . get_current_user(), self::LOG_PROC_INFO);

        $this->startWorkers();

        $this->beginMonitor();

        $this->log('Exiting ... ...');
    }

    protected function handleCliCommand()
    {
        global $argv;
        $tmp = $argv;
        $this->scriptName = array_shift($tmp);

        unset($tmp);

        $command = $this->getCommand(); // e.g 'start'

        $this->checkInputCommand($command);

        $masterPid = $this->getPidFromFile($this->pidFile);
        $masterIsStarted = ($masterPid > 0) && @posix_kill($masterPid, 0);

        // start: do Start Server
        if ($command === 'start') {

            // check master process is running
            if ($masterIsStarted) {
                $this->stdout("The worker manager have been started. (PID:{$masterPid})", true, -__LINE__);
            }

            // run as daemon
            $this->daemon = (bool)$this->cliIn->boolOpt('d', $this->config->get('swoole.daemonize'));

            return $this;
        }

        // check master process
        if (!$masterIsStarted) {
            $this->cliOut->error("The swoole server({$this->name}) is not running.", true, -__LINE__);
        }

        // switch command
        switch ($command) {
            case 'stop':
            case 'restart':
            case 'reload':
                // stop: stop and exit. restart: stop and start
                $this->stop($masterPid, $command === 'stop');
                break;

            case 'status':
                $this->showRuntimeStatus();
                break;

            default:
                $this->stdout("The command [{$command}] is don't supported!");
                $this->showHelpPanel();
                break;
        }

        return $this;

    }

    /**
     * parseCliOption
     * @return mixed
     */
    abstract protected function parseCliOption();

    /**
     * Bootstrap a set of workers and any vars that need to be set
     */
    protected function startWorkers()
    {
        $workersCount = [];
        $jobs = $this->getJobs();

        // If we have "doAllWorkers" workers, start them first do_all workers register all functions
        if (($num = $this->doAllWorkers) > 0) {
            for ($x=0; $x < $num; $x++) {
                $this->startWorker();

                /*
                 * Don't start workers too fast. They can overwhelm the
                 * gearmand server and lead to connection timeouts.
                 */
                usleep(500000);
            }

            foreach ($jobs as $job) {
                if (!$this->getJobOpt($job,'dedicated', false)) {
                    $workersCount[$job] = $num;
                }
            }
        }

        // Next we loop the workers and ensure we have enough running for each worker
        foreach ($this->handlers as $job => $handler) {
            // If we don't have do_all workers, this won't be set, so we need to init it here
            if (!isset($workersCount[$job])) {
                $workersCount[$job] = 0;
            }

            $workerNum = (int)$this->getJobOpt($job,'worker_num', 0);

            while ($workersCount[$job] < $workerNum) {
                $this->startWorker($job);

                $workersCount[$job]++;

                /*
                 * Don't start workers too fast. They can overwhelm the
                 * gearmand server and lead to connection timeouts.
                 */
                usleep(500000);
            }
        }

        // Set the last code check time to now since we just loaded all the code
        $this->lastCheckTime = time();
    }

    /**
     * Begin monitor workers
     *  - will monitoring children process running status
     *
     * @notice run in the parent main process, children process have been exited in the `startWorkers()`
     */
    protected function beginMonitor()
    {
        cli_set_process_title("workers manager");

        // Main processing loop for the parent process
        while (!$this->stopWork || count($this->children)) {
            $status = null;

            // Check for exited children
            $exited = pcntl_wait($status, WNOHANG);

            // We run other children, make sure this is a worker
            if (isset($this->children[$exited])) {
                /*
                 * If they have exited, remove them from the children array
                 * If we are not stopping work, start another in its place
                 */
                if ($exited) {
                    $workerJobs = $this->children[$exited]['jobs'];
                    $code = pcntl_wexitstatus($status);
                    $exitStatus = $code === 0 ? 'exited' : $code;
                    unset($this->children[$exited]);

                    $this->logChildStatus($exited, $workerJobs, $exitStatus);

                    if (!$this->stopWork) {
                        $this->startWorker($workerJobs);
                    }
                }
            }

            if ($this->stopWork && time() - $this->stopTime > 60) {
                $this->log('Children have not exited, killing.', self::LOG_PROC_INFO);
                $this->stopChildren(SIGKILL);
            } else {
                // If any children have been running 150% of max run time, forcibly terminate them
                foreach ($this->children as $pid => $child) {
                    if (!empty($child['start_time']) && time() - $child['start_time'] > $this->maxLifetime * 1.5) {
                        $this->logChildStatus($pid, $child['jobs'], "killed");
                        $this->killProcess($pid, SIGKILL);
                    }
                }
            }

            // php will eat up your cpu if you don't have this
            usleep(10000);
        }
    }

    /**
     * Start a worker do there are assign jobs.
     * If is in the parent, record child info.
     *
     * @param string|array $jobs Jobs for the current worker.
     */
    protected function startWorker($jobs = 'all')
    {
        $timeouts = [];
        $defTimeout = $this->get('timeout', 0);
        $jobs = is_string($jobs) && $jobs === self::DO_ALL ? $this->getJobs() : (array)$jobs;

        foreach ($jobs as $job) {
            $timeouts[$job] = (int)$this->getJobOpt($job, 'timeout', $defTimeout);
        }

        // fork process
        $pid = pcntl_fork();

        switch ($pid) {
            case 0: // at children
                cli_set_process_title("jobs worker");

                $this->pid = getmypid();
                $this->isParent = false;
                $this->registerSignals(false);

                if (count($jobs) > 1) {
                    // shuffle the list to avoid queue preference
                    shuffle($jobs);

                    // sort the shuffled array by priority
                    // uasort($jobs, array($this, 'sort_priority'));
                }

                if (($splay = $this->get('restart_splay')) > 0) {
                    // Since all child threads use the same seed, we need to reseed with the pid so that we get a new "random" number.
                    mt_srand($this->pid);

                    $this->maxLifetime += mt_rand(0, $splay);
                    $this->log("The worker adjusted max run time to {$this->maxLifetime} seconds", self::LOG_DEBUG);
                }

                $this->startDriverWorker($jobs, $timeouts);

                $this->log('Child exiting', self::LOG_WORKER_INFO);
                $this->quit();
                break;

            case -1: // fork failed.
                $this->log("Could not fork children process!");
                $this->stopWork = true;
                $this->stopChildren();
                break;

            default: // at parent
                $this->log("Started child $pid (" . implode(',', $jobs) . ')', self::LOG_PROC_INFO);
                $this->children[$pid] = array(
                    'jobs'       => $jobs,
                    'start_time' => time(),
                );
        }
    }

    /**
     * Starts a worker for the driver
     *
     * @param   array $jobs     List of worker functions to add
     * @param   array $timeouts list of worker timeouts to pass to server
     * @return void
     */
    abstract protected function startDriverWorker(array $jobs, array $timeouts = []);

//////////////////////////////////////////////////////////////////////
/// job handle methods
//////////////////////////////////////////////////////////////////////

    /**
     * add a job handler (alias of the `addHandler`)
     * @param string $name
     * @param callable $handler
     * @param array $opts
     * @return bool
     */
    public function addFunction($name, $handler, array $opts = [])
    {
        return $this->addHandler($name, $handler, $opts);
    }

    /**
     * add a job handler
     * @param string $name      The job name
     * @param callable $handler The job handler
     * @param array $opts The job options. more @see $jobsOpts property.
     * options allow: [
     *  'timeout' => int
     *  'worker_num' => int
     *  'dedicated' => int
     * ]
     * @return bool
     */
    public function addHandler($name, $handler, array $opts = [])
    {
        if ($this->hasJob($name)) {
            $this->log("The job name [$name] has been registered. don't allow repeat add.", self::LOG_WARN);

            return false;
        }

        if (!$handler && (!is_string($handler) || !is_object($handler))) {
            throw new \InvalidArgumentException("The job [$name] handler data type only allow: string,object");
        }

        // get handler type
        if (is_string($handler)) {
            if (function_exists($handler)) {
                $opts['type'] = self::HANDLER_FUNC;
            } elseif (class_exists($handler) && is_subclass_of($handler, JobInterface::class)) {
                $handler = new $handler;
                $opts['type'] = self::HANDLER_JOB;
            } else {
                throw new \InvalidArgumentException(sprintf(
                    "The job [%s] handler [%s] must be is a function or a subclass of the interface %s",
                    $name,
                    $handler,
                    JobInterface::class
                ));
            }
        } elseif ($handler instanceof \Closure) {
            $opts['type'] = self::HANDLER_CLOSURE;
        } elseif ($handler instanceof JobInterface) {
            $opts['type'] = self::HANDLER_JOB;
        } else {
            throw new \InvalidArgumentException(sprintf(
                'The job [%s] handler [%s] must instance of the interface %s',
                $name,
                get_class($handler),
                JobInterface::class
            ));
        }

        // init opts
        $opts = array_merge([
            'timeout'    => 200,
            'worker_num' => 0,
            'dedicated'  => false,
        ], $this->getJobOpts($name), $opts);

        if (!$opts['dedicated']) {
            $minCount = max($this->doAllWorkers, 1);

            if ($opts['worker_num'] > 0) {
                $minCount = max($opts['worker_num'], $this->doAllWorkers);
            }

            $opts['worker_num'] = $minCount;
        }

        $this->setJobOpts($name, $opts);
        $this->handlers[$name] = $handler;

        return true;
    }

    /**
     * Wrapper function handler for all registered functions
     * This allows us to do some nice logging when jobs are started/finished
     * @param mixed $job
     * @return bool
     */
    abstract public function doJob($job);

//////////////////////////////////////////////////////////////////////
/// process control method
//////////////////////////////////////////////////////////////////////

    /**
     * shutdown Manager
     */
    protected function shutdown()
    {
        $this->log('Stopping ... ...', self::LOG_PROC_INFO);

        if ($pid = $this->pid) {
            $this->killProcess($pid, SIGKILL);
            $this->log('Stopped', self::LOG_PROC_INFO);
        } else {
            $this->log('Failed. pid not found, are you sure running?', self::LOG_NOTICE);
        }

        $this->quit();
    }

    /**
     * Do shutdown Manager
     * @param  int     $masterPid Master Pid
     * @param  boolean $quit      Quit, When stop success?
     */
    protected function stop($masterPid, $quit = true)
    {
        $this->log("The manager process(PID: $masterPid) stopping ...");

        // do stop
        // 向主进程发送此信号(SIGTERM)服务器将安全终止；也可在PHP代码中调用`$server->shutdown()` 完成此操作
        $masterPid && posix_kill($masterPid, SIGTERM);

        $timeout = 5;
        $startTime = time();

        // retry stop if not stopped.
        while ( true ) {
            $masterIsStarted = ($masterPid > 0) && @posix_kill($masterPid, 0);

            if (!$masterIsStarted) {
                break;
            }

            // have been timeout
            if ((time() - $startTime) >= $timeout) {
                $this->log("The manager process(PID: $masterPid) failed!", self::LOG_ERROR);
            }

            usleep(10000);
            continue;
        }

        if ($this->pidFile && file_exists($this->pidFile)) {
            unlink($this->pidFile);
        }

        // stop success
        $this->log("The manager process(PID: $masterPid) stopped.");

        $quit && $this->quit();
    }

    /**
     * Stops all running children
     * @param int $signal
     */
    protected function stopChildren($signal = SIGTERM)
    {
        $this->log('Stopping children ... ...', self::LOG_PROC_INFO);

        foreach ($this->children as $pid => $child) {
            $this->log(sprintf("Stopping child $pid (JOBS: %s)", implode(',', $child['jobs'])), self::LOG_PROC_INFO);

            $this->killProcess($pid, $signal);
        }

        $this->log('Children Stopped', self::LOG_PROC_INFO);
    }

    /**
     * Daemon, detach and run in the background
     */
    protected function runAsDaemon()
    {
        if ($this->multiProcess) {
            $this->log("This is not support run in the background of the current environment.");
            return false;
        }

        $pid = pcntl_fork();

        if ($pid > 0) {
            $this->isParent = false;
            $this->quit();
        }

        $this->pid = getmypid();
        posix_setsid();

        return true;
    }

    /**
     * @param $pid
     * @param $signal
     */
    protected function killProcess($pid, $signal)
    {
        if ($this->multiProcess) {
            posix_kill($pid, $signal);
        }
    }

    /**
     * Registers the process signal listeners
     * @param bool $parent
     */
    protected function registerSignals($parent = true)
    {
        if ($parent) {
            $this->log('Registering signals for parent', self::LOG_DEBUG);

            pcntl_signal(SIGTERM, array($this, 'signalHandler'));
            pcntl_signal(SIGINT,  array($this, 'signalHandler'));
            pcntl_signal(SIGUSR1,  array($this, 'signalHandler'));
            pcntl_signal(SIGUSR2,  array($this, 'signalHandler'));
            pcntl_signal(SIGCONT,  array($this, 'signalHandler'));
            pcntl_signal(SIGHUP,  array($this, 'signalHandler'));
        } else {
            $this->log('Registering signals for child', self::LOG_DEBUG);

            if (!$res = pcntl_signal(SIGTERM, array($this, 'signalHandler'))) {
                exit(0);
            }
        }
    }

    /**
     * Handles signals
     * @param int $sigNo
     */
    public function signal($sigNo)
    {
        static $termCount = 0;

        if (!$this->isParent) {
            $this->stopWork = true;
        } else {
            switch ($sigNo) {
                case SIGUSR1:
                    $this->showHelp("No worker files could be found");
                    break;
                case SIGUSR2:
                    $this->showHelp("Error validating worker functions");
                    break;
                case SIGCONT:
                    $this->waitForSignal = false;
                    break;
                case SIGINT:
                case SIGTERM:
                    $this->log('Shutting down...');
                    $this->stopWork = true;
                    $this->stopTime = time();
                    $termCount++;

                    if ($termCount < 5) {
                        $this->stopChildren();
                    } else {
                        $this->stopChildren(SIGKILL);
                    }
                    break;
                case SIGHUP:
                    $this->log("Restarting children", self::LOG_PROC_INFO);
                    $this->openLogFile();
                    $this->stopChildren();
                    break;
                default:
                    // handle all other signals
            }
        }
    }

//////////////////////////////////////////////////////////////////////
/// events method
//////////////////////////////////////////////////////////////////////

    /**
     * register a event callback
     * @param string $name event name
     * @param callable $cb event callback
     * @param bool $replace replace exists's event cb
     * @return $this
     */
    public function on($name, callable $cb, bool $replace = false)
    {
        if ($replace || !isset($this->_events[$name])) {
            $this->_events[$name] = $cb;
        }

        return $this;
    }

    /**
     * @param string $name
     * @param array $args
     * @return mixed
     */
    protected function trigger($name, array $args = [])
    {
        if (!isset($this->_events[$name]) || !($cb = $this->_events[$name])) {
            return null;
        }

        return call_user_func_array($cb, $args);
    }

//////////////////////////////////////////////////////////////////////
/// getter/setter method
//////////////////////////////////////////////////////////////////////

    /**
     * @return string
     */
    public function getScriptName()
    {
        return $this->scriptName;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        if ($config) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return isset($this->config[$name]) ? $this->config[$name] : $default;
    }

    /**
     * @return mixed
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @return string
     */
    public function getPidFile()
    {
        return $this->pidFile;
    }

    /**
     * @param string $pidFile
     * @return int
     */
    protected function getPidFromFile($pidFile)
    {
        if ($pidFile && file_exists($pidFile)) {
            return (int)file_get_contents($pidFile);
        }

        return 0;
    }

    /**
     * get servers info
     * @param bool $toArray
     * @return array|string
     */
    public function getServers($toArray = true)
    {
        $servers = str_replace(' ', '', $this->get('servers', ''));

        if ($toArray) {
            $servers = strpos($servers, ',') ? explode(',', $servers) : [$servers];
        }

        return $servers;
    }

    /**
     * @return array
     */
    public static function getLevels()
    {
        return self::$levels;
    }

    /**
     * @return bool
     */
    public function isDaemon()
    {
        return $this->daemon;
    }

    /**
     * @return int
     */
    public function getVerbose()
    {
        return $this->verbose;
    }

    /**
     * @return bool
     */
    public function isParent()
    {
        return $this->isParent;
    }

    /**
     * @return int
     */
    public function getMaxLifetime()
    {
        return $this->maxLifetime;
    }

    /**
     * @return array
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getHandler($name)
    {
        return isset($this->handlers[$name]) ? $this->handlers[$name] : null;
    }

    /**
     * @return int
     */
    public function getJobCount()
    {
        return count($this->handlers);
    }

    /**
     * @return array
     */
    public function getJobs()
    {
        return array_keys($this->handlers);
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasJob($name)
    {
        return isset($this->handlers[$name]);
    }

    /**
     * @return array
     */
    public function getRunning()
    {
        return $this->running;
    }

    /**
     * @return array
     */
    public function getJobsOpts()
    {
        return $this->jobsOpts;
    }

    /**
     * @param array $optsList
     */
    public function setJobsOpts(array $optsList)
    {
        $this->jobsOpts = $optsList;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasJobOpts($name)
    {
        return isset($this->jobsOpts[$name]);
    }

    /**
     * get a job's options
     * @param string $name
     * @return array
     */
    public function getJobOpts($name)
    {
        return isset($this->jobsOpts[$name]) ? $this->jobsOpts[$name] : [];
    }

    /**
     * set a job's options
     * @param string $name
     * @param array $opts
     */
    public function setJobOpts($name, array $opts)
    {
        if (isset($this->jobsOpts[$name])) {
            $this->jobsOpts[$name] = array_merge($this->jobsOpts[$name], $opts);
        } else {
            $this->jobsOpts[$name] = $opts;
        }
    }

    /**
     * get a job's option value
     * @param string $name  The job name
     * @param string $key   The option key
     * @param mixed  $default
     * @return mixed
     */
    public function getJobOpt($name, $key, $default = null)
    {
        if ($opts = $this->getJobOpts($name)) {
            return isset($opts[$key]) ? $opts[$key] : $default;
        }

        return $default;
    }

//////////////////////////////////////////////////////////////////////
/// some help method
//////////////////////////////////////////////////////////////////////

    /**
     * Shows the scripts help info with optional error message
     * @param string $msg
     */
    abstract protected function showHelp($msg = '');

    /**
     * delete pidFile
     */
    protected function delPidFile()
    {
        if ($this->pidFile && file_exists($this->pidFile) && !unlink($this->pidFile)) {
            $this->log("Could not delete PID file: {$this->pidFile}", self::LOG_WARN);
        }
    }

    /**
     * exit
     * @param int $code
     */
    protected function quit($code = 0)
    {
        exit((int)$code);
    }

    /**
     * @param int   $pid
     * @param array $jobs
     * @param string|int $status
     */
    protected function logChildStatus($pid, $jobs, $status)
    {
        $jobStr = implode(',', $jobs);

        switch ($status) {
            case 'killed':
                $message = "Child $pid has been running too long. Forcibly killing process. ($jobStr)";
                break;
            case 'exited':
                $message = "Child $pid exited cleanly. ($jobStr)";
                break;
            default:
                $message = "Child $pid died unexpectedly with exit code $status. ($jobStr)";
                break;
        }

        $this->log($message, self::LOG_PROC_INFO);
    }

    /**
     * debug log
     * @param  string $msg
     * @param  array  $data
     */
    public function debug($msg, array $data = [])
    {
        $this->log($msg, self::LOG_DEBUG, $data);
    }

    /**
     * Logs data to disk or stdout
     * @param string $msg
     * @param int    $level
     * @param array  $data
     * @return bool
     */
    public function log($msg, $level = self::LOG_INFO, array $data = [])
    {
        if ($level > $this->verbose) {
            return true;
        }

        $data = $data ? json_encode($data) : '';

        if ($this->get('log_syslog')) {
            return $this->sysLog($msg . $data, $level);
        }

        $label = isset(self::$levels[$level]) ? self::$levels[$level] : self::LOG_INFO;

        list(, $ms) = explode('.', sprintf('%f', microtime(true)));
        $ds = date('y-m-d H:i:s') . '.' . str_pad($ms, 6, 0);

        $logString = sprintf('[%s] [%d] [%s] %s %s' . PHP_EOL, $ds, $this->pid, $label, trim($msg), $data);

        // if not in daemon, print log to \STDOUT
        if (!$this->isDaemon()) {
            $this->stdout($logString, false);
        }

        if ($this->logFileHandle) {
            fwrite($this->logFileHandle, $logString);
        }

        return true;
    }

    /**
     * Opens the log file. If already open, closes it first.
     */
    protected function openLogFile()
    {
        if ($logFile = $this->get('log_file')) {
            if ($this->logFileHandle) {
                fclose($this->logFileHandle);
            }

            $this->logFileHandle = @fopen($logFile, 'a');

            if (!$this->logFileHandle) {
                $this->showHelp("Could not open the log file {$logFile}");
            }
        }
    }

    /**
     * Logs data to stdout
     * @param string $logString
     * @param bool $nl
     * @param bool|int $quit
     */
    protected function stdout($logString, $nl = true, $quit = false)
    {
        fwrite(\STDOUT, $logString . ($nl ? PHP_EOL : ''));

        if (($isTrue = true === $quit) || is_int($quit)) {
            $code = $isTrue ? 0 : $quit;
            exit($code);
        }
    }

    /**
     * Logs data to the syslog
     * @param string $msg
     * @param int $level
     * @return bool
     */
    protected function sysLog($msg, $level)
    {
        switch ($level) {
            case self::LOG_EMERG:
                $priority = LOG_EMERG;
                break;
            case self::LOG_ERROR:
                $priority = LOG_ERR;
                break;
            case self::LOG_WARN:
                $priority = LOG_WARNING;
                break;
            case self::LOG_DEBUG:
                $priority = LOG_DEBUG;
                break;
            case self::LOG_INFO:
            case self::LOG_PROC_INFO:
            case self::LOG_WORKER_INFO:
            default:
                $priority = LOG_INFO;
                break;
        }

        if (!$ret = syslog($priority, $msg)) {
            $this->stdout("Unable to write to syslog\n");
        }

        return $ret;
    }

    /**
     * checkEnvironment
     */
    protected function checkEnvironment()
    {
        $this->multiProcess = true;

        if (!function_exists('posix_kill')) {
            $this->multiProcess = false;
            // $this->log("The function 'posix_kill' was not found. Please ensure POSIX functions are installed");
        }

        if (!function_exists('pcntl_fork')) {
            $this->multiProcess = false;
            // $this->log("The function 'pcntl_fork' was not found. Please ensure Process Control functions are installed");
        }

        if (!$this->multiProcess) {
            $this->log("This is not support run multi worker process of the current environment. Require the 'posix,pcntl' extensions.");
        }
    }

    /**
     * Handles anything we need to do when we are shutting down
     */
    public function __destruct()
    {
        if ($this->isParent) {
            $this->delPidFile();

            // stop children processes
            $this->stopChildren();

            if ($this->logFileHandle) {
                fclose($this->logFileHandle);

                $this->logFileHandle = null;
            }
        }
    }
}