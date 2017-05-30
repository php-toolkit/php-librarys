<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-05-20
 * Time: 10:10
 */

namespace inhere\library\task\worker;

use inhere\library\helpers\CliHelper;
use inhere\library\process\ProcessLogger;
use inhere\library\process\ProcessUtil;
use inhere\library\queue\QueueInterface;

/**
 * Class ProcessManageTrait
 * @package inhere\library\task\worker
 *
 * @property QueueInterface $queue
 */
trait ProcessManageTrait
{
    /**
     * worker Id
     * @var int
     */
    protected $id = 0;

    /**
     * The PID of the master process
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
     * wait response for process signal
     * @var bool
     */
    private $waitForSignal = false;

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

    ////////////////////////////////////////////////////////////////
    /// workers manager
    ////////////////////////////////////////////////////////////////

    /**
     * @param int $workerNum
     * @return array
     */
    protected function startWorkers($workerNum)
    {
        return ProcessUtil::forks($workerNum, function ($id, $pid) {
            $this->runWorker($id, $pid);
        });
    }

    /**
     * @param $tasks
     * @param $id
     * @param bool $first
     * @return array
     */
    protected function startWorker($tasks, $id, $first = true)
    {
        return ProcessUtil::fork($id, function ($id, $pid) use($first) {
            $this->runWorker($id, $pid);
        });
    }

    /**
     * run Worker
     * @param $id
     * @param $pid
     */
    protected function runWorker($id, $pid)
    {
        $this->isWorker = true;
        $this->isMaster = false;

        $this->id = $id;
        $this->masterPid = $this->pid;
        $this->pid = getmypid();

        $eCode = $this->receiveTasks($this->queue);

        $this->log("Worker #$id exited(Exit-Code:$eCode)", ProcessLogger::PROC_INFO);
        $this->quit($eCode);
    }

    /**
     * run Worker
     * @param QueueInterface $queue
     * @return int
     */
    protected function receiveTasks(QueueInterface $queue)
    {
        $eCode = 0;

        while (!$this->stopWork) {
            $this->dispatchSignals();

            if($data = $queue->pop()) {
                $this->log("read queue workerId=$this->id data=$data", ProcessLogger::WORKER_INFO);
                $this->handleTask($data);
            } elseif ($err = $queue->getErrMsg()) {
                $this->log("queue errCode={$queue->getErrCode()} errMsg=$err", ProcessLogger::ERROR);
            } else {
                $this->log("queue is empty, sleep 5s. wait next read.", ProcessLogger::CRAZY);
                sleep(5);
                continue;
            }

            usleep(50000);
        }

        return $eCode;
    }

    /**
     * startMaster
     */
    protected function startManager()
    {
        $this->log('Now, Begin monitor runtime status for all workers', ProcessLogger::DEBUG);

        $this->runWorkersMonitor();

        $this->log('All workers stopped', ProcessLogger::PROC_INFO);
        $this->quit();
    }

    /**
     * runWorkersMonitor
     */
    protected function runWorkersMonitor()
    {
        // Main processing loop for the parent process
        while (!$this->stopWork || count($this->workers)) {
            $this->dispatchSignals();

            // Check for exited workers
            $status = null;
            $exitedPid = pcntl_wait($status, WNOHANG);

            // We run other workers, make sure this is a worker
            if (isset($this->workers[$exitedPid])) {
                /*
                 * If they have exited, remove them from the workers array
                 * If we are not stopping work, start another in its place
                 */
                if ($exitedPid) {
                    $workerId = $this->workers[$exitedPid]['id'];
                    $workerJobs = $this->workers[$exitedPid]['jobs'];
                    $exitCode = pcntl_wexitstatus($status);
                    unset($this->workers[$exitedPid]);

                    $this->logWorkerStatus($exitedPid, $workerJobs, $exitCode);

                    if (!$this->stopWork) {
                        $this->startWorker($workerJobs, $workerId, false);
                    }
                }
            }

            if ($this->stopWork) {
                if (time() - $this->stat['stopTime'] > 60) {
                    $this->log('Workers exiting timeout, will force killing.', ProcessLogger::PROC_INFO);
                    $this->stopWorkers(SIGKILL);
                }
            } else {
                // If any workers have been running 150% of max run time, forcibly terminate them
                foreach ($this->workers as $pid => $worker) {
                    if (!empty($worker['startTime']) && time() - $worker['startTime'] > $this->maxLifetime * 1.5) {
                        $this->logWorkerStatus($pid, $worker['jobs'], self::CODE_MANUAL_KILLED);
                        $this->sendSignal($pid, SIGKILL);
                    }
                }
            }

            // php will eat up your cpu if you don't have this
            usleep(10000);
        }
    }

    /**
     * Do shutdown Manager
     * @param  int $pid Master Pid
     * @param  boolean $quit Quit, When stop success?
     */
    protected function stopMaster($pid, $quit = true)
    {
        $this->stdout("Stop the manager(PID:$pid)");

        ProcessUtil::killAndWait($pid, SIGTERM, 'manager');

        // stop success
        $this->stdout(sprintf("\n%s\n"), CliHelper::color("The manager process stopped", CliHelper::FG_GREEN));

        if ($quit) {
            $this->quit();
        }

        // clear file info
        clearstatcache();

        $this->stdout('Begin restart manager ...');
    }

    /**
     * reloadWorkers
     * @param $masterPid
     */
    protected function reloadWorkers($masterPid)
    {
        $this->stdout("Workers reloading ...");

        $this->sendSignal($masterPid, SIGHUP);

        $this->quit();
    }

    /**
     * Stops all running workers
     * @param int $signal
     * @return bool
     */
    protected function stopWorkers($signal = SIGTERM)
    {
        if (!$this->workers) {
            $this->log('No child process(worker) need to stop', ProcessLogger::PROC_INFO);
            return false;
        }

        return ProcessUtil::stopChildren($this->workers, $signal, [
            'beforeStops' => function ($sigText) {
                $this->log("Stopping workers({$sigText}) ...", ProcessLogger::PROC_INFO);
            },
            'beforeStop' => function ($pid, $info) {
                $this->log("Stopping worker #{$info['id']}(PID:$pid)", ProcessLogger::PROC_INFO);
            },
        ]);
    }

    /**
     * @param int $pid
     * @param array $jobs
     * @param int $statusCode
     */
    protected function logWorkerStatus($pid, $jobs, $statusCode)
    {
        $jobStr = implode(',', $jobs);

        switch ((int)$statusCode) {
            case self::CODE_MANUAL_KILLED:
                $message = "Worker (PID:$pid) has been running too long. Forcibly killing process. (Jobs:$jobStr)";
                break;
            case self::CODE_NORMAL_EXITED:
                $message = "Worker (PID:$pid) normally exited. (Jobs:$jobStr)";
                break;
            case self::CODE_CONNECT_ERROR:
                $message = "Worker (PID:$pid) connect to job server failed. exiting";
                $this->stopWork();
                break;
            default:
                $message = "Worker (PID:$pid) died unexpectedly with exit code $statusCode. (Jobs:$jobStr)";
                break;
        }

        $this->log($message, ProcessLogger::PROC_INFO);
    }

    /**
     * getWorkerId
     * @param  int $pid
     * @return int
     */
    public function getWorkerId($pid)
    {
        return isset($this->workers[$pid]) ? $this->workers[$pid]['id'] : 0;
    }

    /**
     * getPidByWorkerId
     * @param  int $id
     * @return int
     */
    public function getPidByWorkerId($id)
    {
        $thePid = 0;

        foreach ($this->workers as $pid => $item) {
            if ($id === $item['id']) {
                $thePid = $pid;
                break;
            }
        }

        return $thePid;
    }
}
