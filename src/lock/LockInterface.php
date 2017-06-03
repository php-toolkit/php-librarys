<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/1
 * Time: 下午9:53
 */

namespace inhere\library\lock;

/**
 * Interface LockInterface
 * @package inhere\library\lock
 */
interface LockInterface
{
    /**
     * seconds
     */
    const EXPIRE = 5;

    /**
     * @param string $key
     * @param int $timeout
     * @return mixed
     */
    public function lock($key, $timeout = self::EXPIRE);

    /**
     * @param string $key
     * @return mixed
     */
    public function unlock($key);

    public function close();

    /**
     * @return bool
     */
    public static function isSupported();
}
