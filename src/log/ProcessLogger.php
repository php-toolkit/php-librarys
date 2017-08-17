<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/21
 * Time: 下午6:47
 */

namespace inhere\library\log;

use inhere\library\files\Directory;
use inhere\library\helpers\CliHelper;
use inhere\library\helpers\FormatHelper;

/**
 * Class ProcessLogger
 * @package inhere\process
 */
class ProcessLogger implements ProcessLogInterface
{
    /**
     * @var array
     */
    private $cache = [];
    private $count = 0;

    /**
     * @var int
     */
    protected $level = 4;

    /**
     * current log file
     * @var string
     */
    protected $file;

    /**
     * Holds the resource for the log file
     * @var resource
     */
    private $fileHandle;

    /**
     * will write log by `syslog()`
     * @var bool
     */
    protected $toSyslog = false;

    /**
     * @var bool
     */
    protected $toConsole = false;

    /**
     * 'day' 'hour', if is empty, not split.
     * @var string
     */
    protected $spiltType = '';

    /**
     * 日志写入阀值
     *  即是除了手动调用 self::flushAll() 或者 flush() 之外，当 self::$cache 存储到了阀值时，就会自动写入一次
     *  设为 0 则是每次记录都立即写入文件
     * @var int
     */
    protected $logThreshold = 1000;

    /**
     * Logging levels
     * @var array $levels Logging levels
     */
    protected static $levels = [
        self::EMERG => 'EMERGENCY',
        self::ERROR => 'ERROR',
        self::WARN => 'WARNING',
        self::INFO => 'INFO',
        self::PROC_INFO => 'PROC_INFO',
        self::WORKER_INFO => 'WORKER_INFO',
        self::DEBUG => 'DEBUG',
        self::CRAZY => 'CRAZY',
    ];

    /**
     * ProcessLogger constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $prop => $value) {
            $this->$prop = $value;
        }

        $this->init();
    }

    /**
     * init
     */
    protected function init()
    {
        $this->fileHandle = null;
        $this->level = (int)$this->level;
        $this->logThreshold = (int)$this->logThreshold;
        $this->toSyslog = (bool)$this->toSyslog;
        $this->toConsole = (bool)$this->toConsole;

        if ($this->file === 'syslog') {
            $this->file = null;
            $this->toSyslog = true;
        }

        if ($this->spiltType && !in_array($this->spiltType, [self::SPLIT_DAY, self::SPLIT_HOUR], true)) {
            $this->spiltType = self::SPLIT_DAY;
        }

        // open Log File
        $this->open();
    }

    /**
     * Debug log
     * @param  string $msg
     * @param  array $data
     */
    public function debug($msg, array $data = [])
    {
        $this->log($msg, self::DEBUG, $data);
    }

    /**
     * Exception log
     * @param \Exception $e
     * @param string $preMsg
     */
    public function ex(\Exception $e, $preMsg = '')
    {
        $preMsg = $preMsg ? "$preMsg " : '';

        $this->log(sprintf(
            "{$preMsg}Exception: %s On %s Line %s\nCode Trace:\n%s",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ), self::ERROR);
    }

    /**
     * Logs data to disk or stdout
     * @param string $msg
     * @param int $level
     * @param array $data
     * @return bool
     */
    public function log($msg, $level = self::INFO, array $data = [])
    {
        if ($level > $this->level) {
            return true;
        }

        $strData = $data ? json_encode($data) : '';

        if ($this->toSyslog) {
            return $this->sysLog($msg . ' ' . $strData, $level);
        }

        $label = self::$levels[$level] ?? self::INFO;
        $ds = FormatHelper::microTime(microtime(true));

        // [$this->getPidRole():$this->pid] $msg
        $logString = sprintf("[%s] [%s] %s %s\n", $ds, $label, trim($msg), $strData);

        // if not in daemon, print log to \STDOUT
        if (!$this->toConsole) {
            $this->stdout($logString, false);
        }

        if ($this->fileHandle) {
            $this->count++;
            $this->cache[] = $logString;

            if ($this->count >= $this->logThreshold || $this->fileIsChanged()) {
                $this->flush();
            }
        }

        return true;
    }

    /**
     * flush
     */
    public function flush()
    {
        if (!$this->cache) {
            return true;
        }

        $string = '';

        foreach ($this->cache as $log) {
            $string .= $log;
        }

        if ($string) {
            $this->updateLogFile();

            fwrite($this->fileHandle, $string);
        }

        $this->count = 0;
        $this->cache = [];
        return true;
    }

    protected function fileIsChanged()
    {
        if (!$this->fileHandle || !($file = $this->file)) {
            return false;
        }

        if (!$this->spiltType) {
            return false;
        }

        $str = $this->getLogFileDate();

        return !strpos($file, '_' . $str);
    }

    /**
     * update the log file name. If 'log_split' is not empty and manager running to long time.
     */
    protected function updateLogFile()
    {
        // update file. $dtStr is '_Y-m-d' or '_Y-m-d_H'
        if ($this->fileIsChanged()) {
            fclose($this->fileHandle);

            $logFile = $this->genLogFile(true);
            $this->file = $logFile;
            $this->fileHandle = @fopen($logFile, 'ab');

            if (!$this->fileHandle) {
                $this->stderr("Could not open the log file {$logFile}");
            }
        }
    }

    /**
     * Opens the log file. If already open, closes it first.
     */
    public function open()
    {
        if ($logFile = $this->genLogFile(true)) {
            if ($this->fileHandle) {
                fclose($this->fileHandle);
            }

            $this->file = $logFile;
            $this->fileHandle = @fopen($logFile, 'ab');

            if (!$this->fileHandle) {
                $this->stderr("Could not open the log file {$logFile}");
            }
        }
    }

    /**
     * close
     */
    public function close()
    {
        // close logFileHandle
        if ($this->fileHandle) {
            $this->flush();
            fclose($this->fileHandle);

            $this->fileHandle = null;
        }
    }

    /**
     * gen real LogFile
     * @param bool $createDir
     * @return string
     */
    public function genLogFile($createDir = false)
    {
        // log split type
        if (!($type = $this->spiltType) || !($file = $this->file)) {
            return $this->file;
        }

        $info = pathinfo($file);
        $dir = $info['dirname'];
        $name = $info['filename'] ?? 'gw-manager';
        $ext = $info['extension'] ?? 'log';

        if ($createDir) {
            Directory::mkdir($dir, 0775);
        }

        $str = $this->getLogFileDate();

        return "{$dir}/{$name}_{$str}.{$ext}";
    }

    /**
     * @return string
     */
    public function getLogFileDate()
    {
        $str = '';

        if ($this->spiltType === self::SPLIT_DAY) {
            $str = date('Y-m-d');
        } elseif ($this->spiltType === self::SPLIT_HOUR) {
            $str = date('Y-m-d_H');
        }

        return $str;
    }

    /**
     * Logs data to stdout
     * @param string $text
     * @param bool $nl
     * @param bool|int $quit
     */
    protected function stdout($text, $nl = true, $quit = false)
    {
        CliHelper::stdout($text, $nl, $quit);
    }

    /**
     * Logs data to stderr
     * @param string $text
     * @param bool $nl
     * @param bool|int $quit
     */
    protected function stderr($text, $nl = true, $quit = -200)
    {
        CliHelper::stderr($text, $nl, $quit);
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
            case self::EMERG:
                $priority = LOG_EMERG;
                break;
            case self::ERROR:
                $priority = LOG_ERR;
                break;
            case self::WARN:
                $priority = LOG_WARNING;
                break;
            case self::DEBUG:
                $priority = LOG_DEBUG;
                break;
            case self::INFO:
            case self::PROC_INFO:
            case self::WORKER_INFO:
            default:
                $priority = LOG_INFO;
                break;
        }

        if (!$ret = syslog($priority, $msg)) {
            $this->stderr("Unable to write to syslog\n");
        }

        return $ret;
    }

    /**
     * @return array
     */
    public static function getLevels()
    {
        return self::$levels;
    }

    /**
     * getFile
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->close();
    }
}
