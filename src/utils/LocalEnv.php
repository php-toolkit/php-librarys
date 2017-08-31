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
 * in local env file(must is 'ini' format):
 * ```
 * env=dev
 * debug=true
 * ... ...
 * ```
 * in code:
 * ```
 * $lev = new LocalEnv(__DIE__, '.env');
 * $debug = $lev->get('debug', false);// can also use function: local_env('debug', false)
 * $env = $lev->get('env', 'pdt');
 * ```
 */
class LocalEnv extends LocalConfig
{
    /**
     * @param string $filePath
     * @param string $fileName
     * @return static
     */
    public static function load(string $filePath, string $fileName = '.env')
    {
        return new static($filePath, $fileName);
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
    public function env(string $name, $default = null)
    {
        if (trim($name)) {
            return $default;
        }

        $value = getenv(strtoupper($name));

        return false !== $value ? $value : $default;
    }

    /**
     * load env data
     */
    protected function loadData(): void
    {
        parent::loadData();

        foreach ($this->getData() as $name => $value) {
            if (!is_string($value)) {
                continue;
            }

            // eg: "FOO=BAR"
            putenv(strtoupper($name) . "=$value");
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
}
