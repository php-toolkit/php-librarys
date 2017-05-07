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
     * @var array
     */
    private $config = [
        'base_url' => '',
        'timeout' => 3,
        'retry' => 3,

        'proxy' => [
            // 'host' => '',
            // 'port' => '',
        ],

        'headers' => [
            // 'host' => 'xx.com'
        ],
        'curlOptions' => [
            // 'key' => 'value'
        ],
    ];

    /**
     * @var string
     */
    private $error;

    /**
     * @var array
     */
    private $info = [];

    /**
     * @param array $config
     * @return self
     */
    public static function make(array $config = [])
    {
        return new self($config);
    }

    /**
     * SimpleCurl constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * GET
     * @param $url
     * @param array $data
     * @param array $options
     * @return array|mixed
     */
    public function get($url, array $data = [], array $options = [])
    {
        $url = $this->buildUrl($url, $data);

        $options = $options ? array_merge($this->config, $options) : $this->config;
        $ch = $this->createCH($url, $options);

        curl_setopt($ch, CURLOPT_HTTPGET, true);

        return $this->request($ch, (int)$this->config['retry']);
    }

    /**
     * POST
     * @param string $url 地址
     * @param array $data 数据
     * @param array $options
     * @return mixed
     */
    public function post($url, array $data = [], array $options = [])
    {
        $options = $options ? array_merge($this->config, $options) : $this->config;

        $url = $this->buildUrl($url);
        $ch = $this->createCH($url, $options);

        // post
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        return $this->request($ch, (int)$this->config['retry']);
    }

    /**
     * PUT
     * @param string $url 地址
     * @param array $data 数据
     * @param array $options
     * @return mixed
     */
    public function put($url, array $data = [], array $options = [])
    {
        $options = $options ? array_merge($this->config, $options) : $this->config;

        $url = $this->buildUrl($url);
        $ch = $this->createCH($url, $options);

        // PUT
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        return $this->request($ch, (int)$this->config['retry']);
    }

    /**
     * PATCH
     * @param string $url 地址
     * @param array $data 数据
     * @param array $options
     * @return mixed
     */
    public function patch($url, array $data = [], array $options = [])
    {
        $options = $options ? array_merge($this->config, $options) : $this->config;

        $url = $this->buildUrl($url);
        $ch = $this->createCH($url, $options);

        // PATCH
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        return $this->request($ch, (int)$this->config['retry']);
    }

    /**
     * DELETE
     * @param string $url 地址
     * @param array $data 数据
     * @param array $options
     * @return mixed
     */
    public function delete($url, array $data = [], array $options = [])
    {
        $options = $options ? array_merge($this->config, $options) : $this->config;

        $url = $this->buildUrl($url, $data);
        $ch = $this->createCH($url, $options);

        // DELETE
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        return $this->request($ch, (int)$this->config['retry']);
    }

    /**
     * Executes a CURL request with optional retries and exception on failure
     *
     * @param  resource $ch curl handle
     * @param  int $retries 重试
     * @param bool $closeAfterDone
     * @return string
     */
    public function request($ch, $retries = 3, $closeAfterDone = true)
    {
        $ret = '';

        while ($retries--) {
            if (($ret = curl_exec($ch)) === false) {
                $curlErrNo = curl_errno($ch);

                if (false === in_array($curlErrNo, self::$canRetryErrorCodes, true) || !$retries) {
                    $curlError = curl_error($ch);

                    if ($closeAfterDone) {
                        curl_close($ch);
                    }

                    $this->error = sprintf('Curl error (code %s): %s', $curlErrNo, $curlError);
                    // throw new \RuntimeException(sprintf('Curl error (code %s): %s', $curlErrNo, $curlError));
                }

                continue;
            }

            if ($closeAfterDone) {
                curl_close($ch);
            }

            break;
        }

        $this->info = curl_getinfo($ch);

        return $ret;
    }

    /**
     * @param string $url
     * @param array $opts
     * @return resource
     */
    public function createCH($url, array $opts = [])
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        // headers
        if ($a = $opts['headers']) {
            $headers = [];

            foreach ($a as $name => $value) {
                $name = ucwords($name);
                $headers[] = "$name: $value";
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // 设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $opts['timeout']);

        // 如果有配置代理这里就设置代理
        if (isset($opts['proxy']) && $opts['proxy']) {
            curl_setopt($ch, CURLOPT_PROXY, $opts['proxy']['host']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $opts['proxy']['port']);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // 要求返回结果而不是输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 设置不返回header 返回的响应就只有body
        curl_setopt($ch, CURLOPT_HEADER, false);

        // set custom options
        if (isset($opts['curlOptions']) && $cOpts = $opts['curlOptions']) {
            curl_setopt_array($ch, $cOpts);
        }

        return $ch;
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->config = [];
    }

    /**
     * @param string $url
     * @param array $data
     * @return string
     */
    protected function buildQuery($url, array $data)
    {
        if ($param = http_build_query($data)) {
            $url .= (strpos($url, '?') ? '&' : '?') . $param;
        }

        return $url;
    }

    /**
     * @param $url
     * @param array $data
     * @return string
     */
    protected function buildUrl($url, array $data = [])
    {
        $url = trim($url);
        $baseUrl = $this->config['base_url'];

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

    /**
     * @param string $url
     * @return $this
     */
    public function setBaseUrl($url)
    {
        $this->config['base_url'] = trim($url);

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->config['base_url'];
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
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
