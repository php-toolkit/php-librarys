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
 *
 * ```
 * $curl = Curl::make('http://my-site.com');
 * $curl->get('/users/1');
 *
 * $data = $curl->getResponse();
 * ```
 */
class Curl
{
    // request method list
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const PATCH = 'PATCH';
    const DELETE = 'DELETE';
    const HEAD = 'HEAD';
    const OPTIONS = 'OPTIONS';
    const TRACE = 'TRACE';

    private static $supportedMethods = [
        // method => allow post data
        'GET'     => false,
        'POST'    => true,
        'PUT'     => true,
        'PATCH'   => true,
        'DELETE'  => false,
        'HEAD'    => false,
        'OPTIONS' => false,
        'TRACE'   => false,
    ];

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
     * The default curl options
     * @var array
     */
    private static $defaultOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER         => true, // false
        CURLOPT_VERBOSE        => false,
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        // isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        CURLOPT_USERAGENT => '5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
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
     * setting headers for curl
     *
     * [ 'Content-Type' => 'Content-Type: application/json' ]
     *
     * @var array
     */
    private $_headers = [];

    /**
     * setting options for curl
     * @var array
     */
    private $_options = [];

    /**
     * @var array
     */
    private $_cookies = [];

    /**
     * The curl exec response
     * @var string
     */
    private $_response;
    private $_responseBody = '';
    private $_responseHeaders = [];

    /**
     * The curl exec result mete info.
     * @var array
     */
    private $_responseMeta = [
        // http status code
        'status' => 200,
        'errno'  => 0,
        'error'  => '',
        'info'   => '',
    ];

    /**
     * @param string $bashUrl
     * @param int $retries
     * @return Curl
     */
    public static function make($bashUrl = '', $retries = 0)
    {
        return new self($bashUrl, $retries);
    }
    public function __construct($baseUrl = '', $retries = 0)
    {
        if (!extension_loaded('curl')) {
            throw new \ErrorException('The cURL extensions is not loaded, make sure you have installed the cURL extension: https://php.net/manual/curl.setup.php');
        }

        $this->setBashUrl($baseUrl)->setRetries($retries);
    }

    /**
     * @param $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($method, array $args)
    {
        return call_user_func_array([self::make(), $method], $args);
    }

///////////////////////////////////////////////////////////////////////
//   main
///////////////////////////////////////////////////////////////////////

    public function get($url, $params = [], array $headers = [], array $options = [])
    {
        $options[CURLOPT_HTTPGET] = true;

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
        $options[CURLOPT_NOBODY] = true;
        $options[CURLOPT_CUSTOMREQUEST] = self::HEAD;

        return $this->request($url, $params, self::HEAD, $headers, $options);
    }

    public function trace($url, $params = [], array $headers = [], array $options = [])
    {
        $options[CURLOPT_CUSTOMREQUEST] = self::TRACE;

        return $this->request($url, $params, self::TRACE, $headers, $options);
    }

    /**
     * File upload
     * @param string $url       The target url
     * @param string $field     The post field name
     * @param string $filePath  The file path
     * @param string $mimeType The post file mime type
     * param string $postFilename The post file name
     * @return mixed
     */
    public function upload($url, $field, $filePath, $mimeType = '')
    {
        if (!$mimeType) {
            $fInfo = finfo_open(FILEINFO_MIME); // 返回 mime 类型
            $mimeType = finfo_file($fInfo, $filePath) ?: 'application/octet-stream';
        }

        // create file
        if ( function_exists('curl_file_create') ) {
            $file = curl_file_create($filePath, $mimeType); // , $postFilename
        } else {
            $this->setOption(CURLOPT_SAFE_UPLOAD, true);
            $file = "@{$filePath};type={$mimeType}"; // ;filename={$postFilename}
        }

        $headers = [ 'Content-Type' => 'multipart/form-data' ];

        return $this->post($url, [ $field => $file], $headers);
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
        if ( ($fp = fopen($saveTo, 'wb')) === false) {
            throw new \RuntimeException('Failed to save the content', __LINE__);
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
     * @return mixed|false If is fail, will return false
     */
    public function request($url, $data = [], $type = self::GET, array $headers = [], array $options = [])
    {
        $type = strtoupper($type);

        if ( !isset(self::$supportedMethods[$type]) ) {
            throw new \InvalidArgumentException("The method type [$type] is not supported!");
        }

        $this->prepareRequest($headers, $options);

        // init curl
        $ch = curl_init();

        // set options
        curl_setopt_array($ch, $options);

        // add send data
        if ($data) {

            // allow post data
            if ( self::$supportedMethods[$type] ) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                $url .= (strpos($url, '?') ? '&' : '?') . http_build_query($data);
            }
        }

        // set request url
        $url = $this->bashUrl . $url;
        curl_setopt($ch, CURLOPT_URL, $url);

        $response = '';
        $retries = $this->retries + 1;

        // execute
        while ($retries--) {
            if ( false === ($response = curl_exec($ch)) ) {
                $curlErrNo = curl_errno($ch);

                if (false === in_array($curlErrNo, self::$canRetryErrorCodes, true) || !$retries) {
                    $curlError = curl_error($ch);

                    // close
                    curl_close($ch);

                    // throw new \RuntimeException(sprintf('Curl error (code %s): %s', $curlErrNo, $curlError));
                    $this->_responseMeta['errno'] = $curlErrNo;
                    $this->_responseMeta['error'] = $curlError;
                }

                continue;
            }

            // close
            curl_close($ch);
            break;
        }

        // get http status code
        $this->_responseMeta['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if( $this->debug ) {
            $this->_responseMeta['info'] = curl_getinfo($ch);
        }

        $this->_response = $response;

        return $this;
    }

    public function parseResponse()
    {
        if ( !$response = $this->_response ) {
            return $this;
        }

        // have been parsed
        if ( $this->_responseHeaders ) {
            return $this;
        }

        # Headers regex
        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';

        # Extract headers from response
        preg_match_all($pattern, $response, $matches);
        $headers_string = array_pop($matches[0]);
        $headers = explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));

        # Include all received headers in the $headers_string
        while (count($matches[0])) {
            $headers_string = array_pop($matches[0]).$headers_string;
        }

        # Remove all headers from the response body
        $this->_responseBody = str_replace($headers_string, '', $response);

        # Extract the version and status from the first header
        $versionAndStatus = array_shift($headers);

        preg_match_all('#HTTP/(\d\.\d)\s((\d\d\d)\s((.*?)(?=HTTP)|.*))#', $versionAndStatus, $matches);

        $this->_responseHeaders['Http-Version'] = array_pop($matches[1]);
        $this->_responseHeaders['Status-Code'] = array_pop($matches[3]);
        $this->_responseHeaders['Status'] = array_pop($matches[2]);

        # Convert headers into an associative array
        foreach ($headers as $header) {
            preg_match('#(.*?)\:\s(.*)#', $header, $matches);
            $this->_responseHeaders[$matches[1]] = $matches[2];
        }

        return $this;
    }

///////////////////////////////////////////////////////////////////////
//   helper method
///////////////////////////////////////////////////////////////////////

    public function byJson()
    {
        $this->setHeader('Content-Type', 'application/json');

        return $this;
    }

    public function byAjax()
    {
        $this->setHeader('X-Requested-With', 'XMLHttpRequest');

        return $this;
    }

    public function setUserAuth($user, $pwd, $authType = CURLAUTH_BASIC)
    {
        $this->_options[CURLOPT_HTTPAUTH] = $authType;
        $this->_options[CURLOPT_USERPWD] = "$user:$pwd";

        return $this;
    }

    protected function prepareRequest(array $headers = [], array $options = [])
    {
        $this->resetResponse();

        if ($this->debug) {
            $this->_options[CURLOPT_VERBOSE] = true;
        }

        // merge default options
        $this->_options = array_merge(self::$defaultOptions, $this->_options, $options);

        // set headers
        $this->setHeaders($headers);

        // append http headers to options
        if ( $this->_headers ) {
            $this->_options[CURLOPT_HTTPHEADER] = $this->getHeaders(true);
        }

        // append http cookies to options
        if ( $this->_cookies ) {
            $this->_options[CURLOPT_COOKIE] = http_build_query($this->_cookies, '', '; ');
        }
    }

    /**
     * @return array
     */
    public static function supportedMethods()
    {
        return self::$supportedMethods;
    }

    /**
     * @return array
     */
    public static function defaultOptions()
    {
        return self::$defaultOptions;
    }

///////////////////////////////////////////////////////////////////////
//   assert
///////////////////////////////////////////////////////////////////////

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * Was an 'info' header returned.
     */
    public function isInfo()
    {
        return $this->_responseMeta['status'] >= 100 && $this->_responseMeta['status'] < 200;
    }

    /**
     * Was an 'OK' response returned.
     */
    public function isSuccess()
    {
        return $this->_responseMeta['status'] >= 200 && $this->_responseMeta['status'] < 300;
    }

    /**
     * Was a 'redirect' returned.
     */
    public function isRedirect()
    {
        return $this->_responseMeta['status'] >= 300 && $this->_responseMeta['status'] < 400;
    }

    /**
     * Was an 'error' returned (client error or server error).
     */
    public function isError()
    {
        return $this->_responseMeta['status'] >= 400 && $this->_responseMeta['status'] < 600;
    }

///////////////////////////////////////////////////////////////////////
//   response data
///////////////////////////////////////////////////////////////////////

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @param null|string $key
     * @return array|mixed|null
     */
    public function getMeta($key = null)
    {
        return $this->getResponseMeta($key);
    }
    public function getResponseMeta($key = null)
    {
        if ($key) {
            return isset($this->_responseMeta[$key]) ? $this->_responseMeta[$key] : null;
        }

        return $this->_responseMeta;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->getResponseBody();
    }
    public function getResponseBody()
    {
        $this->parseResponse();

        return $this->_responseBody;
    }

    /**
     * @return bool|mixed
     */
    public function getArrayData()
    {
        if ( !$this->getResponseBody() ) {
            return false;
        }

        $data = json_decode($this->getResponseBody(), true);

        if ( json_last_error() > 0 ) {
            return false;
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getResponseHeaders()
    {
        $this->parseResponse();

        return $this->_responseHeaders;
    }

    /**
     * @param string $name
     * @param null $default
     * @return string
     */
    public function getResponseHeader($name, $default = null)
    {
        $this->parseResponse();

        return isset($this->_responseHeaders[$name]) ? $this->_responseHeaders[$name] : $default;
    }


///////////////////////////////////////////////////////////////////////
//   reset
///////////////////////////////////////////////////////////////////////

    /**
     * @return $this
     */
    public function resetHeaders()
    {
        $this->_headers = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function resetCookies()
    {
        $this->_cookies = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function resetOptions()
    {
        $this->_options = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function resetResponse()
    {
        $this->_response = $this->_responseBody = null;
        $this->_responseHeaders = [];
        $this->_responseMeta = [
            // http status code
            'status' => 200,
            'errno'  => 0,
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
        return $this->resetAll();
    }
    public function resetAll()
    {
        $this->_headers = $this->_options = $this->_cookies = [];

        return $this->resetResponse();
    }

///////////////////////////////////////////////////////////////////////
//   getter/setter
///////////////////////////////////////////////////////////////////////

    /**
     * Set contents of HTTP Cookie header.
     * @param string $key The name of the cookie
     * @param string $value The value for the provided cookie name
     * @return $this
     */
    public function setCookie($key, $value)
    {
        $this->_cookies[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getCookies()
    {
        return $this->_cookies;
    }

    /**
     * get Headers
     * @param bool $onlyValues
     * @return array
     */
    public function getHeaders($onlyValues = false)
    {
        return $onlyValues ? array_values($this->_headers) : $this->_headers;
    }

    /**
     * set Headers
     *
     * [
     *  'Content-Type' => 'application/json'
     * ]
     *
     * @param array $headers
     * @param bool $override
     * @return $this
     */
    public function setHeaders(array $headers, $override = false)
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value, $override);
        }

        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @param bool $override
     * @return $this
     */
    public function setHeader($name, $value, $override = false)
    {
        if ($override || !isset($this->_headers[$name])) {
            $this->_headers[$name] = "$name: $value";
        }

        return $this;
    }

    /**
     * @param string|array $name
     * @return $this
     */
    public function delHeader($name)
    {
        foreach ((array)$name as $item) {
            if (isset($this->_headers[$item])) {
                unset($this->_headers[$item]);
            }
        }

        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->_options = array_merge($this->_options, $options);

        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setOption($name, $value)
    {
        $this->_options[$name] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
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
        $this->debug = (bool)$debug;

        return $this;
    }
}
