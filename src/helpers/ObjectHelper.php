<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 14-
 * Time: 10:35
 * Uesd: 主要功能是 hi
 */
namespace inhere\librarys\helpers;

use inhere\librarys\exceptions\InvalidArgumentException;

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
     * @param mixed $object
     * @return array|bool
     */
    public static function toArray($object)
    {
        if ( ! is_object($object) ) {
            throw new InvalidArgumentException('参数必须是个对象！');
        }

        $arr = [];

        foreach($object as $attr => $value){
            /*if (is_object($value)){
                $arr[$attr]=object_to_array($value);
            } else {
                $arr[$attr] = $value;
            }*/
            $arr[$attr] = is_object($value)? self::toArray($value) : $value;
        }

        return $arr;
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