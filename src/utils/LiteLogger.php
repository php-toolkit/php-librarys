<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/9/27
 * Time: 14:17
 */

namespace inhere\library\utils;

use inhere\exceptions\FileSystemException;
use inhere\library\helpers\PhpHelper;
use Psr\Log\LoggerInterface;

/**
 * simple file logger handler
 * Class LiteLogger
 * @package inhere\library\utils
 * ```
 * $config = [...];
 * $logger = LiteLogger::make($config);
 * $logger->info(...);
 * $logger->debug(...);
 *
 * ......
 *
 * // Notice: must call LiteLogger::flushAll() on application run end.
 * LiteLogger::flushAll();
 * ```
 */
class LiteLogger implements LoggerInterface
{
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

    /**
     * Logging levels from syslog protocol defined in RFC 5424
     * @var array $levels Logging levels
     */
    protected static $levelMap = array(
        self::TRACE     => 'trace',
        self::DEBUG     => 'debug',
        self::INFO      => 'info',
        self::NOTICE    => 'notice',
        self::WARNING   => 'warning',
        self::ERROR     => 'error',
        self::EXCEPTION => 'exception',
        self::CRITICAL  => 'critical',
        self::ALERT     => 'alert',
        self::EMERGENCY => 'emergency',
    );

    /**
     * logger instance list
     * @var static[]
     */
    private static $loggers = [];

    /**
     * @var bool
     */
    private static $shutdownRegistered = false;

    /**
     * log text records list
     * @var array[]
     */
    private $_records = [];

    /**
     * 日志实例名称 channel name
     * @var string
     */
    public $name;

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
     * 存放日志的基础路径
     * @var string
     */
    protected $basePath;

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

    /**
     * @var array
     */
    protected $levels = [];

    /**
     * log Level
     * @var int
     */
    protected $logLevel = 0;

    /**
     * @var bool
     */
    public $splitByCopy = true;

    /**
     * @var string 'day' 'hour', if is empty, not split
     */
    public $splitType = 'day';

    /**
     * file content max size. (M)
     * @var int
     */
    public $maxSize = 4;

    /**
     * @var integer Number of log files used for rotation. Defaults to 20.
     */
    public $maxFiles = 20;

    /**
     * log print to console (when on the CLI is valid.)
     * @var bool
     */
    public $logConsole = false;

    /**
     * 日志写入阀值
     *  即是除了手动调用 self::flushAll() 或者 flush() 之外，当 self::$_records 存储到了阀值时，就会自动写入一次
     *  设为 0 则是每次记录都立即写入文件
     * @var int
     */
    public $logThreshold = 1000;

    /**
     * 格式
     * @var string
     */
    public $format = "[%datetime%] [%level_name%] %message% %context%\n";

    /**
     * default format
     */
    const DEFAULT_FORMAT = "[%datetime%] [%channel%.%level_name%] {message} {context} {extra}\n";

//////////////////////////////////////////////////////////////////////
/// loggers manager
//////////////////////////////////////////////////////////////////////

    /**
     * create new instance or get exists instance
     * @param string|array $config
     * @param string $name
     * @return static
     */
    public static function make(array $config = [], $name = '')
    {
        if (!is_array($config)) {
            throw new \InvalidArgumentException('Logger config is must be an array and not allow empty.');
        }

        $name = $name ? : ($config['name'] ?? '');

        if (!$name) {
            throw new \InvalidArgumentException('Logger name is required.');
        }

        if (!isset(self::$loggers[$name])) {
            self::$loggers[$name] = new static($config, $name);
        }

        // register shutdown function
        if (!self::$shutdownRegistered) {
            register_shutdown_function(function () {
                // make regular flush before other shutdown functions, which allows session data collection and so on
                self::flushAll();

                // make sure log entries written by shutdown functions are also flushed
                // ensure "flush()" is called last when there are multiple shutdown functions
                register_shutdown_function([self::class, 'flushAll'], true);
            });

            self::$shutdownRegistered = true;
        }

        return self::$loggers[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function has(string $name)
    {
        return isset(self::$loggers[$name]);
    }

    /**
     * exists logger instance
     * @return bool
     */
    public static function count()
    {
        return count(self::$loggers) > 0;
    }

    /**
     * @param $name
     * @param bool $make
     * @return static|null
     */
    public static function get(string $name, bool $make = true)
    {
        if (self::has($name)) {
            return self::$loggers[$name];
        }

        return $make ? self::make($name) : null;
    }

    /**
     * @return array
     */
    public static function getLoggerNames()
    {
        return array_keys(self::$loggers);
    }

    /**
     * del logger
     * @param  string $name
     * @param  bool|boolean $flush
     * @return bool
     */
    public static function del(string $name, bool $flush = true)
    {
        if (isset(self::$loggers[$name])) {
            $logger = self::$loggers[$name];

            return $flush ? $logger->flush() : true;
        }

        return false;
    }

    /**
     * fast get logger instance
     * @param string $name
     * @param array $args
     * @return LiteLogger
     */
    public static function __callStatic(string $name, array $args)
    {
        $args['name'] = $name;

        return self::make($args);
    }

    /**
     * save all logger's info to files.
     */
    public static function flushAll()
    {
        foreach (self::$loggers as $logger) {
            $logger->flush();
        }
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
        if (is_string($level) && !is_numeric($level)) {
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
     * @return static[]
     */
    public static function getLoggers()
    {
        return self::$loggers;
    }

//////////////////////////////////////////////////////////////////////
/// logic methods
//////////////////////////////////////////////////////////////////////

    /**
     * create new logger instance
     * @param array     $config
     * @param null|string $name
     */
    public function __construct(array $config = [], $name = null)
    {
        if (!$name) {
            throw new \InvalidArgumentException('Logger name is required.');
        }

        $this->name = $name;

        // attributes
        $attributes = [
            'logConsole', 'logThreshold', 'debug', 'logFile', 'basePath', 'subFolder',
            'format', 'splitType', 'splitByCopy', 'logLevel', 'levels'
        ];

        foreach ($attributes as $prop) {
            if (isset($config[$prop])) {
                $setter = 'set' . ucfirst($prop);

                if (method_exists($this, $setter)) {
                    $this->$setter($config[$prop]);
                } else {
                    $this->$prop = $config[$prop];
                }
            }
        }

        $this->init();
    }

    protected function init()
    {
        if ($this->maxFiles < 1) {
            $this->maxFiles = 10;
        }

        if ($this->maxSize < 1) {
            $this->maxSize = 4;
        }
    }

    /**
     * destruct
     */
    public function __destruct()
    {
        $this->flush();
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function emergency($message, array $context = array())
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    public function emerg($message, array $context = [])
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function critical($message, array $context = array())
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log(self::ERROR, $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log(self::ALERT, $message, $context);
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
            get_class($e),
            $e->getTraceAsString()
        );

        // If log the request info
        if ($logRequest) {
            $message .= "\nRequest Info:\n  " . implode("\n  ", [
                    'HOST ' . $this->getServer('HTTP_HOST'),
                    'IP ' . $this->getServer('REMOTE_ADDR'),
                    'METHOD ' . $this->getServer('REQUEST_METHOD'),
                    'URI ' . $this->getServer('REQUEST_URI'),
                    'REFERRER ' . $this->getServer('HTTP_REFERER'),
                ]) . "\n";

            $context['request'] = $_REQUEST;
        }

        $this->log('exception', $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function trace($message = '', array $context = [])
    {
        // 不在记录的级别内
        if ($this->isCanRecord(self::TRACE)) {
            return null;
        }

        $file = $method = $line = 'Unknown';

        if ($data = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)) {
            if (isset($data[0]['file'])) {
                $file = $data[0]['file'];
            }

            if (isset($data[0]['line'])) {
                $line = $data[0]['line'];
            }

            if (isset($data[1])) {
                $t = $data[1];
                $method = self::arrayRemove($t, 'class', 'CLASS') . '::' . self::arrayRemove($t, 'function', 'METHOD');
            }
        }

        $message .= "\n  Function: $method\n  Position $file, At Line $line\n  Trace:";
        $this->log(self::TRACE, $message, $context);

        return true;
    }

    public function warning($message, array $context = [])
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log(self::NOTICE, $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log(self::INFO, $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * record log info to file
     * @param int $level
     * @param string $message
     * @param array $context
     * @param array $extra
     * @return null|bool
     */
    public function log($level, $message, array $context = [], array $extra = [])
    {
        // 不在记录的级别内
        if (!$this->isCanRecord($level)) {
            return null;
        }

        $levelName = self::getLevelName($level);
        $record = array(
            'message' => (string) $message,
            'context' => $context,
            'level' => $level,
            'level_name' => $levelName,
            'channel' => $this->name,
            'datetime' => date('Y-m-d H:i:s'),
            'extra' => $extra,
        );

        // serve is running in php build in server env.
        if ($this->logConsole && (PhpHelper::isBuiltInServer() || PhpHelper::isCli())) {
            defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'wb'));
            fwrite(STDOUT, "[{$record['datetime']}] [$levelName] $message\n");
        }

        $this->_records[] = $record;

        // 检查阀值
        if ($this->logThreshold > 0 && count($this->_records) >= $this->logThreshold) {
            return $this->flush();
        }

        return null;
    }

    /**
     * flush data to file.
     * @return bool
     */
    public function save()
    {
        return $this->flush();
    }
    public function flush()
    {
        if (!$this->_records) {
            return true;
        }

        $str = '';
        foreach ($this->_records as $record) {
            $str .= $this->recordFormat($record);
        }

        $this->write($str);
        $this->_records = [];
        unset($str);

        return true;
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
        $record['extra']   = $record['extra'] ? json_encode($record['extra']) : '';

        foreach ($record as $var => $val) {
            if (false !== strpos($output, '%'.$var.'%')) {
                $output = str_replace('%'.$var.'%', $this->stringify($val), $output);
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
     * @throws FileSystemException
     */
    protected function write($str)
    {
        $file = $this->getLogPath() . $this->getFilename();
        $dir = dirname($file);

        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            throw new FileSystemException("Create log directory failed. $dir");
        }

        // check file size
        if (is_file($file) && filesize($file) > $this->maxSize * 1000 * 1000) {
            rename($file, substr($file, 0, -3) . time() . '.log');
        }

        // return error_log($str, 3, $file);
        return file_put_contents($file, $str, FILE_APPEND);
    }

    /**
     * @param array|string $levels
     */
    public function setLevels($levels)
    {
        if (is_array($levels)) {
            $this->levels = $levels;
        } elseif (is_string($levels)) {
            $levels = trim($levels, ', ');

            $this->levels = strpos($levels, ',') ? array_map('trim', explode(',', $levels)) : [$levels];
        }
    }

    /**
     * @param $level
     * @return bool
     */
    protected function isCanRecord($level)
    {
        // 不在记录的级别内
        return $this->levels && in_array(self::getLevelName($level), $this->levels, true);
    }

    /**
     * @return \array[]
     */
    public function getRecords()
    {
        return $this->_records;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @return array
     */
    public function getLevels()
    {
        return $this->levels;
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

        return $this->basePath . '/' . ($this->subFolder ? $this->subFolder . '/' : '');
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
     * get value and unset it
     * @param $arr
     * @param $key
     * @param null $default
     * @return null
     */
    public static function arrayRemove($arr, $key, $default = null)
    {
        if (isset($arr[$key])) {
            $value = $arr[$key];
            unset($arr[$key]);

            return $value;
        }

        return $default;
    }

    /**
     * get value from $_SERVER
     * @param $name
     * @param string $default
     * @return string
     */
    public function getServer($name, $default = '')
    {
        return $_SERVER[$name] ?? $default;
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
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return (string) $data;
        }

        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
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
     * Rotates log files.
     */
    protected function splitFiles()
    {
        $file = $this->logFile;

        for ($i = $this->maxFiles; $i >= 0; --$i) {
            // $i == 0 is the original log file
            $rotateFile = $file . ($i === 0 ? '' : '.' . $i);

            if (is_file($rotateFile)) {
                // suppress errors because it's possible multiple processes enter into this section
                if ($i === $this->maxFiles) {
                    @unlink($rotateFile);
                } else {
                    if ($this->splitByCopy) {
                        copy($rotateFile, $file . '.' . ($i + 1));
                        if ($fp = @fopen($rotateFile, 'ab')) {
                            ftruncate($fp, 0);
                            @fclose($fp);
                        }
                    } else {
                        rename($rotateFile, $file . '.' . ($i + 1));
                    }
                }
            }
        }
    }

    /**
     * Rotates the files.
     */
    protected function rotateFiles()
    {
        // update filename
        // $filename = $this->getFilename();
        $path = $this->getLogPath();

        // skip GC of old logs if files are unlimited
        if (0 === $this->maxFiles) {
            return;
        }

        $logFiles = glob($path . "{$this->name}*.log");
        if ($this->maxFiles >= count($logFiles)) {
            // no files to remove
            return;
        }

        // Sorting the files by name to remove the older ones
        usort($logFiles, function ($a, $b) {
            return strcmp($b, $a);
        });

        foreach (array_slice($logFiles, $this->maxFiles) as $file) {
            if (is_writable($file)) {
                // suppress errors here as unlink() might fail if two processes
                // are cleaning up/rotating at the same time
                set_error_handler(function ($errno, $errstr, $errfile, $errline) {});
                unlink($file);
                restore_error_handler();
            }
        }

        // $this->mustRotate = false;
    }
}


