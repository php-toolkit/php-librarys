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
    static public function requestTime($float=true)
    {
        if ( (bool) $float ) {
            return $_SERVER['REQUEST_TIME_FLOAT'];
        } else {
            return $_SERVER['REQUEST_TIME'];
        }
    }

    static public function getUserConsts()
    {
        $const = get_defined_constants(true);

        return isset($const['user']) ? $const['user'] : [];
    }


///////////////////////////////////// public method /////////////////////////////////////

    static public function fileSuffix()
    {
        return ['js','json','css','php','yaml','inc'];
    }

    /**
     * get classname by full classname
     * @param string $class
     * @return null|string
     */
    static public function getClassname( $class )
    {
        return self::parseFullClassname( $class, true);
    }

    /**
     * get classname by full classname
     * @param $class
     * @return null|string
     */
    static public function getNamespace( $class )
    {
        return self::parseFullClassname( $class, false);
    }

    /**
     * _parseFullClassname 解析类名
     * @param  mixed $class 类 或 类的实例
     * @param  boolean $onlyName true:namespace false:classname
     * @return null|string
     */
    static private function parseFullClassname( $class, $onlyName = false)
    {
        if (is_object($class)) {
            $classname = get_class($class);
        } else if ( $class && is_string($class) ) {
            $classname = rtrim($class,'\\ ');
        } else {
            return null;
        }

        return !$onlyName ? trim(dirname($classname),'.'): basename($classname);
    }
}
