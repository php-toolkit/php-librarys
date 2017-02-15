<?php
/**
 * @referee  windwalker-registry {@link https://github.com/ventoviro/windwalker-registry}
 */

namespace inhere\librarys\collections;

use RuntimeException;

/**
 * Class Config - 配置数据
 * @package collections
 */
class Config extends DataCollector
{
    /**
     * mode
     * Allow:
     *     folder 文件夹模式，传入一个配置文件夹路径，根据文件夹中的配置名称读取配置数据
     *     data  数据模式，可以一个配置文件的路径，将会自动读取载入；或直接传入数组数据
     * @var string
     */
    protected $mode = 'data';

    /**
     * when mode is 'folder', the config folder path
     * @var string
     */
    protected $folderPath;

    /**
     * 数据是否只读的
     * @var boolean
     */
    protected $readonly = true;

    /**
     * @var string
     */
    protected $defaultFormat = 'php';

    const MODE_FOLDER = 'folder';
    const MODE_DATA = 'data';

    /**
     * __construct
     * @param mixed $data If mode is 'folder', $data is config folder path
     * @param array $options
     * @param string $name
     */
    public function __construct($data = [], $options = [], $name = 'config')
    {
        if (is_string($options)) {
            $options = [ 'format' => $options ];
        }

        $options = array_merge([
            'format'     => 'php',
            'readonly'   => true,
            'mode'       => 'data', // 'data'
        ], $options);

        $this->mode = $options['mode'];
        $this->readonly = (bool)$options['readonly'];

        if ( is_string($data) && is_dir($data) ) {
            $this->mode = self::MODE_FOLDER;
            $this->folderPath = $data;
            $data = [];
        } elseif ( $this->mode === self::MODE_FOLDER && !is_dir($data)) {
            throw new RuntimeException("Config mode is 'folder'. the first arg must is a folder path!");
        }

        $this->defaultFormat = $options['format'];

        parent::__construct($data, $this->defaultFormat, $name);
    }

    /**
     * set config value by path
     * @param string $path
     * @param mixed $value
     */
    public function set($path, $value)
    {
        // if is readonly
        if ($this->readonly) {
            throw new RuntimeException("Config data have been setting readonly. don't allow change.");
        }

        return parent::set($path, $value);
    }

    /**
     * get value by path
     * @param string $path
     * @param string $default
     * @return mixed
     */
    public function get($path, $default = null)
    {
        if ( $this->mode === self::MODE_FOLDER ) {
            $nodes = static::getPathNodes($path, $separator);
            $name = array_shift($nodes);// config file name

            // if config file not load. load it.
            if ( !isset($this->data[$name]) ) {
                $file = $this->folderPath . "/{$name}.{$this->defaultFormat}";
                $this->load($file, $this->defaultFormat);
            }
        }

        $result = static::getByPath($this->data, $path, $this->separator);

        return $result !== null ? $result : $default;
    }

    /**
     * get Mode
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * data is Readonly
     * @return boolean
     */
    public function isReadonly()
    {
        return $this->readonly;
    }
}