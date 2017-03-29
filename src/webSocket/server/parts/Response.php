<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/29 0029
 * Time: 00:19
 *
 * @from Slim 3
 */

namespace inhere\librarys\webSocket\server\parts;

/**
 * Class Response
 * response for handshake
 * @package inhere\librarys\webSocket\server\parts
 *
 * @property string $protocol
 * @property string $protocolVersion
 *
 * @property int    $statusCode
 * @property string $statusMsg
 *
 * @property array $headers
 * @property Cookies $cookies
 *
 * @property array $body
 */
class Response
{
    /**
     * the connection header line data end char
     */
    const EOL = "\r\n";

    /**
     * eg: 404
     * @var int
     */
    private $statusCode;

    /**
     * eg: 'OK'
     * @var string
     */
    private $reasonPhrase;

    /**
     * @var string
     */
    private $protocol;

    /**
     * @var string
     */
    private $protocolVersion;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var Cookies
     */
    private $cookies;

    /**
     * @var array
     */
    private $body;

    /**
     * @var resource
     */
    private $stream;

    /**
     * Status codes and reason phrases
     *
     * @var array
     */
    protected static $messages = [
        //Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        //Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        //Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        //Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        499 => 'Client Closed Request',
        //Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];

    public static function make(
        int $statusCode = 200, array $headers = [], array $cookies = [], $body = '',
        string $protocol = 'HTTP', string $protocolVersion = '1.1'
    ) {
        return new self($statusCode, $headers, $cookies, $body, $protocol, $protocolVersion);
    }

    /**
     * Request constructor.
     * @param int $statusCode
     * @param array $headers
     * @param array $cookies
     * @param string|array $body
     * @param string $protocol
     * @param string $protocolVersion
     */
    public function __construct(
        int $statusCode = 200, array $headers = [], array $cookies = [], $body = '',
        string $protocol = 'HTTP', string $protocolVersion = '1.1'
    ) {
        $this->setStatus($statusCode);

        $this->headers = $headers;
        $this->cookies = new Cookies($cookies);

        $this->protocol = $protocol ?: 'HTTP';
        $this->protocolVersion = $protocolVersion ?: '1.1';

//        $stream = fopen('php://temp', 'w+');
//        stream_copy_to_stream(fopen('php://input', 'r'), $stream);
//        rewind($stream);
//
//        $this->stream = $stream;

        $this->setBody($body);
    }

    /**
     * @param $code
     * @param string $reasonPhrase
     * @return Response
     */
    public function setStatus($code, $reasonPhrase = '')
    {
        $code = $this->filterStatus($code);

        if (!is_string($reasonPhrase) && !method_exists($reasonPhrase, '__toString')) {
            throw new \InvalidArgumentException('ReasonPhrase must be a string');
        }

        $this->statusCode = $code;
        if ($reasonPhrase === '' && isset(static::$messages[$code])) {
            $reasonPhrase = static::$messages[$code];
        }

        if ($reasonPhrase === '') {
            throw new \InvalidArgumentException('ReasonPhrase must be supplied for this code');
        }

        $this->reasonPhrase = $reasonPhrase;

        return $this;
    }

    /**
     * Filter HTTP status code.
     *
     * @param  int $status HTTP status code.
     * @return int
     * @throws \InvalidArgumentException If an invalid HTTP status code is provided.
     */
    protected function filterStatus($status)
    {
        if (!is_integer($status) || $status<100 || $status>599) {
            throw new \InvalidArgumentException('Invalid HTTP status code');
        }

        return $status;
    }

    /**
     * build response data
     * @return string
     */
    public function toString()
    {
        // first line
        $output = sprintf(
            '%s/%s %s %s',
            $this->getProtocol(),
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );
        $output .= self::EOL;

        // set headers
        foreach ($this->headers as $name => $value) {
            $output .= "$name: $value" . self::EOL;
        }

        // set cookies
        foreach ($this->cookies->toHeaders() as $value) {
            $output .= "Set-Cookie: $value" . self::EOL;
        }

        $output .= self::EOL;

        return $output . $this->getBody(true);
    }

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol ?: 'HTTP';
    }

    /**
     * @param string $protocol
     * @return $this
     */
    public function setProtocol(string $protocol)
    {
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion ?: '1.1';
    }

    /**
     * @param string $protocolVersion
     * @return $this
     */
    public function setProtocolVersion(string $protocolVersion)
    {
        $this->protocolVersion = $protocolVersion;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode ?: 200;
    }

    /**
     * @return string
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * @return array
     */
    public static function getMessages(): array
    {
        return self::$messages;
    }

    /**
     * @param $name
     * @param $value
     * @param bool $replace
     */
    public function setHeader($name, $value, $replace = true)
    {
        if ($replace || !isset($this->headers[$name])) {
            $this->headers[$name] = $value;
        }
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @param bool $replace
     * @return $this
     */
    public function setHeaders(array $headers, $replace = false)
    {
        if ($replace) {
            $this->headers = array_merge($this->headers, $headers);
        } else {
            $this->headers = $headers;
        }

        return $this;
    }

    /**
     * @param string $name
     * @param string|array $value
     */
    public function setCookie(string $name, $value)
    {
        $this->cookies->set($name, $value);
    }

    /**
     * @return Cookies
     */
    public function getCookies(): Cookies
    {
        return $this->cookies;
    }

    /**
     * @param Cookies $cookies
     * @return $this
     */
    public function setCookies(Cookies $cookies)
    {
        $this->cookies = $cookies;

        return $this;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function addContent(string $content)
    {
        if ( $this->body === null ) {
            $this->body = [];
        }

        $this->body[] = $content;

        return $this;
    }

    /**
     * @param bool $toString
     * @return array|string
     */
    public function getBody(bool $toString = false)
    {
        return $toString ? implode('', $this->body) :$this->body;
    }

    /**
     * @param string|array $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = is_array($body) ? $body : [$body];

        return $this;
    }

    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return property_exists($this, $name);
    }

    /**
     * @param $name
     * @return null|mixed
     */
    public function __get($name)
    {
        $getter = 'get' . ucfirst($name);

        if ( method_exists($this, $getter) ) {
            return $this->$getter();
        }

        return null;
    }

    /**
     * @param string $name
     * @param $value
     * @throws \RuntimeException
     */
    public function __set(string $name, $value)
    {
        $setter = 'set' . ucfirst($name);

        if ( method_exists($this, $setter) ) {
            $this->$setter($name, $value);
        }

        throw new \RuntimeException("Setting a not exists property: $name");
    }
}
