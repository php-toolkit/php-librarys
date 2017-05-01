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
        $script = $this->getScript();

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
  
  -h,--help      Shows this help
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
            // 'a' => 'auto_reload', // auto load modify files
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
        $shortOpts = implode(':', array_keys($map)) . ':';
        $opts = getopt(
            // short opts
            $shortOpts . // server address
            'v::' . // verbosity level
            'ahVZ', // a: auto load modify files h: show help V: version Z: dump config to screen

            // long opts
            [
                'help',          // No value
                // 'worker-num:',   // Required value
                // 'max-lifetime:', // Required value
                // 'max-run-job:',
            ]
        );
var_dump($opts, $shortOpts);
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

        if (isset($opts['a'])) {
            $this->config['auto_reload'] = $opts['a'];
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

//        if ((int)$this->config['restart_splay'] > 0) {
//            $this->worker_restart_splay = (int)$this->config['restart_splay'];
//        }

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
        $this->config['worker_num'] = (int)$this->config['worker_num'];
        $this->config['max_lifetime'] = (int)$this->config['max_lifetime'];

        if ($this->config['worker_num'] <= 0) {
            $this->config['worker_num'] = 1;
        }

        if ($this->config['max_lifetime'] <= 100) {
            $this->config['max_lifetime'] = 600;
        }

        $this->doAllWorkers = $this->config['worker_num'];
        $this->maxLifetime = $this->config['max_lifetime'];

    }

    protected function loadConfigFile($file)
    {
        if ($file && file_exists($file)) {
            $config = require $file;

            $this->setConfig($config);
        }
    }
}
