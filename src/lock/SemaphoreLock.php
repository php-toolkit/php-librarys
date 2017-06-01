<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/1
 * Time: 下午9:53
 */

namespace inhere\library\lock;

/**
 * Class SemaphoreLock - Semaphore
 * @package inhere\library\lock
 */
class SemaphoreLock extends BaseDriver
{
    /**
     * @var int
     */
    private $semId;

    /**
     * @var resource
     */
    private $sem;

    /**
     * @var array
     */
    protected $options = [
        'id' => null,
        'uniKey' => 'php_sem',
    ];

    protected function init()
    {
        parent::init();

        if ($this->options['semId'] > 0) {
            $this->semId = (int)$this->options['id'];
        } else {
            // 定义共享内存,信号量key
            $this->semId = $this->options['semId'] = $this->ftok(__FILE__, $this->options['uniKey']);
        }

        $this->sem = sem_get($this->semId);
    }

    /**
     * @param string $key
     * @param int $timeout
     * @return bool
     */
    public function lock($key, $timeout = self::EXPIRE)
    {
        return sem_acquire($this->sem);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function unlock($key)
    {
        return sem_release($this->sem);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        parent::close();

        sem_remove($this->sem);

        $this->sem = null;
    }

    /**
     * @return bool
     */
    public static function isSupported()
    {
        return function_exists('sem_get');
    }

    /**
     * @param $pathname
     * @param $projectId
     * @return int|string
     */
    private function ftok($pathname, $projectId)
    {
        if (function_exists('ftok')) {
            return ftok($pathname, $projectId);
        }

        if (!$st = @stat($pathname)) {
            return time();
        }

        $key = sprintf("%u", (($st['ino'] & 0xffff) | (($st['dev'] & 0xff) << 16) | (($projectId & 0xff) << 24)));

        return $key;
    }
}
