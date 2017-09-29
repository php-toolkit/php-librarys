<?php
/**
 *
 */

namespace inhere\library\helpers;

use inhere\exceptions\ExtensionMissException;

/**
 * Class PhpHelper
 * @package inhere\library\helpers
 */
class PhpHelper extends EnvHelper
{
    /**
     * @param $name
     * @param bool|false $throwException
     * @return bool
     * @throws ExtensionMissException
     */
    public static function extIsLoaded($name, $throwException = false): bool
    {
        $result = extension_loaded($name);

        if (!$result && $throwException) {
            throw new ExtensionMissException("Extension [$name] is not loaded.");
        }

        return $result;
    }

    /**
     * 检查多个扩展加载情况
     * @param array $extensions
     * @return array|bool
     */
    public static function checkExtList(array $extensions = array())
    {
        $allTotal = [];

        foreach ((array)$extensions as $extension) {
            if (!extension_loaded($extension)) {
                # 没有加载此扩展，记录
                $allTotal['no'][] = $extension;
            } else {
                $allTotal['yes'][] = $extension;
            }
        }

        return $allTotal;
    }

    /**
     * 返回加载的扩展
     * @param bool $zend_extensions
     * @return array
     */
    public static function getLoadedExtension($zend_extensions = false): array
    {
        return get_loaded_extensions($zend_extensions);
    }

    /**
     * 获取资源消耗
     * @param int $startTime
     * @param int|float $startMem
     * @param array $info
     * @return array
     */
    public static function runtime($startTime, $startMem, array $info = [])
    {
        // 显示运行时间
        $info['time'] = number_format(microtime(true) - $startTime, 4) . 's';

        $startMem = array_sum(explode(' ', $startMem));
        $endMem = array_sum(explode(' ', memory_get_usage()));

        $info['memory'] = number_format(($endMem - $startMem) / 1024) . 'kb';

        return $info;
    }

    /**
     * 根据服务器设置得到文件上传大小的最大值
     * @param int $max_size optional max file size
     * @return int max file size in bytes
     */
    public static function getMaxUploadSize($max_size = 0): int
    {
        $post_max_size = FormatHelper::convertBytes(ini_get('post_max_size'));
        $upload_max_fileSize = FormatHelper::convertBytes(ini_get('upload_max_filesize'));

        if ($max_size > 0) {
            $result = min($post_max_size, $upload_max_fileSize, $max_size);
        } else {
            $result = min($post_max_size, $upload_max_fileSize);
        }

        return $result;
    }

    /**
     * setStrict
     * @return  void
     */
    public static function setStrict(): void
    {
        error_reporting(32767);
    }

    /**
     * setMuted
     * @return  void
     */
    public static function setMuted(): void
    {
        error_reporting(0);
    }

    /**
     * Returns true when the runtime used is PHP and Xdebug is loaded.
     * @return boolean
     */
    public static function hasXdebug(): bool
    {
        return static::isPHP() && extension_loaded('xdebug');
    }

    /**
     * Converts an exception into a simple string.
     * @param \Exception|\Throwable $e the exception being converted
     * @param bool $clearHtml
     * @param bool $getTrace
     * @param null|string $catcher
     * @return string the string representation of the exception.
     */
    public static function exceptionToString($e, $clearHtml = false, $getTrace = false, $catcher = null): string
    {
        if (!$getTrace) {
            $message = "Error: {$e->getMessage()}";
        } else {
            $type = $e instanceof \ErrorException ? 'Error' : 'Exception';
            $catcher = $catcher ? "Catch By: $catcher\n" : '';
            $message = sprintf(
                "<h3>%s(%d): %s</h3>\n<pre><strong>File: %s(Line %d)</strong>%s \n\n%s</pre>",
                $type,
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $catcher,
                $e->getTraceAsString()
            );

            if ($clearHtml) {
                $message = strip_tags($message);
            }
        }

        return $message;
    }

    /**
     * @param string $cmd
     */
    public static function execInBackground($cmd)
    {
        if (strpos(PHP_OS, 'Windows') === 0) {
            pclose(popen('start /B ' . $cmd, 'r'));
        } else {
            exec($cmd . ' > /dev/null &');
        }
    }

    /**
     * @param string $pathname
     * @param int|string $projectId This must be a one character
     * @return int|string
     * @throws \LogicException
     */
    public static function ftok($pathname, $projectId)
    {
        if (strlen($projectId) > 1) {
            throw new \LogicException("the project id must be a one character(int/str). Input: $projectId");
        }

        if (function_exists('ftok')) {
            return ftok($pathname, $projectId);
        }

        if (!$st = @stat($pathname)) {
            return -1;
        }

        $key = sprintf('%u', ($st['ino'] & 0xffff) | (($st['dev'] & 0xff) << 16) | (($projectId & 0xff) << 24));

        return $key;
    }

    /**
     * 本次请求开始时间
     * @param bool $float
     * @return mixed
     */
    public static function requestTime($float = true)
    {
        if ((bool)$float) {
            return $_SERVER['REQUEST_TIME_FLOAT'];
        }

        return $_SERVER['REQUEST_TIME'];
    }

    /**
     * @return array
     */
    public static function userConstants(): array
    {
        $const = get_defined_constants(true);

        return $const['user'] ?? [];
    }

    /**
     * dump vars
     * @param array ...$args
     * @return string
     */
    public static function dumpVars(...$args): string
    {
        ob_start();
        var_dump(...$args);
        $string = ob_get_clean();

        return preg_replace("/=>\n\s+/", '=> ', $string);
    }

    /**
     * print vars
     * @param array ...$args
     * @return string
     */
    public static function printVars(...$args): string
    {
        ob_start();
        foreach ($args as $arg) {
            print_r($arg);
        }
        $string = ob_get_clean();

        return preg_replace("/Array\n\s+\(/", 'Array (', $string);
    }

    /**
     * @param $cb
     * @param array $args
     * @return mixed
     */
    public static function call($cb, array $args = [])
    {
        $args = array_values($args);

        if (
            (is_object($cb) && method_exists($cb, '__invoke')) ||
            (is_string($cb) && function_exists($cb))
        ) {
            $ret = $cb(...$args);
        } elseif (is_array($cb)) {
            list($obj, $mhd) = $cb;

            $ret = is_object($obj) ? $obj->$mhd(...$args) : $obj::$mhd(...$args);
        } elseif (method_exists('Swoole\Coroutine', 'call_user_func_array')) {
            $ret = \Swoole\Coroutine::call_user_func_array($cb, $args);
        } else {
            $ret = call_user_func_array($cb, $args);
        }

        return $ret;
    }
}
