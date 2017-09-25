<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-09-25
 * Time: 17:51
 */

namespace inhere\library\interfaces;

/**
 * Interface ContextInterface
 * @package inhere\library\interfaces
 */
interface ContextInterface
{
    public function getRequest();

    public function getResponse();
}