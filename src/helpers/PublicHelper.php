<?php
/**
 *
 * PublicHelper.php
 */

namespace inhere\library\helpers;

/**
 * Class PublicHelper
 * @package inhere\library\helpers
 */
class PublicHelper
{
    /**
     * 本次请求开始时间
     * @param bool $float
     * @return mixed
     */
    static public function requestTime($float = true)
    {
        if ((bool)$float) {
            return $_SERVER['REQUEST_TIME_FLOAT'];
        }

        return $_SERVER['REQUEST_TIME'];
    }

    /**
     * @return array
     */
    static public function userConstants()
    {
        $const = get_defined_constants(true);

        return $const['user'] ?? [];
    }


///////////////////////////////////// public method /////////////////////////////////////

    static public function fileSuffix()
    {
        return ['js', 'json', 'css', 'php', 'yaml', 'inc'];
    }

    /**
     * get className by full className
     * @param string $class
     * @return null|string
     */
    static public function getClassName($class)
    {
        return self::parseFullClassName($class, true);
    }

    /**
     * get className by full className
     * @param $class
     * @return null|string
     */
    static public function getNamespace($class)
    {
        return self::parseFullClassName($class, false);
    }

    /**
     * _parseFull ClassName 解析类名
     * @param  mixed $class 类 或 类的实例
     * @param  boolean $onlyName true:namespace false:className
     * @return null|string
     */
    static private function parseFullClassName($class, $onlyName = false)
    {
        if (is_object($class)) {
            $className = get_class($class);
        } else if ($class && is_string($class)) {
            $className = rtrim($class, '\\ ');
        } else {
            return null;
        }

        return !$onlyName ? trim(dirname($className), '.') : basename($className);
    }
}
