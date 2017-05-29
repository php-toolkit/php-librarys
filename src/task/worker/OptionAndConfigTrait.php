<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-05-20
 * Time: 13:26
 */

namespace inhere\library\task\worker;

use inhere\library\helpers\CliHelper;

/**
 * Class OptionAndConfigTrait
 * @package inhere\library\task\worker
 */
trait OptionAndConfigTrait
{
    /**
     * @var string
     */
    private $fullScript;

    /**
     * @var string
     */
    private $script;

    /**
     * @var string
     */
    private $command;

    /**
     * @var array
     */
    private $cliOpts = [];

    /**
     * handle CLI command and load options
     */
    protected function parseCommandAndConfig()
    {
        $this->parseCliOptions();

        $command = $this->command;
        $supported = ['start', 'stop', 'restart', 'reload', 'status'];

        if (!in_array($command, $supported, true)) {
            $this->showHelp("The command [{$command}] is don't supported!");
        }

        // load CLI Options
        $this->loadCliOptions($this->cliOpts);

        // init Config And Properties
        $this->initConfigAndProperties($this->config);

        // Debug option to dump the config and exit
        if (isset($result['D']) || isset($result['dump'])) {
            $val = isset($result['D']) ? $result['D'] : (isset($result['dump']) ? $result['dump'] : '');
            $this->dumpInfo($val === 'all');
        }
    }

    /**
     * parseCliOptions
     */
    protected function parseCliOptions()
    {
        $result = CliHelper::parseOptArgs([
            'd', 'daemon', 'w', 'watch', 'h', 'help', 'V', 'version', 'no-test', 'watch-status'
        ]);
        $this->fullScript = implode(' ', $GLOBALS['argv']);
        $this->script = strpos($result[0], '.php') ? "php {$result[0]}" : $result[0];
        $this->command = $command = isset($result[1]) ? $result[1] : 'start';

        unset($result[0], $result[1]);

        $this->cliOpts = $result;
    }

    /**
     * @param $command
     * @return bool
     */
    protected function dispatchCommand($command)
    {
        $masterPid = $this->getPidFromFile($this->pidFile);
        $isRunning = $this->isRunning($masterPid);

        // start: do Start Server
        if ($command === 'start') {
            // check master process is running
            if ($isRunning) {
                $this->stderr("The worker manager has been running. (PID:{$masterPid})\n", true, -__LINE__);
            }

            return true;
        }

        // check master process
        if (!$isRunning) {
            $this->stderr("The worker manager is not running. can not execute the command: {$command}\n", true, -__LINE__);
        }

        // switch command
        switch ($command) {
            case 'stop':
            case 'restart':
                // stop: stop and exit. restart: stop and start
                $this->stopMaster($masterPid, $command === 'stop');
                break;
            case 'reload':
                // reload workers
                $this->reloadWorkers($masterPid);
                break;
            case 'status':
                $cmd = isset($result['cmd']) ? $result['cmd'] : 'status';
                $this->showStatus($cmd, isset($result['watch-status']));
                break;
            default:
                $this->showHelp("The command [{$command}] is don't supported!");
                break;
        }

        return true;
    }

    /**
     * load the command line options
     * @param array $opts
     */
    protected function loadCliOptions(array $opts)
    {
        $map = [
            'c' => 'conf_file',   // config file
            's' => 'servers', // server address

            'n' => 'workerNum',  // worker number do all tasks
            'u' => 'user',
            'g' => 'group',

            'l' => 'logFile',
            'p' => 'pidFile',

            'r' => 'maxRunTasks', // max run tasks for a worker
            'x' => 'maxLifetime',// max lifetime for a worker
            't' => 'timeout',
        ];

        // show help
        if (isset($opts['h']) || isset($opts['help'])) {
            $this->showHelp();
        }
        // show version
        if (isset($opts['V']) || isset($opts['version'])) {
            $this->showVersion();
        }

        // load opts values to config
        foreach ($map as $k => $v) {
            if (isset($opts[$k]) && $opts[$k]) {
                $this->config[$v] = $opts[$k];
            }
        }

        // load Custom Config File
        if ($file = $this->config['conf_file']) {
            if (!file_exists($file)) {
                $this->showHelp("Custom config file {$file} not found.");
            }

            $config = require $file;
            $this->setConfig($config);
        }

        // watch modify
        if (isset($opts['w']) || isset($opts['watch'])) {
            $this->config['watch_modify'] = $opts['w'];
        }

        // run as daemon
        if (isset($opts['d']) || isset($opts['daemon'])) {
            $this->config['daemon'] = true;
        }

        // no test
        if (isset($opts['no-test'])) {
            $this->config['no_test'] = true;
        }

        // only added tasks
        if (isset($opts['tasks']) && ($added = trim($opts['tasks'], ','))) {
            $this->config['added_tasks'] = strpos($added, ',') ? explode(',', $added) : [$added];
        }

        if (isset($opts['v'])) {
            $opts['v'] = $opts['v'] === true ? '' : $opts['v'];

            switch ($opts['v']) {
                case '':
                    $this->config['logLevel'] = self::LOG_INFO;
                    break;
                case 'v':
                    $this->config['logLevel'] = self::LOG_PROC_INFO;
                    break;
                case 'vv':
                    $this->config['logLevel'] = self::LOG_WORKER_INFO;
                    break;
                case 'vvv':
                    $this->config['logLevel'] = self::LOG_DEBUG;
                    break;
                case 'vvvv':
                    $this->config['logLevel'] = self::LOG_CRAZY;
                    break;
                default:
                    // $this->config['logLevel'] = self::LOG_INFO;
                    break;
            }
        }
    }

    /**
     * @param array $config
     */
    protected function initConfigAndProperties(array $config)
    {
        // init config attributes

        $this->config['daemon'] = (bool)$config['daemon'];
        $this->config['pidFile'] = trim($config['pidFile']);
        $this->config['workerNum'] = (int)$config['workerNum'];
        $this->config['servers'] = str_replace(' ', '', $config['servers']);

        $this->config['logLevel'] = (int)$config['logLevel'];
        $logFile = trim($config['logFile']);

        if ($logFile === 'syslog') {
            $this->config['logSyslog'] = true;
            $this->config['logFile'] = '';
        } else {
            $this->config['logFile'] = $logFile;
        }

        $this->config['timeout'] = (int)$config['timeout'];
        $this->config['maxLifetime'] = (int)$config['maxLifetime'];
        $this->config['maxRunTasks'] = (int)$config['maxRunTasks'];
        $this->config['restartSplay'] = (int)$config['restartSplay'];

        $this->config['user'] = trim($config['user']);
        $this->config['group'] = trim($config['group']);

        // config value fix ... ...

        if ($this->config['workerNum'] <= 0) {
            $this->config['workerNum'] = self::WORKER_NUM;
        }

        if ($this->config['maxLifetime'] < self::MIN_LIFETIME) {
            $this->config['maxLifetime'] = self::MAX_LIFETIME;
        }

        if ($this->config['maxRunTasks'] < self::MIN_RUN_TASKS) {
            $this->config['maxRunTasks'] = self::MAX_RUN_TASKS;
        }

        if ($this->config['restartSplay'] <= 100) {
            $this->config['restartSplay'] = self::RESTART_SPLAY;
        }

        if ($this->config['timeout'] <= self::MIN_TASK_TIMEOUT) {
            $this->config['timeout'] = self::TASK_TIMEOUT;
        }

        // init properties

        $this->name = trim($config['name']) ?: substr(md5(microtime()), 0, 7);
        $this->workerNum = $this->config['workerNum'];
        $this->maxLifetime = $this->config['maxLifetime'];
        $this->logLevel = $this->config['logLevel'];
        $this->pidFile = $this->config['pidFile'];

        unset($config);
    }

    /**
     * @return string
     */
    public function getFullScript()
    {
        return $this->fullScript;
    }

    /**
     * @return string
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }
}
