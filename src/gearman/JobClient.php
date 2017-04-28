<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-04-27
 * Time: 9:56
 */

namespace inhere\library\gearman;
use inhere\exceptions\UnknownMethodException;

/**
 * Class JobClient
 * @package inhere\library\gearman
 *
 * @method string doHighBackground($function_name, $workload, $unique = null)
 * @method string doBackground($function_name, $workload, $unique = null)
 * @method string doLowBackground($function_name, $workload, $unique = null)
 * @method array jobStatus($job_handle)
 */
class JobClient
{
    /**
     * @var bool
     */
    public $enable = true;

    /**
     * allow 'json','php'
     * @var string
     */
    public $serialize = 'json';
    
    /**
     * @var array|string
     * [
     *  '10.0.0.1', // use default port 4730
     *  '10.0.0.2:7003'
     * ]
     */
    public $servers = [];

    /**
     * @var \GearmanClient
     */
    private $client;

    /**
     * init
     */
    public function init()
    {
        if (!$this->enable) {
            return false;
        }

        $client = new \GearmanClient();

        if ($servers = implode(',', (array)$this->servers)) {
            $client->addServers($servers);
        } else {
            $client->addServer();
        }

        $this->enable = true;
        $this->client = $client;

        return true;
    }

    /**
     * @param string $funcName
     * @param string $workload
     * @param null $unique
     * @param string $clientMethod
     * @return bool
     */
    public function doJob($funcName, $workload, $unique = null, $clientMethod = 'doBackground')
    {
        if (!$this->enable) {
            return null;
        }

        if ($this->serialize === 'json') {
            $workload = json_encode($workload);
        } elseif ($this->serialize === 'php') {
            $workload = serialize($workload);
        }

        $jobHandle = $this->client->$clientMethod($funcName, $workload, $unique);
        $stat = $this->client->jobStatus($jobHandle);

        return !$stat[0];
    }

    /**
     * @return array|string
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * @param array|string $servers
     */
    public function setServers($servers)
    {
        $this->servers = $servers;
    }

    /**
     * @return \GearmanClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param \GearmanClient $client
     */
    public function setClient(\GearmanClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $name
     * @param array $params
     * @return mixed
     * @throws UnknownMethodException
     */
    public function __call($name, $params)
    {
        if (!$this->enable) {
            return null;
        }

        if (in_array($name, ['doBackground', 'doHighBackground', 'doLowBackground'])) {
            return $this->doJob(
                $params[0],
                isset($params[1]) ? $params[1] : '',
                isset($params[2]) ? $params[2] : null,
                $name
            );
        }

        if (method_exists($this->client, $name)) {
            return call_user_func_array([$this->client, $name], $params);
        }

        throw new UnknownMethodException('Calling unknown method: ' . get_class($this) . "::$name()");
    }
}
