<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/27
 * Time: 下午11:14
 */

namespace Inhere\Library\Web;

use Inhere\Library\Collections\SimpleCollection;

/**
 * Class Session
 * @package Inhere\Library\Web
 */
class Session extends SimpleCollection
{
    /**
     * Session constructor.
     * @param array $data
     * @throws \LogicException
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
