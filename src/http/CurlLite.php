<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-04-27
 * Time: 18:44
 */

namespace inhere\library\http;

/**
 * Class CurlLite - a lite curl tool
 * @package inhere\library\http
 */
class CurlLite
{
    /**
     * Can to retry
     * @var array
     */
    private static $canRetryErrorCodes = [
        CURLE_COULDNT_RESOLVE_HOST,
        CURLE_COULDNT_CONNECT,
        CURLE_HTTP_NOT_FOUND,
        CURLE_READ_ERROR,
        CURLE_OPERATION_TIMEOUTED,
        CURLE_HTTP_POST_ERROR,
        CURLE_SSL_CONNECT_ERROR,
    ];

    /**
     * base Url
     * @var string
     */
    private $baseUrl = '';

    /**
     * @var string
     */
    private $error;

    /**
     * @var array
     */
    private $info = [];

    /**
     * @var array
     */
    protected $defaultOptions = [
        'url' => '',
        'method' => 'GET', // 'POST'
        'retry' => 3,
        'timeout' => 10,

        'headers' => [
            // name => value
        ],
        'proxy' => [
            // 'host' => '',
            // 'port' => '',
        ],
        'data' => [],
        'curlOptions' => [],
    ];

    /**
     * @param array $options
     * @return static
     */
    public static function make(array $options = [])
    {
        return new static($options);
    }

    /**
     * SimpleCurl constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['baseUrl'])) {
            $this->setBaseUrl($options['baseUrl']);
            unset($options['baseUrl']);
        }

        $this->defaultOptions = array_merge($this->defaultOptions, $options);
    }

    /**
     * GET
     * @param $url
     * @param mixed $data
     * @param array $options
     * @return array|mixed
     */
    public function get($url, $data = null, array $headers = [], array $options = [])
    {
        return $this->request($url, $data, 'GET', $headers, $options);
    }

    /**
     * POST
     * @param string $url 地址
     * @param mixed $data 数据
     * @param array $options
     * @return mixed
     */
    public function post($url, $data = null, array $headers = [], array $options = [])
    {
        return $this->request($url, $data, 'POST', $headers, $options);
    }

    /**
     * PUT
     * @param string $url 地址
     * @param mixed $data 数据
     * @param array $options
     * @return mixed
     */
    public function put($url, $data = null, array $headers = [], array $options = [])
    {
        return $this->request($url, $data, 'PUT', $headers, $options);
    }

    /**
     * PATCH
     * @param string $url 地址
     * @param mixed $data 数据
     * @param array $options
     * @return mixed
     */
    public function patch($url, $data = null, array $headers = [], array $options = [])
    {
        return $this->request($url, $data, 'PATCH', $headers,  $options);
    }

    /**
     * DELETE
     * @param string $url 地址
     * @param mixed $data 数据
     * @param array $options
     * @return mixed
     */
    public function delete($url, $data = null, array $headers = [], array $options = [])
    {
        return $this->request($url, $data, 'DELETE', $headers, $options);
    }

    /**
     * Executes a CURL request with optional retries and exception on failure
     *
     * @param string $url url path
     * @param mixed $data send data
     * @param array $options
     * @return string
     */
    public function request($url, $data = null, $method = 'GET', array $headers = [], array $options = [])
    {
        $options = array_merge($this->options, $options);

        if ($method) {
            $options['method'] = $method;
        }

        $ch = $this->createResource($url, $data, $headers, $options);

        $ret = '';
        $retries = isset($options['retry']) ? (int)$options['retry'] : 3;

        while ($retries--) {
            if (($ret = curl_exec($ch)) === false) {
                $curlErrNo = curl_errno($ch);

                if (false === in_array($curlErrNo, self::$canRetryErrorCodes, true) || !$retries) {
                    $curlError = curl_error($ch);

                    $this->error = sprintf('Curl error (code %s): %s', $curlErrNo, $curlError);
                    // throw new \RuntimeException(sprintf('Curl error (code %s): %s', $curlErrNo, $curlError));
                }
                continue;
            }

            break;
        }

        curl_close($ch);
        $this->info = curl_getinfo($ch);

        return $ret;
    }

    /**
     * @param string $url
     * @param array $opts
     * @return resource
     */
    public function createResource($url, $data = null, array $headers = [], array $opts = [])
    {
        $ch = curl_init();

        $curlOptions = [
            // 设置超时
            CURLOPT_TIMEOUT => (int)$opts['timeout'],

            // disable 'https' verify
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,

            // 要求返回结果而不是输出到屏幕上
            CURLOPT_RETURNTRANSFER => true,

            // 设置不返回header 返回的响应就只有body
            CURLOPT_HEADER => false,
        ];

        $curlOptions[CURLOPT_URL] = $this->buildUrl($url);

        $method = strtoupper($opts['method']);
        switch ($method) {
            case 'GET':
                $curlOptions[CURLOPT_HTTPGET] = true;
                break;
            case 'POST':
                $curlOptions[CURLOPT_POST] = true;
                break;
            case 'PUT':
                $curlOptions[CURLOPT_PUT] = true;
                break;
            case 'HEAD':
                $curlOptions[CURLOPT_HEADER] = true;
                $curlOptions[CURLOPT_NOBODY] = true;
                break;
            default:
                $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
        }

        // data
        if (isset($opts['data'])) {
            $data = array_merge($opts['data'], $data);
        }
        if ($data) {
            $curlOptions[CURLOPT_POSTFIELDS] = $data;
        }

        // headers
        if ($opts['headers']) {
            $headers = array_merge($opts['headers'], $headers);
        }
        if ($headers) {
            $formatted = [];

            foreach ($headers as $name => $value) {
                $name = ucwords($name);
                $formatted[] = "$name: $value";
            }

            $curlOptions[CURLOPT_HTTPHEADER]  = $formatted;
        }

        foreach ($curlOptions as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        // 如果有配置代理这里就设置代理
        if (isset($opts['proxy']) && $opts['proxy']) {
            curl_setopt($ch, CURLOPT_PROXY, $opts['proxy']['host']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $opts['proxy']['port']);
        }

        // add custom options
        if ($opts['curlOptions']) {
            curl_setopt_array($opts['curlOptions']);
        }

        return $ch;
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->options = [];
    }

    /**
     * @param string $url
     * @param mixed $data
     * @return string
     */
    protected function buildQuery($url, mixed $data)
    {
        if ($param = http_build_query($data)) {
            $url .= (strpos($url, '?') ? '&' : '?') . $param;
        }

        return $url;
    }

    /**
     * @param $url
     * @param mixed $data
     * @return string
     */
    protected function buildUrl($url, $data = null)
    {
        $url = trim($url);
        $baseUrl = $this->options['base_url'];

        // is a url part.
        if (!$this->isFullUrl($url)) {
            $url = $baseUrl . $url;
        }

        // check again
        if (!$this->isFullUrl($url)) {
            throw new \RuntimeException("The request url is not full, URL $url");
        }

        if ($data) {
            return $this->buildQuery($url, $data);
        }

        return $url;
    }

    /**
     * @param $url
     * @return bool
     */
    public function isFullUrl($url)
    {
        return 0 === strpos($url, 'http:') || 0 === strpos($url, 'https:') || 0 === strpos($url, '//');
    }

    public static function mergeOptions($a, $b)
    {
        if (!$a) {
            return $b;
        }

        if (!$b) {
            return $a;
        }

        foreach ($b as $key => $val) {
            $a[$key] = $val;
        }

        return $a;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setBaseUrl($url)
    {
        $this->baseUrl = trim($url);

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return array
     */
    public function getDefaultOptions(): array
    {
        return $this->defaultOptions;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
    }
}
