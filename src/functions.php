<?php
/**
 * function collection
 */

if (!function_exists('dump_v')) {
    function dump_v(...$args)
    {
        return \inhere\library\helpers\PhpHelper::dumpVar($args);
    }
}

if (!function_exists('print_v')) {
    function print_v(...$args)
    {
        return \inhere\library\helpers\PhpHelper::printVar($args);
    }
}

if (!function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object / class.
     *
     * @param  string|object $class
     * @return string
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('retry')) {
    /**
     * Retry an operation a given number of times.
     *
     * @param  int $times
     * @param  callable $callback
     * @param  int $sleep
     * @return mixed
     *
     * @throws \Exception
     */
    function retry($times, callable $callback, $sleep = 0)
    {
        $times--;
        beginning:
        try {
            return $callback();
        } catch (Exception $e) {
            if (!$times) {
                throw $e;
            }
            $times--;
            if ($sleep) {
                usleep($sleep * 1000);
            }
            goto beginning;
        }
    }
}

if (!function_exists('local_env')) {
    function local_env($name = null, $default = null)
    {
        //return inhere\library\collections\Local::env($name, $default);
    }
}

if (!function_exists('html_minify')) {
    function html_minify($body)
    {
        $search = array('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/', '/\n/', '/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s');
        $replace = array(' ', ' ', '>', '<', '\\1');

        return preg_replace($search, $replace, $body);
    }
}

if (!function_exists('value')) {
    /**
     * @param $value
     * @return mixed
     *
     * value(new Class)->xxx
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}


if (!function_exists('with')) {
    /**
     * Return the given object. Useful for chaining.
     *
     * @param  mixed $object
     * @return mixed
     */
    function with($object)
    {
        return $object;
    }
}

if (!function_exists('tap')) {
    function tap($value, callable $callback)
    {

        $callback($value);

        return $value;
    }
}

if (!function_exists('cookie')) {
    /**
     * cookie get
     * @param  string|array $name
     * @param  mixed $default
     * @return mixed
     */
    function cookie($name, $default = null)
    {
        // get
        if ($name && is_string($name)) {
            return $_COOKIE[$name] ?? $default;
        }

        return $default;
    }
}

if (!function_exists('random_token')) {
    function random_token($length = 32)
    {
        if (!isset($length) || (int)$length <= 8) {
            $length = 32;
        }

        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        }

        if (function_exists('mcrypt_create_iv')) {
            // return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));

            $random = mcrypt_create_iv($length, MCRYPT_DEV_RANDOM);
            if (false === $random) {
                throw new \RuntimeException('IV generation failed');
            }

            return $random;
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            //return bin2hex(openssl_random_pseudo_bytes($length));
            $random = openssl_random_pseudo_bytes($length, $isSourceStrong);

            if (false === $isSourceStrong || false === $random) {
                throw new \RuntimeException('IV generation failed');
            }

            return $random;
        }

        return md5(microtime(true));
    }
}

if (!function_exists('create_salt')) {
    function create_salt()
    {
        return \inhere\library\helpers\Str::genSalt();
    }
}

if (!function_exists('session')) {
    /**
     * session get or set
     * @param  string|array $name
     * @param  mixed $default
     * @return mixed
     */
    function session($name, $default = null)
    {
        if (!isset($_SESSION)) {
            throw new \RuntimeException('session set or get failed. Session don\'t start.');
        }

        // set, when $name is array
        if ($name && is_array($name)) {
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

if (!function_exists('make_object')) {
    function make_object($class, array $args = [])
    {
        static $__object_list_box = [];

        if (!isset($__object_list_box[$class])) {
            $__object_list_box[$class] = $args ? new $class($args) : new $class;
        }

        return $__object_list_box[$class];
    }
}
