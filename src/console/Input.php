<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 19:23
 */

namespace inhere\librarys\console;

use inhere\librarys\io\Input as BaseInput;

/**
 * Class Input
 * @package inhere\librarys\console
 */
class Input extends BaseInput
{
    protected $inputStream = STDIN;

    public static $scriptName;

    /**
     * @return string
     */
    public function read()
    {
        return trim(fgets($this->inputStream));
    }

    /**
     * @param $key
     * @param bool $default
     * @return bool
     */
    public function getBool($key, $default = false)
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        return in_array($value, ['0', 0, 'false'], true) ? false : true;
    }

    /**
     * @return resource
     */
    public function getInputStream()
    {
        return $this->inputStream;
    }


    /**
     * @param array $argv
     */
    public static function parseConsoleArgs($argv)
    {
        // fixed: '/home' is not equals to '/home/'
        if (isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = rtrim($_SERVER['REQUEST_URI'],'/ ');
        }

        // fixed: PHP_SELF = 'index.php', it is should be '/index.php'
        if (isset($_SERVER['PHP_SELF'])) {
            $_SERVER['PHP_SELF'] = '/' . ltrim($_SERVER['PHP_SELF'],'/ ');
        }

        self::$scriptName = array_shift($argv);

        // $_SERVER['PHP_SELF'] = self::$scriptName;
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        if ( isset($argv[0]) && strpos($argv[0], '=') === false ) {
            $path = trim(array_shift($argv), '/');

            $_SERVER['REQUEST_URI'] .= $path;
        }

        // parse query params
        // ./bin/app image/packTask start test -d -s=df --debug=true
        // parse to
        // ./bin/app image/packTask?start&test&d&s=df&debug=true
        if ($argv) {
            $url = preg_replace('/&[-]+/', '&', implode('&',$argv));

            parse_str($url, $data);
            $_REQUEST = $_GET = $data;
        }
    }
}
