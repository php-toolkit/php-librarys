<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 14-6
 * Time: 10:35
 * Uesd: 主要功能是 数组处理
 */
namespace inhere\librarys\helpers;

/**
 * Class ArrHelper
 * @package inhere\librarys\helpers
 */
class ArrHelper
{
    /**
     * @param mixed $array
     * @return \Traversable
     */
    public static function toIterator($array)
    {
        if (!$array instanceof \Traversable) {
            $array = new \ArrayObject(is_array($array) ? $array : array($array));
        }

        return $array;
    }
    
    /**
     * 递归合并多维数组,后面的值将会递归覆盖原来的值
     * @param  array|null $old
     * @param  array  $new
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

        foreach($new as $key => $value) {
            if ( array_key_exists($key, $old) && is_array($value)) {
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
     * remove the $key of the $arr, and return value.
     * @param string $key
     * @param array $arr
     * @param mixed $default
     * @return mixed
     */
    public static function remove($key, array &$arr, $default = null)
    {
        if ( isset($arr[$key]) ) {
            $value = $arr[$key];
            unset($arr[$key]);
        } else {
            $value = $default;
        }

        return $value;
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

        array_walk_recursive($data, function( &$value) {
            $value = trim($value);
        });

        return $data;
    }

    /**
     * php数组转换成为对象
     * @param array $array
     * @param string $class
     * @return mixed
     */
    public static function toObject(array $array, $class = '\stdClass')
    {
        if (!is_array($array)) {
            return $array;
        }

        $object = new $class();

        foreach ($array as $name=>$value) {
            $name = trim($name);

            if (!$name || is_numeric($name)) {
                continue;
            }

            $object->$name = is_array($value) ? self::toObject($value) : $value;
        }

        return $object;
    }

    public static function arrayToObject($array, $class = '\stdClass')
    {
        return self::toObject($array, $class);
    }

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
    public static function toString($array,$length=800,$cycles=6,$showKey=true,$addMark = false,$separator=', ',$string = '')
    {

        if (!is_array($array) || empty($array)) {
            return '';
        }

        $mark = $addMark ? '\'' : '';
        $num = 0;

        foreach ($array as $key => $value) {
            $num++;

            if ( $num >= $cycles || strlen($string)>(int)$length) {
                $string .= '... ...';
                break;
            }

            $keyStr = $showKey ? $key.'=>' : '';

            if (is_array($value)) {
                $string .= $keyStr . 'Array('. self::toString($value,$length,$cycles,$showKey,$addMark,$separator,$string).')'. $separator;
            } else if (is_object($value)) {
                $string .= $keyStr . 'Object('.get_class($value) .')'. $separator;
            } else if (is_resource($value)) {
                $string .= $keyStr . 'Resource('.get_resource_type($value) .')'. $separator;
            } else {
                $value = strlen($value)>150 ? substr($value, 0,150) : $value;
                $string .= $mark. $keyStr . trim(htmlspecialchars($value)). $mark .$separator;
            }
        }

        return trim($string,$separator);
    }

    public static function toStringNoKey($array,$length=800,$cycles=6,$showKey=false,$addMark = true,$separator=', ')
    {
        return static::toString( $array, $length, $cycles, $showKey, $addMark, $separator );
    }

    public static function getFormatString($array,$length=400)
    {
        $string = var_export($array, true);

        # 将非空格替换为一个空格
        $string = preg_replace('/[\n\r\t]/', ' ', $string);
        # 去掉两个空格以上的
        $string = preg_replace('/\s(?=\s)/', '', $string);
        $string = trim($string);

        if (strlen($string)>$length) {
            $string = substr($string, 0,$length).'...';
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
                $value = gettype($value).'(...)';
            } else if (is_string($value) || is_numeric($value)) {
                $value = strlen(trim($value));
            } else {
                $value = gettype($value)."($value)";
            }

            $array[$key] = $value;
        }

        $num++;

        return $array;
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
     * 将数组中的值全部转为大写或小写
     * @param array $arr
     * @param int $toUpper    1 值大写 0 值小写
     * @return array
     */
    public static function changeValueCase($arr, $toUpper = 1)
    {
        $function = $toUpper ? 'strtoupper' : 'strtolower';
        $newArr   = array(); //格式化后的数组

        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $newArr[$k] = self::changeValueCase($v, $toUpper);
            } else {
                $v          = trim($v);
                $newArr[$k] = $function($v);
            }
        }

        return $newArr;
    }

    public static function valueToLower($arr)
    {
        return self::changeValueCase($arr);
    }

    public static function valueToUpper($arr)
    {
        return self::changeValueCase($arr,1);
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
            $check = strpos($check,',')!==false ? explode(',',$check) : array($check);
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
            $check = strpos($check,',')!==false ? explode(',',$check) : array($check);
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
    public static function existsAll($need,$arr,$type=false)
    {
        if (is_array($need)) {
            foreach($need as $v) {
                self::existsAll($v,$arr,$type);
            }

        } else {

            #以逗号分隔的会被拆开，组成数组
            if ( strpos($need,',')!==false ) {
                $need = explode(',',$need);
                self::existsAll($need,$arr,$type);
            } else {
                $arr  = self::valueToLower($arr);//小写
                $need = strtolower(trim($need));//小写

                if (!in_array($need,$arr,$type))
                {
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
    public static function existsOne($need,$arr,$type=false)
    {
        if (is_array($need)) {
            foreach($need as $v) {
                $result = self::existsOne($v,$arr,$type);
                if ($result) {
                    return true;
                }
            }
        } else {
            if ( strpos($need,',')!==false ) {
                $need = explode(',',$need);
                return self::existsOne($need,$arr,$type);
            } else {
                $arr  = self::changeValueCase($arr);//小写
                $need = strtolower($need);//小写

                if ( in_array($need,$arr,$type) ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 取出出所有子数组的相同列（单元）组成新的一位数组
     * @param array $input 原始数组
     * @param string $columnKey 要获取的列 键名
     * @param null $indexKey 可选 用相同的列的值作为新数组的键值
     * @return array
     */
    public static function columns($input, $columnKey, $indexKey=null)
    {
        if ( function_exists('array_column') ) {
            return array_column($input, $columnKey, $indexKey);
        }

        $columnKeyIsNumber  = is_numeric($columnKey);
        $indexKeyIsNull     = null === $indexKey;
        $indexKeyIsNumber   = is_numeric($indexKey);
        $result             = array();

        foreach((array)$input as $key=>$row) {
            if ($columnKeyIsNumber) {
                $tmp            = array_slice($row, $columnKey, 1);
                $tmp            = (is_array($tmp) && $tmp) ? current($tmp) : null;
            } else {
                $tmp            = isset($row[$columnKey]) ? $row[$columnKey] : null;
            }
            if (!$indexKeyIsNull) {
                if ($indexKeyIsNumber) {
                    $key            = array_slice($row, $indexKey, 1);
                    $key            = (is_array($key) && !empty($key)) ? current($key) : null;
                    $key            = null === $key ? 0 : $key;
                } else {
                    $key            = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }
            }
            $result[$key]       = $tmp;
        }

        return $result;
    }
}
