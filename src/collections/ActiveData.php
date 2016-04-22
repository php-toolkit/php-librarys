<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/3/14
 * Time: 19:44
 * Use : 跟 \stdClass 一样，多的功能是 -- 提供数组方式访问属性
 * 与同文件夹下的 FixedData.php 区别是
 *     数据较为活跃，可随意添加属性，
 *     获取不存在的属性也并不报错(return null)
 * File: ActiveData.php
 */

class ActiveData implements \ArrayAccess, \IteratorAggregate
{
    public function __construct(array $data=[])
    {
        if ($data) {
            $this->load($data);
        }
    }

    /**
     * 初始化，载入数据
     * @param  array $data
     * @return $this
     */
    public function load(array $data)
    {
        foreach ($data as $name=>$value) {
            $name = trim($name);

            if( is_numeric($name) ) {
                $name = 'attr_'.$name;
            }

            if (empty($name)) {
                $name = 'attr_';
            }

            # code...
            if ( is_array($value) ) {
                $this->$name = (new self)->load($value);
            } else {
                $this->$name = $value;
            }
        }

        return $this;
    }

    public function isStrict()
    {
        return false;
    }


    /**
     * @return array
     */
    public function getAll()
    {
        $class = new \ReflectionClass( get_class($this) );
        $attrs = array();

        foreach($class->getProperties() as $property) {
            if($property->isPublic() && !$property->isStatic()) {
                $attrs[] = $property->getName();
            }
        }

        return $attrs;
    }

    /**
     * 以点连接 快速获取子级节点的值
     * @param $name
     * @return ActiveData|null
     */
    public function get($name)
    {
        if (strpos($name,'.')) {
            $names = explode('.', $name);
            $node = $this;

            foreach ($names as $name) {
                if($node instanceof self && property_exists($node, $name) ) {
                    $node = $node->$name;
                } else {
                    if ($this->isStrict()) {
                        exit("Stored data don't exists node '$name'\n") ;
                    }

                    $node = null;
                    break;
                }
            }

            return $node;
        }

        return $this->$name;
    }

    /**
     * Defined by IteratorAggregate interface
     * Returns an iterator for this object, for use with foreach
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator(get_object_vars($this));
    }

    /**
     * Checks whether an offset exists in the iterator.
     * @param   mixed  $offset  The array offset.
     * @return  boolean  True if the offset exists, false otherwise.
     */
    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }

    /**
     * Gets an offset in the iterator.
     * @param   mixed  $offset  The array offset.
     * @return  mixed  The array value if it exists, null otherwise.
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Sets an offset in the iterator.
     * @param   mixed  $offset  The array offset.
     * @param   mixed  $value   The array value.
     * @return  void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * Unset an offset in the iterator.
     * @param   mixed  $offset  The array offset.
     * @return  void
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

}// end class ActiveData