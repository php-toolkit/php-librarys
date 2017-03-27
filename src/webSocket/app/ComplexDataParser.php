<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-27
 * Time: 9:27
 */

namespace inhere\librarys\webSocket\app;


use inhere\librarys\webSocket\Application;

/**
 * Class ComplexDataParser
 * @package inhere\librarys\webSocket\app
 */
class ComplexDataParser implements IDataParser
{
    /**
     * @param string $data
     * @param int $index
     * @param Application $app
     * @return array
     */
    public function parse(string $data, int $index, Application $app): array
    {
        // default format: [@command]data
        // eg:
        // [@test]hello
        // [@login]{"name":"john","pwd":123456}
        if (preg_match('/^\[@([\w-]+)\](.+)/', $data, $matches)) {
            array_shift($matches);
            [$command, $realData] = $matches;

            // access default command
        } else {
            $realData = $data;
            $command = $app->getOption('defaultCmd') ?: Application::DEFAULT_CMD;
        }

        $app->log("The #{$index} request command: $command, data: $realData");
        $to = $app->getOption('jsonParseTo') ?: Application::JSON_TO_RAW;

        if ( $app->isJsonType() && $to !== Application::JSON_TO_RAW ) {
            $realData = json_decode(trim($realData), $to === Application::JSON_TO_ARRAY);

            // parse error
            if ( json_last_error() > 0 ) {
                // revert
                $realData = trim($matches[2]);
                $command = Application::PARSE_ERROR;
                $errMsg = json_last_error_msg();

                $app->log("Request data parse to json failed! MSG: {$errMsg}, JSON: {$realData}", 'error');
            }
        }

        return [ $command, $realData ];
    }
}