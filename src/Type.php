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
final class Type
{
    // php data type
    const INT = 'int';
    const INTEGER = 'integer';
    const FLOAT = 'float';
    const DOUBLE = 'double';
    const BOOL = 'bool';
    const BOOLEAN = 'boolean';
    const STRING = 'string';

    const ARRAY = 'array';
    const OBJECT = 'object';
    const RESOURCE = 'resource';

    /**
     * @return array
     */
    public static function all()
    {
        return [
            self::ARRAY,
            self::BOOL,
            self::BOOLEAN,
            self::DOUBLE,
            self::FLOAT,
            self::INT,
            self::INTEGER,
            self::OBJECT,
            self::STRING,
            self::RESOURCE
        ];
    }

    /**
     * @return array
     */
    public static function scalars()
    {
        return [
            self::BOOL,
            self::BOOLEAN,
            self::DOUBLE,
            self::FLOAT,
            self::INT,
            self::INTEGER,
            self::STRING
        ];
    }
}
