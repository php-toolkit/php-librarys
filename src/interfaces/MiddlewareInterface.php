<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-29
 * Time: 13:55
 */

namespace inhere\library\interfaces;

/**
 * Interface MiddlewareInterface
 * @package inhere\library\components
 */
interface MiddlewareInterface
{
    // method 1
//    public function handle($request, \Closure $next);

    // method 2
//    public function process($request, $response, \Closure $next);

    // method 3
    public function __invoke(ContextInterface $ctx, $next);
}