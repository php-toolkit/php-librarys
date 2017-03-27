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
 * Class JsonDataParser
 * @package inhere\librarys\webSocket\app
 */
class JsonDataParser implements IDataParser
{
    /**
     * @param string $data
     * @param int $index
     * @param Application $app
     * @return array
     */
    public function parse(string $data, int $index, Application $app): array
    {
        // json parser
        $temp = $data;
        $to = $app->getOption('jsonParseTo') ?: Application::JSON_TO_RAW;
        $cmdKey = $app->getOption('cmdKey') ?: Application::DEFAULT_CMD_KEY;
        $command = $app->getOption('defaultCmd') ?: Application::DEFAULT_CMD;

        $app->log("The #{$index} request command: $command, data: $data");

        $data = json_decode(trim($data), $toAssoc = $to === Application::JSON_TO_ARRAY);

        // parse error
        if ( json_last_error() > 0 ) {
            // revert
            $data = $temp;
            $command = Application::PARSE_ERROR;
            $errMsg = json_last_error_msg();

            $app->log("The #{$index} request data parse to json failed! MSG: $errMsg Data: {$temp}", 'error');
        } elseif ($toAssoc) {
            if ( isset($data[$cmdKey]) && $data[$cmdKey]) {
                $command = $data[$cmdKey];
                unset($data[$cmdKey]);
            }
        } elseif ($to === Application::JSON_TO_OBJECT) {
            if ( isset($data->{$cmdKey}) && $data->{$cmdKey}) {
                $command = $data->{$cmdKey};
                unset($data->{$cmdKey});
            }
        } else {
            // revert
            $data = $temp;
        }

        return [$command, $data];
    }
}