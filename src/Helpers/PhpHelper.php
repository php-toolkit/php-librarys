<?php
/**
 *
 */

namespace Inhere\Library\Helpers;

use Inhere\Exceptions\ExtensionMissException;

/**
 * Class PhpHelper
 * @package Inhere\Library\Helpers
 */
class PhpHelper extends EnvHelper
{
    /**
     * @param callable $cb
     * @param array ...$args
     * @return mixed
     */
    public static function call(callable $cb, ...$args)
    {
        if (\is_string($cb)) {
            // function
            if (strpos($cb, '::') === false) {
                return $cb(...$args);
            }

            // ClassName::method
            $cb = explode('::', $cb, 2);
        } elseif (\is_object($cb) && method_exists($cb, '__invoke')) {
            return $cb(...$args);
        }

        if (\is_array($cb)) {
            list($obj, $mhd) = $cb;

            return \is_object($obj) ? $obj->$mhd(...$args) : $obj::$mhd(...$args);
        }

        return $cb(...$args);
    }

    /**
     * @param callable $cb
     * @param array $args
     * @return mixed
     */
    public static function callByArray(callable $cb, array $args)
    {
        return self::call($cb, ...$args);
    }

    /**
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public static function serverParam($name, $default = null)
    {
        return Req::serverParam($name, $default);
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
        $info['startTime'] = $startTime;
        $info['endTime'] = microtime(true);
        $info['endMemory'] = memory_get_usage(true);

        // 计算运行时间
        $info['runtime'] = number_format(($info['endTime'] - $startTime) * 1000, 3)  . 'ms';

        if ($startMem) {
            $startMem = array_sum(explode(' ', $startMem));
            $endMem = array_sum(explode(' ', $info['endMemory']));

            $info['memory'] = number_format(($endMem - $startMem) / 1024, 3) . 'kb';
        }

        $peakMem = memory_get_peak_usage(true) / 1024 / 1024;
        $info['peakMemory'] = number_format($peakMem, 3) . 'Mb';

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
     * Converts an exception into a simple string.
     * @param \Exception|\Throwable $e the exception being converted
     * @param bool $getTrace
     * @param null|string $catcher
     * @return string the string representation of the exception.
     */
    public static function exceptionToString($e, $getTrace = true, $catcher = null): string
    {
        return PhpException::toString($e, $getTrace, $catcher);
    }

    public static function exceptionToHtml($e, $getTrace = true, $catcher = null): string
    {
        return PhpException::toHtml($e, $getTrace, $catcher);
    }

    /**
     * @param \Exception|\Throwable $e
     * @param bool $getTrace
     * @param null $catcher
     * @return string
     */
    public static function exceptionToJson($e, $getTrace = true, $catcher = null): string
    {
        return PhpException::toJson($e, $getTrace, $catcher);
    }

    /**
     * @return array
     */
    public static function getUserConstants(): array
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
        $string = '';

        foreach ($args as $arg) {
            $string .= print_r($arg, 1) . PHP_EOL;
        }

        return preg_replace("/Array\n\s+\(/", 'Array (', $string);
    }

    /**
     * @param $var
     * @return mixed
     */
    public static function exportVar($var)
    {
        return var_export($var, true);
    }


    /**
     * @param $name
     * @param bool|false $throwException
     * @return bool
     * @throws ExtensionMissException
     */
    public static function extIsLoaded($name, $throwException = false): bool
    {
        $result = \extension_loaded($name);

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

        foreach ($extensions as $extension) {
            if (!\extension_loaded($extension)) {
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

}
