<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-04-27
 * Time: 16:06
 */

namespace inhere\library\gearman;

/**
 * Class JobInterface
 * @package inhere\library\gearman
 */
interface JobInterface
{
    public function run();
}