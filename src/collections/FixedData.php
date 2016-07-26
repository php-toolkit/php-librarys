<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/3/14
 * Time: 19:44
 * Use : 跟 \stdClass 一样， 多的功能是 -- 提供数组方式访问属性;
 * 与同文件夹下的 ActiveData.php 区别是
 *     数据严格固定，不可随意添加属性，获取不存在的属性会报异常错误
 *     仅允许通过实例化时 或 调用load() 载入数据
 * File: FixedData.php StrictData.php
 */
namespace inhere\librarys\collections;
use inhere\librarys\exceptions\UnknownCalledException;

/**
 * Class FixedData
 * @package inhere\librarys\collections
 */
class FixedData extends ActiveData
{
    public function isStrict()
    {
        return true;
    }

    /**
     * Unset 操作 (与 ActiveData::offsetUnset()不同的是) 仅会将属性值设置为 null, 并不会真的删除属性
     * @param   mixed  $offset  The array offset.
     * @return  void
     */
    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }

    public function __set($name, $value)
    {
        throw new UnknownCalledException(sprintf('设置不存在的属性 %s ！',$name));
    }

    public function __get($name)
    {
        if ( $value=$this->get($name) ) {
            return $value;
        }

        throw new UnknownCalledException(sprintf('获取不存在的属性 %s ！',$name));
    }

}// end class FixedData
