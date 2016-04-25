<?php
/**
 * function collection
 */

if ( !function_exists('html_minify') ) {
    function html_minify($body)
    {
        $search = array('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '/\n/','/\>[^\S ]+/s','/[^\S ]+\</s','/(\s)+/s');
        $replace = array(' ', ' ','>','<','\\1');
        $squeezedHTML = preg_replace($search, $replace, $body);

        return $squeezedHTML;
    }
}

if ( !function_exists('cookie') ) {
    /**
     * cookie get or set
     * @param  string|array $name
     * @param  mixed $default
     * @param array $params
     * @return mixed
     */
    function cookie($name, $default=null, array $params = [])
    {
        // set, when $name is array
        if ($name && is_array($name) ) {
            $p = array_merge([
                'expire'   => null,
                'path'     => null,
                'domain'   => null,
                'secure'   => null,
                'httponly' => null
            ],$params);

            foreach ($name as $key => $value) {
                if ($key && $value && is_string($key) && is_scalar($value)) {
                    $_COOKIE[$key] = $value;
                    setcookie($key, $value, $p['expire'], $p['path'], $p['domain'], $p['secure'], $p['httponly']);
                }
            }

            return $name;
        }

        // get
        if ($name && is_string($name)) {
            return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
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
            foreach ($name as $key => $value) {
                if (is_string($key)) {
                    $_SESSION[$key] = $value;
                }
            }

            return $name;
        }

        // get
        if ($name && is_string($name)) {
            return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
        }

        return $default;
    }
}