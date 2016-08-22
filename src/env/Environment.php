<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/2/27
 * Use : ...
 * File: Environment.php
 */

namespace inhere\librarys\env;

use inhere\librarys\StdBase;

/**
 * 环境信息
 * Class Environment
 * @package inhere\librarys\env
 */
class Environment extends StdBase
{
    /**
     * @var Server
     */
    protected $server;

    /**
     * @var Client
     */
    protected $client;

    public static function make(Server $server=null, Client $client=null)
    {
        return new static($server, $client);
    }

    public function __construct(Server $server=null, Client $client=null)
    {
        $this->server = $server ? : new Server;
        $this->client = $client ? : new Client;
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param Server $server
     */
    public function setServer(Server $server)
    {
        $this->server = $server;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

}// end class Environment