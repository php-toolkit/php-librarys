<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/1
 * Time: 下午9:46
 */

namespace inhere\library\process\shm;


use inhere\library\traits\LiteConfigTrait;

/**
 * Class BaseShm
 * @package inhere\library\process\shm
 */
abstract class BaseShm
{
    use LiteConfigTrait;

    /**
     * @var int
     */
    private $id;

    /**
     * @var int|resource
     */
    private $shm;

    /**
     * @var array
     */
    protected $config = [
        'id' => null,
        'serialize' => true,

        'size' => 256000,
        'uniKey' => 'php_shm', // shared memory, semaphore
        'tmpPath' => './', // tmp path
    ];
}
