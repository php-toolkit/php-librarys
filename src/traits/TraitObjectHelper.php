<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-1-24
 * Time: 下午12:39
 */

namespace inhere\library\traits;

use inhere\library\exceptions\InvalidArgumentException;

/**
 * Class TraitObjectHelper
 * @package inhere\library\traits
 */
trait TraitObjectHelper
{
    /**
     * php对象转换成为数组
     * @param mixed $object
     * @return array|bool
     */
    static public function toArray($object)
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
    static public function encode( $obj )
    {
        return base64_encode(gzcompress(serialize($obj)));
    }

    //反序列化
    static public function decode($txt)
    {
        return unserialize(gzuncompress(base64_decode($txt)));
    }
}
