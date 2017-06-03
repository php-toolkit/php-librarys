<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/1
 * Time: 下午9:53
 */

namespace inhere\library\lock;

use inhere\library\helpers\PhpHelper;

/**
 * Class SemaphoreLock - Semaphore
 * @package inhere\library\lock
 */
class SemaphoreLock extends BaseDriver
{
    /**
     * A numeric shared memory segment ID
     * @var int
     */
    private $key;

    /**
     * The semaphore id
     * @var resource
     */
    private $semId;

    /**
     * @var array
     */
    protected $options = [
        'key' => null,
        'project' => 'php_sem',
        'permission' => 0664,
    ];

    protected function init()
    {
        parent::init();

        if ($this->options['key'] > 0) {
            $this->key = (int)$this->options['key'];
        } else {
            // 定义信号量key
            $this->key = $this->options['key'] = PhpHelper::ftok(__FILE__, $this->options['project']);
        }

        $this->semId = sem_get($this->key, 1, $this->options['permission']);
    }

    /**
     * @param string $key
     * @param int $timeout
     * @return bool
     */
    public function lock($key, $timeout = self::EXPIRE)
    {
        return sem_acquire($this->semId);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function unlock($key)
    {
        return sem_release($this->semId);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        parent::close();

        sem_remove($this->semId);

        $this->semId = null;
    }

    /**
     * @return bool
     */
    public static function isSupported()
    {
        return function_exists('sem_get');
    }
}
