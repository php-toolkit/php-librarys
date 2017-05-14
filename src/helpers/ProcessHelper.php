<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/4/12 0012
 * Time: 23:52
 */

namespace inhere\library\helpers;

/**
 * Class ProcessHelper
 * @package inhere\library\helpers
 */
class ProcessHelper
{
    /**
     * Daemon, detach and run in the background
     * @param \Closure|null $beforeQuit
     */
    public static function runAsDaemon(\Closure $beforeQuit = null)
    {
        // umask(0);

        // fork new process
        $pid = pcntl_fork();

        switch ($pid) {
            case 0: // at new process
                // $pid = getmypid(); // can also use: posix_getpid()

                if(posix_setsid() < 0) {
                    throw new \RuntimeException('posix_setsid() execute failed! exiting');
                }

//                chdir('/');
//                umask(0);

                break;

            case -1: // fork failed.
                throw new \RuntimeException('Fork new process is failed! exiting');
                break;

            default: // at parent
                if ($beforeQuit) {
                    $beforeQuit($pid);
                }

                exit;
        }
    }

    /**
     * fork multi process
     * @param $number
     * @return array|int
     */
    public static function spawn($number)
    {
        $num = (int)$number >= 0 ? (int)$number : 0;

        if ($num <= 0) {
            return posix_getpid();
        }

        $pidAry = array();

        for ($i = 0; $i < $num; $i++) {
            $pid = pcntl_fork();

            if ($pid > 0) {// at parent, get forked child PID
                $pidAry[] = $pid;
            } else {
                break;
            }
        }

        return $pidAry;
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
        CliHelper::stdout("Stop the $name(PID:$pid)");

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

        // stop success
        CliHelper::stdout(sprintf("\n%s\n"), CliHelper::color("The $name process stopped", CliHelper::FG_GREEN));

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

//////////////////////////////////////////////////////////////////////
/// some help method
//////////////////////////////////////////////////////////////////////

    /**
     * @return int
     */
    public static function getMasterPID()
    {
        return posix_getpid();
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
    public static function setProcessTitle($title)
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
     * get Pid By PidFile
     * @param string $pidFile
     * @return int
     */
    public static function getPidByPidFile($pidFile)
    {
        if ($pidFile && file_exists($pidFile)) {
            $pid = (int)file_get_contents($pidFile);

            // check
            if (posix_getpgid($pid)) {
                return $pid;
            }

            unlink($pidFile);
        }

        return 0;
    }

}
