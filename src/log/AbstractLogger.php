<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-09-20
 * Time: 15:19
 */

namespace inhere\library\log;

use inhere\exceptions\FileSystemException;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * Class AbstractLogger
 * @package inhere\library\log
 */
abstract class AbstractLogger
{
    use LoggerTrait;

    // * Log runtime info
    const CODE_TRACE = 50;

    // Detailed debug information
    const CODE_DEBUG = 100;

    // Interesting events
    const CODE_INFO = 200;

    // Uncommon events
    const CODE_NOTICE = 250;

    // Exceptional occurrences that are not errors
    const CODE_WARNING = 300;

    // Runtime errors
    const CODE_ERROR = 400;

    // * Runtime exceptions
    const CODE_EXCEPTION = 450;

    // Critical conditions
    const CODE_CRITICAL = 500;

    // Action must be taken immediately
    const CODE_ALERT = 550;

    // Urgent alert.
    const CODE_EMERGENCY = 600;

    /**
     * Logging levels from syslog protocol defined in RFC 5424
     * value @see LogLevel
     * @var array $levels Logging levels
     */
    private static $levelMap = [
        self::CODE_TRACE => 'trace', // custom add
        self::CODE_DEBUG => 'debug',
        self::CODE_INFO => 'info',
        self::CODE_NOTICE => 'notice',
        self::CODE_WARNING => 'warning',
        self::CODE_ERROR => 'error',
        self::CODE_EXCEPTION => 'exception',// custom add
        self::CODE_CRITICAL => 'critical',
        self::CODE_ALERT => 'alert',
        self::CODE_EMERGENCY => 'emergency',
    ];

    /**
     * @var array
     * Each log message is of the following structure:
     * ```
     * [
     *   [0] => message (mixed, can be a string or some complex data, such as an exception object)
     *   [1] => level (integer)
     *   [2] => category (string)
     *   [3] => timestamp (float, obtained by microtime(true))
     *   [4] => traces (array, debug backtrace, contains the application code call stacks)
     * ]
     * ```
     */
    private $logs = [];

    /**
     * @var array
     */
    private $profiles = [];

    /**
     * @var HandlerInterface[]
     */
    private $handlers = [];

    /**
     * allow multi line for a record
     * @var bool
     */
    public $allowMultiLine = true;

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
    protected $flushInterval = 1000;

    public function __construct()
    {

    }

    protected function init()
    {
        register_shutdown_function(function () {
            // make regular flush before other shutdown functions, which allows session data collection and so on
            $this->flush();

            // make sure log entries written by shutdown functions are also flushed
            // ensure "flush()" is called last when there are multiple shutdown functions
            register_shutdown_function([$this, 'flush'], true);
        });
    }

    public function appendLog($name, $msg)
    {

    }

    public function runtimeLog($beginTime)
    {
        // php耗时单位ms毫秒
        $timeUsed = sprintf('%.0f', (microtime(true) - $beginTime) * 1000);

        // php运行内存大小单位M
        $memUsed = sprintf('%.0f', memory_get_peak_usage() / (1024 * 1024));
    }

    /**
     * mark data analysis start
     * @param $name
     * @param array $context
     * @param string $category
     */
    public function profile($name, array $context = [], $category = 'application')
    {
        $context['startTime'] = microtime(true);
        $context['memUsage'] = memory_get_usage();
        $context['memPeakUsage'] = memory_get_peak_usage();

        $this->profiles[$category][$name] = $context;
    }

    public function profileEnd($name, array $context = [], $category = 'application')
    {
        if (isset($this->profiles[$category][$name])) {
            $oldInfo = $this->profiles[$category][$name];
            $info['endTime'] = microtime(true);
            $info['memUsage'] = memory_get_usage();
            $info['memPeakUsage'] = memory_get_peak_usage();

//            $this->log(LogLevel::INFO, $message, $context);
        }
    }

    /**
     * flush data to file.
     * @return bool
     */
    public function save()
    {
        return $this->flush();
    }

    public function flush($final = false)
    {
        if (!$this->logs) {
            return true;
        }

        $logs = $this->logs;
        $this->logs = [];

        if ($this->dispatcher instanceof Dispatcher) {
            $this->dispatcher->dispatch($logs, $final);
        }

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
            return (string)$data;
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
     * @return array
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * @param array $handlers
     */
    public function setHandlers(array $handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * Gets all supported logging levels.
     * @param bool $flip
     * @return array Assoc array with human-readable level names => level codes.
     */
    public static function getLevelMap($flip = false)
    {
        return $flip ? array_flip(self::$levelMap) : self::$levelMap;
    }
}