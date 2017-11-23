<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/9/27
 * Time: 14:17
 */

namespace Inhere\Library\Utils;

use Inhere\Exceptions\FileSystemException;
use Inhere\Library\Files\Directory;
use Inhere\Library\Helpers\Arr;
use Inhere\Library\Helpers\Obj;
use Inhere\Library\Helpers\Php;
use Inhere\Library\Traits\LogProfileTrait;
use Inhere\Library\Traits\PathResolverTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Class LiteLogger - simple file logger handler
 * @package Inhere\Library\Utils
 * ```
 * $config = [...];
 * $logger = LiteLogger::make($config);
 * $logger->info(...);
 * $logger->debug(...);
 * ......
 *
 * ```
 */
class LiteLogger implements LoggerInterface
{
    use LoggerTrait, LogProfileTrait, PathResolverTrait;

    // * Log runtime info
    const TRACE = 50;

    // Detailed debug information
    const DEBUG = 100;

    // Interesting events
    const INFO = 200;

    // Uncommon events
    const NOTICE = 250;

    // Exceptional occurrences that are not errors
    const WARNING = 300;

    // Runtime errors
    const ERROR = 400;

    // * Runtime exceptions
    const EXCEPTION = 450;

    // Critical conditions
    const CRITICAL = 500;

    // Action must be taken immediately
    const ALERT = 550;

    // Urgent alert.
    const EMERGENCY = 600;

    // default format
    const DEFAULT_FORMAT = "[%datetime%] [%channel%.%level_name%] {message} {context} {extra}\n";

    /**
     * Logging levels from syslog protocol defined in RFC 5424
     * @var array $levels Logging levels
     */
    protected static $levelMap = [
        self::TRACE => 'trace',
        self::DEBUG => 'debug',
        self::INFO => 'info',
        self::NOTICE => 'notice',
        self::WARNING => 'warning',
        self::ERROR => 'error',
        self::EXCEPTION => 'exception',
        self::CRITICAL => 'critical',
        self::ALERT => 'alert',
        self::EMERGENCY => 'emergency',
    ];

    /**
     * 日志实例名称
     * @var string
     */
    public $name;

    /**
     * log text records list
     * @var array[]
     */
    private $records = [];

    /** @var int  */
    private $recordSize = 0;

    /**
     * 存放日志的基础路径
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    public $logFile = 'default.log';

    /**
     * allow multi line for a record
     * @var bool
     */
    public $allowMultiLine = true;

    /**
     * log path = $bashPath + $subFolder
     * 文件夹名称
     * @var string
     */
    protected $subFolder;

    /**
     * 日志文件名称处理
     * @var \Closure
     */
    protected $filenameHandler;

    /** @var int log Level */
    protected $level = 0;

    /**
     * @var string 'day' 'hour', if is empty, not split
     */
    public $splitType = 'day';

    /**
     * log print to console (when on the CLI is valid.)
     * @var bool
     */
    public $logConsole = false;

    /**
     * 日志写入阀值
     *  即是除了手动调用 flush() 之外，当 $records 存储到了阀值时，就会自动写入一次
     *  设为 0 则是每次记录都立即写入文件
     * @var int
     */
    public $bufferSize = 1000;

    /** @var int The log file max size(Mb) */
    public $fileMaxSize = 2;

    /**
     * 格式
     * @var string
     */
    public $format = "[%datetime%] [%level_name%] %message% %context%\n";

    /** @var bool  */
    private $initialized = false;

//////////////////////////////////////////////////////////////////////
/// loggers manager
//////////////////////////////////////////////////////////////////////

    /**
     * create new logger instance
     * @param array $config
     * @return static
     */
    public static function make(array $config = [])
    {
        return new static($config);
    }

//////////////////////////////////////////////////////////////////////
/// logic methods
//////////////////////////////////////////////////////////////////////

    /**
     * create new logger instance
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        Obj::init($this, $config);

        $this->init();
    }

    protected function init()
    {
        if (!$this->initialized) {
            // __destructor() doesn't get called on Fatal errors
            register_shutdown_function([$this, 'flush']);
            $this->initialized = true;
        }
    }

    /**
     * destruct
     */
    public function __destruct()
    {
        $this->flush();
    }

    public function emerg($message, array $context = [])
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * @param \Exception $e
     * @param array $context
     * @param bool $logRequest
     */
    public function ex(\Exception $e, array $context = [], $logRequest = true)
    {
        $this->exception($e, $context, $logRequest);
    }
    public function exception(\Exception $e, array $context = [], $logRequest = true)
    {
        $message = sprintf(
            "Exception(%d): %s\nCalled At %s, Line: %d\nCatch the exception by: %s\nCode Trace:\n%s",
            $e->getCode(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            \get_class($e),
            $e->getTraceAsString()
        );

        // If log the request info
        if ($logRequest) {
//            $message .= "\nRequest Info:\n  " . implode("\n  ", [
//                'HOST ' . Php::serverParam('HTTP_HOST'),
//                'IP ' . Php::serverParam('REMOTE_ADDR'),
//                'METHOD ' . Php::serverParam('REQUEST_METHOD'),
//                'URI ' . Php::serverParam('REQUEST_URI'),
//                'REFERRER ' . Php::serverParam('HTTP_REFERER'),
//            ]) . "\n";

            $context['request'] = $_REQUEST;
        }

        $this->log(self::EXCEPTION, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function trace($message = '', array $context = [])
    {
        if (!$this->isCanRecord(self::TRACE)) {
            return;
        }

        if (!isset($context['_called_at'])) {
            $file = $method = $line = 'Unknown';
            $data = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            if (isset($data[1])) {
                $file = $data[0]['file'];
                $line = $data[0]['line'];
                $t = $data[1];
                $method = Arr::remove($t, 'class', 'CLASS') . '::' . Arr::remove($t, 'function', 'METHOD');
            }

            $context['_called_at'] = "$method, On $file line $line";
        }

        $this->log(self::TRACE, $message, $context);
    }

    /**
     * record log info to file
     * @param int $level
     * @param string $message
     * @param array $context
     * @param array $extra
     * @throws \InvalidArgumentException
     */
    public function log($level, $message, array $context = [], array $extra = [])
    {
        if (!$this->isCanRecord($level)) {
            return;
        }

        if (!$this->name) {
            throw new \InvalidArgumentException('Logger name is required.');
        }

        $levelName = self::getLevelName($level);
        $record = [
            'message' => trim($message),
            'context' => $context,
            'level' => $level,
            'level_name' => $levelName,
            'channel' => $this->name,
            'datetime' => date('Y-m-d H:i:s'),
            'extra' => $extra,
        ];

        // serve is running in php build in server env.
        if ($this->logConsole && (Php::isBuiltInServer() || Php::isCli())) {
            \defined('STDOUT') or \define('STDOUT', fopen('php://stdout', 'wb'));
            fwrite(STDOUT, "[{$record['datetime']}] [$levelName] $message\n");
        }

        $this->records[] = $record;
        $this->recordSize++;

        // 检查阀值
        if ($this->recordSize > $this->bufferSize) {
            $this->flush();
        }
    }

    /**
     * flush data to file.
     * @throws \Inhere\Exceptions\FileSystemException
     */
    public function flush()
    {
        if ($this->recordSize === 0) {
            return;
        }

        $str = '';
        foreach ($this->records as $record) {
            $str .= $this->recordFormat($record);
        }

        $this->clear();
        $this->write($str);
    }

    public function clear()
    {
        $this->recordSize = 0;
        $this->records = [];
    }

    /**
     * @param array $record
     * @return string
     */
    protected function recordFormat(array $record)
    {
        $output = $this->format ?: self::DEFAULT_FORMAT;
        $record['level_name'] = strtoupper($record['level_name']);
        $record['channel'] = strtoupper($record['channel']);
        $record['context'] = $record['context'] ? json_encode($record['context']) : '';
        $record['extra'] = $record['extra'] ? json_encode($record['extra']) : '';

        foreach ($record as $var => $val) {
            if (false !== strpos($output, '%' . $var . '%')) {
                $output = str_replace('%' . $var . '%', $this->stringify($val), $output);
            }
        }

        // remove leftover %extra.xxx% and %context.xxx% if any
        if (false !== strpos($output, '%')) {
            $output = preg_replace('/%(?:extra|context)\..+?%/', '', $output);
        }

        return $output;
    }

    /**
     * write log info to file
     * @param string $str
     * @return bool
     * @throws \InvalidArgumentException
     * @throws FileSystemException
     */
    protected function write($str)
    {
        $file = $this->getLogPath() . $this->getFilename();
        $dir = \dirname($file);

        if (!Directory::create($dir)) {
            throw new FileSystemException("Create log directory failed. $dir");
        }

        // check file size
        if (is_file($file) && filesize($file) > $this->fileMaxSize * 1000 * 1000) {
            rename($file, substr($file, 0, -3) . time() . '.log');
        }

        // return error_log($str, 3, $file);
        return file_put_contents($file, $str, FILE_APPEND);
    }

    /**
     * @param $level
     * @return bool
     */
    protected function isCanRecord($level)
    {
        return self::getLevelByName($level) >= $this->level;
    }

    /**
     * @return \array[]
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param bool $resolve
     * @return string
     */
    public function getBasePath($resolve = true)
    {
        return $resolve ? $this->resolverPath($this->basePath) : $this->basePath;
    }

    /**
     * @param string $basePath
     */
    public function setBasePath(string $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * get log path
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getLogPath()
    {
        if (!$this->basePath) {
            throw new \InvalidArgumentException('The property basePath is required.');
        }

        return $this->getBasePath() . '/' . ($this->subFolder ? $this->subFolder . '/' : '');
    }

    /**
     * 设置日志文件名处理
     * @param \Closure $handler
     * @return $this
     */
    public function setFilenameHandler(\Closure $handler)
    {
        $this->filenameHandler = $handler;

        return $this;
    }

    /**
     * 得到日志文件名
     * @return string
     */
    public function getFilename()
    {
        if ($handler = $this->filenameHandler) {
            return $handler($this);
        }

        if ($this->splitType === 'hour') {
            return $this->name . '.' . date('Ymd_H') . '.log';
        }

        return $this->name . '.' . date('Ymd') . '.log';
    }

    /**
     * Gets all supported logging levels.
     * @return array Assoc array with human-readable level names => level codes.
     */
    public static function getLevelMap()
    {
        return static::$levelMap;
    }

    /**
     * @param int|string $level
     * @return mixed|string
     */
    public static function getLevelName($level)
    {
        if (\is_string($level) && !is_numeric($level)) {
            return $level;
        }

        return self::$levelMap[$level] ?? 'info';
    }

    /**
     * @param string $name
     * @return mixed|string
     */
    public static function getLevelByName($name)
    {
        static $nameMap;

        if (is_numeric($name)) {
            return (int)$name;
        }

        if (!$nameMap) {
            $nameMap = array_flip(self::$levelMap);
        }

        $name = strtolower($name);

        return $nameMap[$name] ?? 0;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function stringify($value)
    {
        return $this->replaceNewlines($this->convertToString($value));
    }

    /**
     * @param $data
     * @return mixed|string
     */
    protected function convertToString($data)
    {
        if (null === $data || \is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return (string)$data;
        }

        if (PHP_VERSION_ID >= 50400) {
            return json_encode($data);
        }

        return str_replace('\\/', '/', json_encode($data));
    }

    /**
     * @param $str
     * @return mixed
     */
    protected function replaceNewlines($str)
    {
        if ($this->allowMultiLine) {
            if (0 === strpos($str, '{')) {// json ?
                return str_replace(array('\r', '\n'), array("\r", "\n"), $str);
            }

            return $str;
        }

        return str_replace(array("\r\n", "\r", "\n"), ' ', $str);
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel($level)
    {
        $this->level = self::getLevelByName($level);
    }
}
