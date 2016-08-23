<?php

namespace inhere\librarys\exceptions;


class InvalidArgumentException extends \LogicException {}

//////////////////////////////////// Http exception ////////////////////////////////////


class HttpException extends \Exception{}

class HttpRuntimeException extends HttpException{}
class HttpInvalidParamException extends HttpException{}
class HttpHeaderException extends HttpException  {}
class HttpMalformedHeadersException extends HttpException{}
class HttpRequestMethodException extends HttpException{}
class HttpMessageTypeException extends HttpException{}
class HttpEncodingException extends HttpException{}
class HttpRequestException extends HttpException{}
class HttpRequestPoolException extends HttpException{}
class HttpSocketException extends HttpException{}
class HttpResponseException extends HttpException{}
class HttpUrlException extends HttpException{}
class HttpQueryStringException extends HttpException{}

//////////////////////////////////// Custom exception ////////////////////////////////////

class ExtensionMissException extends \Exception{}
class ConnectException extends \LogicException{}

class NotFoundException extends \LogicException{}
class FileSystemException extends \LogicException{}

class InvalidConfigException extends \LogicException{}
class DataTypeException extends \LogicException{}
class DataParseException extends \RuntimeException{}

class GetPropertyException extends \LogicException{}
class SetPropertyException extends \LogicException{}

class UnknownCalledException extends \LogicException{}
class RequestException extends \RuntimeException{}
class ResponseException extends \RuntimeException{}

class ContainerException extends \LogicException{}
class DependencyResolutionException extends ContainerException{}