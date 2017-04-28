<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-04-28
 * Time: 17:03
 */

namespace inhere\library\gearman;

/**
 * Class JobWorker
 * @package inhere\library\gearman
 */
class JobWorker
{
    /**
     * @event PushEvent
     */
    const EVENT_BEFORE_PUSH = 'beforePush';

    /**
     * @event PushEvent
     */
    const EVENT_AFTER_PUSH = 'afterPush';

    /**
     * @event JobEvent
     */
    const EVENT_BEFORE_WORK = 'beforeWork';

    /**
     * @event JobEvent
     */
    const EVENT_AFTER_WORK = 'afterWork';

    /**
     * @event ErrorEvent
     */
    const EVENT_AFTER_ERROR = 'afterError';
    
    /**
     * Log levels can be enabled from the command line with -v, -vv, -vvv
     */
    const LOG_LEVEL_INFO = 1;
    const LOG_LEVEL_PROC_INFO = 2;
    const LOG_LEVEL_WORKER_INFO = 3;
    const LOG_LEVEL_DEBUG = 4;
    const LOG_LEVEL_CRAZY = 5;

    /**
     * @var bool
     */
    protected $isParent = true;

    /**
     * The PID of the running process. Set for parent and child processes
     */
    protected $pid = 0;

    /**
     * The PID of the parent process, when running in the forked helper.
     */
    protected $parentPid = 0;

    /**
     * Verbosity level for the running script. Set via -v option
     */
    protected $verbose = 0;

    /**
     * children
     * @var array
     */
    protected $children = [];

    /**
     * Holds the last timestamp of when the code was checked for updates
     */
    protected $lastCheckTime = 0;

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
     * Number of times this worker has run a job
     */
    protected $jobExecCount = 0;

    /**
     * List of handlers(functions) available for work
     * @var array
     */
    protected $handlers = [];

    /**
     * The array of jobs that have workers running
     */
    protected $jobs = [];

    /**
     * Holds the resource for the log file
     * @var resource
     */
    protected $logFileHandle;

    /**
     * When true, workers will stop look for jobs and the parent process will kill off all running children
     * @var boolean
     */
    protected $stopWork = false;

    /**
     * multi process
     * @var boolean
     */
    protected $multiProcess = true;

    protected $config = [
        'debug' => false,
        'servers' => [
            '127.0.0.1:4730',
        ],

        'log_syslog' => false,
        'log_file' => './job_worker.log',

        'pid_file' => './job_worker.pid',

        'work_num' => 4,
        'auto_reload' => 1,

        // Workers will only live for 1 hour
        'max_lifetime' => 3600,

        // Workers max handle 2000 job. after will restart.
        'max_handle' => 2000,
    ];

    protected $channel = 'queue';

    /**
     * gearman worker
     * @var \GearmanWorker
     */
    private $worker;

    public function __construct(array $config = [], $bootstrap = true)
    {
        $this->config = array_merge($this->config, $config);
        $this->pid = getmypid();

        $this->config['work_num'] = (int)$this->config['work_num'];

        if ($this->config['work_num'] <= 0) {
            $this->config['work_num'] = 1;
        }

        if ($bootstrap) {
            $this->bootstrap();
        }
    }

    /**
     * Handles anything we need to do when we are shutting down
     */
    public function __destruct()
    {
        if ($this->isParent && ($pidFile = $this->get('pid_file')) && file_exists($pidFile)) {
            if (!unlink($$pidFile)) {
                $this->log("Could not delete PID file", self::LOG_LEVEL_PROC_INFO);
            }
        }

        if ($this->logFileHandle) {
            fclose($this->logFileHandle);
        }
    }

    public function bootstrap()
    {
        $this->checkEnvironment();

        $this->log("Started with pid $this->pid, Current script owner: " . get_current_user(), self::LOG_LEVEL_PROC_INFO);

        $host = $this->getHost();
        $port = $this->getPort();
        $this->worker = new \GearmanWorker();

        $this->worker->addServer($host, $port);
        $this->worker->setTimeout(-1);

        $this->debug("Start gearman worker, connection to the gearman server {$host}:{$port}");
    }

    /**
     * add job handler
     * @param string  $name
     * @param callable  $handler
     * @param mixed   $context
     * @param integer $timeout
     */
    public function addHandler($name, $handler, $context = null, $timeout = 0)
    {
        $this->addFunction($name, $handler, $context, $timeout);
    }
    public function addFunction($name, $handler, $context = null, $timeout = 0)
    {
        $this->worker->addFunction($name, $handler, $context, $timeout);
    }

    protected function startWorker($worker = "all" )
    {
        $pid = pcntl_fork();

        switch ($pid) {
            case 0: // children
                $this->isParent = false;
                $this->registerSignals(false);
                $this->pid = getmypid();

                if (count($worker_list) > 1) {
                    // shuffle the list to avoid queue preference
                    shuffle($worker_list);

                    // sort the shuffled array by priority
                    uasort($worker_list, array($this, "sort_priority"));
                }

                if ($this->worker_restart_splay > 0) {
                    // Since all child threads use the same seed, we need to reseed with the pid so that we get a new "random" number.
                    mt_srand($this->pid);
                    $splay = mt_rand(0, $this->worker_restart_splay);
                    $this->maxLifetime += $splay;
                    $this->log("Adjusted max run time to {$this->maxLifetime} seconds", self::LOG_LEVEL_DEBUG);
                }

                $this->startGearmanWorker($worker_list, $timeouts);

                $this->log("Child exiting", self::LOG_LEVEL_WORKER_INFO);
                exit();
                break;

            case -1:
                $this->log("Could not fork children process!");
                $this->stopWork = true;
                $this->stopChildrens();
                break;

            default: // parent
                $this->log("Started child $pid (".implode(",", $worker_list).")", self::LOG_LEVEL_PROC_INFO);
                $this->children[$pid] = array(
                    "job"        => $workerList,
                    "start_time" => time(),
                );
        }
    }


    /**
     * Starts a worker for the PECL library
     *
     * @param   array $jobList List of worker functions to add
     * @param   array $timeouts list of worker timeouts to pass to server
     * @return void
     * @throws \GearmanException
     */
    protected function startGearmanWorker($jobList, array $timeouts = [])
    {
        $gmWorker = new \GearmanWorker();
        $gmWorker->addOptions(GEARMAN_WORKER_NON_BLOCKING);
        $gmWorker->setTimeout(5000);

        foreach ((array)$this->get('servers') as $s) {
            $this->log("Adding server $s", self::LOG_LEVEL_WORKER_INFO);

            // see: https://bugs.php.net/bug.php?id=63041
            try {
                $gmWorker->addServers($s);
            } catch (\GearmanException $e) {
                if ($e->getMessage() !== 'Failed to set exception option') {
                    throw $e;
                }
            }
        }

        $this->debug("Gearman worker started, connection to the gearman server " . implode(',', (array)$this->get('servers')));

        foreach ($jobList as $w) {
            $timeout = (isset($timeouts[$w]) ? $timeouts[$w] : null);
            $this->log("Adding job $w ; timeout: " . $timeout, self::LOG_LEVEL_WORKER_INFO);
            $gmWorker->addFunction($w, array($this, "doJob"), $this, $timeout);
        }

        $start = time();
        while (!$this->stopWork) {
            if (
                @$gmWorker->work() ||
                $gmWorker->returnCode() === GEARMAN_IO_WAIT ||
                $gmWorker->returnCode() === GEARMAN_NO_JOBS
            ) {

                if ($gmWorker->returnCode() === GEARMAN_SUCCESS) {
                    continue;
                }

                if (!@$gmWorker->wait()) {
                    if ($gmWorker->returnCode() == GEARMAN_NO_ACTIVE_FDS) {
                        usleep(50000);
                    }
                }

            }

            /**
             * Check the running time of the current child. If it has
             * been too long, stop working.
             */
            if ($this->maxLifetime > 0 && time() - $start > $this->maxLifetime) {
                $this->log("Been running too long, exiting", self::LOG_LEVEL_WORKER_INFO);
                $this->stopWork = true;
            }

            if (!empty($this->config["max_runs_per_worker"]) && $this->job_execution_count >= $this->config["max_runs_per_worker"]) {
                $this->log("Ran $this->job_execution_count jobs which is over the maximum({$this->config['max_runs_per_worker']}), exiting", self::LOG_LEVEL_WORKER_INFO);
                $this->stopWork = true;
            }

        }

        $gmWorker->unregisterAll();
    }

    /**
     * Wrapper function handler for all registered functions
     * This allows us to do some nice logging when jobs are started/finished
     */
    public function doJob(\GearmanJob $job)
    {
        static $objects;

        if ($objects===null) $objects = array();

        $w = $job->workload();
        $h = $job->handle();
        $jobName = $job->functionName();

        if ($this->prefix) {
            $func = $this->prefix . $jobName;
        } else {
            $func = $jobName;
        }

        if (empty($objects[$jobName]) && !function_exists($func) && !class_exists($func, false)) {
            if (!isset($this->functions[$jobName])) {
                $this->log("Function $func is not a registered job name");
                return;
            }

            require_once $this->functions[$jobName]["path"];

            if (class_exists($func) && method_exists($func, "run")) {

                $this->log("Creating a $func object", self::LOG_LEVEL_WORKER_INFO);
                $ns_func = "\\$func";
                $objects[$jobName] = new $ns_func();

            } elseif (!function_exists($func)) {

                $this->log("Function $func not found");
                return;
            }
        }

        $this->log("($h) Starting Job: $jobName", self::LOG_LEVEL_WORKER_INFO);
        $this->log("($h) Workload: $w", self::LOG_LEVEL_DEBUG);

        $log = array();

        /**
         * Run the real function here
         */
        if (isset($objects[$jobName])) {
            $this->log("($h) Calling object for $jobName.", self::LOG_LEVEL_DEBUG);
            $result = $objects[$jobName]->run($job, $log);
        } elseif (function_exists($func)) {
            $this->log("($h) Calling function for $jobName.", self::LOG_LEVEL_DEBUG);
            $result = $func($job, $log);
        } else {
            $this->log("($h) FAILED to find a function or class for $jobName.", self::LOG_LEVEL_INFO);
        }

        if (!empty($log)) {
            foreach ($log as $l) {

                if (!is_scalar($l)) {
                    $l = explode("\n", trim(print_r($l, true)));
                } elseif (strlen($l) > 256) {
                    $l = substr($l, 0, 256)."...(truncated)";
                }

                if (is_array($l)) {
                    foreach ($l as $ln) {
                        $this->log("($h) $ln", self::LOG_LEVEL_WORKER_INFO);
                    }
                } else {
                    $this->log("($h) $l", self::LOG_LEVEL_WORKER_INFO);
                }

            }
        }

        $result_log = $result;

        if (!is_scalar($result_log)) {
            $result_log = explode("\n", trim(print_r($result_log, true)));
        } elseif (strlen($result_log) > 256) {
            $result_log = substr($result_log, 0, 256)."...(truncated)";
        }

        if (is_array($result_log)) {
            foreach ($result_log as $ln) {
                $this->log("($h) $ln", self::LOG_LEVEL_DEBUG);
            }
        } else {
            $this->log("($h) $result_log", self::LOG_LEVEL_DEBUG);
        }

        /**
         * Workaround for PECL bug #17114
         * http://pecl.php.net/bugs/bug.php?id=17114
         */
        $type = gettype($result);
        settype($result, $type);

        $this->jobExecCount++;

        return $result;
    }

    /**
     * Listens gearman-queue and runs new jobs.
     */
    public function listen()
    {
        $this->worker->addFunction($this->channel, function (\GearmanJob $job) {
            $this->handleJob($job->workload());
        });

        do {
            $this->worker->work();
            usleep(50000);
        } while (!Signal::isExit() && $this->worker->returnCode() === GEARMAN_SUCCESS);
    }

    public function handleJob($payload)
    {
        $job = unserialize($payload);

        if (!($job instanceof Job)) {
            throw new InvalidParamException('Message must be ' . Job::class . ' object.');
        }

        $error = null;
        $this->trigger(self::EVENT_BEFORE_WORK, new JobEvent(['job' => $job]));

        try {
            $job->run();
        } catch (\Exception $error) {
            $this->trigger(self::EVENT_AFTER_ERROR, new ErrorEvent(['job' => $job, 'error' => $error]));
        }
        if (!$error) {
            $this->trigger(self::EVENT_AFTER_WORK, new JobEvent(['job' => $job]));
        }

        return !$error;
    }

    protected function checkEnvironment()
    {
        $this->multiProcess = true;

        if (!function_exists('posix_kill')) {
            $this->multiProcess = false;
            //$this->debug("The function 'posix_kill' was not found. Please ensure POSIX functions are installed");
        }

        if (!function_exists('pcntl_fork')) {
            $this->multiProcess = false;
            //$this->debug("The function 'pcntl_fork' was not found. Please ensure Process Control functions are installed");
        }

        if (!$this->multiProcess) {
            $this->debug("This is not support run multi process of the current enviroment. require the 'posix_kill', 'pcntl_fork'.");
        }
    }

    /**
     * Stops all running children
     */
    protected function stopChildrens($signal = SIGTERM)
    {
        $this->log("Stopping children", self::LOG_LEVEL_PROC_INFO);

        foreach ($this->children as $pid=>$child) {
            $this->log("Stopping child $pid (".implode(",", $child['job']).")", self::LOG_LEVEL_PROC_INFO);

            $this->killProcess($pid, $signal);
        }
    }

    protected function killProcess($pid, $signal)
    {
        if ($this->multiProcess) {
            posix_kill($pid, $signal);
        }

        exit(0);
    }

    /**
     * Registers the process signal listeners
     */
    protected function registerSignals($parent = true)
    {
        if ($parent) {
            $this->log("Registering signals for parent", self::LOG_LEVEL_DEBUG);

            pcntl_signal(SIGTERM, array($this, 'signalHandler'));
            pcntl_signal(SIGINT,  array($this, 'signalHandler'));
            pcntl_signal(SIGUSR1,  array($this, 'signalHandler'));
            pcntl_signal(SIGUSR2,  array($this, 'signalHandler'));
            pcntl_signal(SIGCONT,  array($this, 'signalHandler'));
            pcntl_signal(SIGHUP,  array($this, 'signalHandler'));
        } else {
            $this->log("Registering signals for child", self::LOG_LEVEL_DEBUG);

            if (!$res = pcntl_signal(SIGTERM, array($this, 'signalHandler'))) {
                exit(0);
            }
        }
    }

    /**
     * Handles signals
     */
    public function signal($signo)
    {
        static $term_count = 0;

        if (!$this->isParent) {
            $this->stopWork = true;
        } else {
            switch ($signo) {
                case SIGUSR1:
                    $this->showHelp("No worker files could be found");
                    break;
                case SIGUSR2:
                    $this->showHelp("Error validating worker functions");
                    break;
                case SIGCONT:
                    $this->wait_for_signal = false;
                    break;
                case SIGINT:
                case SIGTERM:
                    $this->log("Shutting down...");
                    $this->stopWork = true;
                    $this->stop_time = time();
                    $term_count++;

                    if ($term_count < 5) {
                        $this->stopChildrens();
                    } else {
                        $this->stopChildrens(SIGKILL);
                    }
                    break;
                case SIGHUP:
                    $this->log("Restarting children", self::LOG_LEVEL_PROC_INFO);
                    $this->openLogFile();
                    $this->stopChildrens();
                    break;
                default:
                    // handle all other signals
            }
        }
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
     * Parses the command line options
     */
    protected function parseCliOption()
    {
        $opts = getopt("ac:dD:h:Hl:o:p:P:u:v::w:r:x:Z");

        if (isset($opts["H"])) {
            $this->showHelp();
        }

        if (isset($opts["c"])) {
            $this->config['file'] = $opts['c'];
        }

        if (isset($this->config['file'])) {
            if (file_exists($this->config['file'])) {
                $this->parse_config($this->config['file']);
            }
            else {
                $this->showHelp("Config file {$this->config['file']} not found.");
            }
        }

        /**
         * command line opts always override config file
         */
        $this->config['pid_file'] = isset($opts['P']) ? $opts['P'] : $this->config['pid_file'];
        $this->config['log_file'] = isset($opts["l"]) ? $opts["l"] : $this->config['log_file'];
        $this->config['auto_reload'] = isset($opts['a']) ? true : false;

        if (isset($opts['w'])) {
            $this->config['handler_dir'] = $opts['w'];
        }

        if (isset($opts['x'])) {
            $this->config['max_lifetime'] = (int)$opts['x'];
        }

        if (isset($opts['r'])) {
            $this->config['max_runs_per_worker'] = (int)$opts['r'];
        }

        if (isset($opts['D'])) {
            $this->config['count'] = (int)$opts['D'];
        }

        if (isset($opts['t'])) {
            $this->config['timeout'] = $opts['t'];
        }

        if (isset($opts['h'])) {
            $this->config['host'] = $opts['h'];
        }

        if (isset($opts['p'])) {
            $this->prefix = $opts['p'];
        } elseif (!empty($this->config['prefix'])) {
            $this->prefix = $this->config['prefix'];
        }

        if (isset($opts['u'])) {
            $this->user = $opts['u'];
        } elseif (isset($this->config["user"])) {
            $this->user = $this->config["user"];
        }

        /**
         * If we want to daemonize, fork here and exit
         */
        if (isset($opts["d"])) {
            $pid = pcntl_fork();
            if ($pid>0) {
                $this->isparent = false;
                exit();
            }
            $this->pid = getmypid();
            posix_setsid();
        }

        if (!empty($this->config['pid_file'])) {
            $fp = @fopen($this->config['pid_file'], "w");
            if ($fp) {
                fwrite($fp, $this->pid);
                fclose($fp);
            } else {
                $this->showHelp("Unable to write PID to {$this->config['pid_file']}");
            }
            $this->pid_file = $this->config['pid_file'];
        }

        if (!empty($this->config['log_file'])) {
            if ($this->config['log_file'] === 'syslog') {
                $this->log_syslog = true;
            } else {
                $this->log_file = $this->config['log_file'];
                $this->open_log_file();
            }
        }

        if (isset($opts["v"])) {
            switch ($opts["v"]) {
                case false:
                    $this->verbose = self::LOG_LEVEL_INFO;
                    break;
                case "v":
                    $this->verbose = self::LOG_LEVEL_PROC_INFO;
                    break;
                case "vv":
                    $this->verbose = self::LOG_LEVEL_WORKER_INFO;
                    break;
                case "vvv":
                    $this->verbose = self::LOG_LEVEL_DEBUG;
                    break;
                case "vvvv":
                default:
                    $this->verbose = self::LOG_LEVEL_CRAZY;
                    break;
            }
        }

        if ($this->user) {
            $user = posix_getpwnam($this->user);
            if (!$user || !isset($user['uid'])) {
                $this->showHelp("User ({$this->user}) not found.");
            }

            /**
             * Ensure new uid can read/write pid and log files
             */
            if (!empty($this->pid_file)) {
                if (!chown($this->pid_file, $user['uid'])) {
                    $this->log("Unable to chown PID file to {$this->user}", self::LOG_LEVEL_PROC_INFO);
                }
            }
            if (!empty($this->log_file_handle)) {
                if (!chown($this->log_file, $user['uid'])) {
                    $this->log("Unable to chown log file to {$this->user}", self::LOG_LEVEL_PROC_INFO);
                }
            }

            posix_setuid($user['uid']);
            if (posix_geteuid() != $user['uid']) {
                $this->showHelp("Unable to change user to {$this->user} (UID: {$user['uid']}).");
            }
            $this->log("User set to {$this->user}", self::LOG_LEVEL_PROC_INFO);
        }

        if (!empty($this->config['auto_reload'])) {
            $this->check_code = true;
        }

        if (!empty($this->config['handler_dir'])) {
            $this->handler_dir = $this->config['handler_dir'];
        } else {
            $this->handler_dir = "./workers";
        }

        if (isset($this->config['max_lifetime']) && (int)$this->config['max_lifetime'] > 0) {
            $this->maxLifetime = (int)$this->config['max_lifetime'];
        }

        if (isset($this->config['worker_restart_splay']) && (int)$this->config['worker_restart_splay'] > 0) {
            $this->worker_restart_splay = (int)$this->config['worker_restart_splay'];
        }

        if (isset($this->config['count']) && (int)$this->config['count'] > 0) {
            $this->do_all_count = (int)$this->config['count'];
        }

        if (!empty($this->config['host'])) {
            if (!is_array($this->config['host'])) {
                $this->servers = explode(",", $this->config['host']);
            } else {
                $this->servers = $this->config['host'];
            }
        } else {
            $this->servers = array("127.0.0.1");
        }

        if (!empty($this->config['include']) && $this->config['include'] != "*") {
            $this->config['include'] = explode(",", $this->config['include']);
        } else {
            $this->config['include'] = array();
        }

        if (!empty($this->config['exclude'])) {
            $this->config['exclude'] = explode(",", $this->config['exclude']);
        } else {
            $this->config['exclude'] = array();
        }

        /**
         * Debug option to dump the config and exit
         */
        if (isset($opts["Z"])) {
            print_r($this->config);
            exit();
        }
    }

    public function get($name, $default = null)
    {
        return isset($this->config[$name]) ? $this->config[$name] : $default;
    }

    /**
     * get server host
     * @return string
     */
    public function getHost()
    {
        return $this->config['host'] ?: '127.0.0.1';
    }

    /**
     * get server port
     * @return string
     */
    public function getPort()
    {
        return $this->config['port'] ?: 4730;
    }

    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * debug log
     * @param  string $msg
     * @param  array  $data
     */
    public function debug($msg, array $data = [])
    {
        fwrite(\STDOUT, sprintf('[%s] %s %s' . PHP_EOL,
            date('y-m-d H:i:s'),
            $msg,
            $data ? json_encode($data) : ''
        ));
    }

    /**
     * Logs data to disk or stdout
     */
    protected function log($message, $level = self::LOG_LEVEL_INFO, array $data = [])
    {
        if ($level > $this->verbose) {
            return true;
        }

        $data = $data ? json_encode($data) : '';

        if ($this->get('log_syslog')) {
            $this->syslog($message . $data, $level);
            return true;
        }

        $label = 'NORMAL';

        switch ($level) {
            case self::LOG_LEVEL_INFO;
                $label = 'INFO';
                break;
            case self::LOG_LEVEL_PROC_INFO:
                $label = 'PROC';
                break;
            case self::LOG_LEVEL_WORKER_INFO:
                $label = 'WORKER';
                break;
            case self::LOG_LEVEL_DEBUG:
                $label = 'DEBUG';
                break;
            case self::LOG_LEVEL_CRAZY:
                $label = 'CRAZY';
                break;
        }

        list($ts, $ms) = explode('.', sprintf('%f', microtime(true)));
        $ds = date('y-m-d H:i:s') . '.' . str_pad($ms, 6, 0);

        $logString = sprintf('[%s] [%d] [%s] %s %s' . PHP_EOL, $ds, $this->pid, $label, trim($msg), $data);

        $this->stdout($logString);

        if ($this->logFileHandle) {
            fwrite($this->logFileHandle, $logString);
        }

        return true;
    }

    /**
     * Logs data to syslog
     */
    protected function stdout($logString)
    {
        fwrite(\STDOUT, $logString);
    }

    /**
     * Logs data to syslog
     */
    protected function syslog($message, $level)
    {
        switch ($level) {
            case self::LOG_LEVEL_INFO;
            case self::LOG_LEVEL_PROC_INFO:
            case self::LOG_LEVEL_WORKER_INFO:
            default:
                $priority = LOG_INFO;
                break;
            case self::LOG_LEVEL_DEBUG:
                $priority = LOG_DEBUG;
                break;
        }

        if (!syslog($priority, $message)) {
            $this->stdout("Unable to write to syslog\n");
        }
    }

    /**
     * Shows the scripts help info with optional error message
     */
    protected function showHelp($msg = '')
    {
        if ($msg) {
            echo "ERROR:\n  " . wordwrap($msg, 72, "\n  ") . "\n\n";
        }

        echo <<<EOF
Gearman worker manager script tool.

USAGE:
  # {script} -h | -c CONFIG [-v] [-l LOG_FILE] [-d] [-v] [-a] [-P PID_FILE]

OPTIONS:
  -a             Automatically check for new worker code
  -c CONFIG      Worker configuration file
  -d             Daemon, detach and run in the background
  -D NUMBER      Start NUMBER workers that do all jobs
  -h HOST[:PORT] Connect to HOST and optional PORT
  -H             Shows this help
  -l LOG_FILE    Log output to LOG_FILE or use keyword 'syslog' for syslog support
  -p PREFIX      Optional prefix for functions/classes of PECL workers. PEAR requires a constant be defined in code.
  -P PID_FILE    File to write process ID out to
  -u USERNAME    Run workers as USERNAME
  -v             Increase verbosity level by one
  -w DIR         Directory where workers are located, defaults to ./workers. If you are using PECL, you can provide multiple directories separated by a comma.
  -r NUMBER      Maximum job iterations per worker
  -t SECONDS     Maximum number of seconds gearmand server should wait for a worker to complete work before timing out and reissuing work to another worker.
  -x SECONDS     Maximum seconds for a worker to live
  -Z             Parse the command line and config file then dump it to the screen and exit.
EOF;
        exit(0);
    }
}
