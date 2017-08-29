<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-29
 * Time: 13:55
 */

namespace inhere\library\components;

/**
 * Interface MiddlewareInterface
 * @package inhere\library\components
 */
interface MiddlewareInterface
{
    public function handle($request, \Closure $next);

    public function process($request, $response, \Closure $next);
}