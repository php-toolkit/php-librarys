<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/31
 * Time: 下午2:25
 */

namespace inhere\librarys\traits;

use inhere\librarys\exceptions\SetPropertyException;
use inhere\librarys\exceptions\GetPropertyException;
use inhere\librarys\exceptions\NotFoundException;
use inhere\librarys\exceptions\UnknownCalledException;

/**
 * Class TraitStaBase
 * @package inhere\librarys\traits
 */
trait TraitStaBase
{
    /**
     * get called class full name
     * @return string
     */
    final public static function fullName()
    {
        return get_called_class();
    }

    /**
     * get called class namespace
     * @param null $fullName
     * @return string
     */
    final public static function spaceName($fullName=null)
    {
        $fullName = $fullName ? : self::fullName();

        return strpos($fullName, '\\') ? dirname($fullName) : null;
    }

    /**
     * get called class name
     * @param null $fullName
     * @return string
     */
    final public static function className($fullName=null)
    {
        $fullName = $fullName ? : self::fullName();

        return basename( $fullName );
    }

    /**
     * @reference yii2 yii\base\Object::__set()
     * @param $name
     * @param $value
     * @throws SetPropertyException
     */
    public function __set($name,$value)
    {
        $method = 'set'.ucfirst( $name );

        if ( method_exists($this, $method) ) {
            $this->$method($value);
        } elseif ( method_exists($this, 'get'.ucfirst( $name )) ) {
            throw new SetPropertyException("Setting a Read-only property! ".get_class($this)."::{$name}");
        } else {
            throw new SetPropertyException("Setting a Unknown property! ".get_class($this)."::{$name}");
        }
    }

    /**
     * @reference yii2 yii\base\Object::__set()
     * @param $name
     * @throws GetPropertyException
     * @return mixed
     */
    public function __get($name)
    {
        $method = 'get'.ucfirst( $name );

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        if (method_exists($this, 'set'.ucfirst( $name ))) {
            throw new GetPropertyException("Getting a Write-only property! ".get_class($this)."::{$name}");
        }

        throw new GetPropertyException("Getting a Unknown property! ".get_class($this)."::{$name}");
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        $getter = 'get'.ucfirst( $name );

        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        }

        return false;
    }

    /**
     * @param $name
     */
    public function __unset($name)
    {
        $setter = 'set'.ucfirst( $name );

        if (method_exists($this, $setter)) {
            $this->$setter(null);
            return;
        }

        throw new NotFoundException('Unset an unknown or read-only property: ' . get_class($this) . '::' . $name);
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

        throw new UnknownCalledException("Called a Unknown method! ".get_class($this)."->{$method}()");
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    static public function __callStatic($method, $args)
    {
        if (method_exists( get_called_class() , $method) ) {

            return call_user_func_array( array( get_called_class() , $method), (array) $args);
        }

        throw new UnknownCalledException("Called a Unknown static method! [ ".get_called_class()."::{$method}()]");
    }
}