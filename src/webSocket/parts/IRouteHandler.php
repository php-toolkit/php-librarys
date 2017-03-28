<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/26 0026
 * Time: 15:35
 */

namespace inhere\librarys\webSocket\parts;
use inhere\librarys\webSocket\Application;

/**
 * Interface IRouteHandler
 * @package inhere\librarys\webSocket\parts
 */
interface IRouteHandler
{
    /**
     * @param Request $request
     */
    public function onHandshake(Request $request);

    /**
     * @param Request $request
     */
    public function onOpen(Request $request);

    /**
     * @param Request $request
     */
    public function onClose(Request $request);

    /**
     * @param Application $app
     * @param string $msg
     */
    public function onError(Application $app, string $msg);

    /**
     * @param string $data
     * @param int $index
     * @return mixed
     */
    public function dispatch(string $data, int $index);

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
