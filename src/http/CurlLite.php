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

        'proxy_host' => '',
        'proxy_port' => '',

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
     * @param array $headers
     * @return array|mixed
     */
    public function get($url, array $data = [], array $headers = [])
    {
        $url = $this->buildQuery($url, $data);
        $url = $this->buildUrl($url);
        $ch = $this->createCH($url, $headers, $this->config);

        curl_setopt($ch, CURLOPT_HTTPGET, true);

        return $this->request($ch, (int)$this->config['retry']);
    }

    /**
     * POST
     * @param string $url 地址
     * @param array $data 数据
     * @param array $headers
     * @return mixed
     */
    public function post($url, array $data = [], array $headers = [])
    {
        $url = $this->buildUrl($url);
        $ch = $this->createCH($url, $headers, $this->config);

        // post
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        return $this->request($ch, (int)$this->config['retry']);
    }

    /**
     * PUT
     * @param string $url 地址
     * @param array $data 数据
     * @param array $headers
     * @return mixed
     */
    public function put($url, array $data = [], array $headers = [])
    {
        $url = $this->buildUrl($url);
        $ch = $this->createCH($url, $headers, $this->config);

        // PUT
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        return $this->request($ch, (int)$this->config['retry']);
    }

    /**
     * PATCH
     * @param string $url 地址
     * @param array $data 数据
     * @param array $headers
     * @return mixed
     */
    public function patch($url, array $data = [], array $headers = [])
    {
        $url = $this->buildUrl($url);
        $ch = $this->createCH($url, $headers, $this->config);

        // PATCH
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        return $this->request($ch, (int)$this->config['retry']);
    }

    /**
     * DELETE
     * @param string $url 地址
     * @param array $data 数据
     * @param array $headers
     * @return mixed
     */
    public function delete($url, array $data = [], array $headers = [])
    {
        $url = $this->buildQuery($url, $data);
        $url = $this->buildUrl($url);
        $ch = $this->createCH($url, $headers, $this->config);

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

        return $ret;
    }

    /**
     * @param string $url
     * @param array $headers
     * @param array $opts
     * @return resource
     */
    public function createCH($url, array $headers = [], array $opts = [])
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        // headers
        if ($a = array_merge($this->config['headers'], $headers)) {
            $headers = [];

            foreach ($a as $name => $value) {
                $name = ucwords($name);
                $headers[$name] = $value;
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // set custom options
        if (isset($opts['curlOptions']) && $cOpts = $opts['curlOptions']) {
            curl_setopt_array($ch, $cOpts);
        }

        // 设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $opts['timeout']);

        // 如果有配置代理这里就设置代理
        if ($opts['proxy_host'] && $opts['proxy_port'] > 0) {
            curl_setopt($ch, CURLOPT_PROXY, $opts['proxy_host']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $opts['proxy_port']);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // 要求返回结果而不是输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 设置不返回header 返回的响应就只有body
        curl_setopt($ch, CURLOPT_HEADER, false);

        return $ch;
    }

    /**
     * @var array
     */
    private $chMap = [];

    /**
     * @var resource
     */
    private $mh;

    /**
     * @var array
     */
    private $defaultOpts = [
        'url' => '',
        'type' => 'GET', // 'POST'
        'timeout' => 3,
        'headers' => [],
        'data' => [],
        'curlOptions' => [],
    ];

    /**
     * make Multi
     * @param  array $data
     * @return self
     */
    public function createMultiCh(array $data)
    {
        $this->mh = curl_multi_init();

        foreach ($data as $key => $opts) {
            $opts = array_merge($this->defaultOpts, $opts);

            switch ($opts['type']) {
                case 'POST':
                    $opts[CURLOPT_POST] = true;
                    $opts[CURLOPT_POSTFIELDS] = $data;
                    break;
                case 'PUT':
                    $opts[CURLOPT_PUT] = true;
                    $opts[CURLOPT_POSTFIELDS] = $data;
                    break;
                case 'PATCH':
                    $opts[CURLOPT_CUSTOMREQUEST] = 'PATCH';
                    $opts[CURLOPT_POSTFIELDS] = $data;
                    break;
                case 'DELETE':
                    $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                    $opts['url'] = $this->buildQuery($opts['url'], $opts['data']);
                    break;
                case 'GET':
                default:
                    $opts[CURLOPT_HTTPGET] = true;
                    $opts['url'] = $this->buildQuery($opts['url'], $opts['data']);
                break;
            }

            $this->chMap[$key] = $this->createCH($opts['url'], $opts['headers'], $opts);

            curl_multi_add_handle($this->mh, $this->chMap[$key]);
        }

        unset($data);

        return $this;
    }

    /**
     * execute multi request
     * @param null|resource $mh
     * @return bool|array
     */
    public function execute($mh = null)
    {
        if (!($mh = $mh ?: $this->mh)) {
            return false;
        }

        $active = true;
        $mrc = CURLM_OK;

        while ($active && $mrc === CURLM_OK) {

            // Solve CPU 100% usage
            if (curl_multi_select($mh) === -1) {
                usleep(100);
            }

            do {
                $mrc = curl_multi_exec($mh, $active);
                // curl_multi_select($mh); // Solve CPU 100% usage
            } while ($mrc === CURLM_CALL_MULTI_PERFORM);
        }

        $responseMap = [];

        // 关闭全部句柄
        foreach ($this->chMap as $key => $ch) {
            curl_multi_remove_handle($mh, $ch);
            $responseMap[$key] = curl_multi_getcontent($ch);
        }

        curl_multi_close($mh);

        return $responseMap;
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->mh = null;
        $this->chMap = [];
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
     * @return string
     */
    protected function buildUrl($url)
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
}
