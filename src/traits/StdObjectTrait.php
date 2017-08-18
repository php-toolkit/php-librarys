<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/31
 * Time: 下午2:25
 */

namespace inhere\library\traits;

use inhere\exceptions\SetPropertyException;
use inhere\exceptions\GetPropertyException;
use inhere\exceptions\NotFoundException;
use inhere\exceptions\UnknownCalledException;
use inhere\library\helpers\Obj;

/**
 * Class StdObjectTrait
 * @package inhere\library\traits
 */
trait StdObjectTrait
{
    /**
     * get called class full name
     * @return string
     */
    final public static function fullName()
    {
        return static::class;
    }

    /**
     * get called class namespace
     * @param null|string $fullName
     * @return string
     */
    final public static function spaceName($fullName = null)
    {
        $fullName = $fullName ?: self::fullName();
        $fullName = str_replace('\\', '/', $fullName);

        return strpos($fullName, '/') ? dirname($fullName) : null;
    }

    /**
     * get called class name
     * @param null|string $fullName
     * @return string
     */
    final public static function className($fullName = null)
    {
        $fullName = $fullName ?: self::fullName();
        $fullName = str_replace('\\', '/', $fullName);

        return basename($fullName);
    }

    /**
     * StdObject constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        Obj::configure($this, $config);

        $this->init();
    }

    /**
     * init
     */
    protected function init()
    {
        // init something ...
    }

    /**
     * @param $method
     * @param $args
     * @throws UnknownCalledException
     * @return mixed
     */
    public function __call($method, $args)
    {
        // if (method_exists($this, $method) && $this->isAllowCall($method) ) {
        //     return call_user_func_array( array($this, $method), (array) $args);
        // }

        throw new UnknownCalledException('Called a Unknown method! ' . get_class($this) . "->{$method}()");
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     * @throws UnknownCalledException
     */
    public static function __callStatic($method, $args)
    {
        if (method_exists(self::class, $method)) {
            return call_user_func_array([self::class, $method], (array)$args);
        }

        throw new UnknownCalledException('Called a Unknown static method! [ ' . self::class . "::{$method}()]");
    }
}
