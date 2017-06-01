<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/4/12 0012
 * Time: 23:52
 */

namespace inhere\library\process;

use inhere\library\helpers\CliHelper;
use inhere\library\helpers\PhpHelper;

/**
 * Class ProcessUtil
 * @package inhere\library\process
 */
class ProcessUtil
{
    /**
     * Daemon, detach and run in the background
     * @param \Closure|null $beforeQuit
     * @return int Return new process PID
     */
    public static function runAsDaemon(\Closure $beforeQuit = null)
    {
        // umask(0);
        $pid = pcntl_fork();

        switch ($pid) {
            case 0: // at new process
                $pid = getmypid(); // can also use: posix_getpid()

                if(posix_setsid() < 0) {
                    CliHelper::stderr('posix_setsid() execute failed! exiting');
                }

                // chdir('/');
                // umask(0);
                break;

            case -1: // fork failed.
                CliHelper::stderr('Fork new process is failed! exiting');
                break;

            default: // at parent
                if ($beforeQuit) {
                    $beforeQuit($pid);
                }

                exit;
        }

        return $pid;
    }

    /**
     * fork multi child processes.
     * @param int $number
     * @param callable|null $childHandler
     * @return array|int
     */
    public static function forks($number, callable $childHandler = null)
    {
        $num = (int)$number > 0 ? (int)$number : 0;

        if ($num <= 0) {
            return false;
        }

        $pidAry = [];

        for ($id = 0; $id < $num; $id++) {
            $child = self::fork($id, $childHandler);
            $pidAry[$child['pid']] = $child;
        }

        return $pidAry;
    }

    /**
     * fork a child process.
     * @param int $id
     * @param callable|null $childHandler
     * param bool $first
     * @return array
     */
    public static function fork($id, callable $childHandler = null)
    {
        $info = [];
        $pid = pcntl_fork();

        if ($pid > 0) {// at parent, get forked child info
            $info = [
                'id'  => $id,
                'pid' => $pid,
                'startTime' => time(),
            ];
        } elseif ($pid == 0) { // at child
            $pid = getmypid();

            if ($childHandler) {
                call_user_func($childHandler, $id, $pid);
            }

        } else {
            CliHelper::stderr("Fork child process failed! exiting.\n");
        }

        return $info;
    }

    /**
     * wait child exit.
     * @param  callable $onExit
     * @return bool
     */
    public static function wait(callable $onExit)
    {
        $status = null;

        //pid<0：子进程都没了
        //pid>0：捕获到一个子进程退出的情况
        //pid=0：没有捕获到退出的子进程
        while (($pid = pcntl_waitpid(-1, $status, WNOHANG)) >= 0) {
            if ($pid) {
                // ... (callback, pid, exitCode, status)
                call_user_func($onExit, $pid, pcntl_wexitstatus($status), $status);
            } else {
                usleep(50000);
            }
        }

        return true;
    }

    /**
     * Stops all running children
     * @param array $children
     * [
     *  'pid' => [
     *      'id' => worker id
     *  ],
     *  ... ...
     * ]
     * @param int $signal
     * @param array $events
     * [
     *   'beforeStops' => function ($sigText) {
     *      echo "Stopping processes({$sigText}) ...\n";
     *  },
     *  'beforeStop' => function ($pid, $info) {
     *      echo "Stopping process(PID:$pid)\n";
     *  }
     * ]
     * @return bool
     */
    public static function stopChildren(array $children, $signal = SIGTERM, array $events = [])
    {
        if (!$children) {
            return false;
        }

        $events = array_merge([
            'beforeStops' => null,
            'beforeStop' => null,
        ], $events);
        $signals = [
            SIGINT => 'SIGINT(Ctrl+C)',
            SIGTERM => 'SIGTERM',
            SIGKILL => 'SIGKILL',
        ];

        if ($cb = $events['beforeStops']) {
            $cb($signal, $signals[$signal]);
        }

        foreach ($children as $pid => $child) {
            if ($cb = $events['beforeStop']) {
                $cb($pid, $child);
            }

            // send exit signal.
            self::sendSignal($pid, $signal);
        }

        return true;
    }

//////////////////////////////////////////////////////////////////////
/// basic signal method
//////////////////////////////////////////////////////////////////////

    /**
     * send kill signal to the process
     * @param int $pid
     * @param int $signal
     * @param int $timeout
     * @return bool
     */
    public static function kill($pid, $signal = SIGTERM, $timeout = 3)
    {
        return self::sendSignal($pid, $signal, $timeout);
    }

    /**
     * Do shutdown process and wait it exit.
     * @param  int $pid Master Pid
     * @param int $signal
     * @param string $name
     * @param int $waitTime
     * @return bool
     */
    public static function killAndWait($pid, $signal = SIGTERM, $name = 'process', $waitTime = 30)
    {
        // do stop
        if (!self::kill($signal, SIGTERM)) {
            CliHelper::stderr("Send stop signal to the $name(PID:$pid) failed!");

            return false;
        }

        // not wait, only send signal
        if ($waitTime <= 0) {
            CliHelper::stdout("The $name process stopped");

            return true;
        }

        $startTime = time();
        CliHelper::stdout('Stopping .', false);

        // wait exit
        while (true) {
            if (!self::isRunning($pid)) {
                break;
            }

            if (time() - $startTime > $waitTime) {
                CliHelper::stderr("Stop the $name(PID:$pid) failed(timeout)!");
                break;
            }

            CliHelper::stdout('.', false);
            sleep(1);
        }

        return true;
    }

    /**
     * 杀死所有进程
     * @param $name
     * @param int $sigNo
     * @return string
     */
    public static function killByName($name, $sigNo = 9)
    {
        $cmd = 'ps -eaf |grep "' . $name . '" | grep -v "grep"| awk "{print $2}"|xargs kill -' . $sigNo;
        return exec($cmd);
    }

    /**
     * send signal to the process
     * @param int $pid
     * @param int $signal
     * @param int $timeout
     * @return bool
     */
    public static function sendSignal($pid, $signal, $timeout = 3)
    {
        if ($pid <= 0) {
            return false;
        }

        // do send
        if ($ret = posix_kill($pid, $signal)) {
            return true;
        }

        // don't want retry
        if ($timeout <= 0) {
            return $ret;
        }

        // failed, try again ...

        $timeout = $timeout > 0 && $timeout < 10 ? $timeout : 3;
        $startTime = time();

        // retry stop if not stopped.
        while (true) {
            // success
            if (!$isRunning = @posix_kill($pid, 0)) {
                break;
            }

            // have been timeout
            if ((time() - $startTime) >= $timeout) {
                return false;
            }

            // try again kill
            $ret = posix_kill($pid, $signal);
            usleep(10000);
        }

        return $ret;
    }

    /**
     * @param $pid
     * @return bool
     */
    public static function isRunning($pid)
    {
        return ($pid > 0) && @posix_kill($pid, 0);
    }

    /**
     * exit
     * @param int $code
     */
    public static function quit($code = 0)
    {
        exit((int)$code);
    }

//////////////////////////////////////////////////////////////////////
/// some help method
//////////////////////////////////////////////////////////////////////

    /**
     * get current process id
     * @return int
     */
    public static function getPid()
    {
        return posix_getpid();// or use getmypid()
    }

    /**
     * Get unix user of current process.
     *
     * @return array
     */
    public static function getCurrentUser()
    {
        return posix_getpwuid(posix_getuid());
    }

    /**
     * Set process title.
     * @param string $title
     * @return bool
     */
    public static function setTitle($title)
    {
        if (PhpHelper::isMac()) {
            return false;
        }

        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($title);
        }

        return true;
    }

    /**
     * Set unix user and group for current process script.
     * @param string $user
     * @param string $group
     */
    public static function changeScriptOwner($user, $group = '')
    {
        $uInfo = posix_getpwnam($user);

        if (!$uInfo || !isset($uInfo['uid'])) {
            throw new \RuntimeException("User ({$user}) not found.");
        }

        $uid = (int)$uInfo['uid'];

        // Get gid.
        if ($group) {
            if (!$gInfo = posix_getgrnam($group)) {
                throw new \RuntimeException("Group {$group} not exists", -300);
            }

            $gid = (int)$gInfo['gid'];
        } else {
            $gid = (int)$uInfo['gid'];
        }

        if (!posix_initgroups($uInfo['name'], $gid)) {
            throw new \RuntimeException("The user [{$user}] is not in the user group ID [GID:{$gid}]", -300);
        }

        posix_setgid($gid);

        if (posix_geteuid() !== $gid) {
            throw new \RuntimeException("Unable to change group to {$user} (UID: {$gid}).", -300);
        }

        posix_setuid($uid);

        if (posix_geteuid() !== $uid) {
            throw new \RuntimeException("Unable to change user to {$user} (UID: {$uid}).", -300);
        }
    }

    /**
     * 获取资源消耗
     * @param int $startTime
     * @param int $startMem
     * @return array
     */
    public static function runtime($startTime, $startMem)
    {
        // 显示运行时间
        $return['time'] = number_format(microtime(true) - $startTime, 4) . 's';
        $startMem = (int)array_sum(explode(' ', $startMem));
        $endMem = array_sum(explode(' ', memory_get_usage()));
        $return['memory'] = number_format(($endMem - $startMem) / 1024) . 'kb';

        return $return;
    }

    /**
     * get Pid from File
     * @param string $pidFile
     * @param bool $check
     * @return int
     */
    public static function getPidFromFile($pidFile, $check = false)
    {
        if ($pidFile && file_exists($pidFile)) {
            $pid = (int)file_get_contents($pidFile);

            // check
            if ($check && self::isRunning($pid)) {
                return $pid;
            }

            unlink($pidFile);
        }

        return 0;
    }

}
