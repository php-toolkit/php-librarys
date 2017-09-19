<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-09-19
 * Time: 17:02
 */

namespace inhere\library\helpers;

/**
 * Class ObjPool
 * @package Sws\Components
 */
class ObjPool
{
    /**
     * @var \SplStack[] [class => \SplStack]
     */
    private static $pool = [];

    /**
     * @param string $class
     * @return mixed
     */
    public static function get(string $class)
    {
        $stack = self::getStack($class);

        if (!$stack->isEmpty()) {
            return $stack->shift();
        }

        return new $class;
    }

    /**
     * @param \stdClass $object
     */
    public static function put($object)
    {
        self::getStack($object)->push($object);
    }

    /**
     * @param string|\stdClass $class
     * @return \SplStack
     */
    public static function getStack($class)
    {
        $class = is_string($class) ? $class : get_class($class);

        if (!isset(self::$pool[$class])) {
            self::$pool[$class] = new \SplStack();
        }

        return self::$pool[$class];
    }

    /**
     * @param null $class
     * @return int
     */
    public static function count($class = null)
    {
        if ($class) {
            if (!isset(self::$pool[$class])) {
                throw new \InvalidArgumentException("The object is never created of the class: $class");
            }

            return self::$pool[$class]->count();
        }

        return count(self::$pool);
    }

    /**
     * @param null $class
     */
    public static function destroy($class = null)
    {
        if ($class) {
            if (!isset(self::$pool[$class])) {
                throw new \InvalidArgumentException("The object is never created of the class: $class");
            }

            unset(self::$pool[$class]);
        } else {
            self::$pool = [];
        }
    }
}