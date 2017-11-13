<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/17
 * Time: 上午10:11
 */

namespace Inhere\Library\Utils;

/**
 * Class PhpDotEnv - local env read
 * @package Inhere\Library\Utils
 * in local config file(must is 'ini' format):
 * ```ini
 * env=dev
 * debug=true
 * ... ...
 * ```
 * IN CODE:
 * ```php
 * new PhpDotEnv(__DIE__, '.env');
 * env('debug', false);
 * env('env_name', 'pdt');
 * ```
 */
class PhpDotEnv
{
    /**
     * @param string $fileDir
     * @param string $fileName
     * @return static
     */
    public static function init(string $fileDir, string $fileName = '.env')
    {
        return new static($fileDir, $fileName);
    }

    /**
     * constructor.
     * @param string $fileDir
     * @param string $fileName
     */
    public function __construct(string $fileDir, string $fileName = '.env')
    {
        $file = $fileDir . DIRECTORY_SEPARATOR . ($fileName ?: '.env');

        if ($file && is_file($file) && is_readable($file)) {
            $this->load(parse_ini_file($file));
        }
    }

    /**
     * load env data
     */
    protected function load($data)
    {
        foreach ($data as $name => $value) {
            if (!is_string($value)) {
                continue;
            }

            // is a constant var
            if (isset($value[2]) && defined($value)) {
                $value = constant($value);
            }

            // eg: "FOO=BAR"
            putenv(strtoupper($name) . "=$value");
        }
    }
}
