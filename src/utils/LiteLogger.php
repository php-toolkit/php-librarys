<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/9/27
 * Time: 14:17
 */

namespace inhere\library\utils;

use inhere\library\log\FileLogger;

/**
 * simple file logger handler
 * Class LiteLogger
 * @package inhere\library\utils
 * @deprecated please use `inhere\library\log\FileLogger` instead it.
 * ```
 * $config = [...];
 * $logger = LiteLogger::make($config);
 * $logger->info(...);
 * $logger->debug(...);
 * ......
 * // Notice: must call LiteLogger::flushAll() on application run end.
 * LiteLogger::flushAll();
 * ```
 */
class LiteLogger extends FileLogger
{
}
