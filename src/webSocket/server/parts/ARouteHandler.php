<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/27 0027
 * Time: 22:51
 */

namespace inhere\librarys\webSocket\server\parts;

use inhere\librarys\webSocket\server\Application;

/**
 * Class ARouteHandler
 * @package inhere\librarys\webSocket\server\parts
 */
abstract class ARouteHandler implements IRouteHandler
{
    // custom ws handler position
    const OPEN_HANDLER = 0;
    const MESSAGE_HANDLER = 1;
    const CLOSE_HANDLER = 2;
    const ERROR_HANDLER = 3;

    const DATA_JSON = 'json';
    const DATA_TEXT = 'text';

    /**
     * @var Application
     */
    private $app;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var IDataParser
     */
    private $_dataParser;

    /**
     * @var array
     * [
     *   cmd => callback,
     * ]
     */
    protected $cmdHandlers = [];

    // default command name, if request data not define command name.
    const DEFAULT_CMD = 'index';
    const DEFAULT_CMD_SUFFIX = 'Command';

    // default command suffix
    public $cmdSuffix = 'Command';

    /**
     * @var array
     */
    protected $options = [
        // request and response data type: json text
        'dataType' => 'json',

        // It is valid when `'dataType' => 'json'`, allow: 1 raw 2 array 3 object
        'jsonParseTo'    => IDataParser::JSON_TO_ARRAY,

        'defaultCmd'     => self::DEFAULT_CMD,

        // allowed request Origins. e.g: [ 'localhost', 'site.com' ]
        'allowedOrigins' => [],
    ];

    /**
     * ARouteHandler constructor.
     * @param array $options
     * @param IDataParser|null $dataParser
     */
    public function __construct(array $options = [], IDataParser $dataParser = null)
    {
        $this->setOptions($options);

        $this->_dataParser = $dataParser;
    }

    /**
     * @inheritdoc
     */
    public function onHandshake(Request $request, Response $response)
    {
        $this->log('A new user connection. join the path(route): ' . $request->getPath());
    }

    /**
     * @inheritdoc
     */
    public function onOpen(int $id)
    {
        $this->log('A new user open connection. route path: ' . $this->request->getPath());
    }

    /**
     * @inheritdoc
     */
    public function onClose(int $id)
    {
        $this->log('A user has been disconnected. route path: ' . $this->request->getPath());
    }

    /**
     * @inheritdoc
     */
    public function onError(Application $app, string $msg)
    {
        $this->log('Accepts a connection on a socket error, when request : ' . $msg, 'error');
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// handle request command
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * parse and dispatch command
     * @param string $data
     * @param int $id
     * @return mixed
     */
    public function dispatch(string $data, int $id)
    {
        $route = $this->request->path;

        // parse: get command and real data
        if ( $results = $this->getDataParser()->parse($data, $id, $this->app) ) {
            [$command, $data] = $results;
            $command = $command ?: $this->getOption('defaultCmd') ?? self::DEFAULT_CMD;
            $this->log("The #{$id} request command is: $command in route [$route]");
        } else {
            $command = self::PARSE_ERROR;
            $this->log("The #{$id} request data parse failed in route [$route]! Data: $data", 'error');
        }

        // dispatch command

        // is a outside command `by add()`
        if ( $this->isCommandName($command) ) {
            $handler = $this->getCmdHandler($command);
            return call_user_func_array($handler, [$data, $id, $this]);
        }

        $suffix = 'Command';
        $method = $command . $suffix;

        // not found
        if ( !method_exists( $this, $method) ) {
            $this->log("The #{$id} request command: $command not found, run 'notFound' command", 'notice');
            $method = self::NOT_FOUND . $suffix;
        }

        return $this->$method($data, $id);
    }

    /**
     * register a command handler
     * @param string $command
     * @param callable $handler
     * @return IRouteHandler
     */
    public function command(string $command, callable $handler)
    {
        return $this->add($command, $handler);
    }
    public function add(string $command, $handler)
    {
        if ( $command && preg_match('/^[a-z][\w-]+$/', $command)) {
            $this->cmdHandlers[$command] = $handler;
        }

        return $this;
    }

    /**
     * @param $data
     * @param int $id
     * @return int
     */
    public function pingCommand(string $data, int $id)
    {
        return $this->target($id)->respond($data . '+PONG');
    }

    /**
     * @param $data
     * @param int $id
     * @return int
     */
    public function parseErrorCommand(string $data, int $id)
    {
        return $this->target($id)->respond($data, 'you send data format is error!', -200);
    }

    /**
     * @param string $command
     * @param int $id
     * @return int
     */
    public function notFoundCommand(string $command, int $id)
    {
        $msg = "You request command [$command] not found in the route [{$this->request->getPath()}].";

        return $this->target($id)->respond('', $msg, -404);
    }

    /**
     * @param string $command
     * @return bool
     */
    public function isCommandName(string $command): bool
    {
        return array_key_exists($command, $this->cmdHandlers);
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        return array_keys($this->cmdHandlers);
    }

    /**
     * @param string $command
     * @return callable|null
     */
    public function getCmdHandler(string $command)//: ?callable
    {
        if ( !$this->isCommandName($command) ) {
            return null;
        }

        return $this->cmdHandlers[$command];
    }

    /**
     * @return array
     */
    public function getCmdHandlers(): array
    {
        return $this->cmdHandlers;
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// helper method
    /////////////////////////////////////////////////////////////////////////////////////////

    public function target($ids)
    {
        $this->app->target($ids);

        return $this;
    }

    public function respond($data, string $msg = 'success', int $code = 0): int
    {
        return $this->app->respond($data, $msg, $code);
    }

    public function log(string $message, string $type = 'info', array $data = [])
    {
        $this->app->log($message, $type, $data);
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// getter/setter method
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function getOption(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @return IDataParser
     */
    public function getDataParser(): IDataParser
    {
        // if not set, use default parser.
        return $this->_dataParser ?: new ComplexDataParser();
    }

    /**
     * @param IDataParser $dataParser
     */
    public function setDataParser(IDataParser $dataParser)
    {
        $this->_dataParser = $dataParser;
    }

    /**
     * @return Application
     */
    public function getApp(): Application
    {
        return $this->app;
    }

    /**
     * @param Application $app
     */
    public function setApp(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
}
