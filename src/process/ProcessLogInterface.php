<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-05-20
 * Time: 9:49
 */

namespace inhere\library\process;

/**
 * Interface ProcessLogInterface
 * @package inhere\library\task
 */
interface ProcessLogInterface
{
    /**
     * Log levels can be enabled from the command line with -v, -vv, -vvv
     */
    const EMERG = -8;
    const ERROR = -6;
    const WARN = -4;
    const NOTICE = -2;
    const INFO = 0;
    const PROC_INFO = 2;
    const WORKER_INFO = 4;
    const DEBUG = 6;
    const CRAZY = 8;

    /**
     * Log file save type.
     */
    const SPLIT_NO = '';
    const SPLIT_DAY = 'day';
    const SPLIT_HOUR = 'hour';
}
