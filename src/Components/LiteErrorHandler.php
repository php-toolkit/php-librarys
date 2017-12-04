<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/12/4
 * Time: 下午10:53
 * @from https://github.com/phalcon/phalcon-devtools/blob/master/scripts/Phalcon/Error/ErrorHandler.php
 */

namespace Inhere\Library\Components;

use Inhere\Library\Utils\LiteLogger as Logger;

/**
 * Class LiteErrorHandler
 * @package Inhere\Library\Components
 */
class LiteErrorHandler
{
    /**
     * Registers itself as error and exception handler.
     */
    public function register()
    {
        $that = $this;
        set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($that) {
            if (!($errno & error_reporting())) {
                return;
            }

            $options = [
                'type' => $errno,
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline,
                'isError' => true,
            ];
            $that->handle(new ErrorPayload($options));
        });

        set_exception_handler(function ($e) use ($that) {
            /** @var \Exception|\Error $e */
            $options = [
                'type' => $e->getCode(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'isException' => true,
                'exception' => $e,
            ];
            $that->handle(new ErrorPayload($options));
        });

        register_shutdown_function(function () use ($that) {
            if (null !== ($options = error_get_last())) {
                $that->handle(new ErrorPayload($options));
            }
        });
    }

    /**
     * @param ErrorPayload $error
     */
    public function handle(ErrorPayload $error)
    {
        $di = container();
        $type = $this->mapErrors($error->type());
        $message = "$type: {$error->message()} in {$error->file()} on line {$error->line()}";

        if ($di->has('logger')) {
            $logger = $di->get('logger');
            $logger->log($this->mapErrorsToLogType($error->type()), $message);
        }

        switch ($error->type()) {
            case E_WARNING:
            case E_NOTICE:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
            case E_USER_NOTICE:
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
            case E_ALL:
                break;
            default:
                if ($di->has('view')) {
                    // @todo
                } else {
                    echo $message;
                }
        }
    }

    /**
     * Maps error code to a string.
     * @param  integer $code
     * @return mixed
     */
    public function mapErrors($code)
    {
        switch ($code) {
            case 0:
                return 'Uncaught exception';
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
        }

        return $code;
    }

    /**
     * Maps error code to a log type.
     * @param  integer $code
     * @return integer
     */
    public function mapErrorsToLogType($code)
    {
        switch ($code) {
            case E_ERROR:
            case E_RECOVERABLE_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_PARSE:
                return Logger::ERROR;
            case E_WARNING:
            case E_USER_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
                return Logger::WARNING;
            case E_NOTICE:
            case E_USER_NOTICE:
                return Logger::NOTICE;
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return Logger::INFO;
        }

        return Logger::ERROR;
    }
}
