<?php
/**
 * function collection
 */

if (!function_exists('dump_vars')) {
    function dump_vars(...$args)
    {
        echo \Inhere\Library\Helpers\PhpHelper::dumpVars(...$args);
    }
}

if (!function_exists('print_vars')) {
    function print_vars(...$args)
    {
        echo \Inhere\Library\Helpers\PhpHelper::printVars(...$args);
    }
}

if (!function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object / class.
     * @param  string|mixed $class
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
     * @param  int $times
     * @param  callable $callback
     * @param  int $sleep
     * @return mixed
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

if (!function_exists('env')) {
    function env(string $name, $default = null)
    {
        $value = getenv(strtoupper($name));

        return false !== $value ? $value : $default;
    }
}

if (!function_exists('collect')) {
    function collect(array $values)
    {
        return new \Inhere\Library\Collections\LiteCollection($values);
    }
}

if (!function_exists('msleep')) {
    function msleep($ms)
    {
        usleep($ms * 1000);
    }
}

if (!function_exists('local')) {
    function local($name = null, $default = null)
    {
        return Inhere\Library\Utils\LocalConfig::instance()->get($name, $default);
    }
}

if (!function_exists('server_value')) {
    /**
     * get $_SERVER value
     * @param  string $name
     * @param  string $default
     * @return mixed
     */
    function server_value($name, $default = '')
    {
        $name = strtoupper($name);

        return isset($_SERVER[$name]) ? trim($_SERVER[$name]) : $default;
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
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}


if (!function_exists('with')) {
    /**
     * Return the given value. Useful for chaining.
     *   with(new Class)->xxx
     * @param  mixed $value
     * @return mixed
     */
    function with($value)
    {
        return $value;
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
        if ((int)$length <= 8) {
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
        return \Inhere\Library\Helpers\Str::genSalt();
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
        if (null === $_SESSION) {
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
    function make_object($class)
    {
        return \Inhere\Library\Helpers\Obj::create($class);
    }
}
