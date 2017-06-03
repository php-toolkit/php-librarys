<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/1
 * Time: 下午9:52
 */

namespace inhere\library\lock;

/**
 * Class LockFactory
 * @package inhere\library\lock
 */
class LockFactory
{
    const DRIVER_FILE = 'File';
    const DRIVER_DB   = 'Database';
    const DRIVER_MEM  = 'Memcache';
    const DRIVER_SEM  = 'Semaphore';

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
     * @return LockInterface
     */
    public static function make(array $options = [], $driverName = null)
    {
        $class = null;

        if (!$driverName && isset($config['driver'])) {
            $driverName = $config['driver'];
            unset($config['driver']);
        }

        /** @var LockInterface $class */
        if (in_array($driverName, self::$driverMap, true)) {
            $class = $driverName . 'Lock';

        } else {
            foreach ([self::DRIVER_SEM, self::DRIVER_FILE] as $driverName) {
                $class = $driverName . 'Lock';

                if ($class::isSupported()){
                    break;
                }
            }
        }

        if (!$class) {
            throw new \RuntimeException('No available driver! MAP: ' . implode(',', self::$driverMap));
        }

        return new $class($options);
    }

    /**
     * @return array
     */
    public static function getDriverMap()
    {
        return self::$driverMap;
    }

    /**
     * {@inheritDoc}
     */
    public static function isSupported()
    {
        /** @var LockInterface $class */
        foreach ([self::DRIVER_SEM, self::DRIVER_FILE] as $driverName) {
            $class = $driverName . 'Lock';

            if ($class::isSupported()){
                return true;
            }
        }

        return false;
    }
}
