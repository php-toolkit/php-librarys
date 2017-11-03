<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/18
 * Time: 下午9:39
 */

namespace Inhere\Library\Components;

use Inhere\Exceptions\UnknownMethodException;
use Inhere\Library\Helpers\DsnHelper;
use Inhere\Library\Traits\LiteConfigTrait;
use Inhere\Library\Traits\LiteEventTrait;
use PDO;
use PDOStatement;

/**
 * Class DatabaseClient
 * @package Inhere\Library\Components
 */
class DatabaseClient
{
    use LiteEventTrait, LiteConfigTrait;

    //
    const CONNECT = 'connect';
    const DISCONNECT = 'disconnect';

    // will provide ($sql, $type, $data)
    // $sql - executed SQL
    // $type - operate type.  e.g 'insert'
    // $data - data
    const BEFORE_EXECUTE = 'beforeExecute';
    const AFTER_EXECUTE = 'afterExecute';

    /** @var PDO */
    protected $pdo;

    /** @var bool */
    protected $debug = false;

    /** @var string */
    protected $databaseName;

    /** @var string */
    protected $tablePrefix;

    /** @var string */
    protected $prefixPlaceholder = '{@pfx}';

    /** @var string */
    protected $quoteNamePrefix = '"';

    /** @var string */
    protected $quoteNameSuffix = '"';

    /** @var string */
    protected $quoteNameEscapeChar = '"';

    /** @var string */
    protected $quoteNameEscapeReplace = '""';

    /**
     * All of the queries run against the connection.
     * @var array
     * [
     *  [time, category, message, context],
     *  ... ...
     * ]
     */
    protected $queryLog = [];

    /**
     * database config
     * @var array
     */
    protected $config = [
        'driver' => 'mysql', // 'sqlite'
        // 'dsn' => 'mysql:host=localhost;port=3306;dbname=test;charset=UTF8',
        'host' => 'localhost',
        'port' => '3306',
        'user' => 'root',
        'password' => '',
        'database' => 'test',
        'charset' => 'utf8',

        'timeout' => 0,
        'timezone' => null,
        'collation' => 'utf8_unicode_ci',

        'options' => [],

        'tablePrefix' => '',

        'debug' => false,
        // retry times.
        'retry' => 0,
    ];

    /**
     * The default PDO connection options.
     * @var array
     */
    protected static $pdoOptions = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"',
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * @param array $config
     * @return static
     */
    public static function make(array $config = [])
    {
        return new static($config);
    }

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (!class_exists(\PDO::class, false)) {
            throw new \RuntimeException("The php extension 'redis' is required.");
        }

        $this->setConfig($config);

        // init something...
        $this->debug = (bool)$this->config['debug'];
        $this->tablePrefix = $this->config['tablePrefix'];
        $this->databaseName = $this->config['database'];

        $retry = (int) $this->config['retry'];
        $this->config['retry'] = ($retry > 0 && $retry <= 5) ? $retry : 0;
        $this->config['options'] = static::$pdoOptions + $this->config['options'];

        if (!self::isSupported($this->config['driver'])) {
            throw new \RuntimeException("The system is not support driver: {$this->config['driver']}");
        }

        $this->initQuoteNameChar($this->config['driver']);
    }

    /**
     * @return static
     */
    public function connect()
    {
        if ($this->pdo) {
            return $this;
        }

        $config = $this->config;
        $retry = (int) $config['retry'];
        $retry = ($retry > 0 && $retry <= 5) ? $retry : 0;
        $dsn = DsnHelper::getDsn($config);

        do {
            try {
                $this->pdo = new PDO($dsn, $config['user'], $config['password'], $config['options']);
                break;
            } catch (\PDOException $e) {
                if ($retry <= 0) {
                    throw new \PDOException('Could not connect to DB: ' . $e->getMessage() . '. DSN: ' . $dsn);
                }
            }

            $retry--;
            usleep(50000);
        } while ($retry >= 0);

        $this->log('connect to DB server', ['config' => $config], 'connect');
        $this->fire(self::CONNECT, [$this]);

        return $this;
    }

    public function reconnect()
    {
        $this->pdo = null;
        $this->connect();
    }

    /**
     * disconnect
     */
    public function disconnect()
    {
        $this->fire(self::DISCONNECT, [$this]);
        $this->pdo = null;
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * @param $name
     * @param array $arguments
     * @return mixed
     * @throws UnknownMethodException
     */
    public function __call($name, array $arguments)
    {
        $this->connect();

        if (!method_exists($this->pdo, $name)) {
            $class = get_class($this);
            throw new UnknownMethodException("Class '{$class}' does not have a method '{$name}'");
        }

        return $this->pdo->$name(...$arguments);
    }

    /**************************************************************************
     * extra methods
     *************************************************************************/

    /**
     * @var array
     */
    protected static $queryNodes = [
        // string: 'id, name'; array: ['id', 'name']
        'select',
        'from',
        // string: full join clause; array: [$table, $condition, $type = 'LEFT']
        'join',

        'where',

        'having', // [$conditions, $glue = 'AND']
        'group', // 'id, name' || ['id', 'name']
        'order', // 'created ASC' || ['created', 'publish', 'DESC'] ['created ASC', 'publish DESC']
        'limit', // 10 OR [2, 10]
    ];

    /**
     * @var array
     */
    protected static $queryOptions = [
        /* data index column. */
        'indexKey' => null,

        /*
        data load type, in :
        'a className' -- return object, instanceof the class`
        'array'       -- return array, only  [ 'value' ]
        'assoc'       -- return array, Contain  [ 'column' => 'value']
         */
        'fetchType' => 'assoc',
    ];

    /**
     * Run a select statement, fetch one
     * @param  string $from
     * @param  array|string|int $wheres
     * @param  string|array $select
     * @param  array $options
     * @return array
     */
    public function find(string $from, $wheres = 1, $select = '*', array $options = [])
    {
        $options['select'] = $this->qns($select);
        $options['from'] = $this->qn($from);

        list($where, $bindings) = $this->handleWheres($wheres);

        $options['where'] = $where;
        $options['limit'] = 1;

        $statement = $this->compileSelect($options);

        return $this->fetchOne($statement, $bindings ?: []);
    }

    /**
     * Run a select statement, fetch all
     * @param  string $from
     * @param  array|string|int $wheres
     * @param  string|array $select
     * @param  array $options
     * @return array
     */
    public function findAll(string $from, $wheres = 1, $select = '*', array $options = [])
    {
        $options['select'] = $this->qns($select);
        $options['from'] = $this->qn($from);

        list($where, $bindings) = $this->handleWheres($wheres);

        $options['where'] = $where;

        if (!isset($options['limit'])) {
            $options['limit'] = 1000;
        }

        $statement = $this->compileSelect($options);

        return $this->fetchAll($statement, $bindings ?: []);
    }

    /**
     * Run a insert statement
     * @param  string $statement
     * @param  array $bindings
     * @param null|string $sequence For special driver, like PgSQL
     * @return int
     */
    public function insert($statement, array $bindings = [], $sequence = null)
    {
        $this->fetchAffected($statement, $bindings);

        return $this->lastInsertId($sequence);
    }

    /**
     * Run a update statement
     * @param  string $statement
     * @param  array $bindings
     * @return int
     */
    public function update($statement, array $bindings = [])
    {
        return $this->fetchAffected($statement, $bindings);
    }

    /**
     * Run a delete statement
     * @param  string $statement
     * @param  array $bindings
     * @return int
     */
    public function delete($statement, array $bindings = [])
    {
        return $this->fetchAffected($statement, $bindings);
    }

    /**
     * count
     * ```
     * $db->count();
     * ```
     * @param  string $table
     * @param  array|string $wheres
     * @return int
     */
    public function count(string $table, $wheres)
    {
        list($where, $bindings) = $this->handleWheres($wheres);
        $sql = "SELECT COUNT(*) AS total FROM {$table} WHERE {$where}";

        $result = $this->fetchObject($sql, $bindings);

        return $result ? (int)$result->total : 0;
    }

    /**
     * exists
     * ```
     * $db->exists();
     * // SQL: select exists(select * from `table` where (`phone` = 152xxx)) as `exists`;
     * ```
     * @param $statement
     * @param array $bindings
     * @return int
     */
    public function exists($statement, array $bindings = [])
    {
        $sql = sprintf('SELECT EXISTS(%s) AS `exists`', $statement);

        $result = $this->fetchObject($sql, $bindings);

        return $result ? $result->exists : 0;
    }

    /********************************************************************************
     * fetch data methods
     *******************************************************************************/

    /**
     * @param string $statement
     * @param array $bindings
     * @return int
     */
    public function fetchAffected($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);
        $affected = $sth->rowCount();

        $this->freeResource($sth);

        return $affected;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        $result = $sth->fetchAll(PDO::FETCH_ASSOC);

        $this->freeResource($sth);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAssoc($statement, array $bindings = [])
    {
        $data = [];
        $sth = $this->execute($statement, $bindings);

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $data[current($row)] = $row;
        }

        $this->freeResource($sth);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        $column = $sth->fetchAll(PDO::FETCH_COLUMN, 0);

        $this->freeResource($sth);

        return $column;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchGroup($statement, array $bindings = [], $style = PDO::FETCH_COLUMN)
    {
        $sth = $this->execute($statement, $bindings);

        $group = $sth->fetchAll(PDO::FETCH_GROUP | $style);
        $this->freeResource($sth);

        return $group;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchObject($statement, array $bindings = [], $class = 'stdClass', array $args = [])
    {
        $sth = $this->execute($statement, $bindings);

        if (!empty($args)) {
            $result = $sth->fetchObject($class, $args);
        } else {
            $result = $sth->fetchObject($class);
        }

        $this->freeResource($sth);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchObjects($statement, array $bindings = [], $class = 'stdClass', array $args = [])
    {
        $sth = $this->execute($statement, $bindings);

        if (!empty($args)) {
            $result = $sth->fetchAll(PDO::FETCH_CLASS, $class, $args);
        } else {
            $result = $sth->fetchAll(PDO::FETCH_CLASS, $class);
        }

        $this->freeResource($sth);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        $result = $sth->fetch(PDO::FETCH_ASSOC);

        $this->freeResource($sth);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchPairs($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        $result = $sth->fetchAll(PDO::FETCH_KEY_PAIR);

        $this->freeResource($sth);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchValue($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        $result = $sth->fetchColumn();

        $this->freeResource($sth);

        return $result;
    }

    /********************************************************************************
     * Generator methods
     *******************************************************************************/

    /**
     * @param string $statement
     * @param array $bindings
     * @param int $fetchType
     * @return \Generator
     */
    public function cursor($statement, array $bindings = [], $fetchType = PDO::FETCH_ASSOC)
    {
        $sth = $this->execute($statement, $bindings);

        while ($row = $sth->fetch($fetchType)) {
            $key = current($row);
            yield $key => $row;
        }

        $this->freeResource($sth);
    }

    /**
     * @param string $statement
     * @param array $bindings
     * @return \Generator
     */
    public function yieldAssoc($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $key = current($row);
            yield $key => $row;
        }

        $this->freeResource($sth);
    }

    /**
     * @param string $statement
     * @param array $bindings
     * @return \Generator
     */
    public function yieldAll($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }

        $this->freeResource($sth);
    }

    /**
     * @param string $statement
     * @param array $bindings
     * @return \Generator
     */
    public function yieldColumn($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        while ($row = $sth->fetch(PDO::FETCH_NUM)) {
            yield $row[0];
        }

        $this->freeResource($sth);
    }

    /**
     * @param string $statement
     * @param array $bindings
     * @param string $class
     * @param array $args
     * @return \Generator
     */
    public function yieldObjects($statement, array $bindings = [], $class = 'stdClass', array $args = [])
    {
        $sth = $this->execute($statement, $bindings);

        while ($row = $sth->fetchObject($class, $args)) {
            yield $row;
        }

        $this->freeResource($sth);
    }

    /**
     * @param string $statement
     * @param array $bindings
     * @return \Generator
     */
    public function yieldPairs($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        while ($row = $sth->fetch(PDO::FETCH_KEY_PAIR)) {
            yield $row;
        }

        $this->freeResource($sth);
    }

    /********************************************************************************
     * extended methods
     *******************************************************************************/

    /**
     * @param string $statement
     * @param array $params
     * @return PDOStatement
     */
    public function execute($statement, array $params = [])
    {
        // trigger before event
        $this->fire(self::BEFORE_EXECUTE, [$statement, $params, 'execute']);

        $sth = $this->prepareWithBindings($statement, $params);

        $sth->execute();

        // trigger after event
        $this->fire(self::AFTER_EXECUTE, [$statement, 'execute']);

        return $sth;
    }

    /**
     * @param string $statement
     * @param array $params
     * @return PDOStatement
     */
    public function prepareWithBindings($statement, array $params = [])
    {
        $this->connect();

        // if there are no values to bind ...
        if (!$params) {
            // ... use the normal preparation
            return $this->prepare($statement);
        }

        // prepare the statement
        $sth = $this->pdo->prepare($statement);

        $this->log($statement, $params);

        // for the placeholders we found, bind the corresponding data values
        $this->bindValues($sth, $params);

        // done
        return $sth;
    }

    /**
     * 事务
     * {@inheritDoc}
     */
    public function transactional(callable $func)
    {
        if (!is_callable($func)) {
            throw new \InvalidArgumentException('Expected argument of type "callable", got "' . gettype($func) . '"');
        }

        $this->connect();
        $this->pdo->beginTransaction();

        try {
            $return = $func($this);
            $this->pdo->commit();

            return $return ?: true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param PDOStatement $sth
     * @param array|\ArrayIterator $bindings
     */
    public function bindValues(PDOStatement $sth, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $sth->bindValue(
                is_string($key) ? $key : $key + 1, $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }

    /**
     * @param PDOStatement $sth
     * @param $key
     * @param $val
     * @return bool
     */
    protected function bindValue(PDOStatement $sth, $key, $val)
    {
        if (is_int($val)) {
            return $sth->bindValue($key, $val, PDO::PARAM_INT);
        }

        if (is_bool($val)) {
            return $sth->bindValue($key, $val, PDO::PARAM_BOOL);
        }

        if (null === $val) {
            return $sth->bindValue($key, $val, PDO::PARAM_NULL);
        }

        if (!is_scalar($val)) {
            $type = gettype($val);
            throw new \RuntimeException("Cannot bind value of type '{$type}' to placeholder '{$key}'");
        }

        return $sth->bindValue($key, $val);
    }

    /**************************************************************************
     * helper method
     *************************************************************************/

    /**
     * Check whether the connection is available
     * @return bool
     */
    public function ping()
    {
        try {
            $this->pdo->query('select 1')->fetchColumn();
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'server has gone away') !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * handle where condition
     * @param array|string|\Closure $wheres
     * @example
     * ```
     * ...
     * $result = $db->findAll('user', [
     *      'userId' => 23,      // ==> 'AND `userId` = 23'
     *      'title' => 'test',  // value will auto add quote, equal to "AND title = 'test'"
     * 
     *      ['publishTime', '>', '0'],  // ==> 'AND `publishTime` > 0'
     *      ['createdAt', '<=', 1345665427, 'OR'],  // ==> 'OR `createdAt` <= 1345665427'
     *      ['id', 'IN' ,[4,5,56]],   // ==> '`id` IN ('4','5','56')'
     *      ['id', 'NOT IN', [4,5,56]], // ==> '`id` NOT IN ('4','5','56')'
     *      // a closure
     *      function () {
     *          return 'a < 5 OR b > 6';
     *      }
     * ]);
     * ```
     * @return array
     */
    public function handleWheres($wheres)
    {
        if (is_object($wheres) && $wheres instanceof \Closure) {
            $wheres = $wheres($this);
        }

        if (!$wheres || $wheres === 1) {
            return [1, null];
        }

        if (is_string($wheres)) {
            return [$wheres, null];
        }

        $nodes = $bindings = [];

        if (is_array($wheres)) {
            foreach ($wheres as $key => $val) {
                if (is_object($val) && $val instanceof \Closure) {
                    $val = $val($this);
                }

                $key = trim($key);

                // string key: $key is column name, $val is column value
                if ($key && !is_numeric($key)) {
                    $nodes[] = 'AND ' . $this->qn($key) . '= ?';
                    $bindings[] = $val;

                    // array: [column, operator(e.g '=', '>=', 'IN'), value, option(Is optional, e.g 'AND', 'OR')]
                } elseif (is_array($val)) {
                    if (!isset($val[2])) {
                        throw new \RuntimeException('Where condition data is incomplete, at least 3 elements');
                    }

                    $bool = $val[3] ?? 'AND';
                    $nodes[] = strtoupper($bool) . ' ' . $this->qn($val[0]) . " {$val[1]} ?";
                    $bindings[] = $val[2];
                } else {
                    $val = trim((string)$val);
                    $nodes[] = preg_match('/^and |or /i', $val) ? $val : 'AND ' . $val;
                }
            }
        }

        $where = implode(' ', $nodes);
        unset($nodes);

        return [$this->removeLeadingBoolean($where), $bindings];
    }

    /**
     * @param array $opts
     * @return string
     */
    public function compileSelect(array $opts)
    {
        $nodes = [];

        foreach (self::$queryNodes as $node) {
            if (!isset($opts[$node])) {
                continue;
            }

            $val = $opts[$node];
            if ($isString = is_string($val)) {
                $val = trim($val);
            }

            if ($node === 'join') {
                //string: full join structure. e.g 'left join TABLE t2 on t1.id = t2.id'
                if ($isString) {
                    $nodes[] = stripos($val, 'join') !== false ? $val : 'LEFT JOIN ' . $val;

                    // array: ['TABLE t2', 't1.id = t2.id', 'left']
                } elseif (is_array($val)) {
                    $nodes[] = ($val[2] ?? 'LEFT') . " JOIN {$val[0]} ON {$val[1]}";
                }

                continue;
            }

            if ($node === 'having') {
                // string: 'having AND col = val'
                if ($isString) {
                    $nodes[] = stripos($val, 'having') !== false ? $val: 'HAVING ' . $val;

                    // array: ['t1.id = t2.id', 'AND']
                } elseif (is_array($val)) {
                    $nodes[] = 'HAVING ' . ($val[1] ?? 'AND') . " {$val[0]}";
                }

                continue;
            }

            if ($node === 'group') {
                $nodes[] = 'GROUP BY ' . $this->qns($val);
                continue;
            }

            if ($node === 'order') {
                $nodes[] = 'ORDER BY ' . ($isString ? $val : implode(' ', $val));
                continue;
            }

            $nodes[] = strtoupper($node) . ' ' . ($isString ? $val : implode(',', (array)$val));
        }

        return implode(' ', $nodes);
    }

    /**
     * @param array|string $names
     * @return string
     */
    public function qns($names)
    {
        if (is_string($names)) {
            $names = trim($names, ', ');
            $names = strpos($names, ',') ? explode(',', $names) : [$names];
        }

        $names = array_map(function ($field) {
            return $this->quoteName($field);
        }, $names);

        return implode(',', $names);
    }

    /**
     * {@inheritdoc}
     */
    public function qn(string $name)
    {
        return $this->quoteName($name);
    }

    /**
     * @param string $name
     * @return string
     */
    public function quoteName(string $name)
    {
        // field || field as f
        if (strpos($name, '.') === false) {
            return $this->quoteSingleName($name);
        }

        // t1.field || t1.field as f
        return implode('.', array_map([$this, 'quoteSingleName'], explode('.', $name)));
    }

    /**
     * @param string $name
     * @return string
     */
    public function quoteSingleName(string $name)
    {
        if ($name === '*') {
            return $name;
        }

        if (stripos($name, ' as ') === false) {
            if (strpos($name, $this->quoteNamePrefix) !== false) {
                $name = str_replace($this->quoteNameEscapeChar, $this->quoteNameEscapeReplace, $name);
            }

            return $this->quoteNamePrefix . $name . $this->quoteNameSuffix;
        }

        // field as f
        $name = str_ireplace(' as ', '#', $name);

        return implode(' AS ', array_map([$this, 'quoteSingleName'], explode('#', $name)));
    }

    /**
     * {@inheritdoc}
     */
    protected function initQuoteNameChar($driver)
    {
        switch ($driver) {
            case 'mysql':
                $this->quoteNamePrefix = '`';
                $this->quoteNameSuffix = '`';
                $this->quoteNameEscapeChar = '`';
                $this->quoteNameEscapeReplace = '``';

                return;
            case 'sqlsrv':
                $this->quoteNamePrefix = '[';
                $this->quoteNameSuffix = ']';
                $this->quoteNameEscapeChar = ']';
                $this->quoteNameEscapeReplace = '][';

                return;
            default:
                $this->quoteNamePrefix = '"';
                $this->quoteNameSuffix = '"';
                $this->quoteNameEscapeChar = '"';
                $this->quoteNameEscapeReplace = '""';

                return;
        }
    }

    public function q($value, $type = PDO::PARAM_STR)
    {
        return $this->quote($value, $type);
    }

    /**
     * @param string|array $value
     * @param int $type
     * @return string
     */
    public function quote($value, $type = PDO::PARAM_STR)
    {
        $this->connect();

        // non-array quoting
        if (!is_array($value)) {
            return $this->pdo->quote($value, $type);
        }

        // quote array values, not keys, then combine with commas
        /** @var array $value */
        foreach ((array) $value as $k => $v) {
            $value[$k] = $this->pdo->quote($v, $type);
        }

        return implode(', ', $value);
    }

    /********************************************************************************
     * Pdo methods
     *******************************************************************************/

    /**
     * @param string $statement
     * @return int
     */
    public function exec($statement)
    {
        $this->connect();

        // trigger before event
        $this->fire(self::BEFORE_EXECUTE, [$statement, 'exec']);

        $affected = $this->pdo->exec($statement);

        // trigger after event
        $this->fire(self::AFTER_EXECUTE, [$statement, 'exec']);

        return $affected;
    }

    /**
     * {@inheritDoc}
     * @return PDOStatement
     */
    public function query($statement, ...$fetch)
    {
        $this->connect();

        // trigger before event
        $this->fire(self::BEFORE_EXECUTE, [$statement, 'query']);

        $sth = $this->pdo->query($statement, ...$fetch);

        // trigger after event
        $this->fire(self::AFTER_EXECUTE, [$statement, 'query']);

        return $sth;
    }

    /**
     * @param string $statement
     * @param array $options
     * @return PDOStatement
     */
    public function prepare($statement, array $options = [])
    {
        $this->connect();
        $this->log($statement, $options);

        return $this->pdo->prepare($statement, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        $this->connect();

        return $this->pdo->beginTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function inTransaction()
    {
        $this->connect();

        return $this->pdo->inTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        $this->connect();

        return $this->pdo->rollBack();
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        $this->connect();

        return $this->pdo->rollBack();
    }

    /**
     * {@inheritDoc}
     */
    public function errorCode()
    {
        $this->connect();

        return $this->pdo->errorCode();
    }

    /**
     * {@inheritDoc}
     */
    public function errorInfo()
    {
        $this->connect();

        return $this->pdo->errorInfo();
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($name = null)
    {
        $this->connect();

        return $this->pdo->lastInsertId($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute($attribute)
    {
        $this->connect();

        return $this->pdo->getAttribute($attribute);
    }

    /**
     * {@inheritDoc}
     */
    public function setAttribute($attribute, $value)
    {
        $this->connect();

        return $this->pdo->setAttribute($attribute, $value);
    }

    /**
     * {@inheritDoc}
     */
    public static function getAvailableDrivers()
    {
        return PDO::getAvailableDrivers();
    }

    /**
     * Is this driver supported.
     * @param string $driver
     * @return bool
     */
    public static function isSupported(string $driver)
    {
        return in_array($driver, \PDO::getAvailableDrivers(), true);
    }

    /**
     * @param PDOStatement $sth
     * @return $this
     */
    public function freeResource($sth = null)
    {
        if ($sth && $sth instanceof PDOStatement) {
            $sth->closeCursor();
        }

        return $this;
    }

    /**************************************************************************
     * getter/setter methods
     *************************************************************************/

    /**
     * Get the name of the driver.
     * @return string
     */
    public function getDriverName()
    {
        return $this->config['driver'];
    }

    /**
     * Get the name of the connected database.
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    /**
     * Set the name of the connected database.
     * @param  string $database
     */
    public function setDatabaseName($database)
    {
        $this->databaseName = $database;
    }

    /**
     * Get the table prefix for the connection.
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Set the table prefix in use by the connection.
     * @param  string $prefix
     * @return void
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;
    }

    /**
     * @param $sql
     * @return mixed
     */
    public function replaceTablePrefix($sql)
    {
        return str_replace($this->prefixPlaceholder, $this->tablePrefix, (string)$sql);
    }

    /**
     * Remove the leading boolean from a statement.
     * @param  string $value
     * @return string
     */
    protected function removeLeadingBoolean($value)
    {
        return preg_replace('/^and |or /i', '', $value, 1);
    }

    /**
     * @param string $message
     * @param array $context
     * @param string $category
     */
    public function log(string $message, array $context = [], $category = 'query')
    {
        if ($this->debug) {
            $this->queryLog[] = [microtime(1), 'db.' . $category, $message, $context];
        }
    }

    /**
     * @return array
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * @return PDO
     */
    public function getPdo()
    {
        if ($this->pdo instanceof \Closure) {
            return $this->pdo = ($this->pdo)($this);
        }

        return $this->pdo;
    }

    /**
     * @param PDO $pdo
     */
    public function setPdo(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return (bool) $this->pdo;
    }

}
