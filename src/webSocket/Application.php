<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/24 0024
 * Time: 23:13
 */

namespace inhere\librarys\webSocket;

use inhere\librarys\webSocket\app\Request;
use inhere\librarys\webSocket\app\IRouteHandler;
use inhere\librarys\webSocket\server\WebSocketServer;

/**
 * Class Application
 *
 * 1.
 * ```
 * $app = new Application;
 *
 * // register command handler
 * $app->add('test', function () {
 *
 *     return 'hello';
 * });
 *
 * // start server
 * $app->run();
 * ```
 * 2.
 * ```
 * $app = new Application($host, $port);
 *
 * // register command handler
 * $app->add('test', function () {
 *
 *     return 'hello';
 * });
 *
 * // start server
 * $app->run();
 * ```
 */
class Application
{
    const PING = 'ping';
    const NOT_FOUND = 'notFound';
    const PARSE_ERROR = 'error';

    const DATA_JSON = 'json';
    const DATA_TEXT = 'text';

    //
    const JSON_TO_RAW = 1;
    const JSON_TO_ARRAY = 2;
    const JSON_TO_OBJECT = 3;

    // default cmd key in the request json data.
    const DEFAULT_CMD_KEY = 'cmd';

    // default command name, if request data not define command name.
    const DEFAULT_CMD = 'index';

    /**
     * @var WebSocketServer
     */
    private $ws;

    private $host = '0.0.0.0';
    private $port = 8080;

    private $openHandler;
    private $messageHandler;
    private $closeHandler;
    private $errorHandler;

    /**
     * @var callable
     */
    private $_dataParser;

    /**
     * @var array
     */
    protected $handlers = [];

    /**
     * @var array
     */
    protected $options = [
        // request and response data type: json text
        'dataType' => 'json',

        // It is valid when `'dataType' => 'json'`, allow: 1 raw 2 array 3 object
        'jsonParseTo'    => self::JSON_TO_ARRAY,

        'defaultCmd'     => self::DEFAULT_CMD,
        'cmdKey'         => self::DEFAULT_CMD_KEY,

        // allowed request Origins. e.g: [ 'localhost', 'site.com' ]
        'allowedOrigins' => [],
    ];

    protected $routes;

    /**
     * @var Request
     */
    private $request;

    /**
     * WebSocketServerHandler constructor.
     * @param string $host
     * @param int $port
     * @param array $options
     * @internal param null|WebSocketServer $ws
     */
    public function __construct(string $host = '0.0.0.0', $port = 8080, array $options = [])
    {
        $this->host = $host;
        $this->port = $port;
        $this->options = array_merge($this->options, $options);

        $this->routes = new \SplObjectStorage();
    }

    /**
     * run
     */
    public function run()
    {
        if (!$this->ws) {
            $this->ws = new WebSocketServer($this->host, $this->port);
        }

        // register events
        $this->ws->on(WebSocketServer::ON_OPEN, [$this, 'handleOpen']);
        $this->ws->on(WebSocketServer::ON_MESSAGE, [$this, 'handleMessage']);
        $this->ws->on(WebSocketServer::ON_CLOSE, [$this, 'handleClose']);

        $this->add('ping', [$this, 'pingHandler']);

        if (!$this->hasCommand('error')) {
            $this->add('error', [$this, 'parseErrorHandler']);
        }

        if (!$this->hasCommand('notFound')) {
            $this->add('notFound', [$this, 'notFoundHandler']);
        }

        $this->ws->start();
    }

    /*
    getopt($options, $longOpts)

    options 可能包含了以下元素：
    - 单独的字符（不接受值）
    - 后面跟随冒号的字符（此选项需要值）
    - 后面跟随两个冒号的字符（此选项的值可选）

    ```
    $shortOpts = "f:";  // Required value
    $shortOpts .= "v::"; // Optional value
    $shortOpts .= "abc"; // These options do not accept values

    $longOpts  = array(
        "required:",     // Required value
        "optional::",    // Optional value
        "option",        // No value
        "opt",           // No value
    );
    $options = getopt($shortOpts, $longOpts);
    ```
    */
    /**
     * parse cli Opt and Run
     */
    public function parseOptRun()
    {
        $opts = getopt('p::H::h', ['port::', 'host::', 'help']);

        if ( isset($opts['h']) || isset($opts['help']) ) {
            $help = <<<EOF
Start a webSocket server.  
  
Options:
  -H,--host  Setting the webSocket server host.(default:9501)
  -p,--port  Setting the webSocket server port.(default:127.0.0.1)
  -h,--help  Show help information
EOF;

            fwrite(\STDOUT, $help);
            exit(0);
        }

        $this->host = $opts['H'] ?? $opts['host'] ?? $this->host;
        $this->port = $opts['p'] ?? $opts['port'] ?? $this->port;

        $this->run();
    }

    /**
     * @param WebSocketServer $ws
     * @param string $rawData
     */
    public function handleOpen(WebSocketServer $ws, string $rawData)
    {
        $this->log('A new user connection. Now, connected user count: ' . $ws->count());
        // $this->log("SERVER Data: \n" . var_export($_SERVER, 1), 'info');
        $this->log( "Raw data: \n". $rawData);

        $this->request = Request::makeByParseData($rawData);
        // $this->log("Parsed data:\n" . var_export($this->request,1));

        if ( $openHandler = $this->openHandler ) {
            $openHandler($ws, $this);
        }
    }

    /**
     * @param WebSocketServer $ws
     */
    public function handleClose(WebSocketServer $ws)
    {
        $this->log('A user disconnected. Now, connected user count: ' . $ws->count());

        if ( $closeHandler = $this->closeHandler ) {
            $closeHandler($ws, $this);
        }
    }

    /**
     * @param WebSocketServer $ws
     * @param string $msg
     */
    public function handleError(string $msg, WebSocketServer $ws)
    {
        $this->log('Accepts a connection on a socket error: ' . $msg, 'error');

        if ( $closeHandler = $this->closeHandler ) {
            $closeHandler($ws, $this);
        }
    }

    /**
     * @param string $data
     * @param int $index
     * @param WebSocketServer $ws
     */
    public function handleMessage(string $data, WebSocketServer $ws, int $index)
    {
        $goon = true;
        $this->request->setBody($data);
        $this->log("Received user [$index] sent message. MESSAGE: $data, LENGTH: " . mb_strlen($data));

        // call custom message handler
        if ( $messageHandler = $this->messageHandler ) {
            $goon = $messageHandler($ws, $this);
        }

        // go on handle
        if ( false !== $goon ) {
            $result = $this->dispatch($data, $index);

            if ( $result && is_string($result) ) {
                $this->log("Response message: $result");
                $this->beforeSend($result);

                $ws->send($result, $index);
            }
        }
    }

    /**
     * @param callable $openHandler
     */
    public function onOpen(callable $openHandler)
    {
        $this->openHandler = $openHandler;
    }

    /**
     * @param callable $closeHandler
     */
    public function onClose(callable $closeHandler)
    {
        $this->closeHandler = $closeHandler;
    }

    /**
     * @param callable $errorHandler
     */
    public function onError(callable $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param callable $messageHandler
     */
    public function onMessage(callable $messageHandler)
    {
        $this->messageHandler = $messageHandler;
    }

    public function route($path, IRouteHandler $routeHandler)
    {
        // $path = trim($path, '/');
    }

    /**
     * parse and dispatch command
     * @param string $data
     * @param int $index
     * @return mixed
     */
    public function dispatch(string $data, int $index)
    {
        $dataParser = $this->getDataParser();

        // parse: get command and real data
        if ( $matches = $dataParser($data, $index, $this) ) {
            [$command, $data] = $matches;

            // not found
            if ( !$this->hasCommand($command) ) {
                $this->log("The #{$index} request command: $command not found");
                $data = $command;
                $command = self::NOT_FOUND;
            }
        } else {
            $command = self::PARSE_ERROR;
            $this->log("The #{$index} request data parse failed! Data: $data", 'error');
        }

        $handler = $this->getHandler($command);

        return call_user_func_array($handler, [$data, $index, $this]);
    }

    /**
     * register a command handler
     * @param string $command
     * @param callable $handler
     * @return self
     */
    public function register(string $command, callable $handler)
    {
        return $this->add($command, $handler);
    }
    public function add(string $command, $handler)
    {
        if ( $command && preg_match('/^[a-z][\w-]+$/', $command)) {
            $this->handlers[$command] = $handler;
        }

        return $this;
    }

    /**
     * @param $data
     * @param int $index
     * @return int
     */
    public function pingHandler(string $data, int $index)
    {
        return $this->target($index)->respond($data . '+PONG');
    }

    /**
     * @param $data
     * @param int $index
     * @return int
     */
    public function parseErrorHandler(string $data, int $index)
    {
        return $this->target($index)->respond($data, 'you send data format is error!', -200);
    }

    /**
     * @param string $command
     * @param int $index
     * @return int
     */
    public function notFoundHandler(string $command, int $index)
    {
        $msg = 'You request command [' . $command . '] not found.';

        return $this->target($index)->respond('', $msg, -404);
    }

    /**
     * @param string $data
     * @param string $msg
     * @param int $code
     * @return string
     */
    public function fmtJson($data, string $msg = 'success', int $code = 0): string
    {
        return json_encode([
            'data' => $data,
            'msg'  => $msg,
            'code' => (int)$code,
            'time' => time(),
        ]);
    }

    /**
     * @return callable
     */
    public function getDataParser(): callable
    {
        // if not set, use default parser.
        return $this->_dataParser ?: $this->complexDataParser();
    }

    public function complexDataParser()
    {
        return function ($data, $index) {
            // default format: [@command]data
            // eg:
            // [@test]hello
            // [@login]{"name":"john","pwd":123456}
            if (preg_match('/^\[@([\w-]+)\](.+)/', $data, $matches)) {
                array_shift($matches);
                [$command, $realData] = $matches;

                // access default command
            } else {
                $realData = $data;
                $command = $this->getOption('defaultCmd') ?: Application::DEFAULT_CMD;
            }

            $this->log("The #{$index} request command: $command, data: $realData");
            $to = $this->getOption('jsonParseTo') ?: Application::JSON_TO_RAW;

            if ( $this->isJsonType() && $to !== self::JSON_TO_RAW ) {
                $realData = json_decode(trim($realData), $to === self::JSON_TO_ARRAY);

                // parse error
                if ( json_last_error() > 0 ) {
                    // revert
                    $realData = trim($matches[2]);
                    $command = self::PARSE_ERROR;
                    $errMsg = json_last_error_msg();

                    $this->log("Request data parse to json failed! MSG: {$errMsg}, JSON: {$realData}", 'error');
                }
            }

            return [ $command, $realData ];
        };
    }

    public function jsonDataParser()
    {
        return function($data, $index, self $app) {
            // json parser
            $temp = $data;
            $to = $app->getOption('jsonParseTo') ?: Application::JSON_TO_RAW;
            $cmdKey = $app->getOption('cmdKey') ?: Application::DEFAULT_CMD_KEY;
            $command = $app->getOption('defaultCmd') ?: Application::DEFAULT_CMD;

            $this->log("The #{$index} request command: $command, data: $data");

            $data = json_decode(trim($data), $toAssoc = $to === Application::JSON_TO_ARRAY);

            // parse error
            if ( json_last_error() > 0 ) {
                // revert
                $data = $temp;
                $command = Application::PARSE_ERROR;
                $errMsg = json_last_error_msg();

                $app->log("The #{$index} request data parse to json failed! MSG: $errMsg Data: {$temp}", 'error');
            } elseif ($toAssoc) {
                if ( isset($data[$cmdKey]) && $data[$cmdKey]) {
                    $command = $data[$cmdKey];
                    unset($data[$cmdKey]);
                }
            } elseif ($to === Application::JSON_TO_OBJECT) {
                if ( isset($data->{$cmdKey}) && $data->{$cmdKey}) {
                    $command = $data->{$cmdKey};
                    unset($data->{$cmdKey});
                }
            } else {
                // revert
                $data = $temp;
            }

            return [$command, $data];
        };
    }

    /**
     * @param callable $dataParser
     */
    public function setDataParser(callable $dataParser)
    {
        $this->_dataParser = $dataParser;
    }

    /**
     * @param string $command
     * @return bool
     */
    public function hasCommand(string $command): bool
    {
        return array_key_exists($command, $this->handlers);
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        return array_keys($this->handlers);
    }

    /**
     * @param string $command
     * @return callable|null
     */
    public function getHandler(string $command)//: ?callable
    {
        if ( !$this->hasCommand($command) ) {
            return null;
        }

        return $this->handlers[$command];
    }

    /**
     * @return array
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

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
     * @return bool
     */
    public function isJsonType()
    {
        return $this->getOption('dataType') === self::DATA_JSON;
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// response
    /////////////////////////////////////////////////////////////////////////////////////////

    private $sender = null;
    private $targets = [];
    private $excepted = [];

    /**
     * @param null|int $index
     */
    public function bySender($index = null)
    {
        $this->sender = $index;
    }

    /**
     * @param $indexes
     * @return $this
     */
    public function target($indexes)
    {
        foreach ((array)$indexes as $index) {
            $this->targets[$index] = true;
        }

        return $this;
    }

    /**
     * @param $indexes
     * @return $this
     */
    public function except($indexes)
    {
        foreach ((array)$indexes as $index) {
            $this->excepted[$index] = true;
        }

        return $this;
    }

    /**
     * @param $data
     * @param string $msg
     * @param int $code
     * @return int
     */
    public function respond($data, string $msg = 'success', int $code = 0): int
    {
        // json
        if ( $this->isJsonType() ) {
            $data = json_encode([
                'data' => $data,
                'msg'  => $msg,
                'code' => (int)$code,
                'time' => time(),
            ]);
        } else {
            // text
            $data = $data ?: $msg;
        }

        $this->log("Response data: $data");
        $this->beforeSend($data);

        $status = $this->ws->send($data, $this->sender, $this->targets, $this->excepted);

        // reset data
        $this->sender = null;
        $this->targets = $this->excepted = [];

        return $status;
    }

    public function beforeSend($result)
    {
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// a very simple's user storage
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @var array
     */
    private $users = [];

    public function getUser($index)
    {
        return $this->users[$index] ?? null;
    }

    public function userLogin($index, $data)
    {

    }

    public function userLogout($index, $data)
    {

    }

    /**
     * @return WebSocketServer
     */
    public function getWs(): WebSocketServer
    {
        return $this->ws;
    }

    /**
     * @param WebSocketServer $ws
     */
    public function setWs(WebSocketServer $ws)
    {
        $this->ws = $ws;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param string $message
     * @param string $type
     * @param array $data
     */
    public function log(string $message, string $type = 'info', array $data = [])
    {
        $date = date('Y-m-d H:i:s');
        $type = strtoupper(trim($type));

        $this->write("[$date] [$type] $message " . ( $data ? json_encode($data) : '' ) );
    }

    /**
     * @param mixed $messages
     * @param bool $nl
     * @param null|int $exit
     */
    public function write($messages, $nl = true, $exit = null)
    {
        $text = is_array($messages) ? implode(($nl ? "\n" : ''), $messages) : $messages;

        fwrite(\STDOUT, $text . ($nl ? "\n" : ''));

        if ( $exit !== null ) {
            exit((int)$exit);
        }
    }
}
