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
    private $tableName = 'msg_queue';

    /**
     * DbQueue constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $name => $value) {
            $this->$name = $value;
        }

        $this->id = $config['id'] ?? 'db';
    }

    /**
     * {@inheritDoc}
     */
    protected function doPop()
    {
        $st = $this->db->query(sprintf(
            "SELECT `id`,`data` FROM %s WHERE queue = %s ORDER BY `priority` DESC, `id` ASC LIMIT 1",
            $this->tableName,
            $this->id
        ));

        if ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
            $row['data'] = unserialize($row['data']);
        }

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    protected function doPush($data, $priority = self::PRIORITY_NORM)
    {
        return $this->db->exec(sprintf(
            "INSERT INTO %s (`queue`, `data`, `priority`, `created_at`) VALUES (%s, %s, %d, %d)",
            $this->tableName,
            $this->id,
            serialize($data),
            $priority,
            time()
        ));
    }

    /**
     *
     * ```php
     * $dqe->createTable($dqe->createMysqlTableSql());
     * ```
     * @param string $sql
     * @return int
     */
    public function createTable($sql)
    {
        return $this->db->exec($sql);
    }

    /**
     * @return string
     */
    public function createMysqlTableSql()
    {
        $tName = $this->tableName;
        return <<<EOF
CREATE TABLE IF NOT EXISTS `$tName` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`queue` CHAR(48) NOT NULL COMMENT 'queue name', 
	`data` TEXT NOT NULL COMMENT 'task data',
	`priority` TINYINT(2) UNSIGNED NOT NULL DEFAULT 1,
	`created_at` INT(10) UNSIGNED NOT NULL,
	`started_at` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	`finished_at` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	KEY (`queue`, `created_at`),
	PRIMARY KEY (`iId`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
EOF;
    }

    /**
     * @return int
     */
    public function createSqliteTableSql()
    {
        $tName = $this->tableName;
        return <<<EOF
CREATE TABLE IF NOT EXISTS `$tName` (
	`id` INTEGER PRIMARY KEY NOT NULL,
	`queue` CHAR(48) NOT NULL COMMENT 'queue name', 
	`data` TEXT NOT NULL COMMENT 'task data',
	`priority` INTEGER(2) NOT NULL DEFAULT 1,
	`created_at` INTEGER(10) NOT NULL,
	`started_at` INTEGER(10) NOT NULL DEFAULT 0,
	`finished_at` INTEGER(10) NOT NULL DEFAULT 0
);
CREATE INDEX idxQueue on $tName(queue);
CREATE INDEX idxCreatedAt on $tName(created_at);
EOF;
    }
}
