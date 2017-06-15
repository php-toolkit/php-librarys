<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 14-
 * Time: 10:35
 * Uesd: 主要功能是 hi
 */

namespace inhere\library\helpers;

/**
 * Class ObjectHelper
 * @package inhere\library\helpers
 */
class ObjectHelper
{
    /**
     * 给对象设置属性值
     * @param $object
     * @param array $options
     */
    public static function setAttrs($object, array $options)
    {
        foreach ($options as $property => $value) {
            $object->$property = $value;
        }
    }

    /**
     * 给对象设置属性值
     * @param $object
     * @param array $options
     */
    public static function smartInit($object, array $options)
    {
        foreach ($options as $property => $value) {
            $setter = 'set' . ucfirst($property);

            if (method_exists($object, $setter)) {
                $object->$setter($value);
            } else {
                $object->$property = $value;
            }
        }
    }

    /**
     * @param $data
     * @return array|bool
     */
    public static function toArray($data)
    {
        return DataHelper::toArray($data);
    }

    /**
     * 定义一个用来序列化对象的函数
     * @param mixed $obj
     * @return string
     */
    public static function encode($obj)
    {
        return DataHelper::encode($obj);
    }

    /**
     * 反序列化
     * @param $txt
     * @return mixed
     */
    public static function decode($txt)
    {
        return DataHelper::decode($txt);
    }
}
