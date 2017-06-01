<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/1
 * Time: 下午9:57
 */

namespace inhere\library\lock;

/**
 * Class DatabaseLock
 * @package inhere\library\lock
 */
class DatabaseLock extends BaseDriver
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * {@inheritDoc}
     */
    public function __construct(array $options = [])
    {
        if (isset($options['db'])) {
            $this->setDb($options['db']);
            unset($options['db']);
        }

        parent::__construct($options);
    }

    /**
     * {@inheritDoc}
     */
    public function lock($key, $timeout = self::EXPIRE)
    {
        // 使用给定的字符串得到一个锁,超时为timeout 秒。
        // 若成功得到锁，则返回 1，若操作超时则返回0 (例如,由于另一个客户端已提前封锁了这个名字 ),若发生错误则返回NULL
        $sql = sprintf("SELECT GET_LOCK('%s', '%d') AS lock", $key, $timeout);
        $stm = $this->db->query($sql);
        $row = $stm->fetch(\PDO::FETCH_ASSOC);

        return $row ? $row['lock'] : false;
    }

    /**
     * {@inheritDoc}
     */
    public function unlock($key)
    {
        // RELEASE_LOCK(str) 这个函数的作用是释放名为str的共享锁。
        // 如果锁被成功释放，返回1；如果这个进程没有占有该锁，则返回0；如果这个名为str的锁不存在，则返回NULL。
        $sql = sprintf("SELECT RELEASE_LOCK('%s') AS unlock", $key);
        $stm = $this->db->query($sql);
        $row = $stm->fetch(\PDO::FETCH_ASSOC);

        return $row ? $row['unlock'] : false;
    }

    /**
     * @return \PDO
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @param \PDO $db
     */
    public function setDb(\PDO $db)
    {
        $this->db = $db;
    }
}
