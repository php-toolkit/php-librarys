<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 16-07-25
 * Time: 10:35
 */

namespace inhere\library\helpers;

use inhere\library\files\Directory;
use inhere\library\files\File;

/**
 * Class CurlHelper
 * @package inhere\library\helpers
 */
class CurlHelper
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
     * @param string $imgUrl image url e.g. http://static.oschina.net/uploads/user/277/554046_50.jpg
     * @param string $savePath 图片保存路径
     * @param string $rename 图片重命名(只写名称，不用后缀) 为空则使用原名称
     * @return string
     */
    public static function fetchImg($imgUrl, $savePath, $rename = '')
    {
        // e.g. http://static.oschina.net/uploads/user/277/554046_50.jpg?t=34512323
        if ( strpos($imgUrl, '?')) {
            [$real,] = explode('?', $imgUrl, 2);
        } else {
            $real = $imgUrl;
        }

        $last = trim(strrchr($real, '/'), '/');

        // special url e.g http://img.blog.csdn.net/20150929103749499
        if ( false === strpos($last, '.')) {
            $suffix = '.jpg';
            $name   = $rename ? : $last;
        } else {
            $suffix = File::getSuffix($real) ?: '.jpg';
            $name   = $rename ? : File::getName($real, 1);
        }

        $imgFile = $savePath . '/' . $name .$suffix;

        if ( file_exists($imgFile) ) {
            return $imgFile;
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, UrlHelper::encode2($imgUrl));
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 伪造网页来源地址,伪造来自百度的表单提交
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.baidu.com');

        $imgData = self::execute($ch);

        Directory::create($savePath);

        file_put_contents($imgFile, $imgData);

        return $imgFile;
    }

    /**
     * send GET request
     * @param string $url url
     * @param array $params url params
     * @param array $headers HEADER info
     * @return string
     */
    public static function get($url, array $params = [], array $headers = [])
    {
        if ($params) {
            $url .= (strpos($url, '?') ? '&' : '?') . http_build_query($params);
        }

        // $headers = [ 'Content-Type: application/json' ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //伪造网页来源地址,伪造来自百度的表单提交
        // curl_setopt($ch, CURLOPT_REFERER, "http://www.baidu.com");

        return self::execute($ch);
    }

    /**
     * send POST request
     *
     * @param string        $url     submit url
     * @param array|string  $data    post data. array: form data, string: json data
     * @param array         $headers HEADER info
     * @return string
     */
    public static function post($url, array $data = [], array $headers = [])
    {
        // $headers = [ 'Content-Type: application/json' ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // 发送数据
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 执行HTTP请求
        return self::execute($ch);
    }

    /**
     * Executes a CURL request with optional retries and exception on failure
     *
     * @param  resource $ch curl handler
     * @param  int $retries 重试
     * @param bool $closeAfterDone
     * @return string
     */
    public static function execute($ch, $retries = 3, $closeAfterDone = true)
    {
        $ret = '';
        while ($retries--) {
            if ( ($ret = curl_exec($ch)) === false) {
                $curlErrNo = curl_errno($ch);

                if (false === in_array($curlErrNo, self::$canRetryErrorCodes, true) || !$retries) {
                    $curlError = curl_error($ch);

                    if ($closeAfterDone) {
                        curl_close($ch);
                    }

                    throw new \RuntimeException(sprintf('Curl error (code %s): %s', $curlErrNo, $curlError));
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
}
