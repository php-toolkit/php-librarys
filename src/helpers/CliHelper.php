<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/1
 * Time: 下午5:33
 */

namespace inhere\library\helpers;

/**
 * Class CliHelper
 * @package inhere\library\helpers
 */
class CliHelper
{
    /**
     * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
     *
     * Supports:
     * -e
     * -e <value>
     * --long-param
     * --long-param=<value>
     * --long-param <value>
     * <value>
     *
     * @link http://php.net/manual/zh/function.getopt.php#83414
     * @param array $noOpts List of parameters without values
     * @return array
     */
    public static function parseParameters($noOpts = [])
    {
        $result = [];
        $params = $GLOBALS['argv'];
        reset($params);

        while (list(, $p) = each($params)) {
            if ($p{0} === '-') {
                $pName = substr($p, 1);
                $value = true;

                if ($pName{0} === '-') {
                    // long-opt (--<param>)
                    $pName = substr($pName, 1);

                    if (strpos($p, '=') !== false) {
                        // value specified inline (--<param>=<value>)
                        list($pName, $value) = explode('=', substr($p, 2), 2);
                    }
                }

                // check if next parameter is a descriptor or a value
                $nxParam = current($params);

                if (!in_array($pName, $noOpts) && $value === true && $nxParam !== false && $nxParam{0} != '-') {
                    list(, $value) = each($params);
                }

                $result[$pName] = $value;
            } else {
                // param doesn't belong to any option
                $result[] = $p;
            }
        }

        return $result;
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
    public static function terminal($command)
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