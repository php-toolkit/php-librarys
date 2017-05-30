<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/29
 * Time: 上午10:36
 */

namespace inhere\library\queue;

/**
 * Class QueueFactory
 * @package inhere\library\queue
 */
final class QueueFactory
{
    const DRIVER_REDIS = 'redis';
    const DRIVER_MYSQL = 'mysql';
    const DRIVER_SQLITE = 'sqlite';
    const DRIVER_SYSV = 'sysv';
    const DRIVER_PHP = 'PHP';

    /**
     * driver map
     * @var array
     */
    private static $driverMap = [
        'redis' => RedisQueue::class,
        'mysql' => DbQueue::class,
        'sqlite' => DbQueue::class,
        'sysv' => SysVQueue::class,
        'php' => PhpQueue::class,
    ];

    /**
     * @param string $driver
     * @param array $config
     * @return QueueInterface
     */
    public static function make(array $config = [], $driver = '')
    {
        if (!$driver && isset($config['driver'])) {
            $driver = $config['driver'];
            unset($config['driver']);
        }

        if ($driver && ($class = self::getDriverClass($driver))) {
            return new $class($config);
        }

        return new PhpQueue($config);
    }

    /**
     * @param $driver
     * @return mixed|null
     */
    public static function getDriverClass($driver)
    {
        return self::$driverMap[$driver] ?? null;
    }

    /**
     * @return array
     */
    public static function getDriverMap(): array
    {
        return self::$driverMap;
    }
}
