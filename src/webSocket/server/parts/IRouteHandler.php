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
 * Interface IRouteHandler
 * @package inhere\librarys\webSocket\server\parts
 */
interface IRouteHandler
{
    const PING = 'ping';
    const NOT_FOUND = 'notFound';
    const PARSE_ERROR = 'error';

    /**
     * @param Request $request
     * @param Response $response
     */
    public function onHandshake(Request $request, Response $response);

    /**
     * @param int $id
     */
    public function onOpen(int $id);

    /**
     * @param int $id
     */
    public function onClose(int $id);

    /**
     * @param Application $app
     * @param string $msg
     */
    public function onError(Application $app, string $msg);

    /**
     * @param string $data
     * @param int $id
     * @return mixed
     */
    public function dispatch(string $data, int $id);

    /**
     * @param string $command
     * @param $handler
     * @return static
     */
    public function add(string $command, $handler);

    /**
     * @param Application $app
     */
    public function setApp(Application $app);

    /**
     * @return Application
     */
    public function getApp(): Application;

    /**
     * @return Request
     */
    public function getRequest(): Request;

    /**
     * @param Request $request
     */
    public function setRequest(Request $request);
}
