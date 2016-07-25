<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 16-07-25
 * Time: 10:35
 */

namespace inhere\tools\helpers;

use inhere\tools\files\File;

/**
 *
 */
class CurlHelper
{
    private static $retriableErrorCodes = [
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
     * @param string $rename   图片重命名(只写名称，不用后缀) 为空则使用原名称
     */
    public static function fetchImg($imgUrl, $savePath, $rename = '')
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, UrlHelper::encode2($imgUrl));
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 伪造网页来源地址,伪造来自百度的表单提交
        curl_setopt($ch, CURLOPT_REFERER, "http://www.baidu.com");

        $imgData = self::execute($ch);

        // e.g. http://static.oschina.net/uploads/user/277/554046_50.jpg?t=34512323
        if ( strpos($imgUrl, '?')) {
            list($real,) = explode('?', $imgUrl, 2);
        } else {
            $real = $imgUrl;
        }

        $suffix = File::getSuffix($real);
        $name   = $rename ? : File::getName($real, 1);
        $imgFile = $savePath . '/' . $name .$suffix;

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
     * @param string $url url
     * @param array $params url params
     * @param array $headers HEADER info
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
     * @param  resource     $ch     curl handler
     * @param  int          $retries 重试
     * @throws \RuntimeException
     * @return string
     */
    public static function execute($ch, $retries = 3, $closeAfterDone = true)
    {
        while ($retries--) {
            if ( ($ret = curl_exec($ch)) === false) {
                $curlErrno = curl_errno($ch);

                if (false === in_array($curlErrno, self::$retriableErrorCodes, true) || !$retries) {
                    $curlError = curl_error($ch);

                    if ($closeAfterDone) {
                        curl_close($ch);
                    }

                    throw new \RuntimeException(sprintf('Curl error (code %s): %s', $curlErrno, $curlError));
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