<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date : 2014-9-1
 * Time: 下午10:50
 */

namespace inhere\library\utils;

/**
 * Class DbRecordSort
 * @package inhere\library\utils
 * 公共库 记录排序 RecordSort.php
 * 注意：默认记录展示情况是倒序排列(由上到下排序值减小)
 * use ：
 *     $id         = $request->getParam('id');
 *     $oldSortVal   = $request->getParam('rank');
 *     $sortType   = $request->getParam('sortType');
 *     $sort               = new RecordSort();
 *     $sort->tableName    = 'Faq';
 *     # 1.可在查询列表时 先存储首尾排序值 到 SESSION，实例化时会读取
 *     # 2. 也可在这里设置
 *       // $sort->firstRank     = Yii::app()->session['first_rank'];
 *       // $sort->lastRank      = Yii::app()->session['last_rank'];
 *      # 多传一个参数 $int_sort_request，标明请求 向上还是向下
 *      if ((int)$int_sort_request == 1) {
 *           $sort->moveUp($id,$oldSortVal);
 *      } else {
 *           $sort->moveDown($id,$oldSortVal);
 *      }
 *
 * 置顶置底 操作类似
 * moveTop($id,$oldSortVal,$table='')
 * 置底
 * moveBottom($id,$oldSortVal,$table='')
 *
 */
class DbRecordSort
{
    const MOVE_TOP = 1;
    const MOVE_UP = 2;
    const MOVE_DOWN = 3;
    const MOVE_BOTTOM = 4;

    public $addCondition = '';       # 额外的条件 @example记录有分类，分类记录单独排序
    public $tableName;                  # 操作表名
    public $tablePK = 'id';     # 主键列名
    public $sortField = 'rank';   # 排序字段
    public $preRecord = array();  # 前面一记录的信息
    public $sufRecord = array();  # 后面一记录的信息
    public $firstRank = '';       # 第一条记录 排序值

    // 最后一条记录 排序值
    public $lastRank = '';

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var string
     */
    private $error;

    public function __construct()
    {
        $this->firstRank = isset($_SESSION['first_rank']) ? $_SESSION['first_rank'] : $this->firstRank;
        $this->lastRank = isset($_SESSION['last_rank']) ? $_SESSION['last_rank'] : $this->lastRank;
    }

    /*
    1.array(
        'firstRank' => rankValue,
        'lastRank' => rankValue
     )
    2. ('firstRank','rankValue')
     */
    public function setValue($name, $value = '')
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                if (isset($this->$key)) {
                    $this->$key = $val;
                }
            }
        } else if (isset($this->$name)) {
            $this->$name = $value;
        }
    }

    /**
     * execute 执行排序，
     * @param int $id 当前记录 Id
     * @param int $oldSortVal 当前记录的排序值
     * @param int $sortType 排序请求
     * @return bool
     */
    public function execute($id, $oldSortVal, $sortType = self::MOVE_UP)
    {
        if (empty($this->tableName)) {
            $this->error = '必须传入或设置要操作的表名称(无需前缀)！';
            return false;
        }

        if ($this->firstRank === '' || $this->lastRank === '') {
            $this->error = '必须传入首尾记录的排序值！';
            return false;
        }

        $first_rank = $this->firstRank;
        $last_rank = $this->lastRank;
        $table = $this->tableName;
        $PK = $this->tablePK;
        $field = $this->sortField;
        $whereCondition = $this->addCondition;

        switch ((string)$sortType) {
            case self::MOVE_TOP:     # 置顶

                # 阻止第一条记录升序
                if ($first_rank === $oldSortVal) {
                    $this->error = '记录已在最顶部！';

                    return false;
                }
                $sql = "UPDATE $table SET {$field}=" . ((int)$this->firstRank + 1) . " WHERE {$PK}=" . $id;
                break;
            case self::MOVE_UP:      # 上移一位
                // 阻止第一条记录升序
                if ($first_rank === $oldSortVal) {
                    $this->error = '记录已在最顶部！';

                    return false;
                }

                # 获取前面一记录的信息
                $str_sql = "SELECT {$PK},{$field} FROM " . $table . " WHERE {$whereCondition} {$field}>'{$oldSortVal}' ORDER BY rank ASC LIMIT 1";
                $pre_record = $this->pdo->query($str_sql)->fetch(\PDO::FETCH_ASSOC);
                $pre_record_rank = $pre_record[$field];
                $pre_record_id = $pre_record[$PK];
                $sql = "UPDATE $table SET {$field}='{$pre_record_rank}' WHERE {$PK}='{$id}';
                        UPDATE $table SET {$field}='{$oldSortVal}' WHERE {$PK}='{$pre_record_id}'";
                break;
            case self::MOVE_DOWN:    # 下移一位
                // 阻止最后一条记录降序
                if ($last_rank === $oldSortVal) {
                    $this->error = '记录已在最底部！';
                    return false;
                }
                # 获取后面一记录的信息
                $str_sql = "SELECT {$PK},{$field} FROM " . $table . " WHERE {$whereCondition} {$field}<'{$oldSortVal}' ORDER BY rank DESC LIMIT 1";
                $suf_record = $this->pdo->query($str_sql)->fetch(\PDO::FETCH_ASSOC);
                $suf_record_rank = $suf_record[$field];
                $suf_record_id = $suf_record[$PK];
                $sql = "UPDATE {$table} SET {$field}='{$suf_record_rank}' WHERE {$PK}='{$id}';
                        UPDATE {$table} SET {$field}='{$oldSortVal}' WHERE {$PK}='{$suf_record_id}'";
                break;
            case self::MOVE_BOTTOM:  # 置底
                // 阻止最后一条记录降序
                if ($last_rank === $oldSortVal) {
                    $this->error = '记录已在最底部！';
                    return false;
                }
                $sql = "UPDATE $table SET {$field}=" . ((int)$this->lastRank - 1) . " WHERE {$PK}=" . $id;
                break;
            default:
                throw new \InvalidArgumentException('Invalid argument');
                break;
        }
        $affected = $this->pdo->exec($sql);

        if (false === $affected) {
            $this->error = '操作失败！请重试...';
        }

        return true;
    }

    public function addCondition($value)
    {
        $this->addCondition = $value . ' AND ';
        return true;
    }

    /**
     * 上移一位
     * @param int  $id       当前记录 Id
     * @param int  $oldSortVal     当前记录的排序值
     */
    public function moveUp($id, $oldSortVal)
    {
        $this->execute($id, $oldSortVal);
    }

    # 下移一位
    public function moveDown($id, $oldSortVal)
    {
        $this->execute($id, $oldSortVal, self::MOVE_DOWN);
    }

    # 置顶
    public function moveTop($id, $oldSortVal)
    {
        $this->execute($id, $oldSortVal, self::MOVE_TOP);
    }

    /**
     * 置底
     * @param $id
     * @param $oldSortVal
     */
    public function moveBottom($id, $oldSortVal)
    {
        $this->execute($id, $oldSortVal, self::MOVE_BOTTOM);
    }

    /**
     * @return mixed
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param mixed $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string
     */
    public function getFirstRank(): string
    {
        return $this->firstRank;
    }

    /**
     * @param string $firstRank
     */
    public function setFirstRank(string $firstRank)
    {
        $this->firstRank = $firstRank;
    }

    /**
     * @return string
     */
    public function getLastRank(): string
    {
        return $this->lastRank;
    }

    /**
     * @param string $lastRank
     */
    public function setLastRank(string $lastRank)
    {
        $this->lastRank = $lastRank;
    }

    /**
     * @return \PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * @param \PDO $pdo
     */
    public function setPdo(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
}
