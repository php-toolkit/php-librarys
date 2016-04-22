<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/1/23
 * Use : ...
 */

namespace inhere\tools\helpers;


class CliHelper
{
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
    static public function terminal($command)
    {
        $return_var = 1;

        //system
        if (function_exists('system'))
        {
            ob_start();
            system($command , $return_var);
            $output = ob_get_contents();
            ob_end_clean();
        }//passthru
        else if (function_exists('passthru'))
        {
            ob_start();
            passthru($command , $return_var);
            $output = ob_get_contents();
            ob_end_clean();
        }//exec
        else if (function_exists('exec'))
        {
            exec($command , $output , $return_var);
            $output = implode("\n" , $output);
        } //shell_exec
        else if (function_exists('shell_exec'))
        {
            $output = shell_exec($command) ;
        }
        else
        {
            $output = 'Command execution not possible on this system';
            $return_var = 0;
        }

        return array('output' => $output , 'status' => $return_var);
    }
}