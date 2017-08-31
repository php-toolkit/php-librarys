<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/2/7
 * Time: 19:14
 * Use :
 * File: TraitUseOption.php
 */

namespace inhere\library\traits;

use inhere\library\helpers\ArrayHelper;

/**
 * Class TraitUseOption
 * @package inhere\library\traits
 * @property $options 必须在使用的类定义此属性, 在 Trait 中已定义的属性，在使用 Trait 的类中不能再次定义
 */
trait TraitUseOption
{
    /**
     * 在 Trait 中已定义的属性，在使用 Trait 的类中不能再次定义
     * 而已定义的方法 可以被覆盖，但无法直接使用 已定义的方法体 e.g. parent::set(...)
     * 只能完全重写。但可以用继承 使用了 Trait 的父级来解决,具体请看 \inhere\library\dataStorage\example 的 例子
     */
    //protected $options;

    /**
     * 是否严格获取选项值：
     *  false 直接返回对应值,只有不存在 $options['name'] 时返回 $default
     *  true  会检查是否为空|false|nul，并且等同于空时返回设定的 $default
     * @return bool
     */
    public function isStrict()
    {
        return false;
    }

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
     * @param   null|bool $strict
     * @return  mixed
     */
    public function getOption(string $name, $default = null, $strict = null)
    {
        if (array_key_exists($name, $this->options)) {
            $value = $this->options[$name];

            // use strict, check value is empty ?
            if (true === $strict || (false !== $strict && $this->isStrict())) {
                $value = $value ?: $default;
            }

        } else {
            $value = $default;
        }

        if ($value && ($value instanceof \Closure)) {
            $value = $value();
        }

        return $value;
    }

    /**
     * Method to set property options
     * @param   string $name
     * @param   mixed $value
     * @return  static  Return self to support chaining.
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * delete a option
     * @param $name
     * @return mixed|null
     */
    public function delOption($name)
    {
        $value = null;

        if ($this->hasOption($name)) {
            $value = $this->getOption($name);

            unset($this->options[$name]);
        }

        return $value;
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
    public function setOptions(array $options, $merge = true)
    {
        $this->options = $merge ? ArrayHelper::merge($this->options, $options) : $options;

        return $this;
    }
}
