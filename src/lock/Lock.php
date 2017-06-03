<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/1
 * Time: 下午9:52
 */

namespace inhere\library\lock;

/**
 * Class Lock
 * @package inhere\library\lock
 */
class Lock implements LockInterface
{
    const DRIVER_FILE = 'File';
    const DRIVER_DB   = 'Database';
    const DRIVER_MEM  = 'Memcache';
    const DRIVER_SEM  = 'Semaphore';

    /**
     * @var LockInterface
     */
    private $driver;

    /**
     * @var array
     */
    private static $driverMap = [
        self::DRIVER_FILE,
        self::DRIVER_DB,
        self::DRIVER_SEM,
        self::DRIVER_MEM,
    ];

    /**
     * Lock constructor.
     * @param array $options
     * @param string $driverName
     */
    public function __construct(array $options = [], $driverName = null)
    {
        $this->driver = LockFactory::make($options, $driverName);
    }

    /**
     * {@inheritDoc}
     */
    public function lock($key, $timeout = Lock::EXPIRE)
    {
        $this->driver->lock($key, $timeout);
    }

    /**
     * {@inheritDoc}
     */
    public function unlock($key)
    {
        return $this->driver->unlock($key);
    }

    public function close()
    {
        $this->driver->close();
    }

    /**
     * @return LockInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @param LockInterface $driver
     */
    public function setDriver(LockInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * {@inheritDoc}
     */
    public static function isSupported()
    {
        return LockFactory::isSupported();
    }
}
