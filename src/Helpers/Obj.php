<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/7
 * Time: 下午9:39
 */

namespace Inhere\Library\Helpers;

use Inhere\Library\Traits\ObjectPoolTrait;

/**
 * Class Obj
 *  alias of the ObjectHelper
 * @package Inhere\Library\Helpers
 */
class Obj extends ObjectHelper
{
    use ObjectPoolTrait;

    /**
     * @var array
     */
    private static $objects = [];

    /**
     * @param string $class
     * @return mixed
     */
    public static function make($class)
    {
        if (!isset(self::$objects[$class])) {
            self::$objects[$class] = new $class;
        }

        return self::$objects[$class];
    }

    /**
     * @param $object
     * @return bool
     */
    public static function isArrayable($object)
    {
        return $object instanceof \ArrayAccess || method_exists($object, 'toArray');
    }
}
