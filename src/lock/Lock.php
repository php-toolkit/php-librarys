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
class Lock
{
    const DRIVER_FILE = 'File';
    const DRIVER_DB   = 'Database';
    const DRIVER_SEM  = 'Semaphore';
    const DRIVER_MEM  = 'Memcache';

    /**
     * @var DriverInterface
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
     * @param string $driverName
     * @param array $options
     */
    public function __construct($driverName = self::DRIVER_FILE, array $options = [])
    {
        if (in_array($driverName, self::$driverMap)) {
            $class = $driverName . 'Lock';

            $this->driver = new $class($options);
        }
    }

    /**
     * @return DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @param DriverInterface $driver
     */
    public function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @return array
     */
    public static function getDriverMap()
    {
        return self::$driverMap;
    }
}
