<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 14-
 * Time: 10:35
 * Uesd: 主要功能是 hi
 */
namespace inhere\librarys\helpers;

/**
 * Class ObjectHelper
 * @package inhere\librarys\helpers
 */
class ObjectHelper
{
    /**
     * 给对象设置属性值
     * @param $object
     * @param array $options
     */
    public static function loadAttrs($object, array $options)
    {
        foreach ($options as $property => $value) {
            $object->$property = $value;
        }
    }

    /**
     * php对象转换成为数组
     * @param mixed $data
     * @param bool $recursive
     * @return array|bool
     */
    public static function toArray($data, $recursive = false)
    {
        // Ensure the input data is an array.
        if ($data instanceof \Traversable) {
            $data = iterator_to_array($data);
        } elseif (is_object($data)) {
            $data = get_object_vars($data);
        } else {
            $data = (array) $data;
        }

        if ($recursive) {
            foreach ($data as &$value) {
                if (is_array($value) || is_object($value)) {
                    $value = static::toArray($value, $recursive);
                }
            }
        }

        return $data;
    }

    //定义一个用来序列化对象的函数
    public static function encode( $obj )
    {
        return base64_encode(gzcompress(serialize($obj)));
    }

    //反序列化
    public static function decode($txt)
    {
        return unserialize(gzuncompress(base64_decode($txt)));
    }
}