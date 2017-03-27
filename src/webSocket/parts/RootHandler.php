<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/26 0026
 * Time: 15:34
 */

namespace inhere\librarys\webSocket\parts;

/**
 * Class RootHandler
 *
 * handle the root '/' webSocket request
 *
 * @package inhere\librarys\webSocket\parts
 */
class RootHandler extends ARouteHandler
{
    public function indexCommand()
    {
        $this->getApp()->respond('hello, this is [/index]');
    }
}
