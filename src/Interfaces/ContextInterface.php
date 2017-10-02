<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-09-25
 * Time: 17:51
 */

namespace Inhere\Library\Interfaces;

/**
 * Interface ContextInterface
 * @package Inhere\Library\Interfaces
 */
interface ContextInterface
{
    public function getRequest();

    public function getResponse();
}
