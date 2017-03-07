<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/3/14
 * Time: 19:44
 */

namespace inhere\librarys\collections;

use RuntimeException;
use inhere\exceptions\DataParseException;

/**
 * Class DataCollector - 数据收集器 (数据存储器 - DataStorage)
 * @package inhere\librarys\collections
 *
 * 支持 链式的子节点 设置 和 值获取
 * e.g:
 * ```
 * $data = [
 *      'foo' => [
 *          'bar' => [
 *              'yoo' => 'value'
 *          ]
 *       ]
 * ];
 * $config = new DataCollector();
 * $config->get('foo.bar.yoo')` equals to $data['foo']['bar']['yoo'];
 *
 * ```
 *
 * 简单的数据对象可使用  @see SimpleCollection
 * ```
 * $config = new SimpleCollection($data)
 * $config->get('foo');
 * ```
 */
class DataCollector extends SimpleCollection
{
    /**
     * @var array
     */
//    protected $files = [];

    /**
     * Property separator.
     * @var  string
     */
    protected $separator = '.';

    /**
     * name
     * @var string
     */
    protected $name;

    /**
     * formats
     * @var array
     */
    protected static $formats = ['json', 'php', 'ini', 'yml'];

    const FORMAT_JSON = 'json';
    const FORMAT_PHP = 'php';
    const FORMAT_INI = 'ini';
    const FORMAT_YML = 'yml';

    /**
     * __construct
     * @param mixed $data
     * @param string $format
     * @param string $name
     */
    public function __construct($data = [], $format = 'php', $name = 'box1')
    {
        // Optionally load supplied data.
        $this->load($data, $format);

        parent::__construct();

        $this->name = $name;
    }

    /**
     * @param mixed $data
     * @param string $format
     * @param string $name
     * @return static
     */
    public static function make($data = [], $format = 'php', $name = 'box1')
    {
        return new static($data, $format, $name);
    }

    /**
     * set config value by path
     * @param string $path
     * @param mixed $value
     * @return mixed
     */
    public function set($path, $value)
    {
        if (is_array($value) || is_object($value)) {
            $value = static::dataToArray($value, true);
        }

        static::setByPath($this->data, $path, $value, $this->separator);

        return $this;
    }

    /**
     * get value by path
     * @param string $path
     * @param string $default
     * @return mixed
     */
    public function get($path, $default = null)
    {
        $result = static::getByPath($this->data, $path, $this->separator);

        return $result !== null ? $result : $default;
    }

    public function exists($path)
    {
        return $this->get($path) !== null;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function has($path)
    {
        return $this->exists($path);
    }

    public function reset()
    {
        $this->data = [];

        return $this;
    }

    /**
     * Clear all data.
     * @return  static
     */
    public function clear()
    {
        return $this->reset();
    }

    /**
     * @param $class
     * @return mixed
     */
    public function toObject($class = \stdClass::class)
    {
        return static::dataToObject($this->data, $class);
    }

    /**
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     * @param string $separator
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;
    }

    /**
     * @return array
     */
    public static function getFormats()
    {
        return static::$formats;
    }

    /**
     * setName
     * @param $value
     * @return void
     */
    public function setName($value)
    {
        $this->name = $value;
    }

    /**
     * getName
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * load
     * @param string|array $data
     * @param string $format = 'php'
     * @return static
     * @throws \RangeException
     */
    public function load( $data, $format = 'php')
    {
        if ( is_string($data) && in_array($format, static::$formats) ) {
            switch ( $format ) {
                case static::FORMAT_YML:
                    $this->loadYaml($data);
                    break;

                case static::FORMAT_JSON:
                    $this->loadJson($data);
                    break;

                case static::FORMAT_INI:
                    $this->loadIni($data);
                    break;

                case static::FORMAT_PHP:
                default:
                    $this->loadArray($data);
                    break;
            }

        } else if ( is_array($data) || is_object($data) ) {
            $this->bindData($this->data, $data);
        } else {
            throw new \RangeException('params error!!');
        }

        return $this;
    }

    /**
     * load data form yml file
     * @param $data
     * @throws RuntimeException
     * @return static
     */
    public function loadYaml($data)
    {
        $array  = static::parseYaml(trim($data));

        return $this->bindData($this->data, $array);
    }

    /**
     * load data form php file or array
     * @param array|string $data
     * @return static
     */
    public function loadArray($data)
    {
        if ( is_string($data) && is_file($data) ) {
            $data = require $data;
        }

        if ( !is_array($data) ) {
            throw new \InvalidArgumentException('param type error! must is array.');
        }

        return $this->bindData($this->data, $data);
    }

    /**
     * load data form php file or array
     * @param array|string $data
     * @return static
     */
    public function loadObject($data)
    {
        if ( !is_object($data) ) {
            throw new \InvalidArgumentException('param type error! must is object.');
        }

        return $this->bindData($this->data, $data);
    }

    /**
     * load data form ini file
     * @param $data
     * @return static
     */
    public function loadIni($data)
    {
        if ( !is_string($data) ) {
            throw new \InvalidArgumentException('param type error! must is string.');
        }

        if ( file_exists($data) ) {
            $data = file_get_contents($data);
        }

        $data = parse_ini_string($data);

        return $this->bindData($this->data, $data);
    }

    /**
     * load data form json file
     * @param $data
     * @return DataCollector
     * @throws RuntimeException
     */
    public function loadJson($data)
    {
         return $this->bindData($this->data, static::parseJson($data));
    }

    /**
     * @param $parent
     * @param $data
     * @param bool|false $raw
     * @return $this
     */
    protected function bindData(&$parent, $data, $raw = false)
    {
        // Ensure the input data is an array.
        if (!$raw) {
            $data = static::dataToArray($data, true);
        }

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                if (!isset($parent[$key])) {
                    $parent[$key] = array();
                }

                $this->bindData($parent[$key], $value);
            } else {
                $parent[$key] = $value;
            }
        }

        return $this;
    }

    public function getKeys()
    {
        return array_keys($this->data);
    }

    /**
     * @return \RecursiveArrayIterator
     */
    public function getIterator()
    {
        return new \RecursiveArrayIterator($this->data);
    }

    /**
     * Unsets an offset in the iterator.
     * @param   mixed  $offset  The array offset.
     * @return  void
     */
    public function offsetUnset($offset)
    {
        $this->set($offset, null);
    }

    public function __clone()
    {
        $this->data = unserialize(serialize($this->data));
    }

//////
///////////////////////////// helper /////////////////////////
//////

/**
     * Get data from array or object by path.
     *
     * Example: `DataCollector::getByPath($array, 'foo.bar.yoo')` equals to $array['foo']['bar']['yoo'].
     *
     * @param mixed  $data      An array or object to get value.
     * @param mixed  $path      The key path.
     * @param string $separator Separator of paths.
     *
     * @return  mixed Found value, null if not exists.
     */
    public static function getByPath(array $data, $path, $separator = '.')
    {
        $nodes = static::getPathNodes($path, $separator);

        if (!$nodes) {
            return null;
        }

        $dataTmp = $data;

        foreach ($nodes as $arg) {
            if (is_object($dataTmp) && isset($dataTmp->$arg)) {
                $dataTmp = $dataTmp->$arg;
            } elseif (
                ( is_array($dataTmp) || $dataTmp instanceof \ArrayAccess)
                 && isset($dataTmp[$arg])
            ) {
                $dataTmp = $dataTmp[$arg];
            } else {
                return null;
            }
        }

        return $dataTmp;
    }

    /**
     * setByPath
     *
     * @param mixed  &$data
     * @param string $path
     * @param mixed  $value
     * @param string $separator
     *
     * @return  boolean
     */
    public static function setByPath(array &$data, $path, $value, $separator = '.')
    {
        $nodes = static::getPathNodes($path, $separator);

        if (!$nodes) {
            return false;
        }

        $dataTmp = &$data;

        foreach ($nodes as $node) {
            if (is_array($dataTmp)) {
                if (empty($dataTmp[$node])) {
                    $dataTmp[$node] = array();
                }

                $dataTmp = &$dataTmp[$node];
            } else {
                // If a node is value but path is not go to the end, we replace this value as a new store.
                // Then next node can insert new value to this store.
                $dataTmp = array();
            }
        }

        // Now, path go to the end, means we get latest node, set value to this node.
        $dataTmp = $value;

        return true;
    }

    /**
     * @param string $path
     * @param string $separator
     * @return  array
     */
    public static function getPathNodes($path, $separator = '.')
    {
        return array_values(array_filter(explode($separator, $path), 'strlen'));
    }

    /**
     * @param $data
     * @param bool|false $recursive
     * @return array
     */
    public static function dataToArray($data, $recursive = false)
    {
        // Ensure the input data is an array.
        if ($data instanceof \Traversable) {
            $data = iterator_to_array($data);
        } elseif (is_object($data)) {
            $data = get_object_vars($data);
        } else {
            $data = (array) $data;
        }

        if ($recursive) {
            foreach ($data as &$value) {
                if (is_array($value) || is_object($value)) {
                    $value = static::dataToArray($value, $recursive);
                }
            }
        }

        return $data;
    }

    /**
     * @param array $array
     * @param string $class
     * @return mixed
     */
    public static function dataToObject($array, $class = \stdClass::class)
    {
        $object = new $class;

        foreach ($array as $k => $v) {
            $object->$k = is_array($v) ? static::dataToObject($v, $class) : $v;
        }

        return $object;
    }

    /**
     * @param $data
     * @return array
     * @throws DataParseException
     */
    public static function parseJson($data)
    {
        if ( !is_string($data) ) {
            throw new \InvalidArgumentException('param type error! must is string.');
        }

        if ( !$data ) {
            return [];
        }

        if ( file_exists($data) ) {
            $data = file_get_contents($data);
            $pattern = [
                //去除文件中的注释
                '!/\*[^*]*\*+([^/][^*]*\*+)*/!',

                //去掉所有单行注释
                '/\/\/.*?[\r\n]/is',

                // 多个空格 换成一个
                "/(?!\w)\s*?(?!\w)/is"
            ];
            $replace = ['','',''];
            $data = preg_replace($pattern, $replace, $data);
        }

        $data = json_decode(trim($data), true);
        if ( json_last_error() === JSON_ERROR_NONE ) {
            return $data;
        } else {
            throw new DataParseException('json config data parse error :'.json_last_error_msg());
        }
    }

    const IMPORT_KEY = 'import';

    /**
     * parse YAML
     * @param string $data              Waiting for the parse data
     * @param bool $supportImport       Simple support import other config by tag 'import'. must is bool.
     * @param callable $pathHandler     When the second param is true, this param is valid.
     * @param string $fileDir           When the second param is true, this param is valid.
     * @return array
     */
    public static function parseYaml($data, $supportImport=false, callable $pathHandler=null, $fileDir = '')
    {
        if ( !is_string($data) ) {
            throw new \InvalidArgumentException('param type error! must is string.');
        }

        if ( !$data ) {
            return [];
        }

        $parserClass = '\Symfony\Component\Yaml\Parser';

        if ( !class_exists($parserClass) ) {
            throw new \UnexpectedValueException("yml format parser Class $parserClass don't exists! please install package 'symfony/yaml'.");
        }

        if ( is_file($data) ) {
            $fileDir = $fileDir ? : dirname($data);
            $data = file_get_contents($data);
        }

        /** @var \Symfony\Component\Yaml\Parser $parser */
        $parser = new $parserClass;
        $array = $parser->parse(trim($data));
//        $array  = json_decode(json_encode($array));

        // import other config by tag 'import'
        if ( $supportImport===true && !empty($array[static::IMPORT_KEY]) && is_string($array[static::IMPORT_KEY]) ) {
            $importFile = trim($array[static::IMPORT_KEY]);

            // if needed custom handle $importFile path. e.g: Maybe it uses custom alias path
            if ( $pathHandler && is_callable($pathHandler) ) {
                $importFile = $pathHandler($importFile);
            }

            // if $importFile is not exists AND $importFile is not a absolute path AND have $parentFile
            if ( $fileDir && !file_exists($importFile) && $importFile[0] !== '/') {
                $importFile = $fileDir . '/' . trim($importFile, './');
            }

            // $importFile is file
            if ( is_file($importFile) ) {

                unset($array['import']);
                $data     = file_get_contents($importFile);
                $imported = $parser->parse(trim($data));
                $array    = array_merge($imported, $array);
            } else {
                throw new \UnexpectedValueException("needed imported file $importFile don't exists!");
            }
        }

        unset($parser);

        return $array;
    }
}
