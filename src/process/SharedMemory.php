<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/31
 * Time: 下午9:57
 */

namespace inhere\library\process;

/**
 * Class SharedMemory
 * @package inhere\library\process
 */
class SharedMemory
{
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

    /**
     * SharedMemory constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (!function_exists('shmop_open')) {
            throw new \RuntimeException(
                'To use shmop you will need to compile PHP with the --enable-shmop parameter in your configure line.',
                -500
            );
        }

        $this->setConfig($config);

        $this->config['serialize'] = (bool)$this->config['serialize'];

        if ($this->config['id'] > 0) {
            $this->id = (int)$this->config['id'];
        } else {
            // 定义共享内存,信号量key
            $this->id = $this->config['id'] = $this->ftok(__FILE__, $this->config['uniKey']);
        }
    }

    /**
     * open
     */
    public function open()
    {
        $this->shm = shmop_open($this->id, 'c', 0644, $this->config['size']);

        if (!$this->shm) {
            throw new \RuntimeException('Create shared memory block failed', -200);
        }
    }

    /**
     * close
     */
    public function close()
    {
        shmop_close($this->shm);
    }

    /**
     * write data to SHM
     * @param string $data
     * @return bool
     */
    public function write($data)
    {
        // lock
        $fp = $this->lock();

        // write data
        $ret = shmop_write($this->shm, $data, 0) === strlen($data);

        // unlock
        $this->unlock($fp);

        return $ret;
    }

    /**
     * read data form SHM
     * @return string
     */
    public function read()
    {
        // $shm = shmop_open($this->id, 'w', 0600, 0);
        return shmop_read($this->shm, 0, shmop_size($this->shm));
    }

    /**
     * clear
     */
    protected function clear()
    {
        $this->write('');
    }

//////////////////////////////////////////////////////////////////////
/// extra method
//////////////////////////////////////////////////////////////////////

    /**
     * get a value form SHM-Map
     * @param null|string $name
     * @param mixed $default
     * @return array|null|string
     */
    public function get($name = null, $default = null)
    {
        $map = unserialize(trim($this->read()));

        if ($name === null) {
            return $map;
        }

        return $map[$name] ?? $default;
    }

    /**
     * set a value to SHM-Map
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public function set($name, $value)
    {
        // if is empty, init.
        if (!$map = $this->get()) {
            $map = [];
        }

        $map[$name] = $value;

        return $this->write(serialize($map));
    }

    public function del($name)
    {

    }

//////////////////////////////////////////////////////////////////////
/// helper method
//////////////////////////////////////////////////////////////////////

    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 共享锁定
     * @return resource
     */
    private function lock()
    {
        if (function_exists('sem_get')) {
            $fp = sem_get($this->id);
            sem_acquire($fp);
        } else {
            $fp = fopen($this->config['tmpPath'] . '/' . md5($this->id) . '.sem', 'w');
            flock($fp, LOCK_EX);
        }

        return $fp;
    }

    /**
     * 解除共享锁定
     * @param resource $fp
     * @return bool
     */
    private function unlock($fp)
    {
        if (function_exists('sem_release')) {
            return sem_release($fp);
        } else {
            return fclose($fp);
        }
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
            return -1;
        }

        $key = sprintf("%u", (($st['ino'] & 0xffff) | (($st['dev'] & 0xff) << 16) | (($projectId & 0xff) << 24)));

        return $key;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }
}
