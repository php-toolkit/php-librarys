<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/17
 * Time: 上午10:11
 */

namespace inhere\library\utils;

/**
 * Class LocalConfig local config read
 * @package inhere\library\utils
 *
 * in local config file(must is 'ini' format):
 *
 * ```
 * env=dev
 * debug=true
 * ... ...
 * ```
 *
 * in code:
 *
 * ```
 * $lev = new LocalEnv(__DIE__, '.local');
 *
 * $debug = $lev->get('debug', false);// can also use function: local_env('debug', false)
 * $env = $lev->get('env', 'pdt');
 * ```
 */
class LocalConfig
{
    /**
     * app local env config
     * @var array
     */
    private static $instances = [];

    /**
     * app local env config
     * @var array
     */
    private $data = [];

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @return static
     */
    public static function instance()
    {
        $key = self::getInstanceKey();

        if (!isset(self::$instances[$key])) {
            throw new \LogicException('please instance ' . static::class . ' before use it');
        }

        return self::$instances[$key];
    }

    /**
     * @param string $filePath
     * @param string $fileName
     * @return static
     */
    public static function load(string $filePath, string $fileName = '.local')
    {
        return new static($filePath, $fileName);
    }

    /**
     * LocalEnv constructor.
     * @param string $filePath
     * @param string $fileName
     */
    public function __construct(string $filePath, string $fileName = '.local')
    {
        $key = self::getInstanceKey();
        self::$instances[$key] = $this;

        $this->filePath = $filePath;
        $this->fileName = $fileName;

        $this->loadData();
    }

    /**********************************************************
     * local config
     **********************************************************/

    /**
     * get local env config value
     * @param  string|null $name
     * @param  mixed $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if (null === $name) {
            return $this->data;
        }

        return $this->data[$name] ?? $default;
    }

    /**
     * load env data
     */
    protected function loadData(): void
    {
        $file = $this->getFile();

        if ($file && is_file($file) && is_readable($file)) {
            $this->data = parse_ini_file($file, true);
        }
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * getConfigFile
     * @return string
     */
    public function getFile(): string
    {
        return $this->filePath . DIRECTORY_SEPARATOR . ($this->fileName ?: '.env');
    }

    private static function getInstanceKey()
    {
        return substr(md5(static::class), 0, 7);
    }
}
