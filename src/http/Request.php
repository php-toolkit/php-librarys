<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/26 0026
 * Time: 18:02
 */

namespace inhere\library\http;

/**
 * Class Request
 *
 * @property string $method
 *
 * @property string $host
 * @property string $path
 * @property-read string $origin
 *
 * @property string $body
 */
class Request extends Message
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
        parent::__construct($protocol, $protocolVersion, $headers, $cookies);

        $this->method = $method ?: 'GET';
        $this->host = $host ?: '';
        $this->path = $path ?: '/';

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
            $headers[$name] = trim($value);
        }

        $cookies = [];
        if (isset($headers['Cookie'])) {
            $cookieData = $headers['Cookie'];
            unset($headers['Cookie']);
            $cookies = Cookies::parseFromRawHeader($cookieData);
        }

        $host = '';
        if (isset($headers['Host'])) {
            $host = $headers['Host'];
            unset($headers['Host']);
        }

        return new self($host, $method, $path, $protocol, $protocolVersion, $headers, $cookies, $body);
    }

    /**
     * `Origin: http://foo.example`
     * @return mixed
     */
    public function getOrigin()
    {
        return $this->headers->get('Origin');
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
}
