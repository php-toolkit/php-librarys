<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/10/12
 * Time: 15:46
 */

namespace inhere\librarys\utils;

/**
 * Class Redis
 * @package inhere\librarys\utils
 */
class Redis
{
    /**
     * @var \Predis\Client
     */
    public $redis;

    /**
     * @return \Predis\Client
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * @param \Predis\Client $redis
     */
    public function setRedis($redis)
    {
        $this->redis = $redis;
    }

    /**
     * redis 中 key 是否存在
     * @param string $key
     * @return bool
     */
    public function existsKey($key)
    {
        return (bool)$this->redis->exists($key);
    }

    /**************************************************************************
     * data relation map(set)
     **************************************************************************/

    /**
     * 获取集合的全部数据
     * @param $key
     * @return array
     */
    public function getSet($key)
    {
        return $this->redis->smembers($key);
    }

    /**
     * 集合是否存在
     * @param $key
     * @return bool
     */
    public function existsSet($key)
    {
        return $this->redis->scard($key) > 0;
    }

    /**
     * 集合是否存在元素 $member
     * @param $key
     * @param $member
     * @return bool
     */
    public function existsInSet($key,$member)
    {
        return (bool)$this->redis->sismember($key, $member);
    }

    /**************************************************************************
     * data relation map(zset)
     **************************************************************************/

    /**
     * 将一个或多个 member 元素及其 score 值加入到有序集 key 当中
     * @param string $key redis key
     * @param int|array 为 integer 时 $targetId, 要结合 $score.
     *                  为array 时，一次添加多个
     * e.g:
     * 1. add one
     *  ($key, $member, $score)
     * 2. add multi
     * [
     *   // 成员 member是唯一的
     *  'member1' => score1,
     *  'member2' => score2,
     * ]
     *
     * @param null|int $score
     * @return int
     */
    public function addToZSet($key, $member, $score = null)
    {
        if (!$key) {
            return 0;
        }

        if ( is_array($member) ) {
            return $this->redis->zadd($key, $member);
        }

        return $this->redis->zadd($key, [
            $member => is_numeric($score) ? $score : time()
        ]);
    }

    /**
     *
     * @param array $data
     * e.g:
     *  [
     *      [score, member],
     *      [score1, member1],
     *      ...
     *  ]
     * @param $key
     * @param callable $scoreMemberHandle
     * @return int|number
     */
    protected function addDataListToZSet($key, array $data, callable $scoreMemberHandle)
    {
        if (!$data || !$key) {
            return 0;
        }

//        $pip = $this->redis->pipeline();
//        $pip = $this->redis->watch($key);
        $this->redis->multi();

        foreach ($data as $row) {
            list($score, $member) = $scoreMemberHandle($row);

            $this->redis->zadd($key, [ (string)$member => $score ]);
        }

        $result = $this->redis->exec();

        return array_sum($result);
    }

    /**
     * 获取有序集合的数据列表
     *  按 score 值递增(从小到大)来排序。
     * @param $key
     * @param int $start
     * @param int $stop
     * @param array $options
     * @return array
     */
    public function getZSetList($key, $start = 0, $stop = -1, $options=[])
    {
        return $this->redis->zrange($key, $start, $stop, $options);
    }

    /**
     * 获取有序集合的数据列表
     *  按 score 值递减(从大到小)来排列
     * @param $key
     * @param int $start
     * @param int $stop
     * @param array $options
     * @return array
     */
    public function getRevZSetList($key, $start = 0, $stop = -1, $options=[])
    {
        return $this->redis->zrevrange($key, $start, $stop, $options);
    }

    /**
     * 有序集合是否存在
     * @param $key
     * @return bool
     */
    public function existsZSet($key)
    {
        return $this->redis->zcard($key) > 0;
    }

    /**
     * 有序集合是否存在元素
     * @param $key
     * @param $member
     * @return bool
     */
    public function existsInZSet($key, $member)
    {
        return null !== $this->redis->zscore($key, $member);
    }

    /**************************************************************************
     * data list (list)
     **************************************************************************/

    /**
     * @param $key
     * @return bool
     */
    public function existsList($key)
    {
        return $this->existsKey($key) && $this->redis->llen($key);
    }

    /**
     * @param string $key
     * @param $element
     * @return int
     */
    public function addToList($key, $element)
    {
        return $this->setList($key, $element);
    }

    /**
     * 获取列表数据
     *  默认取出全部
     * @param string $key
     * @param int $start 起始位置
     * @param int $stop 结束位置
     * @return array
     */
    public function getList($key, $start = 0, $stop = -1)
    {
        // list:magazine:100:images 12 13 14
        return $this->redis->lrange($key, $start, $stop);
    }

    /**
     * 设置列表数据
     * @param string $key
     * @param array $elements
     * @return int
     */
    public function setList($key, $elements)
    {
        // list:magazine:100:images 12 13 14
        return $this->redis->rpush($key, (array)$elements);
    }

    /**************************************************************************
     * data table (hash table)
     **************************************************************************/

    /**
     * @param string $key
     * @return bool
     */
    public function existsHTable($key)
    {
        return $this->redis->hlen($key) > 0;
    }

    /**
     * check hash table is empty or is not exists.
     * @param $key
     * @return bool
     */
    public function isEmptyHTable($key)
    {
        return 0 === $this->redis->hlen($key);
    }

    /**
     * @param $key
     * @param $field
     * @return bool
     */
    public function existsInHTable($key, $field)
    {
        return 1 === $this->redis->hexists($key, $field);
    }

    /**
     * @param $key
     * @param $field
     * @param int $step
     * @return int
     */
    public function htIncrField($key, $field, $step = 1)
    {
        return $this->redis->hincrby($key, $field, (int)$step);
    }

    /**
     * @param string $key
     * @param string $field 计数field
     * @param int $step
     * @param bool $zeroLimit 最小为零限制 限制最小可以自减到 0
     * @return int
     */
    public function htDecrField($key, $field, $step = 1, $zeroLimit = true)
    {
        // fix: not allow lt 0
        if ( $zeroLimit && ((int)$this->redis->hget($key, $field) <= 0) ) {
            return 0;
        }

        return $this->redis->hincrby($key, $field, -$step);
    }

    /**
     * 通过默认值来初始化一个 hash table
     * @param $key
     * @param array $fields
     * @param int $default
     * @return mixed
     */
    public function htInitByDefault($key, array $fields, $default = 0)
    {
        $data = array_fill_keys($fields, $default);

        return $this->redis->hmset($key, $data);
    }

    /**
     * 获取hash table指定的一些字段的信息
     * @param $key
     * @param array $fields
     * @param bool $valToInt 将所有的值转成int,在将hash table做为计数器时有用
     * @return array
     */
    public function htGetMulti($key, array $fields, $valToInt=false)
    {
        $data = $this->redis->hmget($key, $fields);

        if ( $data && class_exists('\\Predis\\Client') && is_subclass_of($this->redis,'\\Predis\\ClientInterface') ) {
            $data = array_combine($fields, $data);
        }

        if ($valToInt) {
            array_walk($data, function(&$val){
                $val = (int)$val;
            });
        }

        return $data;
    }

    /**
     * @param $key
     * @param bool $valToInt
     * @return array
     */
    public function htGetAll($key, $valToInt=false)
    {
        $data = $this->redis->hgetall($key);

        if ($valToInt) {
            array_walk($data, function(&$val){
                $val = (int)$val;
            });
        }

        return $data;
    }


    /**************************************************************************
     * cache
     *************************************************************************/

    /**
     * @var \Predis\Client
     */
    protected $cacheRedis;

    /**
     * @return \Predis\Client
     */
    public function cacheRedis()
    {
        return $this->cacheRedis;
    }

    public function setCacheRedis($redis)
    {
        $this->cacheRedis = $redis;
    }

    /**
     * cache redis 中 key 是否存在
     * @param string $key
     * @return bool
     */
    public function existsCacheKey($key)
    {
        return (bool)$this->cacheRedis()->exists($key);
    }

    /**
     * 添加缓存 - key 不存在时才会添加
     * @param $key
     * @param $seconds
     * @param string|array $value
     * @return mixed
     */
    public function addCache($key, $value, $seconds = 3600)
    {
        return $this->cacheRedis()->set($key, serialize($value), 'EX', $seconds, 'NX');
    }

    /**
     * 设置缓存 - key 存在会直接覆盖原来的值，不存在即是添加
     * @param $key
     * @param $seconds
     * @param string|array $value 要存储的数据 可以是字符串或者数组
     * @return mixed
     */
    public function setCache($key, $value, $seconds = 3600)
    {
        return $this->cacheRedis()->set($key, serialize($value), 'EX', $seconds);
    }

    /**
     * @param $key
     * @return string
     */
    public function getCache($key)
    {
        return unserialize($this->cacheRedis()->get($key));
    }

    /**
     * @param $key
     * @return int 成功删除的条数
     */
    public function delCache($key)
    {
        return $this->cacheRedis()->del($key);
    }
}
