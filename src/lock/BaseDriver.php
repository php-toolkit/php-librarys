<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/1
 * Time: 下午10:07
 */

namespace inhere\library\lock;

use inhere\library\traits\LiteOptionsTrait;

/**
 * Class BaseDriver
 * @package inhere\library\lock
 */
abstract class BaseDriver implements DriverInterface
{
    use LiteOptionsTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * BaseDriver constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (!static::isSupported()) {
            throw new \RuntimeException("Current environment is not support the lock driver: {$this->name}");
        }

        $this->setOptions($options);

        $this->init();
    }

    protected function init()
    {
        // ... ...
    }

    /**
     * close
     */
    public function close()
    {
        $this->options = [];
    }


    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public static function isSupported()
    {
        return true;
    }
}
