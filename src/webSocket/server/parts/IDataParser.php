<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/26 0026
 * Time: 15:35
 */

namespace inhere\librarys\webSocket\server\parts;

use inhere\librarys\webSocket\server\Application;

/**
 * Interface IDataParser
 * @package inhere\librarys\webSocket\server\parts
 *
 */
interface IDataParser
{
    //
    const JSON_TO_RAW = 1;
    const JSON_TO_ARRAY = 2;
    const JSON_TO_OBJECT = 3;

    /**
     * @param string $data
     * @param int $index
     * @param Application $app
     * @return array|false
     */
    public function parse(string $data, int $index, Application $app);
}
