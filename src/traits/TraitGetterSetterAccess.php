<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/29 0029
 * Time: 22:03
 */

namespace inhere\library\traits;

use inhere\exceptions\GetPropertyException;
use inhere\exceptions\SetPropertyException;

/**
 * Class TraitGetterSetterAccess
 * @package inhere\library\traits
 *
 * ```
 * class A
 * {
 *     use TraitGetterSetterAccess;
 * }
 * ```
 */
trait TraitGetterSetterAccess
{
    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return property_exists($this, $name);
    }

    /**
     * @param $name
     * @return mixed|null
     * @throws GetPropertyException
     */
    public function __get($name)
    {
        $method = 'get' . ucfirst($name);

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        if (method_exists($this, 'set' . ucfirst($name))) {
            throw new GetPropertyException('Getting a Write-only property! ' . get_class($this) . "::{$name}");
        }

        throw new GetPropertyException('Getting a Unknown property! ' . get_class($this) . "::{$name}");
    }

    /**
     * @param string $name
     * @param $value
     * @throws SetPropertyException
     */
    public function __set(string $name, $value)
    {
        $method = 'set' . ucfirst($name);

        if (method_exists($this, $method)) {
            $this->$method($value);
        } elseif (method_exists($this, 'get' . ucfirst($name))) {
            throw new SetPropertyException('Setting a Read-only property! ' . get_class($this) . "::{$name}");
        } else {
            throw new SetPropertyException('Setting a Unknown property! ' . get_class($this) . "::{$name}");
        }
    }
}
