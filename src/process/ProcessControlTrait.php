<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/4/9 0009
 * Time: 23:39
 */

namespace inhere\library\process;

use inhere\library\helpers\ProcessHelper;

/**
 * Class ProcessControlTrait
 * @package inhere\library\process
 *
 */
trait ProcessControlTrait
{
    /**
     * current support process control
     * @var bool
     */
    protected $supportPC = true;

    /**
     * @var int
     */
    protected $pid = 0;

    /**
     * @var string
     */
    protected $pidFile;

    /**
     * @var bool
     */
    protected $stopWork = false;

    /**
     * The statistics info for server/worker
     * @var array
     */
    protected $stat = [
        'start_time' => 0,
        'stop_time'  => 0,
        'start_times' => 0,
    ];

    /////////////////////////////////////////////////////////////////////////////////////////
    /// process method
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * run as daemon process
     */
    public function runAsDaemon()
    {
        if (!$this->supportPC) {
            $this->pid = ProcessHelper::runAsDaemon();
        }
    }

    public function installSignals($isMaster = true)
    {
        if (!$this->supportPC) {
            return false;
        }

        // ignore
        pcntl_signal(SIGPIPE, SIG_IGN, false);

        if ($isMaster) {
            // $signals = ['SIGTERM' => 'close worker', ];
//            $this->log('Registering signal handlers for master(parent) process', self::LOG_DEBUG);

            pcntl_signal(SIGTERM, [$this, 'signalHandler'], false);
            pcntl_signal(SIGINT, [$this, 'signalHandler'], false);
            pcntl_signal(SIGUSR1, [$this, 'signalHandler'], false);
            pcntl_signal(SIGUSR2, [$this, 'signalHandler'], false);

            pcntl_signal(SIGHUP, [$this, 'signalHandler'], false);

            pcntl_signal(SIGCHLD, [$this, 'signalHandler'], false);

        } else {
//            $this->log("Registering signal handlers for current worker process", self::LOG_DEBUG);

            pcntl_signal(SIGTERM, [$this, 'signalHandler'], false);
        }

        return $this;
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
                    $this->log("Shutting down(signal:$sigText)...", self::LOG_PROC_INFO);
                    $this->stopWork();
                    $stopCount++;

                    if ($stopCount < 5) {
                        $this->stopWorkers();
                    } else {
                        $this->log('Stop workers failed by(signal:SIGTERM), force kill workers by(signal:SIGKILL)', self::LOG_PROC_INFO);
                        $this->stopWorkers(SIGKILL);
                    }
                    break;
                case SIGHUP:
                    $this->log('Restarting workers(signal:SIGHUP)', self::LOG_PROC_INFO);
                    $this->openLogFile();
                    $this->stopWorkers();
                    break;
                case SIGUSR1: // reload workers and reload handlers
                    $this->log('Reloading workers and handlers(signal:SIGUSR1)', self::LOG_PROC_INFO);
                    $this->stopWork();
                    $this->start();
                    break;
                case SIGUSR2:
                    break;
                default:
                    // handle all other signals
            }

        } else {
            $this->stopWork();
            $this->log("Received 'stopWork' signal(signal:SIGTERM), will be exiting.", self::LOG_PROC_INFO);
        }
    }

    /**
     * @param int $pid
     * @return bool
     */
    protected function isRunning(int $pid)
    {
        if ($this->supportPC) {
            return ProcessHelper::isRunning($pid);
        }

        return false;
    }

    /**
     * @param int $pid
     * @param int $signal
     */
    protected function sendSignal(int $pid, int $signal)
    {
        if ($this->supportPC) {
            ProcessHelper::sendSignal($pid, $signal);
        }
    }

    /**
     * dispatchSignals
     */
    protected function dispatchSignals()
    {
        if ($this->supportPC) {
            pcntl_signal_dispatch();
        }
    }

    /**
     * set Process Title
     * @param string $title
     */
    protected function setProcessTitle(string $title)
    {
        if ($this->supportPC) {
            ProcessHelper::setProcessTitle($title);
        }
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * @return string
     */
    public function getPidFile(): string
    {
        return $this->pidFile;
    }

    /**
     * @return string
     */
    public function getPidRole()
    {
        return $this->isMaster ? 'Master' : 'Worker';
    }

    /**
     * savePidFile
     */
    protected function savePidFile()
    {
        if ($this->pidFile && !file_put_contents($this->pidFile, $this->pid)) {
            $this->stderr("Unable to write PID to the file {$this->pidFile}");
        }
    }

    /**
     * delete pidFile
     */
    protected function delPidFile()
    {
        if ($this->pidFile && file_exists($this->pidFile) && !unlink($this->pidFile)) {
            $this->stderr("Could not delete PID file: {$this->pidFile}", true, false);
        }
    }

    /**
     * @param string $pidFile
     * @return int
     */
    protected function getPidFromFile($pidFile)
    {
        if ($pidFile && file_exists($pidFile)) {
            return (int)trim(file_get_contents($pidFile));
        }

        return 0;
    }

    /**
     * checkEnvironment
     */
    protected function checkEnvironment()
    {
        $e1 = function_exists('posix_kill');
        $e2 = function_exists('pcntl_fork');

        if (!$e1 || !$e2) {
            $this->supportPC = false;

            $e1t = $e1 ? 'yes' : 'no';
            $e2t = $e2 ? 'yes' : 'no';

            $this->stdout("Is not support multi process of the current system. the posix($e1t),pcntl($e2t) extensions is required.\n");
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
     * stopWork
     */
    protected function stopWork()
    {
        $this->stopWork = true;
        $this->stat['stop_time'] = time();
    }

    /**
     * @return bool
     */
    public function isSupportPC(): bool
    {
        return $this->supportPC;
    }
}
