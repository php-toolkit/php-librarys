<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-09-20
 * Time: 15:49
 */

namespace inhere\library\log;


/**
 * Interface HandlerInterface
 * @package inhere\library\log
 */
interface HandlerInterface
{
    public function handle(array $logs, $final);
}