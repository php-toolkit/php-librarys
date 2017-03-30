<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-30
 * Time: 13:12
 */

namespace inhere\library\http;

use inhere\library\traits\TraitGetterSetterAccess;

/**
 * Class BaseRequestResponse
 * @package inhere\webSocket\parts
 *
 * @property string $protocol
 * @property string $protocolVersion
 *
 * @property Headers $headers
 * @property Cookies $cookies
 *
 */
abstract class Message
{
    use TraitGetterSetterAccess;

    /**
     * the connection header line data end char
     */
    const EOL = "\r\n";

    /**
     * protocol/schema
     * @var string
     */
    protected $protocol;

    /**
     * @var string
     */
    protected $protocolVersion;

    /**
     * @var Headers
     */
    protected $headers;

    /**
     * @var Cookies
     */
    protected $cookies;

    public function __construct(string $protocol = 'HTTP', string $protocolVersion = '1.1', array $headers = [], array $cookies = [])
    {
        $this->protocol = $protocol ?: 'HTTP';
        $this->protocolVersion = $protocolVersion ?: '1.1';
        $this->headers = new Headers($headers);
        $this->cookies = new Cookies($cookies);
    }

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        if ( !$this->protocol ) {
            $this->protocol = 'HTTP';
        }

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
        if ( !$this->protocolVersion ) {
            $this->protocolVersion = '1.1';
        }

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
     * @param string $name
     * @return string
     */
    public function getHeader(string $name): string
    {
        return $this->headers->get($name);
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->headers->set($name, $value);

        return $this;
    }

    /**
     * @return Headers
     */
    public function getHeaders(): Headers
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers->sets($headers);

        return $this;
    }

    /**
     * @param string $name
     * @param string|array $value
     * @return $this
     */
    public function setCookie(string $name, $value)
    {
        $this->cookies->set($name, $value);

        return $this;
    }

    /**
     * @return Cookies
     */
    public function getCookies(): Cookies
    {
        return $this->cookies;
    }

    /**
     * @param array $cookies
     * @return $this
     */
    public function setCookies(array $cookies)
    {
        $this->cookies = new Cookies($cookies);

        return $this;
    }

}
