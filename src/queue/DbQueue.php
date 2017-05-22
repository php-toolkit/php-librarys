<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/21
 * Time: 上午1:45
 */

namespace inhere\library\queue;

/**
 * Class DbQueue
 * @package inhere\library\queue
 */
class DbQueue extends BaseQueue
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var string
     */
    private $tableName = 'my_queue';

    /**
     * {@inheritDoc}
     */
    public function pop()
    {
//        return $this->db->pop();
    }

    /**
     * push data
     * @param mixed $data
     * @return bool
     */
    public function push($data, $priority = self::PRIORITY_NORM)
    {
        // TODO: Implement push() method.
    }

    /**
     * @return int
     */
    public function createTable()
    {
        $tName = $this->tableName;
        $sql = <<<EOF
CREATE TABLE `$tName` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`channel` VARCHAR(48) NOT NULL,
	`data` TEXT NOT NULL,
	`created_at` INT(10) NOT NULL,
	`started_at` INT(10) NOT NULL DEFAULT 0,
	`finished_at` INT(10) NOT NULL DEFAULT 0,
	KEY (`channel`, `started_at`),
	PRIMARY KEY (`iId`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
EOF;
        return $this->db->exec($sql);
    }
}
