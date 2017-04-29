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
class LiteLogger
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
        self::TRACE     => 'TRACE',
        self::DEBUG     => 'DEBUG',
        self::INFO      => 'INFO',
        self::NOTICE    => 'NOTICE',
        self::WARNING   => 'WARNING',
        self::ERROR     => 'ERROR',
        self::EXCEPTION => 'EXCEPTION',
        self::CRITICAL  => 'CRITICAL',
        self::ALERT     => 'ALERT',
        self::EMERGENCY => 'EMERGENCY',
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
     * split file
     * @var bool
     */
    public $splitFile = false;

    /**
     * @var bool
     */
    public $splitByCopy = true;

    /**
     * @var string 'level' 'day' 'hour', if is empty, not split
     */
    public $splitType = 'day';

    /**
     * file content max size. (M)
     * @var int
     */
    public $maxSize = 4;

    /**
     * @var integer Number of log files used for rotation. Defaults to 5.
     */
    public $maxFiles = 5;

    /**
     * log print to console (when on the CLI is valid.)
     * @var bool
     */
    protected $logConsole = true;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * 日志写入阀值
     *  即是除了手动调用 self::flushAll() 或者 flush() 之外，当 self::$_records 存储到了阀值时，就会自动写入一次
     *  设为 0 则是每次记录都立即写入文件
     *  注意：如果启用了按级别分割文件，次阀值检查可能会出现错误。
     * @var int
     */
    protected $logThreshold = 1000;

    /**
     * 格式
     * @var string
     */
    public $format = "[%datetime%] [%channel%.%level_name%] %message% %context%\n";

    /**
     * default format
     */
    const SIMPLE_FORMAT = "[%datetime%] [%channel%.%level_name%] {message} {context} {extra}\n";

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

        $name = $name ? : (isset($config['name']) ? $config['name'] : '');

        if (!$name) {
            throw new \InvalidArgumentException('Logger name is required.');
        }

        if (!isset(self::$loggers[$name])) {
            self::$loggers[$name] = new static($config, $name);
        }

        // register shutdown function
        if (self::$shutdownRegistered) {
            register_shutdown_function(function () {
                // make regular flush before other shutdown functions, which allows session data collection and so on
                self::flushAll();

                // make sure log entries written by shutdown functions are also flushed
                // ensure "flush()" is called last when there are multiple shutdown functions
                register_shutdown_function([self::class, 'flushAll'], true);
            });
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
            'format', 'splitFile', 'splitByCopy', 'logLevel', 'levels'
        ];

        foreach ($attributes as $name) {
            if (isset($config[$name])) {
                $setter = 'set' . ucfirst($name);

                if (method_exists($this, $setter)) {
                    $this->$setter($config[$name]);
                } else {
                    $this->$name = $config[$name];
                }
            }
        }

        $this->init();
    }

    protected function init()
    {
        if ($this->maxFiles < 1) {
            $this->maxFiles = 1;
        }

        if ($this->maxSize < 1) {
            $this->maxSize = 1;
        }
    }

    /**
     * destruct
     */
    public function __destruct()
    {
        self::flushAll();
    }

    public function emerg($message, array $context = [])
    {
        $this->log(self::EMERGENCY, $message, $context);
        // $this->flush();
    }

    public function error($message, array $context = [])
    {
        $this->log(self::ERROR, $message, $context);
        // $this->flush();
    }

    public function alert($message, array $context = [])
    {
        $this->log(self::ALERT, $message, $context);
        // $this->flush();
    }

    /**
     * 发生异常直接写入
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
        $message = $e->getMessage() . PHP_EOL;
        $message .= 'Called At ' . $e->getFile() . ', On Line: ' . $e->getLine() . PHP_EOL;
        $message .= 'Catch the exception by: ' . get_class($e);
        $message .= "\nCode Trace :\n" . $e->getTraceAsString();

        // If log the request info
        if ($logRequest) {
            $message .= PHP_EOL;
            $context['request'] = [
                'HOST' => $this->getServer('HTTP_HOST'),
                'METHOD' => $this->getServer('request_method'),
                'URI' => $this->getServer('request_uri'),
                'DATA' => $_REQUEST,
                'REFERRER' => $this->getServer('HTTP_REFERER'),
            ];
        }

        $this->log('exception', $message, $context);
        $this->flush();
    }

    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function trace($message = '', array $context = [])
    {
        if (!$this->debug) {
            return false;
        }

        $msg = '';

        if ($message) {
            $msg = "\n  MSG: $message.";
        }

        $file = $method = $line = 'Unknown';

        if ($data = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)) {
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

        $message = "\n  FUNC: $method\n  POS: $file Line [$line]. $msg\n  DATA:";
        $this->log('trace', $message, $context);

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
     * @return null|void
     */
    public function log($level, $message, array $context = [], array $extra = [])
    {
        $levelName = is_int($level) ? self::getLevelName($level) : $level;

        // 不在记录的级别内
        if ($this->levels && !in_array($levelName, $this->levels, true)) {
            return null;
        }

        $string = $this->dataFormatter($level, $message, $context, $extra);

        // serve is running in php build in server env.
        if ($this->logConsole && (PhpHelper::isBuiltInServer() || PhpHelper::isCli())) {
            defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'wb'));
            fwrite(STDOUT, $string . PHP_EOL);
        }

        $this->_records[] = [$level, $string];

        // 检查阀值
        if ($this->logThreshold > 0 && count($this->_records) >= $this->logThreshold) {
            $this->flush();
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
        $written = false;

        foreach ($this->_records as $record) {
            if ($this->splitType === 'level') {
                $this->write(implode(PHP_EOL, $record) . PHP_EOL, self::getLevelName($record['level']));
                $written = true;
            } else {
                $str .= $record . PHP_EOL;
            }
        }

        // no split File
        if (!$written) {
            $this->write($str);
            unset($str);
        }

        $this->_records = [];

        return true;
    }

    /**
     * @param int $level
     * @param $message
     * @param array $context
     * @param array $extra
     * @return string
     */
    protected function dataFormatter($level, $message, array $context = [], array $extra = [])
    {
        $output = $this->format ?: self::SIMPLE_FORMAT;
        $record = [
            'datetime' => date('Y-m-d H:i:s'),
            'message'  => $message,
            'level'     => $level,
            'level_name' => self::getLevelName($level),
        ];

        $record['channel'] = strtoupper(self::arrayRemove($context, 'channel', $this->name));
        $record['context'] = $context ? json_encode($context) : '';
        $record['extra']   = $extra ? json_encode($extra) : '';

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
     * @param string $levelName
     * @return bool
     * @throws FileSystemException
     */
    protected function write($str, $levelName = '')
    {
        $file = $this->getLogPath() . $this->getFilename($levelName);
        $dir = dirname($file);

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new FileSystemException("Create directory failed. $dir");
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
     * @param int $logThreshold
     */
    public function setLogThreshold($logThreshold)
    {
        $this->logThreshold = (int)$logThreshold;
    }

    /**
     * Gets all supported logging levels.
     * @return array Assoc array with human-readable level names => level codes.
     */
    public static function getLevels()
    {
        return array_flip(static::$levelMap);
    }

    /**
     * @param $level
     * @return mixed|string
     */
    public static function getLevelName($level)
    {
        return isset(static::$levelMap[$level]) ? static::$levelMap[$level] : 'UNKNOWN';
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
     * @param string $levelName
     * @return string
     */
    public function getFilename($levelName)
    {
        if ($handler = $this->filenameHandler) {
            return $handler($this, $levelName);
        }

        if ($this->splitType === 'level') {
            return $this->name . '-' . $levelName . '.log';
        } elseif ($this->splitType === 'hour') {
            return $this->name . '-' . date('Y-m-d.H') . '.log';
        }

        return $this->name . '-' . date('Y-m-d') . '.log';
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
        $name = strtoupper($name);

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

        return str_replace('\\/', '/', @json_encode($data));
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
                        @copy($rotateFile, $file . '.' . ($i + 1));
                        if ($fp = @fopen($rotateFile, 'a')) {
                            @ftruncate($fp, 0);
                            @fclose($fp);
                        }
                    } else {
                        @rename($rotateFile, $file . '.' . ($i + 1));
                    }
                }
            }
        }
    }
}


