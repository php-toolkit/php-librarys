<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/12/14
 * Time: 19:44
 */

namespace inhere\library\utils;

/**
 * local env read
 *
 * in local env file(must is 'ini' format):
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
 * $lev = new LocalEnv(__DIE__, '.env');
 *
 * $debug = $lev->get('debug', false);// can also use function: local_env('debug', false)
 * $env = $lev->get('env', 'pdt');
 * ```
 */
class LocalEnv
{
    /**
     * app local env config
     * @var array
     */
    // private static $instances = [];

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
     * LocalEnv constructor.
     * @param string $filePath
     * @param string $fileName
     */
    public function __construct(string $filePath, string $fileName = '.env')
    {
        $this->filePath = $filePath;
        $this->fileName = $fileName;

        $this->loadData();
    }

    /**********************************************************
     * local env
     **********************************************************/

    /**
     * get local env config value
     * @param  string $name
     * @param  mixed $default
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        $value = getenv(strtoupper($name));

        return false !== $value ? $value : $default;
    }

    /**
     * load env data
     */
    private function loadData(): void
    {
        $file = $this->getFile();

        if ($file && is_file($file) && is_readable($file)) {
            $this->data = parse_ini_file($file, true);

            foreach ($this->data as $name => $value) {
                // eg: "FOO=BAR"
                putenv(strtoupper($name) . "=$value");
            }
        }
    }

    /**
     * @param null|string $key
     * @return array
     */
    public function all($key = null): array
    {
        if ($key === 'ENV') {
            return $_ENV;
        }

        if ($key === 'SERVER') {
            return $_SERVER;
        }

        return $_ENV + $_SERVER;
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
}
