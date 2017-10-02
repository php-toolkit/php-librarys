<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/27
 * Time: 下午11:14
 */

namespace Inhere\Library\Components;

use Inhere\Library\Collections\SimpleCollection;

/**
 * Class Cookie
 * @package Inhere\Library\Utils
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
