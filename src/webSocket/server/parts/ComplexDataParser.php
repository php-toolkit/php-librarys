<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-27
 * Time: 9:27
 */

namespace inhere\librarys\webSocket\server\parts;

use inhere\librarys\webSocket\server\Application;

/**
 * Class ComplexDataParser
 * @package inhere\librarys\webSocket\server\parts
 */
class ComplexDataParser implements IDataParser
{
    /**
     * @param string $data
     * @param int $index
     * @param Application $app
     * @return array|false
     */
    public function parse(string $data, int $index, Application $app)
    {
        // default format: [@command]data
        // eg:
        // [@test]hello
        // [@login]{"name":"john","pwd":123456}

        $command = '';

        if (preg_match('/^\[@([\w-]+)\](.+)/', $data, $matches)) {
            array_shift($matches);
            [$command, $realData] = $matches;

            // access default command
        } else {
            $realData = $data;
        }

        $app->log("The #{$index} request command: $command, data: $realData");
        $to = $app->getOption('jsonParseTo') ?: self::JSON_TO_RAW;

        if ( $app->isJsonType() && $to !== self::JSON_TO_RAW ) {
            $realData = json_decode(trim($realData), $to === self::JSON_TO_ARRAY);

            // parse error
            if ( json_last_error() > 0 ) {
                // revert
                $realData = trim($matches[2]);
                $errMsg = json_last_error_msg();

                $app->log("Request data parse to json failed! MSG: {$errMsg}, JSON: {$realData}", 'error');

                return false;
            }
        }

        return [ $command, $realData ];
    }
}
