<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-04-27
 * Time: 9:56
 */

namespace inhere\library\gearman;
use inhere\exceptions\UnknownMethodException;
use inhere\library\helpers\ObjectHelper;

/**
 * Class JobClient
 * @package inhere\library\gearman
 *
 * @method string doHigh($function_name, $workload, $unique = null)
 * @method string doNormal($function_name, $workload, $unique = null)
 * @method string doLow($function_name, $workload, $unique = null)
 *
 * @method string doHighBackground($function_name, $workload, $unique = null)
 * @method string doBackground($function_name, $workload, $unique = null)
 * @method string doLowBackground($function_name, $workload, $unique = null)
 *
 * @method array jobStatus($job_handle)
 */
class JobClient
{
    /**
     * @var bool
     */
    public $enable = true;

    /**
     * @var \GearmanClient
     */
    private $client;

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
     * @var array
     */
    private static $jobMethods = [
        'doHigh', 'doNormal', 'doLow',
    ];

    /**
     * @var array
     */
    private static $backMethods = [
        'doBackground', 'doHighBackground', 'doLowBackground',
    ];

    /**
     * JobClient constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        ObjectHelper::loadAttrs($this, $config);

        $this->init();
    }

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
     * @return mixed
     */
    public function addJob($funcName, $workload, $unique = null, $clientMethod = 'doBackground')
    {
        if (!$this->enable) {
            return null;
        }

        if (is_array($workload) || is_object($workload)) {
            if ($this->serialize === 'json') {
                $workload = json_encode($workload);
            } else { //  $this->serialize === 'php'
                $workload = serialize($workload);
            }
        }

        $ret = $this->client->$clientMethod($funcName, $workload, $unique);

        if (in_array($clientMethod, self::$jobMethods, true)) {
            return $ret;
        }

        $stat = $this->client->jobStatus($ret);

        return !$stat[0];// bool
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

        if (in_array($name, self::$jobMethods + self::$backMethods, true)) {
            return $this->addJob(
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
