<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/2/7
 * Time: 21:28
 * Use :
 * File: TraitUseAlias.php
 */

namespace inhere\library\traits;

/**
 * Class TraitUseAlias
 * @package inhere\library\traits
 *
 * @property array $aliases
 */
trait TraitUseAlias
{
    // protected $aliases      = [];
    private $lockedAliases = [];

    /**
     * 设置/获取别名
     * @param string $alias 别名
     * @param string $value 真实的值. 为空用于获取
     * @throws \RuntimeException
     * @return mixed
     */
    public function alias($alias, $value = '')
    {
        // set
        if ($alias && $value) {
            $this->aliasAndValueCheck($alias, $value);
            $this->aliases[$alias] = trim($value);

            // get
        } else {
            return $this->resolveAlias($alias);
        }

        return $this;
    }

    /**
     * @param $alias
     * @param $value
     * @return $this
     */
    public function addAlias($alias, $value)
    {
        $this->aliasAndValueCheck($alias, $value);

        if (!isset($this->aliases[$alias])) {
            $this->aliases[$alias] = trim($value);
        }

        return $this;
    }

    /**
     * @param $alias
     * @param null $default 默认值
     *                      若设置为 null, 会默认将 $alias 作为默认值；则没有找到时，将会返回$alias
     *                      若要用于判断， 可传入 0 '' false
     * @return mixed
     */
    public function resolveAlias($alias, $default = null)
    {
        $default === null && $default = $alias;

        return $this->aliases[$alias] ?? $default;
    }

    /**
     * @param $alias
     * @param $value
     */
    protected function aliasAndValueCheck(& $alias, & $value)
    {
        if (!$value || !is_string($value)) {
            throw new \InvalidArgumentException(sprintf(
                'The 2th parameter must be of type string is not empty, %s given',
                gettype($value)
            ));
        }

        $alias = trim($alias);

        if (in_array($alias, $this->lockedAliases, true)) {
            throw new \RuntimeException(sprintf('alias name %s has been locked.', $alias));
        }
    }

    /**
     * 载入别名设置
     * @param array $aliases
     * @return $this
     */
    public function loadAliases(array $aliases)
    {
        foreach ($aliases as $alias => $path) {
            $this->alias($alias, $path);
        }

        return $this;
    }

    /**
     * @param $alias
     * @return mixed
     */
    public function hasAlias($alias)
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * @param array $aliases
     * @return $this
     */
    public function lockAliases(array $aliases)
    {
        foreach ($aliases as $alias) {
            $this->lockedAliases[] = $alias;
        }

        return $this;
    }

    /**
     * @param $alias
     */
    public function lockAlias($alias)
    {
        if ($alias && !isset($this->lockedAliases[$alias])) {
            $this->lockedAliases[] = $alias;
        }
    }

    /**
     * @param $alias
     * @return mixed
     */
    public function isLockedAlias($alias)
    {
        return isset($this->lockedAliases[$alias]);
    }

    /**
     * @return array $aliases
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    public function getLockedAliases()
    {
        return $this->lockedAliases;
    }

    /**
     * @param array $lockedAliases
     */
    public function setLockedAliases($lockedAliases)
    {
        $this->lockedAliases = $lockedAliases;
    }
}
