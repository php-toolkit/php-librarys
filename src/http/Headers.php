<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-30
 * Time: 19:02
 */

namespace inhere\library\http;

use inhere\library\collections\SimpleCollection;

/**
 * Class Headers
 * @package inhere\library\http
 */
class Headers extends SimpleCollection
{
    /**
     * @inheritdoc
     */
    public function set($key, $value)
    {
        return parent::set($this->normalizeKey($key), $value);
    }

    /**
     * @inheritdoc
     */
    public function get($key, $default = null)
    {
        return parent::get($this->normalizeKey($key), $default);
    }

    /**
     * @inheritdoc
     */
    public function add($key, $value)
    {
        return parent::add($this->normalizeKey($key), $value);
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return parent::has($this->normalizeKey($key));
    }

    /**
     * @inheritdoc
     */
    public function remove($key)
    {
        parent::remove($this->normalizeKey($key));
    }

    /**
     * @param $key
     * @return bool|string
     */
    public function normalizeKey($key)
    {
        $key = str_replace('_', '-', strtolower($key));

        if (strpos($key, 'http-') === 0) {
            $key = substr($key, 5);
        }

        return $key;
    }

    /**
     * get client supported languages from header
     * eg: `Accept-Language:zh-CN,zh;q=0.8`
     * @return array
     */
    public function getAcceptLanguages()
    {
        $ls = [];

        if ( $value = $this->get('Accept-Language') ) {
            if ( strpos($value, ';') ) {
                [$value,] = explode(';', $value,2);
            }

            $value = str_replace(' ', '', $value);
            $ls = explode(',', $value);
        }

        return $ls;
    }

    /**
     * get client supported languages from header
     * eg: `Accept-Encoding:gzip, deflate, sdch, br`
     * @return array
     */
    public function getAcceptEncodes()
    {
        $ens = [];

        if ( $value = $this->get('Accept-Encoding') ) {
            if ( strpos($value, ';') ) {
                [$value,] = explode(';', $value,2);
            }

            $value = str_replace(' ', '', $value);
            $ens = explode(',', $value);
        }

        return $ens;
    }
}
