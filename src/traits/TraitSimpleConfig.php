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
 * Class TraitSimpleConfig
 * @package inhere\library\traits
 *
 * @property array $config 必须在使用的类定义此属性, 在 Trait 中已定义的属性，在使用 Trait 的类中不能再次定义
 */
trait TraitSimpleConfig
{
    /**
     * 在 Trait 中已定义的属性，在使用 Trait 的类中不能再次定义
     * 而已定义的方法 可以被覆盖，但无法直接使用 已定义的方法体 e.g. parent::set(...)
     * 只能完全重写。但可以用继承 使用了 Trait 的父级来解决,具体请看 \inhere\library\dataStorage\example 的 例子
     */
    //protected $config = [];

    /**
     * @param $name
     * @return bool
     */
    public function hasConfig($name)
    {
        return array_key_exists($name, $this->config);
    }

    /**
     * Method to get property Options
     * @param   string $name
     * @param   mixed $default
     * @return  mixed
     */
    public function getValue(string $name, $default = null)
    {
        $value = array_key_exists($name, $this->config) ? $this->config[$name] : $default;

        if ($value && is_callable($value) && ($value instanceof \Closure)) {
            $value = $value();
        }

        return $value;
    }

    /**
     * Method to set property config
     * @param   string $name
     * @param   mixed $value
     * @return  static  Return self to support chaining.
     */
    public function setValue($name, $value)
    {
        $this->config[$name] = $value;

        return $this;
    }

    /**
     * delete a option
     * @param $name
     * @return mixed|null
     */
    public function delValue($name)
    {
        $value = null;

        if ($this->hasConfig($name)) {
            $value = $this->getValue($name);

            unset($this->config[$name]);
        }

        return $value;
    }

    /**
     * Method to get property Options
     * @return  array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Method to set property config
     * @param  array $config
     * @param  bool $merge
     * @return static Return self to support chaining.
     */
    public function setConfig(array $config, $merge = true)
    {
        $this->config = $merge ? ArrayHelper::merge($this->config, $config) : $config;

        return $this;
    }
}
