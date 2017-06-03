<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/1
 * Time: 下午9:43
 */

namespace inhere\library\shm;

/**
 * Class ShmOpMap 可以当做是共享内存的数组结构
 *  - shared map(array) structure.
 *  - require enable --enable-shmop
 *  - support *nix and windows
 *
 * @package inhere\library\shm
 */
class ShmMap implements ShmMapInterface
{
    /**
     * @var ShmInterface
     */
    private $shm;

    /**
     * ShmOpMap constructor.
     * @param array $config
     * @param null|string $driver
     */
    public function __construct(array $config = [], $driver = null)
    {
        $this->shm = ShmFactory::make($config, $driver);
        $this->shm->open();
    }

//////////////////////////////////////////////////////////////////////
/// map method
//////////////////////////////////////////////////////////////////////

    /**
     * {@inheritDoc}
     */
    public function get($name, $default = null)
    {
        $map = $this->getMap();

        return $map[$name] ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set($name, $value)
    {
        // if is empty, init.
        if (!$map = $this->getMap()) {
            $map = [];
        }

        $map[$name] = $value;

        return $this->setMap($map);
    }

    /**
     * {@inheritDoc}
     */
    public function has($name)
    {
        if ($map = $this->getMap()) {
            return isset($map[$name]);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function del($name)
    {
        // if is empty, init.
        if (!$map = $this->getMap()) {
            return false;
        }

        if (isset($map[$name])) {
            // $value = $map[$name];
            unset($map[$name]);

            return $this->setMap($map);
        }

        return false;
    }

    /**
     * @param array $map
     * @return bool
     */
    public function sets(array $map)
    {
        return $this->setMap($map, true);
    }

    /**
     * @param array $names
     * @return array
     */
    public function gets(array $names)
    {
        $ret = [];
        $map = $this->getMap();

        foreach ($names as $name) {
            if (isset($map[$name])) {
                $ret[$name] = $map[$name];
            }
        }

        return $ret;
    }

    /**
     * get map data
     * @return array
     */
    public function getMap()
    {
        if (!$read = $this->shm->read()) {
            return [];
        }

        return unserialize(trim($read));
    }

    /**
     * set map data
     * @param array $map
     * @param bool $merge
     * @return bool
     */
    public function setMap(array $map, $merge = false)
    {
        if (!$merge) {
            return $this->shm->write(serialize($map));
        }

        if ($old = $this->getMap()) {
            return $this->shm->write(serialize(array_merge($old, $map)));
        }

        return $this->shm->write(serialize($map));
    }

    /**
     * @return ShmInterface
     */
    public function getShm()
    {
        return $this->shm;
    }
}
