<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-04-28
 * Time: 17:03
 */

namespace inhere\library\gearman;

use GearmanJob;
use GearmanWorker;

/**
 * Class JobWorker
 * @package inhere\library\gearman
 */
class WorkerManager extends ManagerAbstracter
{
    /**
     * Starts a worker for the PECL library
     *
     * @param   array $jobs     List of worker functions to add
     * @param   array $timeouts list of worker timeouts to pass to server
     * @return void
     * @throws \GearmanException
     */
    protected function startDriverWorker(array $jobs, array $timeouts = [])
    {
        $gmWorker = new GearmanWorker();
        $gmWorker->addOptions(GEARMAN_WORKER_NON_BLOCKING);
        $gmWorker->setTimeout(5000);

        $this->debug("The #{$this->id}(PID:{$this->pid}) Gearman worker started");

        foreach ($this->getServers() as $s) {
            $this->log("Adding server $s", self::LOG_WORKER_INFO);

            // see: https://bugs.php.net/bug.php?id=63041
            try {
                $gmWorker->addServers($s);
            } catch (\GearmanException $e) {
                if ($e->getMessage() !== 'Failed to set exception option') {
                    throw $e;
                }
            }
        }

        foreach ($jobs as $job) {
            $timeout = $timeouts[$job] >= 0 ? $timeouts[$job] : 0;
            $this->log("Adding job to gearman worker, Name: $job Timeout: $timeout", self::LOG_WORKER_INFO);
            $gmWorker->addFunction($job, [$this, 'doJob'], null, $timeout);
        }

        $start = time();
        $maxRun = (int)$this->get("max_run_job");

        while (!$this->stopWork) {
            if (
                @$gmWorker->work() ||
                $gmWorker->returnCode() === GEARMAN_IO_WAIT ||
                $gmWorker->returnCode() === GEARMAN_NO_JOBS
            ) {
                if ($gmWorker->returnCode() === GEARMAN_SUCCESS) {
                    continue;
                }

                if (!@$gmWorker->wait() && $gmWorker->returnCode() == GEARMAN_NO_ACTIVE_FDS) {
                    usleep(50000);
                }
            }

            $runtime = time() - $start;

            // Check the worker running time of the current child. If it has been too long, stop working.
            if ($this->maxLifetime > 0 && ($runtime > $this->maxLifetime)) {
                $this->log("Worker have been running too long time({$runtime}s), exiting", self::LOG_WORKER_INFO);
                $this->stopWork = true;
            }

            if ($maxRun >= self::MIN_HANDLE && $this->jobExecCount >= $maxRun) {
                $this->log("Ran $this->jobExecCount jobs which is over the maximum($maxRun), exiting and restart", self::LOG_WORKER_INFO);
                $this->stopWork = true;
            }
        }

        $gmWorker->unregisterAll();
    }

    /**
     * Wrapper function handler for all registered functions
     * This allows us to do some nice logging when jobs are started/finished
     * @param GearmanJob $job
     * @return bool
     */
    public function doJob($job)
    {
        $h = $job->handle();
        $wl = $job->workload();
        $name = $job->functionName();

        if (!$handler = $this->getHandler($name)) {
            $this->log("($h) Unknown job, The job name $name is not registered.", self::LOG_ERROR);
            return false;
        }

        $e = $ret = null;

        $this->log("($h) Starting Job: $name", self::LOG_WORKER_INFO);
        $this->log("($h) Job Workload: $wl", self::LOG_DEBUG);
        $this->trigger(self::EVENT_BEFORE_WORK, [$job]);

        // Run the job handler here
        try {
            if ($handler instanceof JobInterface) {
                $jobClass = get_class($handler);
                $this->log("($h) Calling: Calling Job object ($jobClass) for $name.", self::LOG_DEBUG);
                $ret = $handler->run($job->workload(), $this, $job);
            } else {
                $jobFunc = is_string($handler) ? $handler : 'Closure';
                $this->log("($h) Calling: Calling function ($jobFunc) for $name.", self::LOG_DEBUG);
                $ret = $handler($job->workload(), $this, $job);
            }
        } catch (\Exception $e) {
            $this->log("($h) Failed: failed to handle job for $name. Msg: " . $e->getMessage(), self::LOG_ERROR);
            $this->trigger(self::EVENT_AFTER_ERROR, [$job, $e]);
        }

        $this->jobExecCount++;

        if (!$e) {
            $this->log("($h) Completed Job: $name", self::LOG_WORKER_INFO);
            $this->trigger(self::EVENT_AFTER_WORK, [$job, $ret]);
        }

        return $ret;
    }

    /**
     * Shows the scripts help info with optional error message
     * @param string $msg
     */
    protected function showHelp($msg = '')
    {
        $version = self::VERSION;
        $script = $this->getScriptName();

        if ($msg) {
            echo "ERROR:\n  " . wordwrap($msg, 72, "\n  ") . "\n\n";
        }

        echo <<<EOF
Gearman worker manager script tool. Version $version

USAGE:
  # $script -h | -c CONFIG [-v LEVEL] [-l LOG_FILE] [-d] [-a] [-p PID_FILE]

OPTIONS:
  -a             Automatically check for new worker code
  -c CONFIG      Worker configuration file
  -s HOST[:PORT] Connect to server HOST and optional PORT
  
  -d             Daemon, detach and run in the background
  -n NUMBER      Start NUMBER workers that do all jobs
  -u USERNAME    Run workers as USERNAME
  -g GROUP_NAME  Run workers as user's GROUP NAME
  
  -l LOG_FILE    Log output to LOG_FILE or use keyword 'syslog' for syslog support
  -p PID_FILE    File to write process ID out to

  -r NUMBER      Maximum job iterations per worker
  -x SECONDS     Maximum seconds for a worker to live
  -t SECONDS     Maximum number of seconds gearmand server should wait for a worker to complete work before timing out and reissuing work to another worker.
  
  -v LEVEL       Increase verbosity level by one. eg: -v vv
  
  -h             Shows this help
  -V             Display the version of the manager
  -Z             Parse the command line and config file then dump it to the screen and exit.\n
EOF;
        exit(0);
    }

    /**
     * Parses the command line options
     */
    protected function parseCliOption()
    {
        $map = [
            'a' => 'auto_reload', // auto load modify files
            'c' => 'conf_file',   // config file
            's' => 'servers', // server address

            'd' => 'as_daemon',   // run in the background
            'n' => 'worker_num',  // worker number do all jobs
            'u' => 'user',
            'g' => 'group',

            'l' => 'log_file',
            'p' => 'pid_file',

            'r' => 'max_run_job', // max run jobs for a worker
            'x' => 'max_lifetime',// max lifetime for a worker
            't' => 'timeout',
        ];

        // 'c:d:' ... ...
        $shortOpts = implode(':', array_keys($map));
        $opts = getopt(
            // short opts
            $shortOpts . // server address
            'v::' . // verbosity level
            'hVZ', // h: show help V: version Z: dump config to screen

            // long opts
            [
                'help',          // No value
                'worker-num:',   // Required value
                'max-lifetime:', // Required value
                'max-run-job:',
            ]
        );

        // show help
        if (isset($opts['h']) || isset($opts['help'])) {
            $this->showHelp();
        }

        // load opts values to config
        foreach ($map as $k => $v) {
            if (isset($opts[$k]) && $opts[$k]) {
                $this->config[$v] = $opts[$k];
            }
        }

        if ($file = $this->config['conf_file']) {
            if (file_exists($file)) {
                $this->showHelp("Config file {$this->config['file']} not found.");
            }

            $this->loadConfigFile($file);
        }

        $this->verbose = (int)$this->config['log_level'];
        $this->pidFile = trim($this->config['pid_file']);

        // If we want to daemonize, fork here and exit
        if ($this->config['d']) {
            $this->runAsDaemon();
        }

        if ($this->pidFile) {
            $fp = @fopen($this->pidFile, 'w');

            if ($fp) {
                fwrite($fp, $this->pid);
                fclose($fp);
            } else {
                $this->showHelp("Unable to write PID to the file {$this->pidFile}");
            }
        }

        if (!empty($this->config['log_file'])) {
            if ($this->config['log_file'] === 'syslog') {
                $this->log_syslog = true;
            } else {
                $this->log_file = $this->config['log_file'];
                $this->openLogFile();
            }
        }

        if (isset($opts['v'])) {
            switch ($opts['v']) {
                case false:
                    $this->verbose = self::LOG_INFO;
                    break;
                case 'v':
                    $this->verbose = self::LOG_PROC_INFO;
                    break;
                case 'vv':
                    $this->verbose = self::LOG_WORKER_INFO;
                    break;
                case 'vvv':
                    $this->verbose = self::LOG_DEBUG;
                    break;
                case 'vvvv':
                default:
                    $this->verbose = self::LOG_CRAZY;
                    break;
            }

            $this->config['log_level'] = $this->verbose;
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
                    $this->log("Unable to chown PID file to {$this->user}", self::LOG_PROC_INFO);
                }
            }

            if (!empty($this->log_file_handle)) {
                if (!chown($this->log_file, $user['uid'])) {
                    $this->log("Unable to chown log file to {$this->user}", self::LOG_PROC_INFO);
                }
            }

            posix_setuid($user['uid']);
            if (posix_geteuid() != $user['uid']) {
                $this->showHelp("Unable to change user to {$this->user} (UID: {$user['uid']}).");
            }
            $this->log("User set to {$this->user}", self::LOG_PROC_INFO);
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

        // parseConfig
        $this->parseConfig();

        // Debug option to dump the config and exit
        if (isset($opts['Z'])) {
            print_r($this->config);
            $this->quit();
        }
    }

    protected function parseConfig()
    {
        $this->config['work_num'] = (int)$this->config['work_num'];

        if ($this->config['work_num'] <= 0) {
            $this->config['work_num'] = 1;
        }
    }

    protected function loadConfigFile($file)
    {
        if ($file && file_exists($file)) {
            $config = require $file;

            $this->setConfig($config);
        }
    }
}
