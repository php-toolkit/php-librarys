<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 下午9:08
 */

namespace inhere\library\gearman;

/*
 usage:

GMLogger::create()->debug('A debug message');
GMLogger::create()->log('A info message');
GMLogger::create()->warn('A warning');
GMLogger::create()->error('A serious problem');
GMLogger::create()->error('A error', array('an array?', 'interesting...', 'structured log messages!'));
 */

use GearmanClient;

/**
 * Class GMLogger
 * @package inhere\library\gearman
 */
class GMLogger
{
    /**
     * @var array
     */
    private static $instances = [];

    /**
     * Fetch (and create if needed) an instance of this logger.
     *
     * @param string $server
     * @param int $port
     * @param string $queue
     * @return self
     */
    public static function create($server = '127.0.0.1', $port = 4730, $queue = 'log')
    {
        $hash = $queue . $server . $port;

        if (!array_key_exists($hash, self::$instances)) {
            self::$instances[$hash] = new self($queue, $server, $port);
        }

        return self::$instances[$hash];
    }

    /**
     * @var GearmanClient
     */
    private $client;

    /**
     * @var string
     */
    private $queue;

    /**
     * GMLogger constructor.
     * @param $queue
     * @param $server
     * @param $port
     */
    public function __construct($queue, $server, $port)
    {
        $this->queue = $queue;

        $this->client = new GearmanClient();
        $this->client->addServer($server, $port);
    }

    /**
     * Log a message
     *
     * @param mixed $message
     * @param array $data
     * @param string $level
     */
    public function log($message, array $data = [], $level = 'INFO')
    {
        $this->client->doBackground($this->queue, json_encode([
            'level'   => strtoupper($level),
            'message' => $message,
            'data'    => $data,
            'time'    => time(),
            'host'    => gethostname(),
        ]));
    }

    /**
     * Log a warning
     * @param mixed $message
     * @param array $data
     */
    public function debug($message, array $data = [])
    {
        $this->log($message, $data, 'DEBUG');
    }

    /**
     * Log a warning
     * @param mixed $message
     * @param array $data
     */
    public function warn($message, array $data = [])
    {
        $this->log($message, $data, 'WARN');
    }

    /**
     * Log an error
     * @param mixed $message
     * @param array $data
     */
    public function error($message, array $data = [])
    {
        $this->log($message, $data, 'ERROR');
    }

}