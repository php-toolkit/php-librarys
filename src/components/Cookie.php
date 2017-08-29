<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/27
 * Time: 下午11:14
 */

namespace inhere\library\components;

use inhere\library\collections\SimpleCollection;

/**
 * Class Cookie
 * @package inhere\library\utils
 */
class Cookie extends SimpleCollection
{
    /**
     * Session constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = &$_COOKIE;

        parent::__construct($data);
    }
}
