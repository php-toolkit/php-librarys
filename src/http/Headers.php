<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-30
 * Time: 19:02
 */

namespace inhere\librarys\http;

use inhere\librarys\collections\SimpleCollection;

/**
 * Class Headers
 * @package inhere\librarys\http
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
        $key = strtr(strtolower($key), '_', '-');
        if (strpos($key, 'http-') === 0) {
            $key = substr($key, 5);
        }

        return $key;
    }
}