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
     * php对象转换成为数组
     * @param iterable|array|object $data
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
            $data = (array)$data;
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
}
