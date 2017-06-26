<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 14-
 * Time: 10:35
 * Uesd: 主要功能是 hi
 */

namespace inhere\library\helpers;

use inhere\exceptions\DependencyResolutionException;

/**
 * Class ObjectHelper
 * @package inhere\library\helpers
 */
class ObjectHelper
{
    /**
     * 给对象设置属性值
     * @param $object
     * @param array $options
     */
    public static function setAttrs($object, array $options)
    {
        foreach ($options as $property => $value) {
            $object->$property = $value;
        }
    }

    /**
     * 给对象设置属性值
     * @param $object
     * @param array $options
     */
    public static function smartInit($object, array $options)
    {
        foreach ($options as $property => $value) {
            $setter = 'set' . ucfirst($property);

            if (method_exists($object, $setter)) {
                $object->$setter($value);
            } else {
                $object->$property = $value;
            }
        }
    }

    /**
     * @param $data
     * @return array|bool
     */
    public static function toArray($data)
    {
        return DataHelper::toArray($data);
    }

    /**
     * 定义一个用来序列化对象的函数
     * @param mixed $obj
     * @return string
     */
    public static function encode($obj)
    {
        return DataHelper::encode($obj);
    }

    /**
     * 反序列化
     * @param string $txt
     * @param bool|array $allowedClasses
     * @return mixed
     */
    public static function decode($txt, $allowedClasses = false)
    {
        return DataHelper::decode($txt, $allowedClasses);
    }

    /**
     * @param mixed $object
     * @param bool $unique
     * @return string
     */
    public static function hash($object, $unique = true)
    {
        if (is_object($object)) {
            $hash = spl_object_hash($object);

            if ($unique) {
                $hash = md5($hash);
            }

            return $hash;
        }

        // a class
        return is_string($object) ? md5($object) : '';
    }


    /**
     * @from https://github.com/ventoviro/windwalker
     * Build an array of constructor parameters.
     * @param   \ReflectionMethod $method Method for which to build the argument array.
     * @throws DependencyResolutionException
     * @return  array  Array of arguments to pass to the method.
     */
    public static function getMethodArgs(\ReflectionMethod $method)
    {
        $methodArgs = array();

        foreach ($method->getParameters() as $param) {
            $dependency = $param->getClass();
            $dependencyVarName = $param->getName();

            // If we have a dependency, that means it has been type-hinted.
            if (null !== $dependency) {
                $depClass = $dependency->getName();
                $depObject = self::createObject($depClass);

                if ($depObject instanceof $depClass) {
                    $methodArgs[] = $depObject;

                    continue;
                }
            }

            // Finally, if there is a default parameter, use it.
            if ($param->isOptional()) {
                $methodArgs[] = $param->getDefaultValue();

                continue;
            }

            // Couldn't resolve dependency, and no default was provided.
            throw new DependencyResolutionException(sprintf('Could not resolve dependency: %s', $dependencyVarName));
        }

        return $methodArgs;
    }

    /**
     * 从类名创建服务实例对象，会尽可能自动补完构造函数依赖
     * @from windWalker https://github.com/ventoviro/windwalker
     * @param string $class a className
     * @throws DependencyResolutionException
     * @return mixed
     */
    public static function createObject($class)
    {
        try {
            $reflection = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            return false;
        }

        $constructor = $reflection->getConstructor();

        // If there are no parameters, just return a new object.
        if (null === $constructor) {
            return new $class;
        }

        $newInstanceArgs = self::getMethodArgs($constructor);

        // Create a callable for the dataStorage
        return $reflection->newInstanceArgs($newInstanceArgs);
    }
}
