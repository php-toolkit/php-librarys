<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-29
 * Time: 14:12
 */

namespace Inhere\Library\Components;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface HttPMiddlewareInterface
 * @package Inhere\Library\Components
 */
interface HttpMiddlewareInterface
{
    /**
     * Process an incoming request and/or response.
     * ```php
     * return $next($request, $response);
     * ```
     * Middleware MUST return a response, or the result of $next (which should return a response).
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next);
//    public function handle($request, $response, \Closure $next);
//    public function process($request, $response, \Closure $next);
}
