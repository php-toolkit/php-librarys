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
        if ($param = http_build_query($data)) {
            $url .= (strpos($url, '?') ? '?' : '&') . $param;
        }

        $ch = $this->createCH($url, $headers);

        return $this->execute($ch, (int)$this->config['retry']);
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
        $ch = $this->createCH($url, $headers);

        // post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        return $this->execute($ch, (int)$this->config['retry']);
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
     * make Multi
     * @param  array $data
     * @return self
     */
    public function makeMulti(array $data)
    {
        $this->mh = curl_multi_init();

        foreach ($data as $key => $opts) {
            $this->chMap[$key] = curl_init();

            curl_setopt($this->chMap[$key], CURLOPT_RETURNTRANSFER, true);

            curl_multi_add_handle($this->mh, $this->chMap[$key]);
        }

        return $this;
    }

    public function exec($mh = null)
    {
        if (!$mh = $mh ?: $this->mh) {
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

        // 关闭全部句柄
        foreach ($this->chMap as $key => $ch) {
            curl_multi_remove_handle($mh, $ch);
        }

        curl_multi_close($mh);

        return true;
    }

    /**
     * @param string $url
     * @param array $headers
     * @return resource
     */
    protected function createCH($url, array $headers = [])
    {
        $url = trim($url);
        $baseUrl = $this->getBaseUrl();

        // is a url part.
        if (0 !== strpos($url, 'http:') && 0 !== strpos($url, 'https:') && 0 !== strpos($url, '//')) {
            $url = $baseUrl . $url;
        }

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

        // 设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);

        // 如果有配置代理这里就设置代理
        if ($this->config['proxy_host'] && $this->config['proxy_port'] > 0) {
            curl_setopt($ch, CURLOPT_PROXY, $this->config['proxy_host']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->config['proxy_port']);
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
     * Executes a CURL request with optional retries and exception on failure
     *
     * @param  resource $ch curl handle
     * @param  int $retries 重试
     * @param bool $closeAfterDone
     * @return string
     */
    public function execute($ch, $retries = 3, $closeAfterDone = true)
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
