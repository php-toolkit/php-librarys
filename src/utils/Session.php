<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/27
 * Time: 下午11:14
 */

namespace inhere\library\utils;

use inhere\library\collections\SimpleCollection;

/**
 * Class Session
 * @package inhere\library\utils
 */
class Session extends SimpleCollection
{
    /**
     * Session constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        if (null === $_SESSION) {
            throw new \LogicException('the session is not started');
        }

        $this->data = &$_SESSION;

        parent::__construct($data);
    }
}
