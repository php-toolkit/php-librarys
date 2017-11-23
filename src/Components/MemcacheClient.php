<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-27
 * Time: 16:13
 * @from http://www.open-open.com/lib/view/open1372842855097.html
 */

namespace Inhere\Library\Components;

use Inhere\Exceptions\NotFoundException;
use Inhere\Library\Traits\TraitUseOption;

/**
 * Class MemcacheClient
 * support \Memcache and \Memcached extension
 * @package Inhere\Library\Utils
 * @method connect()
 * @method string getVersion() 获取服务器池中所有服务器的版本信息
 */
class MemcacheClient // extends AbstractCacheDriver
{
    use TraitUseOption;

    const KEYS_MAP_PREFIX = '_keys_map_';

    const LIST_KEYS_MAP_PREFIX = '_listKeys_map_';

    /**
     * @var string
     */
    private $driverName;

    /**
     * @var \Memcached|\Memcache
     */
    private $driver;

    /**
     * @var bool
     */
    private $refresh = false;

    /**
     * @var array
     */
    protected $options = [
        'servers' => [
            'name1' => [
                'host' => '127.0.0.1',
                'port' => 11211,
                'weight' => 0,
                'timeout' => 1,
            ]
        ]
    ];

    /**
     * MemcacheDriver constructor.
     * @param array $options
     * @throws NotFoundException
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options, 1);

        if (class_exists('Memcached', false)) {
            $this->driver = new \Memcached();
            $this->driverName = 'Memcached';
        } elseif (class_exists('Memcache', false)) {
            $this->driver = new \Memcache();
            $this->driverName = 'Memcache';
        } else {
            throw new NotFoundException("Please install the corresponding memcache extension, 'memcached'(recommended) or 'memcache'.");
        }

        // do connection
        $this->connection();
    }

    /**
     * @return bool
     */
    public function connection()
    {
        $servers = $this->getOption('servers', []);

        if ($this->isMemcached()) {
            return $this->driver->addServers($servers);
        }

        foreach ((array)$servers as $server) {
            $this->driver->addServer($server);
        }

        return true;
    }

    /**
     * @param array $config
     * @return bool
     */
    public function addServerByConfig(array $config)
    {
        $cfg = array_merge([
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 0,
            'timeout' => 1,
        ], $config);

        // for Memcached
        if ($this->isMemcached()) {
            return $this->driver->addServer($cfg['host'], $cfg['port'], $cfg['weight']);
        }

        // for Memcache
        $cfg = array_merge([
            'persistent' => true,
            'retry_interval' => 15,
            'status' => true,
            'failure_callback' => null,
            'timeoutMs' => 0,
        ], $cfg);

        return $this->driver->addServer(
            $cfg['host'], $cfg['port'], $cfg['persistent'], $cfg['weight'], $cfg['timeout'],
            $cfg['retry_interval'], $cfg['status'], $cfg['failure_callback'], $cfg['timeoutMs']
        );
    }

    /**
     * @param $host
     * @param int $port
     * @param int $weight
     * @param bool $persistent
     * @param int $timeout
     * @param int $retry_interval
     * @param bool $status
     * @param callable|null $failure_callback
     * @param int $timeoutMs
     * @return bool
     */
    public function addServer(
        $host, $port = 11211, $weight = 0, $persistent = true, $timeout = 1,
        $retry_interval = 15, $status = true, callable $failure_callback = null, $timeoutMs = 0
    )
    {
        // for Memcached
        if ($this->isMemcached()) {
            return $this->driver->addServer($host, $port, $weight);
        }

        // for Memcache
        return $this->driver->addServer(
            $host, $port, $persistent, $weight, $timeout,
            $retry_interval, $status, $failure_callback, $timeoutMs
        );
    }

    /**
     * 添加一个要缓存的数据
     * 对于已存在的 key 会跳过，而不覆盖内容
     * @param string $key cache key
     * @param mixed $value cache data
     * @param int $expire 过期时间
     * @param int $flag 当 driver is 'Memcache' 表示是否用MEMCACHE_COMPRESSED来压缩存储的值，true表示压缩，false表示不压缩。
     * @return bool
     */
    public function add($key, $value, $expire = 0, $flag = 0)
    {
        if (!$key) {
            return false;
        }

        if ($this->isMemcached()) {
            return $this->driver->add($key, $value, $expire);
        }

        return $this->driver->add($key, $value, $flag, $expire);
    }

    /**
     * 设置一个指定key 缓存内容。对于已存在的 key 会覆盖内容
     * @param string $key cache key
     * @param mixed $value cache data
     * @param int $expire 过期时间
     * @param int $flag 当 driver is 'Memcache' 表示是否用MEMCACHE_COMPRESSED来压缩存储的值，true表示压缩，false表示不压缩。
     * @return bool
     */
    public function set($key, $value, $expire = 0, $flag = 0)
    {
        if (!$key) {
            return false;
        }

        if ($this->isMemcached()) {
            $this->driver->set($key, $value, $expire);
        }

        return $this->driver->set($key, $value, $flag, $expire);
    }

    /**
     * @param $key
     * @return bool
     */
    public function exists($key)
    {
        return (bool)$this->get($key);
    }

    /**
     * @param string|array $key
     * @return array|bool|string
     */
    public function get($key)
    {
        if (!$key || !$this->refresh) {
            return false;
        }

        if ($this->isMemcached()) {
            return \is_array($key) ? $this->driver->getMulti($key) : $this->driver->get($key);
        }

        return $this->driver->get($key);
    }

    /**
     * replace 替换一个指定已存在key的缓存变量内容 与 set() 类似
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @param int $flag use @see \Memcache::MEMCACHE_COMPRESSED
     * @return bool
     */
    public function replace($key, $value, $expire = 0, $flag = 0)
    {
        if ($this->isMemcached()) {
            return $this->driver->replace($key, $value, $expire);
        }

        return $this->driver->replace($key, $value, $flag, $expire);
    }

    /**
     * delete
     * @param  $key string|array key
     *   $expire int 服务端等待删除该元素的时间, 被设置后，那么存储的值会在设置的秒数以后过期
     * @return true OR false
     * @throws \InvalidArgumentException
     */
    public function delete($key)
    {
        if (!$key) {
            throw new \InvalidArgumentException('The key value cannot be empty');
        }

        if (\is_array($key)) {
            foreach ((array)$key as $k) {
                $this->driver->delete($k);
            }

            return true;
        }

        return $this->driver->delete($key);
    }

    /**
     * 清空所有缓存
     * @return bool
     */
    public function clear()
    {
        return $this->driver->flush();
    }

    /**
     * 给指定kye的缓存变量一个增值
     * @param  string $key
     * @param  int $value
     * @return bool
     */
    public function increment($key, $value)
    {
        return $this->driver->increment($key, (int)$value);
    }

    /**
     * 给指定key的缓存变量一个递减值，与increment操作类拟，将在原有变量基础上减去这个值，
     * 该项的值将会在转化为数字后减去，新项的值不会小于0，对于压缩的变量不要使用本函数因为相应的取值方法会失败
     * @param $key
     * @param  int $value
     * @return int
     */
    public function decrement($key, $value)
    {
        return $this->driver->decrement($key, (int)$value);
    }

    /////////////////////////////////////////////////////////////////////////
    /// extend method
    /////////////////////////////////////////////////////////////////////////

    /**
     *  获取服务器池的统计信息
     * @param string $type
     * @param $slabId
     * @param int $limit
     * @return array
     */
    public function getStats($type = 'items', $slabId, $limit = 100)
    {
        if ($this->isMemcached()) {
            return $this->driver->getStats();
        }

        return $this->driver->getStats($type, $slabId, $limit);
    }

    /**
     * [setServerParams description]
     * @param string $host [服务器的地址]
     * @param int $port [服务器端口]
     * @param int $timeout [连接的持续时间]
     * @param [type]   $retry_interval   [连接重试的间隔时间，默认为15,设置为-1表示不进行重试]
     * @param bool $status [控制服务器的在线状态]
     * @param callback $failure_callback [允许设置一个回掉函数来处理错误信息。]
     */
    public function setServerParams($host, $port, $timeout, $retry_interval, $status, $failure_callback)
    {

    }

    /////////////////////////////////////////////////////////////////////////
    /// 针对列表/分页等数据量较大的数据
    /////////////////////////////////////////////////////////////////////////

    /*
     * 使用示例
     *
     * $baseKey = 'user_product_list_' . "{$uid}_{$categoryId}";
     * $cacheKey = $baseKey . "$page_$pageSize";
     *
     * $productList = [...];
     *
     * // set
     * $cache->setListData($cacheKey, $productList, $expire, $baseKey);
     *
     * // get
     * $cache->getListData($cacheKey);
     *
     * // del
     * // if we known $cacheKey
     * $cache->delListData($cacheKey);
     * // but we most of the time don't get '$cacheKey'
     * // now, you can by $baseKey del all page cache data.
     * $cache->delListKeys($baseKey);
     */

    /**
     * get List Data
     * @param $cacheKey
     * @return array|null
     */
    public function getListData($cacheKey)
    {
        if (!$cacheKey || $this->refresh) {
            return null;
        }

        $listKey = self::KEYS_MAP_PREFIX . $cacheKey;

        if ($keys = $this->driver->get($listKey)) {
            $list = [];
            foreach ((array)$keys as $key) {
                $list[] = $this->driver->get($key);
            }

            return $list;
        }

        return null;
    }

    /**
     * 保存列表/分页等数据量较大的数据到缓存
     * 将会循环拆分数据来缓存, 同时会生成一个 列表key 缓存 key-list
     * 将根据 列表key 删除和获取数据
     * @param string $cacheKey
     * @param array $data
     *  将会循环拆分 $data 存储，单个item缓存key即为 $key + ":$i"
     * @param int $expire
     * @param null|string $baseKey 如果是分页数据，推荐提供 $baseKey
     *  将会把 key-list 的缓存key 存储到 以$baseKey为键的缓存列表中
     *  可以通过 $baseKey (call `self::delListKeys($baseKey)`) 删除所有分页数据的 列表key -- 相当于删除了所有与 $baseKey 相关的缓存
     * @example 使用例子
     * ```
     * // set
     * // get
     * // del
     * ```
     * @return array
     */
    public function setListData($cacheKey, array $data, $expire = 3600, $baseKey = null)
    {
        if (!$cacheKey || !$data) {
            return null;
        }

        $i = 0;
        $keys = [];

        foreach ($data as $item) {
            $keys[] = $key = $cacheKey . ":$i";
            $this->driver->set($key, $item, $expire);
            $i++;
        }

        // save key-list to cache
        $listKey = self::KEYS_MAP_PREFIX . $cacheKey;

        // you can delete list data cache by self::delDataMap($cacheKey)
        $this->driver->set($listKey, $keys, $expire);

        if ($baseKey) {
            // you can delete all page data cache by self::delListKeys($baseKey)
            $this->addListKey($listKey, $baseKey);
        }

        return $keys;
    }

    protected function addListKey($listKey, $baseKey)
    {
        $listKeysKey = self::LIST_KEYS_MAP_PREFIX . $baseKey;

        // init
        if (!$listKeys = $this->driver->get($listKeysKey)) {
            $this->driver->set($listKeysKey, [$listKey]);

            // add
        } elseif (!\in_array($listKey, $listKeys, true)) {
            $listKeys[] = $listKey;
            $this->driver->set($listKeysKey, $listKeys);
        }
    }

    /**
     * del List Data
     * @param $cacheKey
     * @return int
     */
    public function delListData($cacheKey)
    {
        $listKey = self::KEYS_MAP_PREFIX . $cacheKey;

        if ($keys = $this->driver->get($listKey)) {
            foreach ((array)$keys as $key) {
                $this->driver->delete($key);
            }

            $this->driver->delete($listKey);
        }

        return $keys;
    }

    /**
     * @param $baseKey
     * @return array|null|string
     */
    public function delListKeys($baseKey)
    {
        $listKeysKey = self::LIST_KEYS_MAP_PREFIX . $baseKey;

        if ($listKeys = $this->driver->get($listKeysKey)) {
            foreach ((array)$listKeys as $listKey) {
                $this->driver->delete($listKey);
            }

            // NOTICE: delete $listKeysKey
            $this->driver->delete($listKeysKey);
        }

        return $listKeys;
    }


    /////////////////////////////////////////////////////////////////////////
    /// getter/setter
    /////////////////////////////////////////////////////////////////////////

    /**
     * @return bool
     */
    public function isMemcached()
    {
        return $this->driverName === 'Memcached';
    }

    /**
     * @return mixed
     */
    public function getDriverName()
    {
        return $this->driverName;
    }

    /**
     * @param mixed $driverName
     */
    public function setDriverName($driverName)
    {
        $this->driverName = $driverName;
    }

    /**
     * @return \Memcache|\Memcached
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @param \Memcache|\Memcached $driver
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     * @throws \LogicException
     */
    public function __call($method, $args)
    {
        if (method_exists($this->driver, $method)) {
            return \call_user_func_array([$this->driver, $method], $args);
        }

        throw new \LogicException("Call a not exists method: $method");
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return property_exists($this, $name);
    }

    /**
     * @param $name
     * @return null|mixed
     */
    public function __get($name)
    {
        $getter = 'get' . ucfirst($name);

        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        return null;
    }

    /**
     * @param string $name
     * @param $value
     * @throws \RuntimeException
     */
    public function __set(string $name, $value)
    {
        $setter = 'set' . ucfirst($name);

        if (method_exists($this, $setter)) {
            $this->$setter($name, $value);
        }

        throw new \RuntimeException("Setting a not exists property: $name");
    }
}


/**
 * Memcache::connect();
 * Memcache::pconnect(); 长链接
 * Memcache::close(); 关闭对象（对常链接不起作用）
 * Memcache::addServer(); 向对象添加一个服务器
 * Memcache::add() 添加一个要缓存的数据如果作为这个缓存的数据在键在服务器上还不存在的情况下
 * Memcache::replace() 替换一个指定已存在key的缓存变量内容
 * Memcache::set 设置一个指定key的缓存变量内容
 * Memcache::get() 获取某个key的变量缓存值
 * Memcache::delete() 删除某个变量的缓存
 * Memcache::flush() 清空所缓存内容，不是真的删除缓存的内容，只是使所有变量的缓存过期，使内存中的内容被重写
 * Memcache::getExtendedStats() 获取所有服务器扩展静态信息
 * Memcache::getStats(); 获取最后添加服务器静态信息
 * Memcache::getServerStatus() 通过输入的host及port来获取相应的服务器信息
 * Memcache::getVersion() 获取服务器的版本号信息
 * Memcache::setCompressThreshold 设置压缩级根
 * Memcache::setServerParams   Memcache version 2.1.0后增加的函数，运行时设置服务器参数
 * Memcache::increment  给指定kye的缓存变量一个增值，如查该变量不是数字时不会被转化为数字
 * Memcache::decrement
 * //给指定key的缓存变量一个递减值，与increment操作类拟，将在原有变量基础上减去这个值，该项的值将会在转化为数字后减去，新项的值不会小于0，对于压缩的变量不要使用本函数因为相应的取值方法会失败
 */
