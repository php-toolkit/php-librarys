<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-29
 * Time: 14:20
 */

namespace inhere\library\components;

/**
 * Class DeferredCallable
 * @package inhere\library\components
 */
class DeferredCallable
{
    /** @var callable|string $callable */
    private $callable;

    /**
     * DeferredMiddleware constructor.
     * @param callable|string $callable
     */
    public function __construct($callable)
    {
        $this->callable = $callable;
    }

    /**
     * @param array $args
     * @return mixed
     */
    public function __invoke(...$args)
    {
        $callable = $this->callable;

        if (is_callable($callable)) {
            return $this->resolve($callable, $args);
        }

        if (is_string($callable) && class_exists($callable)) {
            $obj = new $callable;

            if (method_exists($obj, '__invoke')) {
                return $this->resolve($obj, $args);
            }

            throw new \InvalidArgumentException('the defined callable is cannot called！');
        }

        throw new \InvalidArgumentException('the defined callable is error！');
    }

    /**
     * @param callable $callable
     * @param array $args
     * @return null
     */
    public function resolve($callable, array $args)
    {
        $result = null;

        if (is_string($callable) || is_object($callable)) {
            $result = $callable(...$args);
        } elseif (is_array($callable)) {
            list($obj, $mhd) = $callable;

            if (is_object($obj)) {
                $result = $obj->$mhd(...$args);
            } else {
                $result = $obj::$mhd(...$args);
            }
        }

        return $result;
    }
}