<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 14-6
 * Time: 10:35
 * Uesd: 主要功能是 数组处理
 */

namespace inhere\library\helpers;

use inhere\library\collections\CollectionInterface;

/**
 * Class ArrayHelper
 * @package inhere\library\helpers
 */
class ArrayHelper
{
    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed $value
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof \ArrayAccess;
    }

    /**
     * @param mixed $array
     * @return \Traversable
     */
    public static function toIterator($array)
    {
        if (!$array instanceof \Traversable) {
            $array = new \ArrayObject((array)$array);
        }

        return $array;
    }

    /**
     * php数组转换成为对象
     * @param array $array
     * @param string $class
     * @return mixed
     */
    public static function toObject(array $array, $class = \stdClass::class)
    {
        if (!is_array($array)) {
            return $array;
        }

        $object = new $class();

        foreach ($array as $name => $value) {
            $name = trim($name);

            if (!$name || is_numeric($name)) {
                continue;
            }

            $object->$name = is_array($value) ? self::toObject($value) : $value;
        }

        return $object;
    }

    /**
     * Get Multi - 获取多个, 可以设置默认值
     * @param array $data array data
     * @param array $needKeys
     * $needKeys = [
     *     'name',
     *     'password',
     *     'status' => '1'
     * ]
     * @param bool|false $unsetKey
     * @return array
     */
    public static function gets(array &$data, array $needKeys = [], $unsetKey = false)
    {
        $needed = [];

        foreach ($needKeys as $key => $default) {
            if (is_int($key)) {
                $key = $default;
                $default = null;
            }

            if (isset($data[$key])) {
                $value = $data[$key];

                if (is_int($default)) {
                    $value = (int)$value;
                } elseif (is_string($default)) {
                    $value = trim($value);
                } elseif (is_array($default)) {
                    $value = (array)$value;
                }

                $needed[$key] = $value;

                if ($unsetKey) {
                    unset($data[$key]);
                }
            } else {
                $needed[$key] = $default;
            }
        }

        return $needed;
    }

    /**
     * 递归合并多维数组,后面的值将会递归覆盖原来的值
     * @param  array|null $old
     * @param  array $new
     * @return array
     */
    public static function merge($old, array $new)
    {
        return self::recursiveMerge($old, $new);
    }

    public static function recursiveMerge($old, array $new)
    {
        if (!$old || !is_array($old)) {
            return $new;
        }

        foreach ($new as $key => $value) {
            if (array_key_exists($key, $old) && is_array($value)) {
                $old[$key] = self::recursiveMerge($old[$key], $new[$key]);
            } elseif (is_int($key)) {
                $old[] = $value;
            } else {
                $old[$key] = $value;
            }
        }

        return $old;
    }

    /**
     * 清理数组值的空白
     * @param array $data
     * @return array|string
     */
    public static function valueTrim(array $data)
    {
        if (is_scalar($data)) {
            return trim($data);
        }

        array_walk_recursive($data, function (&$value) {
            $value = trim($value);
        });

        return $data;
    }

    /**
     * 不区分大小写检测数据键名是否存在
     * @param $key
     * @param $arr
     * @return bool
     */
    public static function keyExists($key, $arr)
    {
        return array_key_exists(strtolower($key), array_change_key_case($arr));
    }

    /**
     * @param $arr
     * @return array
     */
    public static function valueToLower($arr)
    {
        return self::changeValueCase($arr);
    }

    /**
     * @param $arr
     * @return array
     */
    public static function valueToUpper($arr)
    {
        return self::changeValueCase($arr, 1);
    }

    /**
     * 将数组中的值全部转为大写或小写
     * @param array $arr
     * @param int $toUpper 1 值大写 0 值小写
     * @return array
     */
    public static function changeValueCase($arr, $toUpper = 1)
    {
        $function = $toUpper ? 'strtoupper' : 'strtolower';
        $newArr = array(); //格式化后的数组

        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $newArr[$k] = self::changeValueCase($v, $toUpper);
            } else {
                $v = trim($v);
                $newArr[$k] = $function($v);
            }
        }

        return $newArr;
    }

    /**
     * ******* 检查 一个或多个值是否全部存在数组中 *******
     * 有一个不存在即返回 false
     * @param string|array $check
     * @param array $sampleArr 只能检查一维数组
     * 注： 不分类型， 区分大小写  2 == '2' ‘a' != 'A'
     * @return bool
     */
    public static function valueExistsAll($check, array $sampleArr)
    {
        // 以逗号分隔的会被拆开，组成数组
        if (is_string($check)) {
            $check = trim($check, ', ');
            $check = strpos($check, ',') !== false ? explode(',', $check) : array($check);
        }

        return !array_diff((array)$check, $sampleArr);
    }

    /**
     * ******* 检查 一个或多个值是否存在数组中 *******
     * 有一个存在就返回 true 都不存在 return false
     * @param string|array $check
     * @param array $sampleArr 只能检查一维数组
     * @return bool
     */
    public static function valueExistsOne($check, array $sampleArr)
    {
        // 以逗号分隔的会被拆开，组成数组
        if (is_string($check)) {
            $check = trim($check, ', ');
            $check = strpos($check, ',') !== false ? explode(',', $check) : array($check);
        }

        return (bool)array_intersect((array)$check, $sampleArr);
    }

    /**
     * ******* 不区分大小写，检查 一个或多个值是否 全存在数组中 *******
     * 有一个不存在即返回 false
     * @param string|array $need
     * @param array $arr 只能检查一维数组
     * @param bool $type 是否同时验证类型
     * @return bool | string 不存在的会返回 检查到的 字段，判断时 请使用 ArrHelper::existsAll($need,$arr)===true 来验证是否全存在
     */
    public static function existsAll($need, $arr, $type = false)
    {
        if (is_array($need)) {
            foreach ((array)$need as $v) {
                self::existsAll($v, $arr, $type);
            }

        } else {

            #以逗号分隔的会被拆开，组成数组
            if (strpos($need, ',') !== false) {
                $need = explode(',', $need);
                self::existsAll($need, $arr, $type);
            } else {
                $arr = self::valueToLower($arr);//小写
                $need = strtolower(trim($need));//小写

                if (!in_array($need, $arr, $type)) {
                    return $need;
                }
            }
        }

        return true;
    }

    /**
     * ******* 不区分大小写，检查 一个或多个值是否存在数组中 *******
     * 有一个存在就返回 true 都不存在 return false
     * @param string|array $need
     * @param array $arr 只能检查一维数组
     * @param bool $type 是否同时验证类型
     * @return bool
     */
    public static function existsOne($need, $arr, $type = false)
    {
        if (is_array($need)) {
            foreach ((array)$need as $v) {
                $result = self::existsOne($v, $arr, $type);
                if ($result) {
                    return true;
                }
            }
        } else {
            if (strpos($need, ',') !== false) {
                $need = explode(',', $need);
                return self::existsOne($need, $arr, $type);
            }

            $arr = self::changeValueCase($arr);//小写
            $need = strtolower($need);//小写

            if (in_array($need, $arr, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * get key Max Width
     *
     * @param  array $data
     * [
     *     'key1'      => 'value1',
     *     'key2-test' => 'value2',
     * ]
     * @param bool $expectInt
     * @return int
     */
    public static function getKeyMaxWidth(array $data, $expectInt = true)
    {
        $keyMaxWidth = 0;

        foreach ($data as $key => $value) {
            // key is not a integer
            if (!$expectInt || !is_numeric($key)) {
                $width = mb_strlen($key, 'UTF-8');
                $keyMaxWidth = $width > $keyMaxWidth ? $width : $keyMaxWidth;
            }
        }

        return $keyMaxWidth;
    }

    ////////////////////////////////////////////////////////////
    /// from laravel
    ////////////////////////////////////////////////////////////

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  array $array
     * @return array
     */
    public static function collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof CollectionInterface) {
                $values = $values->all();
            } elseif (!is_array($values)) {
                continue;
            }

            $results = array_merge($results, $values);
        }

        return $results;
    }

    /**
     * Cross join the given arrays, returning all possible permutations.
     *
     * @param  array ...$arrays
     * @return array
     */
    public static function crossJoin(...$arrays)
    {
        return array_reduce($arrays, function ($results, $array) {
            return static::collapse(array_map(function ($parent) use ($array) {
                return array_map(function ($item) use ($parent) {
                    return array_merge($parent, [$item]);
                }, $array);
            }, $results));
        }, [[]]);
    }

    /**
     * Divide an array into two arrays. One with keys and the other with values.
     *
     * @param  array $array
     * @return array
     */
    public static function divide($array)
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  array $array
     * @param  string $prepend
     * @return array
     */
    public static function dot($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Get all of the given array except for a specified array of items.
     *
     * @param  array $array
     * @param  array|string $keys
     * @return array
     */
    public static function except($array, $keys)
    {
        static::forget($array, $keys);

        return $array;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  \ArrayAccess|array $array
     * @param  string|int $key
     * @return bool
     */
    public static function exists($array, $key)
    {
        if ($array instanceof \ArrayAccess) {
            return $array->offsetExists($key);
        }
        return array_key_exists($key, $array);
    }

    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     *
     * @param  array $array
     * @param  string $key
     * @param  mixed $value
     * @return array
     */
    public static function add($array, $key, $value)
    {
        if (static::has($array, $key)) {
            static::set($array, $key, $value);
        }

        return $array;
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  \ArrayAccess|array $array
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (!static::accessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }
        return $array;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);
            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  array $array
     * @param  int $depth
     * @return array
     */
    public static function flatten($array, $depth = INF)
    {
        return array_reduce($array, function ($result, $item) use ($depth) {
            $item = $item instanceof CollectionInterface ? $item->all() : $item;
            if (!is_array($item)) {
                return array_merge($result, [$item]);
            } elseif ($depth === 1) {
                return array_merge($result, array_values($item));
            } else {
                return array_merge($result, static::flatten($item, $depth - 1));
            }
        }, []);
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array $array
     * @param  array|string $keys
     * @return void
     */
    public static function forget(&$array, $keys)
    {
        $original = &$array;
        $keys = (array)$keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {

            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);
                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param  \ArrayAccess|array $array
     * @param  string|array $keys
     * @return bool
     */
    public static function has($array, $keys)
    {
        if (is_null($keys)) {
            return false;
        }

        $keys = (array)$keys;

        if (!$array) {
            return false;
        }

        if ($keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            if (static::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Push an item onto the beginning of an array.
     *
     * @param  array  $array
     * @param  mixed  $value
     * @param  mixed  $key
     * @return array
     */
    public static function prepend($array, $value, $key = null)
    {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }

    /**
     * remove the $key of the $arr, and return value.
     * @param string $key
     * @param array $arr
     * @param mixed $default
     * @return mixed
     */
    public static function remove(&$arr,$key, $default = null)
    {
        if (isset($arr[$key])) {
            $value = $arr[$key];
            unset($arr[$key]);
        } else {
            $value = $default;
        }

        return $value;
    }

    /**
     * Get a value from the array, and remove it.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function pull(&$array, $key, $default = null)
    {
        $value = static::get($array, $key, $default);

        static::forget($array, $key);

        return $value;
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @param  array $array
     * @param  array|string $keys
     * @return array
     */
    public static function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }

    /**
     * Shuffle the given array and return the result.
     *
     * @param  array  $array
     * @return array
     */
    public static function shuffle($array)
    {
        shuffle($array);

        return $array;
    }

    /**
     * Determines if an array is associative.
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     *
     * @param  array $array
     * @return bool
     */
    public static function isAssoc(array $array)
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

    /**
     * Filter the array using the given callback.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    public static function where($array, callable $callback)
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * If the given value is not an array, wrap it in one.
     *
     * @param  mixed  $value
     * @return array
     */
    public static function wrap($value)
    {
        return !is_array($value) ? [$value] : $value;
    }


    ////////////////////////////////////////////////////////////
    /// other
    ////////////////////////////////////////////////////////////

    /**
     * array 递归 转换成 字符串
     * @param  array $array [大于1200字符 strlen($string)>1200
     * @param int $length
     * @param array|int $cycles [至多循环六次 $num >= 6
     * @param bool $showKey
     * @param bool $addMark
     * @param  string $separator [description]
     * @param string $string
     * @return string [type]            [description]
     */
    public static function toString($array, $length = 800, $cycles = 6, $showKey = true, $addMark = false, $separator = ', ', $string = '')
    {

        if (!is_array($array) || empty($array)) {
            return '';
        }

        $mark = $addMark ? '\'' : '';
        $num = 0;

        foreach ($array as $key => $value) {
            $num++;

            if ($num >= $cycles || strlen($string) > (int)$length) {
                $string .= '... ...';
                break;
            }

            $keyStr = $showKey ? $key . '=>' : '';

            if (is_array($value)) {
                $string .= $keyStr . 'Array(' . self::toString($value, $length, $cycles, $showKey, $addMark, $separator, $string) . ')' . $separator;
            } else if (is_object($value)) {
                $string .= $keyStr . 'Object(' . get_class($value) . ')' . $separator;
            } else if (is_resource($value)) {
                $string .= $keyStr . 'Resource(' . get_resource_type($value) . ')' . $separator;
            } else {
                $value = strlen($value) > 150 ? substr($value, 0, 150) : $value;
                $string .= $mark . $keyStr . trim(htmlspecialchars($value)) . $mark . $separator;
            }
        }

        return trim($string, $separator);
    }

    public static function toStringNoKey($array, $length = 800, $cycles = 6, $showKey = false, $addMark = true, $separator = ', ')
    {
        return static::toString($array, $length, $cycles, $showKey, $addMark, $separator);
    }

    public static function getFormatString($array, $length = 400)
    {
        $string = var_export($array, true);

        # 将非空格替换为一个空格
        $string = preg_replace('/[\n\r\t]/', ' ', $string);
        # 去掉两个空格以上的
        $string = preg_replace('/\s(?=\s)/', '', $string);
        $string = trim($string);

        if (strlen($string) > $length) {
            $string = substr($string, 0, $length) . '...';
        }

        return $string;
    }

    public static function toLimitOut($array) //, $cycles=1
    {
        if (!is_array($array)) {
            return $array;
        }

        static $num = 1;

        foreach ($array as $key => $value) {
            // if ( $num >= $cycles) {
            //     break;
            // }

            if (is_array($value) || is_object($value)) {
                $value = gettype($value) . '(...)';
            } else if (is_string($value) || is_numeric($value)) {
                $value = strlen(trim($value));
            } else {
                $value = gettype($value) . "($value)";
            }

            $array[$key] = $value;
        }

        $num++;

        return $array;
    }

}
