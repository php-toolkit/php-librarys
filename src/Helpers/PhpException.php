<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/9
 * Time: 下午11:15
 */

namespace Inhere\Library\Helpers;

/**
 * Class PhpException
 * @package Inhere\Library\Helpers
 */
class PhpException
{
    /**
     * @see PhpException::toHtml()
     * {@inheritdoc}
     */
    public static function toString($e, $getTrace = true, $catcher = null): string
    {
        return self::toHtml($e, $getTrace, $catcher, true);
    }

    /**
     * Converts an exception into a simple string.
     * @param \Exception|\Throwable $e the exception being converted
     * @param bool $clearHtml
     * @param bool $getTrace
     * @param null|string $catcher
     * @return string the string representation of the exception.
     */
    public static function toHtml($e, $getTrace = true, $catcher = null, $clearHtml = false): string
    {
        if (!$getTrace) {
            $message = "Error: {$e->getMessage()}";
        } else {
            $message = sprintf(
                "<h3>%s(%d): %s</h3>\n<pre><strong>File: %s(Line %d)</strong>%s \n\n%s</pre>",
                get_class($e),
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $catcher ? "\nCatch By: $catcher" : '',
                $e->getTraceAsString()
            );
        }

        if ($clearHtml) {
            $message = strip_tags($message);
        } else {
            $message = "<div class=\"exception-box\">{$message}</div>";
        }

        return $message;
    }

    /**
     * Converts an exception into a json string.
     * @param \Exception|\Throwable $e the exception being converted
     * @param bool $getTrace
     * @param null|string $catcher
     * @return string the string representation of the exception.
     */
    public static function toJson($e, $getTrace = true, $catcher = null)
    {
        if (!$getTrace) {
            $message = "Error: {$e->getMessage()}";
        } else {
            $map = [
                'code' => $e->getCode() ?: 500,
                'msg' => sprintf(
                    '%s(%d): %s, File: %s(Line %d)%s',
                    get_class($e),
                    $e->getCode(),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $catcher ? ", Catch By: $catcher" : ''
                ),
                'data' => $e->getTrace()
            ];
            $message = json_encode($map);
        }

        return $message;
    }
}
