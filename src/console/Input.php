<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 19:23
 */

namespace inhere\librarys\console;

/**
 * Class Input
 * @package inhere\librarys\console
 * e.g:
 *     ./bin/app image/packTask test -d -s=df --debug=true
 *     php bin/cli.php start test -d -s=df --debug=true
 */
class Input
{
    /**
     * @var @resource
     */
    protected $inputStream = STDIN;

    /**
     * Input data
     * @var array
     */
    protected $data = [];

    /**
     * the script name
     * e.g `./bin/app` OR `bin/cli.php`
     * @var string
     */
    public static $scriptName;

    /**
     * the script name
     * e.g `image/packTask` OR `start`
     * @var string
     */
    public static $command;

    public function __construct($parseArgv = true, $fixServer = false, $fillToGlobal = false)
    {
        if ($parseArgv) {
            $this->data = self::parseGlobalArgv($fixServer, $fillToGlobal);
        }
    }

    /**
     * @return string
     */
    public function read()
    {
        return trim(fgets($this->inputStream));
    }

    /**
     * @param null|string $name
     * @param mixed $default
     * @return mixed
     */
    public function get($name=null, $default = null)
    {
        if (null === $name) {
            return $this->data;
        }

        return isset($this->data[$name]) ? $this->data[$name] : $default;
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

        return in_array($value, ['0', 0, 'false', false], true) ? false : true;
    }

    /**
     * @return string
     */
    public function getScriptName()
    {
        return self::$scriptName;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return self::$command;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return resource
     */
    public function getInputStream()
    {
        return $this->inputStream;
    }

    /**
     */
    public static function parseGlobalArgv($fixServer = false, $fillToGlobal = false)
    {
        // ./bin/app image/packTask start test -d -s=df --debug=true
        // php bin/cli.php image/packTask start test -d -s=df --debug=true
        global $argv;
        $args = $argv;

        if ($args[0] === 'php') {
            array_shift($args);
        }

        self::$scriptName = array_shift($args);

        if ($fixServer) {
            // fixed: '/home' is not equals to '/home/'
            if (isset($_SERVER['REQUEST_URI'])) {
                $_SERVER['REQUEST_URI'] = rtrim($_SERVER['REQUEST_URI'],'/ ');
            }

            // fixed: PHP_SELF = 'index.php', it is should be '/index.php'
            if (isset($_SERVER['PHP_SELF'])) {
                $_SERVER['PHP_SELF'] = '/' . ltrim($_SERVER['PHP_SELF'],'/ ');
            }


            // $_SERVER['PHP_SELF'] = self::$scriptName;
            $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = '/';
        }

        // collect command
        if ( isset($args[0]) && strpos($args[0], '=') === false ) {
            self::$command = trim(array_shift($args), '/');

            if ($fixServer) {
                $_SERVER['REQUEST_URI'] .= self::$command;
            }
        }

        $data = [];

        // parse query params
        // ./bin/app image/packTask start test -d -s=df --debug=true
        // parse to
        // ./bin/app image/packTask?start&test&d&s=df&debug=true
        if ($args) {
            $url = preg_replace('/&[-]+/', '&', implode('&',$args));

            parse_str($url, $data);

            if ($fillToGlobal) {
                $_REQUEST = $_GET = $data;
            }
        }

        return $data;
    }
}
