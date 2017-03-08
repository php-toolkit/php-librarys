<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-08
 * Time: 16:40
 */

namespace inhere\librarys\utils;

/**
 * Class Curl
 * @package inhere\librarys\utils
 */
class Curl
{
    // request method list
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const HEAD = 'HEAD';
    const OPTIONS = 'OPTIONS';
    const TRACE = 'TRACE';
    const PATCH = 'PATCH';

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * set a base url
     * @var string
     */
    private $bashUrl;

    /**
     * set retries times
     * @var int
     */
    private $retries = 0;

    /**
     * setting headers for curl
     *
     * [ 'Content-Type' => 'application/json' ]
     *
     * @var array
     */
    private $headers = [];

    /**
     * setting options for curl
     * @var array
     */
    private $options = [];

    /**
     * The curl exec result mete info.
     * @var array
     */
    private $meta = [
        // http status code
        'status' => 200,
        'error'  => '',
        'info'   => '',
    ];

    /**
     * The default options
     *
     * @var array
     */
    private static $defaultOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER         => false,
        CURLOPT_VERBOSE        => true,
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
       // CURLOPT_USERAGENT => '5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
        //CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
    ];

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
     * @param string $bashUrl
     * @return Curl
     */
    public static function init($bashUrl = '')
    {
        return new self($bashUrl);
    }
    public function __construct($baseUrl = '')
    {
        $this->setBashUrl($baseUrl);
    }

    /**
     * @param $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($method, array $args)
    {
        return call_user_func_array([self::init(), $method], $args);
    }

    public function get($url, $params = [], array $headers = [], array $options = [])
    {
        return $this->request($url, $params, self::GET, $headers, $options);
    }

    public function post($url, $data = [], array $headers = [], array $options = [])
    {
        $options[CURLOPT_POST] = true;

        return $this->request($url, $data, self::POST, $headers, $options);
    }

    public function put($url, $data = [], array $headers = [], array $options = [])
    {
        $options[CURLOPT_PUT] = true;

        return $this->request($url, $data, self::PUT, $headers, $options);
    }

    public function patch($url, $data = [], array $headers = [], array $options = [])
    {
        $options[CURLOPT_CUSTOMREQUEST] = self::PATCH;

        return $this->request($url, $data, self::PATCH, $headers, $options);
    }

    public function delete($url, $data = [], array $headers = [], array $options = [])
    {
        $options[CURLOPT_CUSTOMREQUEST] = self::DELETE;

        return $this->request($url, $data, self::DELETE, $headers, $options);
    }

    public function options($url, $data = [], array $headers = [], array $options = [])
    {
        return $this->request($url, $data, self::OPTIONS, $headers, $options);
    }

    public function head($url, $params = [], array $headers = [], array $options = [])
    {
        return $this->request($url, $params, self::HEAD, $headers, $options);
    }

    public function trace($url, $params = [], array $headers = [], array $options = [])
    {
        return $this->request($url, $params, self::TRACE, $headers, $options);
    }

    /**
     * File upload
     * @param $url
     * @param string $field The post field name
     * @param string $file  The file path
     * @param string $postFilename The post file name
     * @return mixed
     */
    public function upload($url, $field, $file, $postFilename = '')
    {
        $postFilename = $postFilename ? : $file;
        $postFilename = basename($postFilename);

        $fInfo = finfo_open(FILEINFO_MIME); // 返回 mime 类型
        $mimeType = finfo_file($fInfo, $file);

        if ( class_exists('CURLFile', false) ) {
            $this->setOption('CURLOPT_SAFE_UPLOAD', true);

            $file = curl_file_create($file, $mimeType, $postFilename);
        } else {
            $file = "@{$file};type={$mimeType};filename={$postFilename}";
        }

        return $this->post($url, [$field => $file] );
    }

    /**
     * File download and save
     * @param string $url
     * @param string $saveTo
     * @return self
     * @throws \Exception
     */
    public function download($url, $saveTo)
    {
        if ( $err = $this->meta['error'] ) {
            throw new \Exception($err, __LINE__);
        }

        $fp = fopen($saveTo, 'wb');

        if ($fp === false) {
            throw new \Exception('Failed to save the content', __LINE__);
        }

        $data = $this->request($url);

        fwrite($fp, $data);
        fclose($fp);

        return $this;
    }

    /**
     * send request
     * @param $url
     * @param array $data
     * @param string $type
     * @param array $headers
     * @param array $options
     * @return mixed
     */
    public function request($url, $data = [], $type = self::GET, array $headers = [], array $options = [])
    {
        $url = $this->bashUrl . $url;

        $this->resetMeta();

        // set some property
        $this->setHeaders($headers)->setOptions($options);

        // merge default options
        $options = array_merge(self::$defaultOptions, $this->options);

        // append headers to options
        if ( $this->headers ) {
            $options[CURLOPT_HTTPHEADER] = $this->getHeaders(true);
        }

        // init curl
        $ch = curl_init();

        // set options
        curl_setopt_array($ch, $options);

        // add send data
        if ($data) {
            if ( in_array($type, self::allowPostData()) ) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                $url .= (strpos($url, '?') ? '&' : '?') . http_build_query($data);
            }
        }

        // set request url
        curl_setopt($ch, CURLOPT_URL, $url);

        $result = '';
        $retries = $this->retries + 1;

        // execute
        while ($retries--) {
            if ( ($result = curl_exec($ch)) === false) {
                $curlErrNo = curl_errno($ch);

                if (false === in_array($curlErrNo, self::$canRetryErrorCodes, true) || !$retries) {
                    $curlError = curl_error($ch);

                    // close
                    curl_close($ch);

                    throw new \RuntimeException(sprintf('Curl error (code %s): %s', $curlErrNo, $curlError));
                }

                continue;
            }

            // close
            curl_close($ch);
            break;
        }

        // get http status code
        $this->meta['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

//        if( !$result ){
//            $this->meta['error'] = curl_error($ch);
//        }

        if($this->debug) {
            $this->meta['info'] = curl_getinfo($ch);
        }

        return $result;
    }

    /**
     * @return array
     */
    public static function allowPostData()
    {
        return [self::POST, self::PUT, self::DELETE, self::PATCH, self::OPTIONS];
    }

    /**
     * @return array
     */
    public static function supportedMethods()
    {
        return [self::GET, self::POST, self::PUT, self::DELETE, self::HEAD, self::PATCH, self::OPTIONS, self::TRACE];
    }

    /**
     * get Headers
     * @param bool $handle
     * @return array
     */
    public function getHeaders($handle = false)
    {
        if ($handle) {
            $headers = [];

            foreach ($this->headers as $name => $value) {
                $headers[] = "$name: $value";
            }

            return $headers;
        }

        return $this->headers;
    }

    /**
     * set Headers
     *
     * [
     *  'Content-Type' => 'application/json'
     * ]
     *
     * @param array $headers
     * @param bool $replace
     * @return $this
     */
    public function setHeaders(array $headers, $replace = false)
    {
        if ($replace) {
            $this->headers = $headers;
        } else {
            $this->headers = array_merge($this->headers, $headers);
        }

        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param string|array $name
     * @return $this
     */
    public function delHeader($name)
    {
        foreach ((array)$name as $item) {
            if (isset($this->headers[$item])) {
                unset($this->headers[$item]);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function resetHeaders()
    {
        $this->headers = [];

        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return $this
     */
    public function resetOptions()
    {
        $this->options = [];

        return $this;
    }

    /**
     * @param null|string $key
     * @return array|mixed|null
     */
    public function getMeta($key = null)
    {
        if ($key) {
            return isset($this->meta[$key]) ? $this->meta[$key] : null;
        }

        return $this->meta;
    }

    /**
     * @return $this
     */
    public function resetMeta()
    {
        $this->meta = [
            // http status code
            'status' => 200,
            'error'  => '',
            'info'   => '',
        ];

        return $this;
    }

    /**
     * reset
     * @return $this
     */
    public function reset()
    {
        $this->headers = $this->options = $this->meta = [];

        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultOptions()
    {
        return self::$defaultOptions;
    }

    /**
     * @return int
     */
    public function getRetries()
    {
        return $this->retries;
    }

    /**
     * @param int $retries
     * @return $this
     */
    public function setRetries($retries)
    {
        $this->retries = (int)$retries;

        return $this;
    }

    /**
     * @return string
     */
    public function getBashUrl()
    {
        return $this->bashUrl;
    }

    /**
     * @param string $bashUrl
     * @return $this
     */
    public function setBashUrl($bashUrl)
    {
        $this->bashUrl = trim($bashUrl);

        return $this;
    }

    /**
     * @param bool $debug
     * @return $this
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->debug;
    }
}