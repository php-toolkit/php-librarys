<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/2/27
 * Use : environment information
 * File: AbstructEnv.php
 */

namespace inhere\tools\env;

use inhere\tools\collections\SimpleCollection;

abstract class AbstractEnv extends SimpleCollection
{
    /**
     * @var array
     */
    static public $config = [];

    /**
     * 初始化信息
     */
    public function __construct(array $data=[])
    {
        parent::__construct($data);

        foreach (static::$config as $name => $realName) {
            $this->set($name, isset($_SERVER[$realName]) ? trim($_SERVER[$realName]) : null);
        }

        $this->init();
    }
}