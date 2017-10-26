<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 14-
 * Time: 10:35
 * Uesd: 主要功能是 hi
 */

namespace Inhere\Library\Helpers;

use Inhere\Exceptions\DependencyResolutionException;

/**
 * Class ObjectHelper
 * @package Inhere\Library\Helpers
 */
class ObjectHelper
{
    /**
     * @param mixed $object An object instance
     * @param array $options
     * @return mixed
     */
    public static function init($object, array $options)
    {
        return self::smartConfigure($object, $options);
    }

    /**
     * 给对象设置属性值
     * - 会先尝试用 setter 方法设置属性
     * - 再尝试直接设置属性
     * @param mixed $object An object instance
     * @param array $options
     * @return mixed
     */
    public static function smartConfigure($object, array $options)
    {
        foreach ($options as $property => $value) {
            if (is_numeric($property)) {
                continue;
            }

            $setter = 'set' . ucfirst($property);

            // has setter
            if (method_exists($object, $setter)) {
                $object->$setter($value);
            } else {
                $object->$property = $value;
            }
        }

        return $object;
    }

    /**
     * 给对象设置属性值
     * @param $object
     * @param array $options
     */
    public static function setAttrs($object, array $options)
    {
        self::configure($object, $options);
    }

    /**
     * 给对象设置属性值
     * @param $object
     * @param array $options
     */
    public static function configure($object, array $options)
    {
        foreach ($options as $property => $value) {
            $object->$property = $value;
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
     * @param \ReflectionMethod $method Method for which to build the argument array.
     * @param array $extraArgs
     * @return array
     * @throws DependencyResolutionException
     */
    public static function getMethodArgs(\ReflectionMethod $method, array $extraArgs = [])
    {
        $methodArgs = [];

        foreach ($method->getParameters() as $idx => $param) {
            // if user have been provide arg
            if (isset($extraArgs[$idx])) {
                $methodArgs[] = $extraArgs[$idx];
                continue;
            }

            $dependencyClass = $param->getClass();

            // If we have a dependency, that means it has been type-hinted.
            if ($dependencyClass && ($depClass = $dependencyClass->getName()) !== \Closure::class) {
                $depClass = $dependencyClass->getName();
                $depObject = self::create($depClass);

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

            // $dependencyVarName = $param->getName();
            // Couldn't resolve dependency, and no default was provided.
            throw new DependencyResolutionException(sprintf(
                'Could not resolve dependency: %s for the %dth parameter',
                $param->getPosition(),
                $param->getName()
            ));
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
    public static function create($class)
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

    /**
     * @param string|array $config
     * @return mixed
     */
    public static function smartCreate($config)
    {
        if (is_string($config)) {
            return new $config;
        }

        if (is_array($config) && !empty($config['target'])) {
            $class = Arr::remove($config, 'target');
            $args = Arr::remove($config, '_args', []);
            $props = $config;

            $obj = new $class(...$args);

            return self::init($obj, $props);
        }

        return null;
    }
}
