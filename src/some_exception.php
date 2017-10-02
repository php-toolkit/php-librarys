<?php

namespace Inhere\Exceptions;

abstract class BaseException extends \Exception
{
    /**
     * Whether the user can see the error message
     * @var bool
     */
    public $isVisible = false;

    /**
     * append custom data
     * @var array
     */
    public $params = [];

    /**
     * BaseException constructor.
     * @param string $message
     * @param int $code
     * @param array $params
     * @param \Exception|null $previous
     */
    public function __construct($message = '', $code = 1000, \Exception $previous = null, $params = [])
    {
        parent::__construct($message, $code, $previous);

        $this->params = $params;
    }
}

//////////////////////////////////// Http exception ////////////////////////////////////

class HttpException extends BaseException
{
}

class HttpRuntimeException extends HttpException
{
}

class HttpInvalidParamException extends HttpException
{
}

class HttpHeaderException extends HttpException
{
}

class HttpMalformedHeadersException extends HttpException
{
}

class HttpRequestMethodException extends HttpException
{
}

class HttpMessageTypeException extends HttpException
{
}

class HttpEncodingException extends HttpException
{
}

class HttpRequestException extends HttpException
{
}

class HttpRequestPoolException extends HttpException
{
}

class HttpSocketException extends HttpException
{
}

class HttpResponseException extends HttpException
{
}

class HttpUrlException extends HttpException
{
}

class HttpQueryStringException extends HttpException
{
}

//////////////////////////////////// Custom exception ////////////////////////////////////

class UserPromptException extends BaseException
{
    /**
     * UserPromptException constructor.
     * @param string $message
     * @param int $code
     * @param array $params
     * @param \Exception|null $previous
     */
    public function __construct($message = '', $code = 1000, $params = [], \Exception $previous = null)
    {
        parent::__construct($message, $code, $params, $previous);

        $this->isVisible = true;
    }
}

class LogicException extends BaseException
{
}

class RuntimeException extends BaseException
{
}

class CreateResourceFailedException extends BaseException
{
}

class ExtensionMissException extends RuntimeException
{
}

class ConnectException extends RuntimeException
{
}

class ConnectionException extends RuntimeException
{
}

class FileSystemException extends LogicException
{
}

class IOException extends FileSystemException
{
}

class FileNotFoundException extends FileSystemException
{
}

class FileReadException extends FileSystemException
{
}

class FileWrittenException extends FileSystemException
{
}

class FileUploadException extends FileSystemException
{
}

class InvalidArgumentException extends RuntimeException
{
}

class InvalidConfigException extends RuntimeException
{
}

class InvalidOptionException extends RuntimeException
{
}

class DataParseException extends RuntimeException
{
}

class DataTypeException extends RuntimeException
{
}

class PropertyException extends LogicException
{
}

class GetPropertyException extends PropertyException
{
}

class SetPropertyException extends PropertyException
{
}

class NotFoundException extends LogicException
{
}

class UnknownCalledException extends NotFoundException
{
}

class UnknownMethodException extends NotFoundException
{
}

class RequestException extends RuntimeException
{
}

class ResponseException extends RuntimeException
{
}

class ContainerException extends RuntimeException
{
}

class DependencyResolutionException extends ContainerException
{
}
