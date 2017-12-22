<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/12/22 0022
 * Time: 22:40
 */

namespace Inhere\Library\Traits;

/**
 * Trait NotAllowNewTrait
 * @package Inhere\Library\Traits
 */
trait NotAllowNewTrait
{
    /**
     * This class should not be instantiated.
     */
    private function __construct()
    {
    }
}
