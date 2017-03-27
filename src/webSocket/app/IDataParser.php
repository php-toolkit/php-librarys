<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/26 0026
 * Time: 15:35
 */

namespace inhere\librarys\webSocket\app;

use inhere\librarys\webSocket\Application;

/**
 * Interface IDataParser
 * @package inhere\librarys\webSocket\app
 *
 */
interface IDataParser
{
    /**
     * @param string $data
     * @param int $index
     * @param Application $app
     * @return array
     */
    public function parse(string $data, int $index, Application $app): array ;
}
