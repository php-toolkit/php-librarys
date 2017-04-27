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
    /**
     * logger instance list
     * @var static[]
     */
    private static $loggers = [];

    /**
     * log text records list
     * @var array[]
     */
    private $_records = [];

    /**
     * 日志实例名称
     * @var string
     */
    public $name = 'default';

    /**
     * file content max size. (M)
     * @var int
     */
    protected $maxSize = 4;

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
     * channel name
     * @var string
     */
    protected $channel = 'WEB';

    /**
     * level name
     * @var string
     */
    protected $levelName = 'info';

    /**
     * split file by level name
     * @var bool
     */
    protected $splitFile = false;

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
     *  即是除了手动调用 self::flushAll() 之外，当 self::$_records 存储到了阀值时，就会自动写入一次
     *  设为 0 则是每次记录都立即写入文件
     *  注意：如果启用了按级别分割文件，次阀值检查可能会出现错误。
     * @var int
     */
    protected $logThreshold = 1000;

    /**
     * 格式
     * @var string
     */
    public $format = '[{datetime}] [{level_name}] {message} {context}';

    /**
     * default format
     */
    const SIMPLE_FORMAT = "[{datetime}] [{channel}.{level_name}] {message} {context} {extra}\n";

    const EXCEPTION = 'exception';
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    const TRACE = 'trace';

    /**
     * create new instance or get exists instance
     * @param string|array $config
     * @return static
     */
    public static function make($config)
    {
        if (!$config || !is_array($config)) {
            throw new \InvalidArgumentException('Log config is must be an array and not allow empty.');
        }

        if (!isset($config['name'])) {
            $config['name'] = 'default';
        }

        $name = $config['name'];

        if (!isset(self::$loggers[$name])) {
            self::$loggers[$name] = new static($config);
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
    public static function existLogger()
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
     * del logger
     * @param  string $name
     * @param  bool|boolean $flush
     * @return bool
     */
    public static function del(string $name, bool $flush = true)
    {
        if (isset(self::$loggers[$name])) {
            $logger = self::$loggers[$name];

            return $flush ? $logger->save() : true;
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
            $logger->save();
        }
    }

    /**
     * create new instance
     * @param array $config
     * @throws \InvalidArgumentException
     */
    private function __construct(array $config = [])
    {
        $this->name = $config['name'];
        $canSetting = ['logConsole', 'logThreshold', 'debug', 'channel', 'basePath', 'subFolder', 'format', 'splitFile', 'levels'];

        foreach ($canSetting as $name) {
            if (isset($config[$name])) {
                $setter = 'set' . ucfirst($name);
                if (method_exists($this, $setter)) {
                    $this->$setter($config[$name]);
                } else {
                    $this->$name = $config[$name];
                }
            }
        }
    }

    public function error($message, array $context = array())
    {
        $this->log(self::ERROR, $message, $context);
        $this->save();
    }

    public function alert($message, array $context = array())
    {
        $this->log(self::ALERT, $message, $context);
        $this->save();
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
                'REFERER' => $this->getServer('HTTP_REFERER'),
            ];
        }

        $this->format .= PHP_EOL;
        $this->log('exception', $message, $context);
        $this->save();
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

    public function warning($message, array $context = array())
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->log(self::NOTICE, $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->log(self::INFO, $message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * record log info to file
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function log($level, $message, array $context = [])
    {
        // 不在记录的级别内
        if ($this->levels && !in_array($level, $this->levels, true)) {
            return null;
        }

        $string = $this->dataFormatter($level, $message, $context);

        // serve is running in php build in server env.
        if ($this->logConsole && (PhpHelper::isBuiltInServer() || PhpHelper::isCli())) {
            defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'wb'));
            fwrite(STDOUT, $string . PHP_EOL);
        }

        if ($this->splitFile) {
            $this->_records[$level][] = $string;
        } else {
            $this->_records[] = $string;
        }

        // 检查阀值
        if (count($this->_records) >= $this->logThreshold) {
            $this->save();
        }

        return null;
    }

    /**
     * @return bool
     */
    public function save()
    {
        if (!$this->_records) {
            return true;
        }

        $written = false;
        $str = '';

        foreach ($this->_records as $key => $record) {
            $this->levelName = $key;

            if ($this->splitFile) {
                $str = '';

                foreach ($record as $text) {
                    $str .= $text . "\n";
                }

                $this->write($str);
                $written = true;
            } else {
                $str .= $record . "\n";
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
     * @param $level
     * @param $message
     * @param array $context
     * @return string
     */
    protected function dataFormatter($level, $message, array $context)
    {
        $format = $this->format ?: self::SIMPLE_FORMAT;
        $record = [
            '{datetime}' => date('Y-m-d H:i:s'),
            '{message}' => $message,
            '{level_name}' => strtoupper($level),
        ];

        $record['{channel}'] = strtoupper(self::arrayRemove($context, 'channel', $this->channel));
        $record['{context}'] = $context ? json_encode($context) : '';

        return strtr($format, $record);
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

        return ($this->splitFile ? $this->levelName : $this->name) . '.' . date('Y-m-d') . '.log';
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
     * @param $message
     * @param array $context
     * @return string
     */
    protected function interpolate($message, array $context = [])
    {
        // build a replacement array with braces around the context keys
        $replace = [];

        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * @param string $name
     * @return static
     */
    public function getLogger($name = 'default')
    {
        return self::make($name);
    }

    /**
     * @return array
     */
    public function getLoggerNames()
    {
        return array_keys(self::$loggers);
    }

    public static function sendLog()
    {
        // Yii::$app->gearman->doBackground();
    }
}


