<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/2/19 0019
 * Time: 23:35
 */

namespace Inhere\Library;

/**
 * Class Types
 * @package Inhere\Library
 */
final class Types
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
    public static function all()
    {
        return [
            self::T_ARRAY,
            self::T_BOOL,
            self::T_BOOLEAN,
            self::T_DOUBLE,
            self::T_FLOAT,
            self::T_INT,
            self::T_INTEGER,
            self::T_OBJECT,
            self::T_STRING,
            self::T_RESOURCE
        ];
    }

    /**
     * @return array
     */
    public static function scalars()
    {
        return [
            self::T_BOOL,
            self::T_BOOLEAN,
            self::T_DOUBLE,
            self::T_FLOAT,
            self::T_INT,
            self::T_INTEGER,
            self::T_STRING
        ];
    }
}
