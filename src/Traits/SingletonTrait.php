<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/18
 * Time: 下午7:31
 */

namespace Inhere\Library\Traits;

use Inhere\Library\Helpers\Obj;

/**
 * Trait SingletonTrait
 * @package Inhere\Library\Traits
 */
trait SingletonTrait
{
    /**
     * @return mixed
     */
    public static function own()
    {
        return Obj::make(static::class);
    }

    private function __clone()
    {
    }
}
