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
     * 根据服务器设置得到文件上传大小的最大值
     *
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
     * @param \Exception|\Throwable $exp the exception being converted
     * @param string $br
     * @param bool $getTrace
     * @return string the string representation of the exception.
     */
    public static function exceptionToString($exp, $br = "\n", $getTrace = false): string
    {
        if (!$getTrace) {
            $message = "Error: {$exp->getMessage()}";
        } else {
            $message = $exp instanceof \ErrorException ? 'Error' : 'Exception';
            $message .= " '" . get_class($exp) . "' with message '{$exp->getMessage()}' \n\nIn "
                . $exp->getFile() . ':' . $exp->getLine() . "\n\n"
                . "Stack trace:\n" . $exp->getTraceAsString();

            if ($br !== "\n") {
                $message = str_replace("\n", $br, $message);
            }
        }

        return $message;
    }

    /**
     * @param $pathname
     * @param $projectId
     * @return int|string
     */
    public static function ftok($pathname, $projectId)
    {
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
    public static function dumpVar(...$args): string
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
    public static function printVar(...$args): string
    {
        ob_start();

        foreach ($args as $arg) {
            print_r($arg);
        }

        $string = ob_get_clean();

        return preg_replace("/Array\n\s+\(/", 'Array (', $string);
    }
}
