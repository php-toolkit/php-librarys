<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/2/19 0019
 * Time: 23:35
 */

namespace inhere\library;

/**
 * Class DataType
 * @package inhere\library
 */
abstract class DataType
{
    // php data type
    const T_INT = 'int';
    const T_INTEGER = 'integer';
    const T_FLOAT = 'float';
    const T_DOUBLE = 'double';
    const T_BOOL = 'bool';
    const T_BOOLEAN = 'boolean';
    const T_STRING = 'string';

    const T_ARRAY = 'array';
    const T_OBJECT = 'object';
    const T_RESOURCE = 'resource';

    /**
     * @return array
     */
    public static function types()
    {
        return [
            static::T_ARRAY, static::T_BOOL, static::T_BOOLEAN, static::T_DOUBLE, static::T_FLOAT,
            static::T_INT, static::T_INTEGER, static::T_OBJECT, static::T_STRING, static::T_RESOURCE
        ];
    }

    /**
     * @return array
     */
    public static function scalars()
    {
        return [
            static::T_BOOL, static::T_BOOLEAN, static::T_DOUBLE, static::T_FLOAT,
            static::T_INT, static::T_INTEGER, static::T_STRING
        ];
    }
}
