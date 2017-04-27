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
     * @var array
     */
    private $config = [
        'base_url' => '',
        'timeout' => 3,

        'proxy_host' => '',
        'proxy_port' => '',
    ];

    /**
     * @var string
     */
    private $error;

    /**
     * @param array $config
     * @return SimpleCurl
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
     * @return array|mixed
     */
    public function get($url, array $data = [])
    {
        if ($param = http_build_query($data)) {
            $url .= (strpos($url, '?') ? '?' : '&') . $param;
        }

        $ch = $this->createCH($url);

        if (false === ($data = curl_exec($ch))) {
            $this->error = curl_errno($ch) . ': ' . curl_error($ch);
        }

        curl_close($ch);

        return $data;
    }

    /**
     * POST
     * @param string $url 地址
     * @param array $data 数据
     * @return mixed
     */
    public function post($url, array $data = [])
    {
        $ch = $this->createCH($url);

        // post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        if (false === ($data = curl_exec($ch))) {
            $this->error = curl_errno($ch) . ': ' . curl_error($ch);
        }

        curl_close($ch);

        return $data;
    }

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
     * @param $url
     * @return resource
     */
    protected function createCH($url)
    {
        $url = trim($url);
        $baseUrl = $this->getBaseUrl();

        // is a url part.
        if (0 !== strpos($url, 'http:') && 0 !== strpos($url, 'https:') && 0 !== strpos($url, '//')) {
            $url = $baseUrl . $url;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        //设置超时
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
