<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-27
 * Time: 16:13
 * @from http://www.open-open.com/lib/view/open1372842855097.html
 */

namespace inhere\librarys\utils;

use inhere\exceptions\NotFoundException;
use inhere\exceptions\ConnectException;
use inhere\librarys\traits\TraitUseOption;

/**
 * Class MemcacheClient
 *
 * support \Memcache and \Memcached extension
 *
 * @package inhere\librarys\utils
 * 
 * @method connect()
 * @method string getVersion() 获取服务器池中所有服务器的版本信息
 */
class MemcacheClient // extends AbstractCacheDriver
{
    use TraitUseOption;

    const KEYS_MAP_PREFIX       = '_keys_map_';

    const LIST_KEYS_MAP_PREFIX  = '_listKeys_map_';

    private $_runCache  = [];

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
     * MemcacheDriver constructor.
     * @param array $options
     * @throws NotFoundException
     */
    public function __construct(array $options=[])
    {
        $this->setOptions($options, 1);

        $this->_clientType  = class_exists('Memcache',false) ?
            'Memcache' :
            (class_exists('Memcached',false) ? "Memcached" : false);

        if ( ! $this->_clientType ){
            throw new NotFoundException("Please install the corresponding 'Memcache' extension.");
        }

        # 判断引入类型
        $this->driver = $this->_clientType=='Memcached' ? new \Memcached() : new \Memcache();

        $this->connection();
    }

    public function connection()
    {
        $servers =$this->getOption('server');

        if ( !$servers ) {
            \Trigger::error('请配置(array) cache.'.$this->_clientType.'.server');
        }

        # code...
        if ( count($servers)==1 ) {
            $server = $servers[0];

            $this->pconnect($server['host'], $server['port']);
        } else {
            foreach ($servers as $server) {
                $this->addServer( $server);
            }
        }
    }

    /**
     * 添加一个要缓存的数据如果作为这个缓存的数据在键在服务器上还不存在的情况下
     * 对于已存在的 key 会跳过，而不覆盖内容
     *
     * @param array|string $key
     * @param string $value 值
     * @param int $expire 过期时间
     * @param int $flag
     * @return bool
     */
    public function add($key, $value, $expire = 0, $flag = 0)
    {
        if ( $this->isMemcached() ) {
            return $this->driver->add( $this->_keyName($key), $value, $expire );
        }

        return $this->driver->add( $key, $value, $flag, $expire);
    }

    /**
     * 设置一个指定key的缓存变量内容。对于已存在的 key 会覆盖内容
     * @param $key string key
     * @param $value string 值
     * @internal 当 $this->_clientType == 'Memcache' 时候 $this->getOption('compress') 值
     *           表示是否用MEMCACHE_COMPRESSED来压缩存储的值，true表示压缩，false表示不压缩。
     * @param $expire int 过期时间
     * @return   TRUE or FALSE
     */
    public function set($key, $value, $expire = null)
    {
        if (is_null($expire)){
            $expire =  (int) $this->getOption('expire',0);
        }

        if (is_array($key)) {
            foreach($key as $multi)
            {
                if (!isset($multi['expire']) || $multi['expire'] == ''){
                    $multi['expire'] =  (int) $this->getOption('expire',0);
                }

                $this->set($this->_keyName($multi['key']), $multi['value'], $multi['expire']);
            }

            return true;
        } else {
            $this->_runCache[$this->_keyName($key)] = $value;

            switch($this->_clientType){
                case 'Memcache':
                    $setStatus = $this->driver->set( $this->_keyName($key), $value, $this->getOption('compress'), $expire );
                    break;

                default:
                case 'Memcached':
                    $setStatus = $this->driver->set( $this->_keyName($key), $value, $expire );
                    break;
            }

            return $setStatus;
        }
    }

    public function exists($key)
    {
        return (bool)$this->get($key);
    }

    /**
     * @param $key
     * @param string $default
     * @return array|bool|string
     */
    public function get($key, $default='')
    {
        if ( !$key || !$this->driver) {
            return false;
        }

        $realKey = $this->_keyName($key);

        if (isset($this->_runCache[$realKey])) {
            return $this->_runCache[$realKey];
        }


        if (is_array($key)) {
            return $this->gets($key);
        }

        return $this->driver->get($this->_keyName($key));
    }

    /**
     * @param $keys
     * @return array
     */
    public function gets($keys)
    {
        foreach($keys as $n=>$k) {
            $keys[$n] = $this->_keyName($k);
        }

        if (method_exists($this->driver, 'getMulti') ) {
            return $this->driver->getMulti($keys);
        } else {
            $result = [];

            foreach ($keys as $key) {
                $result[] = $this->get($key);
            }

            return $result;
        }
    }

    /**
     * [replace 替换一个指定已存在key的缓存变量内容 与 set() 类似 ]
     * @param string|string $key [description]
     * @param  mixed $var [description]
     * @param int|int $flag [description]
     * @param int|int $expire [description]
     * @return void [type]         [description]
     */
    public function replace($key , $var , $flag , $expire )
    {
        # code...
    }

    /**
     * delete
     * @param  $key string key
     *   $expire int 服务端等待删除该元素的时间, 被设置后，那么存储的值会在设置的秒数以后过期
     * @return true OR false
     */
    public function delete($key)
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('The key value cannot be empty');
        }

        if (is_array($key)) {
            foreach($key as $multi) {
                $this->delete($multi);
            }

            return true;
        }

        $realKey = $this->_keyName($key);
        unset($this->_runCache[$realKey]);

        return $this->driver->delete( $realKey);
    }

    /**
     * 清空所有缓存
     * @return bool
     */
    public function flush()
    {
        return $this->driver->flush();
    }
    public function clear()
    {
        return $this->flush();
    }

    /**
     * 给指定kye的缓存变量一个增值
     * @param  string $key
     * @param  int $value
     * @return bool
     */
    public function increment($key, $value)
    {
        return $this->driver->increment($key,(int)$value);
    }

    /**
     * 给指定key的缓存变量一个递减值，与increment操作类拟，将在原有变量基础上减去这个值，
     * 该项的值将会在转化为数字后减去，新项的值不会小于0，对于压缩的变量不要使用本函数因为相应的取值方法会失败
     * @param $key
     * @param  int $value
     * @return int
     */
    public function decrement($key,$value)
    {
        return $this->driver->decrement($key,(int)$value);
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
        if ( $this->isMemcached() ) {
            return $this->driver->getStats();
        }

        return $this->driver->getStats($type, $slabId, $limit);
    }

    /**
     * [setCompressThreshold 对大于某一大小的数据进行压缩]
     * @param int $threshold [表示处理数据大小的临界点]
     * @param float $min_savings [参数表示压缩的比例，默认为0.2。]
     * @return bool
     */
    public function setCompressThreshold( $threshold , $min_savings=0.2)
    {
        switch($this->_clientType) {
            case 'Memcache':
                $setcompressthreshold_status = $this->driver->setCompressThreshold($threshold, $min_savings);
                break;

            default:
                $setcompressthreshold_status = TRUE;
                break;
        }

        return $setcompressthreshold_status;
    }

    /**
     * [setServerParams description]
     * @param string   $host             [服务器的地址]
     * @param int      $port             [服务器端口]
     * @param int      $timeout          [连接的持续时间]
     * @param [type]   $retry_interval   [连接重试的间隔时间，默认为15,设置为-1表示不进行重试]
     * @param bool     $status           [控制服务器的在线状态]
     * @param callback $failure_callback [允许设置一个回掉函数来处理错误信息。]
     */
    public function setServerParams($host , $port , $timeout , $retry_interval , $status , $failure_callback)
    {

    }

    /////////////////////////////////////////////////////////////////////////
    /// 针对列表/分页等数据量较大的数据
    /// 使用示例参考 app\models\dal\users\CollectionDAL::getUnreadedProductList()
    /////////////////////////////////////////////////////////////////////////

    /**
     * get List Data
     * @param $cacheKey
     * @return array|null
     */
    public function getListData($cacheKey)
    {
        if ( !$cacheKey || $this->refresh ) {
            return null;
        }

        $listKey = self::KEYS_MAP_PREFIX . $cacheKey;

        if ( $keys = $this->cache->get($listKey) ) {
            $list = [];
            foreach ($keys as $key) {
                $list[] = $this->cache->get($key);
            }

            return $list;
        }

        return null;
    }

    /**
     * 保存列表/分页等数据量较大的数据到缓存
     *
     * 将会循环拆分数据来缓存, 同时会生成一个 列表key 缓存 key-list
     * 将根据 列表key 删除和获取数据
     *
     * @param string $cacheKey
     * @param array $data
     *  将会循环拆分 $data 存储，单个item缓存key即为 $key + ":$i"
     *
     * @param int $expire
     * @param null|string $baseKey 如果是分页数据，推荐提供 $baseKey
     *  将会把 key-list 的缓存key 存储到 以$baseKey为键的缓存列表中
     *  可以通过 $baseKey (call `self::delListKeys($baseKey)`) 删除所有分页数据的 列表key -- 相当于删除了所有与 $baseKey 相关的缓存
     *
     * @example 使用例子
     *
     * ```
     * // set
     *
     * // get
     *
     * // del
     *
     * ```
     *
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
            $this->cache->set($key, $item, $expire);
            $i++;
        }

        // save key-list to cache
        $listKey = self::KEYS_MAP_PREFIX . $cacheKey;

        // you can delete list data cache by self::delDataMap($cacheKey)
        $this->cache->set($listKey, $keys, $expire);

        if ($baseKey) {
            // you can delete all page data cache by self::delListKeys($baseKey)
            $this->addListKey($listKey, $baseKey);
        }

        return $keys;
    }
    protected function addListKey($listKey, $baseKey)
    {
        $listKeysKey = self::LIST_KEYS_MAP_PREFIX  . $baseKey;

        // init
        if ( !$listKeys = $this->cache->get($listKeysKey) ) {
            $this->cache->set($listKeysKey, [$listKey]);

            // add
        } elseif (!in_array($listKey, $listKeys)) {
            $listKeys[] = $listKey;
            $this->cache->set($listKeysKey, $listKeys);
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

        if ( $keys = $this->cache->get($listKey) ) {
            foreach ($keys as $key) {
                $this->cache->delete($key);
            }

            $this->cache->delete($listKey);
        }

        return $keys;
    }

    /**
     * @param $baseKey
     * @return array|null|string
     */
    public function delListKeys($baseKey)
    {
        $listKeysKey = self::LIST_KEYS_MAP_PREFIX  . $baseKey;

        if ( $listKeys = $this->cache->get($listKeysKey) ) {
            foreach ($listKeys as $listKey) {
                $this->cache->delete($listKey);
            }

            // NOTICE: delete $listKeysKey
            $this->cache->delete($listKeysKey);
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
     */
    public function __call($method, $args)
    {
        if ( method_exists($this->driver, $method) ) {
            return call_user_func_array([$this->driver, $method], $args);
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

        if ( method_exists($this, $getter) ) {
            return $this->$getter();
        } elseif (method_exists($this->driver, $name)) {
            return 
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

        if ( method_exists($this, $setter) ) {
            $this->$setter($name, $value);
        }

        throw new \RuntimeException("Setting a not exists property: $name");
    }
}


/**
Memcache::connect();

Memcache::pconnect(); 长链接
Memcache::close(); 关闭对象（对常链接不起作用）
Memcache::addServer(); 向对象添加一个服务器
Memcache::add() 添加一个要缓存的数据如果作为这个缓存的数据在键在服务器上还不存在的情况下

Memcache::replace() 替换一个指定已存在key的缓存变量内容
Memcache::set 设置一个指定key的缓存变量内容
Memcache::get() 获取某个key的变量缓存值
Memcache::delete() 删除某个变量的缓存
Memcache::flush() 清空所缓存内容，不是真的删除缓存的内容，只是使所有变量的缓存过期，使内存中的内容被重写
Memcache::getExtendedStats() 获取所有服务器扩展静态信息
Memcache::getStats(); 获取最后添加服务器静态信息
Memcache::getServerStatus() 通过输入的host及port来获取相应的服务器信息
Memcache::getVersion() 获取服务器的版本号信息
Memcache::setCompressThreshold 设置压缩级根
Memcache::setServerParams   Memcache version 2.1.0后增加的函数，运行时设置服务器参数
Memcache::increment  给指定kye的缓存变量一个增值，如查该变量不是数字时不会被转化为数字

Memcache::decrement
//给指定key的缓存变量一个递减值，与increment操作类拟，将在原有变量基础上减去这个值，该项的值将会在转化为数字后减去，新项的值不会小于0，对于压缩的变量不要使用本函数因为相应的取值方法会失败

 */