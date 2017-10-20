<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inhere\Library\Components;

use Inhere\Library\Helpers\Obj;
use Inhere\Library\Helpers\PhpHelper;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Monolog\Handler\AbstractHandler;

/**
 * Monolog error handler
 *
 * A facility to enable logging of runtime errors, exceptions and fatal errors.
 *
 * Quick setup: <code>ErrorHandler::register($logger);</code>
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class ErrorHandler
{
    protected $logger;

    private $previousExceptionHandler;
    private $uncaughtExceptionLevel;

    private $previousErrorHandler;
    private $errorLevelMap;
    private $handleOnlyReportedErrors;

    private $hasFatalErrorHandler;
    protected $fatalLevel;
    protected $reservedMemory;
    public static $fatalErrors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    private $exitOnHandled = true;

    /**
     * ErrorHandler constructor.
     * @param LoggerInterface $logger
     * @param array $options
     */
    public function __construct(LoggerInterface $logger, array $options = [])
    {
        Obj::smartConfigure($this, $options);

        $this->logger = $logger;
    }

    /**
     * Registers a new ErrorHandler for a given Logger
     *
     * By default it will handle errors, exceptions and fatal errors
     *
     * @param  array|false     $errorLevelMap  an array of E_* constant to LogLevel::* constant mapping, or false to disable error handling
     * @param  int|false       $exceptionLevel a LogLevel::* constant, or false to disable exception handling
     * @param  int|false       $fatalLevel     a LogLevel::* constant, or false to disable fatal error handling
     * @return ErrorHandler
     */
    public function register(array $errorLevelMap = [], $exceptionLevel = null, $fatalLevel = null)
    {
        //Forces the autoloader to run for LogLevel. Fixes an autoload issue at compile-time on PHP5.3. See https://github.com/Seldaek/monolog/pull/929
        class_exists(LogLevel::class);

        if ($errorLevelMap !== false) {
            $this->registerErrorHandler($errorLevelMap);
        }
        if ($exceptionLevel !== false) {
            $this->registerExceptionHandler($exceptionLevel);
        }
        if ($fatalLevel !== false) {
            $this->registerFatalHandler($fatalLevel);
        }

        return $this;
    }

    /**
     * @param null $level
     * @param bool $callPrevious
     */
    public function registerExceptionHandler($level = null, $callPrevious = true)
    {
        $prev = set_exception_handler([$this, 'handleException']);
        $this->uncaughtExceptionLevel = $level;

        if ($callPrevious && $prev) {
            $this->previousExceptionHandler = $prev;
        }
    }

    /**
     * @param array $levelMap
     * @param bool $callPrevious
     * @param int $errorTypes
     * @param bool $handleOnlyReportedErrors
     */
    public function registerErrorHandler(array $levelMap = [], $callPrevious = true, $errorTypes = -1, $handleOnlyReportedErrors = true)
    {
        $prev = set_error_handler([$this, 'handleError'], $errorTypes);
        $this->errorLevelMap = array_replace($this->defaultErrorLevelMap(), $levelMap);

        if ($callPrevious) {
            $this->previousErrorHandler = $prev ?: true;
        }

        $this->handleOnlyReportedErrors = $handleOnlyReportedErrors;
    }

    /**
     * @param null $level
     * @param int $reservedMemorySize
     */
    public function registerFatalHandler($level = null, $reservedMemorySize = 20)
    {
        register_shutdown_function([$this, 'handleFatalError']);

        $this->reservedMemory = str_repeat(' ', 1024 * $reservedMemorySize);
        $this->fatalLevel = $level;
        $this->hasFatalErrorHandler = true;
    }

    protected function defaultErrorLevelMap()
    {
        return array(
            E_ERROR             => LogLevel::CRITICAL,
            E_WARNING           => LogLevel::WARNING,
            E_PARSE             => LogLevel::ALERT,
            E_NOTICE            => LogLevel::NOTICE,
            E_CORE_ERROR        => LogLevel::CRITICAL,
            E_CORE_WARNING      => LogLevel::WARNING,
            E_COMPILE_ERROR     => LogLevel::ALERT,
            E_COMPILE_WARNING   => LogLevel::WARNING,
            E_USER_ERROR        => LogLevel::ERROR,
            E_USER_WARNING      => LogLevel::WARNING,
            E_USER_NOTICE       => LogLevel::NOTICE,
            E_STRICT            => LogLevel::NOTICE,
            E_RECOVERABLE_ERROR => LogLevel::ERROR,
            E_DEPRECATED        => LogLevel::NOTICE,
            E_USER_DEPRECATED   => LogLevel::NOTICE,
        );
    }

    /**
     * @param \Throwable $e
     */
    public function handleException($e)
    {
        $this->logger->log(
            $this->uncaughtExceptionLevel ?? LogLevel::ERROR,
            sprintf('Uncaught Exception %s: "%s" at %s line %s', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()),
            ['exception' => $e]
        );

        if ($this->previousExceptionHandler) {
            PhpHelper::call($this->previousExceptionHandler, $e);
        }

        if ($this->exitOnHandled) {
            exit(255);
        }
    }

    /**
     * @private
     * @param $code
     * @param $message
     * @param string $file
     * @param int $line
     * @param array $context
     * @return bool|mixed
     */
    public function handleError($code, $message, $file = '', $line = 0, array $context = array())
    {
        if ($this->handleOnlyReportedErrors && !(error_reporting() & $code)) {
            return false;
        }

        // fatal error codes are ignored if a fatal error handler is present as well to avoid duplicate log entries
        if (!$this->hasFatalErrorHandler || !in_array($code, self::$fatalErrors, true)) {
            $level = $this->errorLevelMap[$code] ?? LogLevel::CRITICAL;
            $this->logger->log($level, self::codeToString($code).': '.$message, [
                'code' => $code,
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'catcher' => __METHOD__,
            ]);
        }

        if ($this->previousErrorHandler === true) {
            return false;
        }

        if ($this->previousErrorHandler) {
            return PhpHelper::call($this->previousErrorHandler, $code, $message, $file, $line, $context);
        }

        return false;
    }

    /**
     * handleFatalError
     * @private
     */
    public function handleFatalError()
    {
        $this->reservedMemory = null;

        $lastError = error_get_last();
        if ($lastError && in_array($lastError['type'], self::$fatalErrors, true)) {
            $this->logger->log(
                $this->fatalLevel ?? LogLevel::ALERT,
                'Fatal Error ('.self::codeToString($lastError['type']).'): '.$lastError['message'],
                [
                    'code' => $lastError['type'],
                    'message' => $lastError['message'],
                    'file' => $lastError['file'],
                    'line' => $lastError['line']
                ]
            );

            if ($this->logger instanceof Logger) {
                foreach ($this->logger->getHandlers() as $handler) {
                    if ($handler instanceof AbstractHandler) {
                        $handler->close();
                    }
                }
            }
        }
    }

    public static function codeToString($code)
    {
        switch ($code) {
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

        return 'Unknown PHP error';
    }

    /**
     * @return bool
     */
    public function isExitOnHandled(): bool
    {
        return $this->exitOnHandled;
    }

    /**
     * @param bool $exitOnHandled
     */
    public function setExitOnHandled($exitOnHandled)
    {
        $this->exitOnHandled = (bool)$exitOnHandled;
    }
}
