<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/1
 * Time: 下午5:33
 */

namespace Inhere\Library\Helpers;

/**
 * Class Cli
 * @package Inhere\Library\Helpers
 */
class Cli
{
    const NORMAL = 0;

    // Foreground color
    const FG_BLACK = 30;
    const FG_RED = 31;
    const FG_GREEN = 32;
    const FG_BROWN = 33;
    const FG_BLUE = 34;
    const FG_CYAN = 36;
    const FG_WHITE = 37;
    const FG_DEFAULT = 39;

    // Background color
    const BG_BLACK = 40;
    const BG_RED = 41;
    const BG_GREEN = 42;
    const BG_BROWN = 43;
    const BG_BLUE = 44;
    const BG_CYAN = 46;
    const BG_WHITE = 47;
    const BG_DEFAULT = 49;

    // color option
    const BOLD = 1;      // 加粗
    const FUZZY = 2;      // 模糊(不是所有的终端仿真器都支持)
    const ITALIC = 3;      // 斜体(不是所有的终端仿真器都支持)
    const UNDERSCORE = 4;      // 下划线
    const BLINK = 5;      // 闪烁
    const REVERSE = 7;      // 颠倒的 交换背景色与前景色

    /**
     * some styles
     * @var array
     */
    public static $styles = [
        'light_red' => '1;31',
        'light_green' => '1;32',
        'yellow' => '1;33',
        'light_blue' => '1;34',
        'magenta' => '1;35',
        'light_cyan' => '1;36',
        'white' => '1;37',
        'black' => '0;30',
        'red' => '0;31',
        'green' => '0;32',
        'brown' => '0;33',
        'blue' => '0;34',
        'cyan' => '0;36',
        'bold' => '1',
        'underscore' => '4',
        'reverse' => '7',
    ];

    /**
     * @param $text
     * @param string|int|array $style
     * @return string
     */
    public static function color($text, $style = self::NORMAL)
    {
        if (!self::isSupportColor()) {
            return $text;
        }

        if (is_string($style)) {
            $out = self::$styles[$style] ?? '0';
        } elseif (is_int($style)) {
            $out = $style;

            // array: [self::FG_GREEN, self::BG_WHITE, self::UNDERSCORE]
        } elseif (is_array($style)) {
            $out = implode(';', $style);
        } else {
            $out = self::NORMAL;
        }

//        $result = chr(27). "$out{$text}" . chr(27) . chr(27) . "[0m". chr(27);
        return "\033[{$out}m{$text}\033[0m";
    }

    /**
     * @param string $text
     * @return string
     */
    public static function clearColor($text)
    {
        // return preg_replace('/\033\[(?:\d;?)+m/', '' , "\033[0;36mtext\033[0m");
        return preg_replace('/\033\[(?:\d;?)+m/', '', $text);
    }

    /**
     * Returns true if STDOUT supports colorization.
     * This code has been copied and adapted from
     * \Symfony\Component\Console\Output\OutputStream.
     * @return boolean
     */
    public static function isSupportColor()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return
                '10.0.10586' === PHP_WINDOWS_VERSION_MAJOR . '.' . PHP_WINDOWS_VERSION_MINOR . '.' . PHP_WINDOWS_VERSION_BUILD
                || false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM')// || 'cygwin' === getenv('TERM')
                ;
        }

        if (!defined('STDOUT')) {
            return false;
        }

        return self::isInteractive(STDOUT);
    }

    /**
     * Returns if the file descriptor is an interactive terminal or not.
     * @param  int|resource $fileDescriptor
     * @return boolean
     */
    public static function isInteractive($fileDescriptor)
    {
        return function_exists('posix_isatty') && @posix_isatty($fileDescriptor);
    }

    /**
     * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
     * Supports:
     * -e
     * -e <value>
     * --long-param
     * --long-param=<value>
     * --long-param <value>
     * <value>
     * @link https://github.com/inhere/php-console/blob/master/src/io/Input.php
     * @param array $noValues List of parameters without values
     * @param bool $mergeOpts
     * @return array
     */
    public static function parseOptArgs(array $noValues = [], $mergeOpts = false)
    {
        $params = $GLOBALS['argv'];
        reset($params);

        $args = $sOpts = $lOpts = [];
        $fullScript = implode(' ', $params);
        $script = array_shift($params);

        while (list(, $p) = each($params)) {
            // is options
            if ($p{0} === '-') {
                $isLong = false;
                $opt = substr($p, 1);
                $value = true;

                // long-opt: (--<opt>)
                if ($opt{0} === '-') {
                    $isLong = true;
                    $opt = substr($opt, 1);

                    // long-opt: value specified inline (--<opt>=<value>)
                    if (strpos($opt, '=') !== false) {
                        list($opt, $value) = explode('=', $opt, 2);
                    }

                    // short-opt: value specified inline (-<opt>=<value>)
                } elseif (strlen($opt) > 2 && $opt{1} === '=') {
                    list($opt, $value) = explode('=', $opt, 2);
                }

                // check if next parameter is a descriptor or a value
                $nxp = current($params);

                if ($value === true && $nxp !== false && $nxp{0} !== '-' && !in_array($opt, $noValues, true)) {
                    list(, $value) = each($params);

                    // short-opt: bool opts. like -e -abc
                } elseif (!$isLong && $value === true) {
                    foreach (str_split($opt) as $char) {
                        $sOpts[$char] = true;
                    }

                    continue;
                }

                if ($isLong) {
                    $lOpts[$opt] = $value;
                } else {
                    $sOpts[$opt] = $value;
                }

                // arguments: param doesn't belong to any option, define it is args
            } else {
                // value specified inline (<arg>=<value>)
                if (strpos($p, '=') !== false) {
                    list($name, $value) = explode('=', $p, 2);
                    $args[$name] = $value;
                } else {
                    $args[] = $p;
                }
            }
        }

        unset($params);

        if ($mergeOpts) {
            return [$fullScript, $script, $args, array_merge($sOpts, $lOpts)];
        }

        return [$fullScript, $script, $args, $sOpts, $lOpts];
    }

    /**
     * Logs data to stdout
     * @param string $logString
     * @param bool $nl
     * @param bool|int $quit
     */
    public static function stdout($logString, $nl = true, $quit = false)
    {
        fwrite(\STDOUT, $logString . ($nl ? PHP_EOL : ''));

        if (($isTrue = true === $quit) || is_int($quit)) {
            $code = $isTrue ? 0 : $quit;
            exit($code);
        }
    }

    /**
     * Logs data to stderr
     * @param string $text
     * @param bool $nl
     * @param bool|int $quit
     */
    public static function stderr($text, $nl = true, $quit = -200)
    {
        fwrite(\STDERR, self::color('[ERROR] ', 'red') . $text . ($nl ? PHP_EOL : ''));

        if (($isTrue = true === $quit) || is_int($quit)) {
            $code = $isTrue ? 0 : $quit;
            exit($code);
        }
    }

    /**
     * run a command in background
     * @param string $cmd
     */
    public static function execInBackground($cmd)
    {
        if (strpos(PHP_OS, 'Windows') === 0) {
            pclose(popen('start /B ' . $cmd, 'r'));
        } else {
            exec($cmd . ' > /dev/null &');
        }
    }

    /**
     * Method to execute a command in the terminal
     * Uses :
     * 1. system
     * 2. passthru
     * 3. exec
     * 4. shell_exec
     * @param $command
     * @return array
     */
    public static function exec($command)
    {
        $return_var = 1;

        //system
        if (function_exists('system')) {
            ob_start();
            system($command, $return_var);
            $output = ob_get_contents();
            ob_end_clean();

            // passthru
        } elseif (function_exists('passthru')) {
            ob_start();
            passthru($command, $return_var);
            $output = ob_get_contents();
            ob_end_clean();
            //exec
        } else if (function_exists('exec')) {
            exec($command, $output, $return_var);
            $output = implode("\n", $output);

            //shell_exec
        } else if (function_exists('shell_exec')) {
            $output = shell_exec($command);
        } else {
            $output = 'Command execution not possible on this system';
            $return_var = 0;
        }

        return array('output' => $output, 'status' => $return_var);
    }
}
