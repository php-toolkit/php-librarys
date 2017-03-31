<?php
/**
 * function collection
 */

if ( !function_exists('local_env') ) {
    function local_env($name = null, $default = null)
    {
        return inhere\library\collections\Local::env($name, $default);
    }
}

if ( !function_exists('html_minify') ) {
    function html_minify($body) {
        $search = array('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '/\n/','/\>[^\S ]+/s','/[^\S ]+\</s','/(\s)+/s');
        $replace = array(' ', ' ','>','<','\\1');

        return preg_replace($search, $replace, $body);
    }
}

if ( !function_exists('value') ) {
    /**
     * @param $value
     * @return mixed
     *
     * value(new Class)->xxx
     */
    function value($value) {
        return $value;
    }
}

if ( !function_exists('tap') ) {
    function tap($value, callable $callback) {

        $callback($value);

        return $value;
    }
}

if ( !function_exists('cookie') ) {
    /**
     * cookie get
     * @param  string|array $name
     * @param  mixed $default
     * @return mixed
     */
    function cookie($name, $default=null)
    {
        // get
        if ($name && is_string($name)) {
            return $_COOKIE[$name] ?? $default;
        }

        return $default;
    }
}

if ( !function_exists('session') ) {
    /**
     * session get or set
     * @param  string|array $name
     * @param  mixed $default
     * @return mixed
     */
    function session($name, $default=null)
    {
        if (!isset($_SESSION)) {
            throw new \RuntimeException('session set or get failed. Session don\'t start.');
        }

        // set, when $name is array
        if ($name && is_array($name) ) {
            foreach ((array)$name as $key => $value) {
                if (is_string($key)) {
                    $_SESSION[$key] = $value;
                }
            }

            return $name;
        }

        // get
        if ($name && is_string($name)) {
            return $_SESSION[$name] ?? $default;
        }

        return $default;
    }
}

if ( !function_exists('make_object')) {
    function make_object($class) {
        static $__object_list_box = [];

        if ( !isset($__object_list_box[$class]) ) {
            $__object_list_box[$class] = new $class;
        }

        return $__object_list_box[$class];
    }
}
