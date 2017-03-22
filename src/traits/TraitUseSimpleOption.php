<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/2/7
 * Time: 19:14
 * Use :
 * File: TraitUseOption.php
 */

namespace inhere\librarys\traits;

/**
 * Class TraitUseSimpleOption
 * @package inhere\librarys\traits
 *
 * @property array $options 必须在使用的类定义此属性, 在 Trait 中已定义的属性，在使用 Trait 的类中不能再次定义
 */
trait TraitUseSimpleOption
{
    /**
     * 在 Trait 中已定义的属性，在使用 Trait 的类中不能再次定义
     * 而已定义的方法 可以被覆盖，但无法直接使用 已定义的方法体 e.g. parent::set(...)
     * 只能完全重写。但可以用继承 使用了 Trait 的父级来解决,具体请看 \inhere\librarys\dataStorage\example 的 例子
     */
    //protected $options;

    /**
     * @param $name
     * @return bool
     */
    public function hasOption($name)
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * Method to get property Options
     * @param   string $name
     * @param   mixed $default
     * @return  mixed
     */
    public function getOption($name, $default = null)
    {
        if (array_key_exists($name, $this->options)) {
            $value = $this->options[$name];
        } else {
            $value = $default;
        }

        if ($value && is_callable($value) && ($value instanceof \Closure)) {
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
     * @param  array $options
     * @param  bool $merge
     * @return static Return self to support chaining.
     */
    public function setOptions($options, $merge = false)
    {
        if ( $merge ) {
            $this->options = array_merge($this->options, $options);
        } else {
            $this->options = $options;
        }

        return $this;
    }
}
