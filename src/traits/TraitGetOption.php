<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/2/7
 * Time: 19:14
 * Use :
 * File: TraitGetOption.php
 */

namespace inhere\librarys\traits;


trait TraitGetOption
{
    /**
     * 在 Trait 中已定义的属性，在使用 Trait 的类中不能再次定义
     * 而已定义的方法 可以被覆盖，但无法直接使用 已定义的方法体 e.g. parent::set(...)
     * 只能完全重写。但可以用继承 使用了 Trait 的父级来解决,具体请看 \inhere\librarys\dataStorage\example 的 例子
     */
    protected $options;

    /**
     * 存在，设置了选项name
     * 获取选项值 方式：
     * loose 直接返回对应值 不管是否为空|false|null
     * strict 会检查是否为空|false|nul，并且等同于空时返回设定的默认值
     * @var bool
     * @return bool
     */
    protected function theGetMode()
    {
        return 'loose';
    }

    /**
     * Method to get property Options
     * @param   string $name
     * @param   mixed $default
     * @param string $mode
     * @return  mixed
     */
    public function getOption($name, $default = null, $mode = 'loose')
    {
        if (array_key_exists($name, $this->options)) {
            $value = $this->options[$name];

            if ($mode ==='strict' || $this->theGetMode()==='strict') {
                $value = $value ?: $default;
            }

        } else {
            $value = $default;
        }

        if (is_callable($value) && $value instanceof \Closure)
        {
            $value = $value();
        }

        return $value;
    }

    /**
     * Method to set property options
     * @param   string  $name
     * @param   mixed   $value
     * @return  static  Return self to support chaining.
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Method to get property Options
     * @return  array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Method to set property options
     * @param   array $options
     * @return  static  Return self to support chaining.
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }
}
