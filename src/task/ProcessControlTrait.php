<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/4/9 0009
 * Time: 23:39
 */

namespace inhere\library\task;

use inhere\library\process\ProcessUtil;

/**
 * Class ProcessControlTrait
 * @package inhere\library\task
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
        'startTime' => 0,
        'stopTime'  => 0,
        'startTimes' => 0,
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
            $this->pid = ProcessUtil::runAsDaemon();
        }
    }

    /**
     * @param int $pid
     * @return bool
     */
    protected function isRunning(int $pid)
    {
        if ($this->supportPC) {
            return ProcessUtil::isRunning($pid);
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
            ProcessUtil::sendSignal($pid, $signal);
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
            ProcessUtil::setTitle($title);
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
        $this->stat['stopTime'] = time();
    }

    /**
     * @return bool
     */
    public function isSupportPC(): bool
    {
        return $this->supportPC;
    }
}
