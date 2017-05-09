<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/5
 * Time: 下午9:17
 */

namespace inhere\library\http;

/**
 * Class CurlMulti
 * @package inhere\library\http
 */
class CurlMulti extends CurlLite
{
    /**
     * @var array
     */
    private $errors = [];

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
    public function create(array $data)
    {
        $this->mh = curl_multi_init();

        foreach ($data as $key => $opts) {
            $opts = array_merge($this->defaultOptions, $opts);

            $this->chMap[$key] = $this->createResource($opts['url'], [], [], $opts);

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

        $responses = [];

        // 关闭全部句柄
        foreach ($this->chMap as $key => $ch) {
            curl_multi_remove_handle($mh, $ch);
            $eno = curl_errno($ch);

            if ($eno) {
                $eor = curl_error($ch);
                $this->errors[$key] = [$eno, $eor];
                $responses[$key] = null;
            } else {
                $responses[$key] = curl_multi_getcontent($ch);
            }
        }

        curl_multi_close($mh);

        return $responses;
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        parent::__destruct();

        $this->mh = null;
        $this->chMap = [];
    }
}
