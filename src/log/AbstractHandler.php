<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-09-20
 * Time: 16:46
 */

namespace inhere\library\log;

use Psr\Log\LogLevel;

/**
 * Class AbstractHandler
 * @package inhere\library\log
 */
abstract class AbstractHandler implements HandlerInterface
{
    /**
     * @var boolean whether to enable this log handler. Defaults to true.
     */
    public $enabled = true;

    /**
     * only want to exported categories
     * @var array
     */
    public $categories = [];

    /**
     * the excepted categories, them are will not export.
     * @var array
     */
    public $except = [];

    /**
     * @var array
     */
    public $logs = [];

    /**
     * @var int
     */
    public $exportInterval = 1000;

    /**
     * @var int
     */
    private $_levels = 0;

    /**
     * @var \Closure
     */
    private $contextCollector;

    /**
     * @param array $logs
     * @param $final
     */
    public function handle(array $logs, $final)
    {
        $this->logs = array_merge($this->logs, static::filterLogs($logs, $this->getLevels(), $this->categories, $this->except));
        $count = count($this->logs);

        if ($count > 0 && ($final || ($this->exportInterval > 0 && $count >= $this->exportInterval))) {
            if ($collector = $this->contextCollector) {
                $this->logs[] = [$collector($this), LogLevel::INFO, 'application', microtime(true)];
            }

            // set exportInterval to 0 to avoid triggering export again while exporting
            $oldExportInterval = $this->exportInterval;
            $this->exportInterval = 0;
            $this->export();
            $this->exportInterval = $oldExportInterval;

            $this->logs = [];
        }
    }

    abstract protected function export();

    /**
     * @return string
     */
    protected function getContextLog()
    {
        return '';
    }

    /**
     * @param array $logs
     * @param int $levels
     * @param array $categories
     * @param array $except except category
     * @return array
     */
    public static function filterLogs(array $logs, $levels = 0, array $categories = [], array $except = [])
    {
        foreach ($logs as $i => $message) {
            if ($levels && !($levels & $message[1])) {
                unset($logs[$i]);
                continue;
            }

            $matched = empty($categories);
            foreach ($categories as $category) {
                if (
                    $message[2] === $category ||
                    (!empty($category) && substr_compare($category, '*', -1, 1) === 0 && strpos($message[2], rtrim($category, '*')) === 0)
                ) {
                    $matched = true;
                    break;
                }
            }

            if ($matched) {
                foreach ($except as $category) {
                    $prefix = rtrim($category, '*');
                    if (($message[2] === $category || $prefix !== $category) && strpos($message[2], $prefix) === 0) {
                        $matched = false;
                        break;
                    }
                }
            }

            if (!$matched) {
                unset($logs[$i]);
            }
        }
        return $logs;
    }


    public function getLevels()
    {
        return $this->_levels;
    }


    /**
     * Sets the message levels that this target is interested in.
     *
     * The parameter can be either an array of interested level names or an integer representing
     * the bitmap of the interested level values.
     * valid names include: {@see AbstractLogger::$levelMap }
     *  'error', 'warning', 'info', ...
     * valid codes include:
     *  LogLevel::ERROR, LogLevel::WARNING, LogLevel::INFO ...
     *
     * For example,
     *
     * ```php
     * ['error', 'warning']
     * // which is equivalent to:
     * LogLevel::ERROR | LogLevel::WARNING
     * ```
     *
     * @param array|integer $levels message levels that this target is interested in.
     * @throws \InvalidArgumentException if an unknown level name is given
     */
    public function setLevels($levels)
    {
        $levelMap = AbstractLogger::getLevelMap(true);

        if (is_array($levels)) {
            $this->_levels = 0;

            foreach ((array)$levels as $level) {
                if (isset($levelMap[$level])) {
                    $this->_levels |= $levelMap[$level];
                } else {
                    throw new \InvalidArgumentException("Unrecognized level: $level");
                }
            }
        } else {
            $this->_levels = (int)$levels;
        }
    }
}
