<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/17
 * Time: 上午10:11
 */

namespace Inhere\Library\Components;

/**
 * Class PhpDotEnv - local env read
 * @package Inhere\Library\Utils
 *
 * in local config file `.env` (must is 'ini' format):
 * ```ini
 * ENV=dev
 * DEBUG=true
 * ... ...
 * ```
 *
 * IN CODE:
 *
 * ```php
 * PhpDotEnv::load(__DIE__);
 * env('DEBUG', false);
 * env('ENV', 'pdt');
 * ```
 */
final class PhpDotEnv
{
    /**
     * @param string $fileDir
     * @param string $fileName
     * @return static
     */
    public static function load(string $fileDir, string $fileName = '.env')
    {
        return new self($fileDir, $fileName);
    }

    /**
     * constructor.
     * @param string $fileDir
     * @param string $fileName
     */
    public function __construct(string $fileDir, string $fileName = '.env')
    {
        $file = $fileDir . DIRECTORY_SEPARATOR . ($fileName ?: '.env');

        if (is_file($file) && is_readable($file)) {
            $this->settingEnv(parse_ini_file($file));
        }
    }

    /**
     * setting env data
     * @param array $data
     */
    private function settingEnv(array $data)
    {
        foreach ($data as $name => $value) {
            if (\is_int($name) || !\is_string($value)) {
                continue;
            }

            // is a constant var
            if ($value && \defined($value)) {
                $value = \constant($value);
            }

            // eg: "FOO=BAR"
            putenv(strtoupper($name) . "=$value");
        }
    }
}
