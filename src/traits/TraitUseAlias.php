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
 */
trait TraitUseAlias
{
    private $_aliases       = [];
    private $lockedAliases = [];

    /**
     * 将别名对应的值动态存下来，便于同时设置多个别名
     * e.g.
     *     $alias = (new self)->alias($name, $value)->alias($name1)->alias($name2)
     * @var string
     */
    protected $tempValue;

    /**
     * 设置别名
     * @param string $alias 别名
     * @param string $value 真实的值
     * @throws \RuntimeException
     * @return $this
     */
    public function alias($alias, $value='')
    {
        $this->aliasAndIdCheck($alias, $value);

        $this->_aliases[$alias] = trim($value);

        return $this;
    }

    public function addAlias($alias, $value='')
    {
        $this->aliasAndIdCheck($alias, $value);

        if ( !isset($this->_aliases[$alias]) ) {
            $this->_aliases[$alias] = trim($value);
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
    public function resolveAlias($alias, $default=null)
    {
        $default === null && $default = $alias;

        return isset($this->_aliases[$alias]) ? $this->_aliases[$alias] : $default;
    }

    protected function aliasAndIdCheck(& $alias, & $value)
    {
        !$value && $value = $this->tempValue;

        if ( !$value || !is_string($value) ) {
            throw new \InvalidArgumentException(sprintf(
                'The 2th parameter must be of type string is not empty, %s given',
                gettype($value)
            ));
        }

        $alias = trim($alias);

        if ( in_array($alias, $this->lockedAliases)) {
            throw new \RuntimeException(sprintf('别名：%s , 已被强制锁定。请设置其他名称。',$alias));
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
    public function isAlias($alias)
    {
        return isset($this->_aliases[$alias]);
    }

    /**
     * @param $alias
     * @return mixed
     */
    public function hasAlias($alias)
    {
        return $this->isAlias($alias);
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
        if ( $alias && !isset($this->lockedAliases[$alias]) )
        {
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
     * @return array $_aliases
     */
    public function getAliases()
    {
        return $this->_aliases;
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
