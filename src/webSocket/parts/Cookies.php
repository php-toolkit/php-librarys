<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/28 0028
 * Time: 23:00
 *
 * @from slim3
 */

namespace inhere\librarys\webSocket\parts;

/**
 * Class Cookies
 * @package inhere\librarys\webSocket\parts
 */
class Cookies
{
    /**
     * Cookies for HTTP response
     * @var array
     * [
     *  'name' => [ options ...]
     * ]
     */
    protected $responseCookies = [];

    /**
     * Default cookie properties
     * @var array
     */
    protected $defaults = [
        'value' => '',
        'domain' => null,
        'hostOnly' => null,
        'path' => null,
        'expires' => null,
        'secure' => false,
        'httpOnly' => false
    ];

    /**
     * @param array $cookies
     * @return Cookies
     */
    public static function make(array $cookies = [])
    {
        return new self($cookies);
    }

    /**
     * Create new cookies helper
     * @param array $cookies
     */
    public function __construct(array $cookies = [])
    {
        $this->requestCookies = $cookies;
    }

    /**
     * Set default cookie properties
     * @param array $settings
     */
    public function setDefaults(array $settings)
    {
        $this->defaults = array_replace($this->defaults, $settings);
    }

    /**
     * Set response cookie
     *
     * @param string       $name  Cookie name
     * @param string|array $value Cookie value, or cookie properties
     */
    public function set($name, $value)
    {
        if (!is_array($value)) {
            $value = ['value' => (string)$value];
        }
        $this->responseCookies[$name] = array_replace($this->defaults, $value);
    }

    /**
     * Convert to `Set-Cookie` headers
     * @return string[]
     */
    public function toHeaders()
    {
        $headers = [];
        foreach ($this->responseCookies as $name => $properties) {
            $headers[] = $this->toHeader($name, $properties);
        }

        return $headers;
    }

    /**
     * Convert to `Set-Cookie` header
     * @param  string $name       Cookie name
     * @param  array  $properties Cookie properties
     * @return string
     */
    protected function toHeader($name, array $properties)
    {
        $result = urlencode($name) . '=' . urlencode($properties['value']);

        if (isset($properties['domain'])) {
            $result .= '; domain=' . $properties['domain'];
        }

        if (isset($properties['path'])) {
            $result .= '; path=' . $properties['path'];
        }

        if (isset($properties['expires'])) {
            if (is_string($properties['expires'])) {
                $timestamp = strtotime($properties['expires']);
            } else {
                $timestamp = (int)$properties['expires'];
            }
            if ($timestamp !== 0) {
                $result .= '; expires=' . gmdate('D, d-M-Y H:i:s e', $timestamp);
            }
        }

        if (isset($properties['secure']) && $properties['secure']) {
            $result .= '; secure';
        }

        if (isset($properties['hostOnly']) && $properties['hostOnly']) {
            $result .= '; HostOnly';
        }

        if (isset($properties['httpOnly']) && $properties['httpOnly']) {
            $result .= '; HttpOnly';
        }

        return $result;
    }
}
