<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/26 0026
 * Time: 18:02
 */

namespace inhere\librarys\webSocket\server\parts;

/**
 * Class Request
 *
 * @property string $method
 *
 * @property string $host
 * @property string $path
 *
 * @property string $protocol
 * @property string $protocolVersion
 *
 * @property array $headers
 * @property array $cookies
 *
 * @property string $body
 */
class Request
{
    /**
     * @var string
     */
    private $method;

    /**
     * eg: site.com 127.0.0.1:9501
     * @var string
     */
    private $host;

    /**
     * eg: /home
     * @var string
     */
    private $path;

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
     * @var array
     */
    private $cookies;

    /**
     * @var string
     */
    private $body;

    /**
     * Request constructor.
     * @param string $host
     * @param string $method
     * @param string $path
     * @param string $protocol
     * @param string $protocolVersion
     * @param array $headers
     * @param string $body
     * @param array $cookies
     */
    public function __construct(
        string $host = '', string $method = 'GET', string $path = '/', string $protocol = 'HTTP',
        string $protocolVersion = '1.1', array $headers = [], array $cookies = [], string $body = ''
    ) {
        $this->method = $method ?: 'GET';
        $this->host = $host ?: '';
        $this->path = $path ?: '/';
        $this->protocol = $protocol ?: 'HTTP';
        $this->protocolVersion = $protocolVersion ?: '1.1';
        $this->headers = $headers;
        $this->cookies = $cookies;
        $this->body = $body ?: '';
    }

    /**
     * @param string $rawData
     * @return self
     */
    public static function makeByParseData(string $rawData): self
    {
        if (!$rawData) {
            return new self();
        }

        // $rawData = trim($rawData);
        $two = explode("\r\n\r\n", $rawData,2);

        if ( !$rawHeader = $two[0] ?? '' ) {
            return new self();
        }

        $body = $two[1] ?? '';

        /** @var array $list */
        $list = explode("\n", trim($rawHeader));

        // e.g: `GET / HTTP/1.1`
        $first = array_shift($list);
        $data = array_map('trim', explode(' ', trim($first)) );

        [$method, $path, $protoStr] = $data;
        [$protocol, $protocolVersion] = explode('/', $protoStr);

        // other header info
        $headers = [];
        foreach ($list as $item) {
            if (!$item) {
                continue;
            }

            [$name, $value] = explode(': ', trim($item));
            $headers[$name] = $value;
        }

        $cookies = [];
        if (isset($headers['Cookie'])) {
            $cookieData = $headers['Cookie'];
            unset($headers['Cookie']);
            $cookies = self::parseHeaderCookie($cookieData);
        }

        $host = '';
        if (isset($headers['Host'])) {
            $host = $headers['Host'];
            unset($headers['Host']);
        }

        return new self($host, $method, $path, $protocol, $protocolVersion, $headers, $cookies, $body);
    }

    /**
     * Parse HTTP request `Cookie:` header and extract
     * into a PHP associative array.
     * @param  string $cookieData The raw HTTP request `Cookie:` header
     * @return array Associative array of cookie names and values
     * @throws \InvalidArgumentException if the cookie data cannot be parsed
     */
    public static function parseHeaderCookie($cookieData)
    {
        if (is_string($cookieData) === false) {
            throw new \InvalidArgumentException('Cannot parse Cookie data. Header value must be a string.');
        }

        $cookieData = rtrim($cookieData, "\r\n");
        $pieces = preg_split('@[;]\s*@', $cookieData);
        $cookies = [];

        foreach ($pieces as $cookie) {
            $cookie = explode('=', $cookie, 2);

            if (count($cookie) === 2) {
                $key = urldecode($cookie[0]);
                $value = urldecode($cookie[1]);

                if (!isset($cookies[$key])) {
                    $cookies[$key] = $value;
                }
            }
        }

        return $cookies;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method)
    {
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * @param string $protocol
     */
    public function setProtocol(string $protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @param string $protocolVersion
     */
    public function setProtocolVersion(string $protocolVersion)
    {
        $this->protocolVersion = $protocolVersion;
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
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @param string $name
     * @return string
     */
    public function getHeader(string $name): string
    {
        return $this->headers[$name] ?? '';
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body)
    {
        $this->body = $body;
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
