<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/24 0024
 * Time: 23:13
 */

namespace inhere\librarys\webSocket;

use inhere\librarys\webSocket\parts\Request;
use inhere\librarys\webSocket\parts\IRouteHandler;
use inhere\librarys\webSocket\parts\RootHandler;
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
    // custom ws handler position
    const OPEN_HANDLER = 0;
    const MESSAGE_HANDLER = 1;
    const CLOSE_HANDLER = 2;
    const ERROR_HANDLER = 3;
    // route not found
    const ROUTE_NOT_FOUND = 4;

    const PING = 'ping';
    const NOT_FOUND = 'notFound';
    const PARSE_ERROR = 'error';

    const DATA_JSON = 'json';
    const DATA_TEXT = 'text';

    /**
     * default is '0.0.0.0'
     * @var string
     */
    private $host;
    /**
     * default is 8080
     * @var int
     */
    private $port;

    /**
     * @var WebSocketServer
     */
    private $ws;

    /**
     * save four custom ws handler
     * @var \SplFixedArray
     */
    private $wsHandlers;

    /**
     * @var array
     */
    protected $options = [
        // request and response data type: json text
        'dataType' => 'json',
    ];

    /**
     * @var IRouteHandler[]
     * [
     *  // path => IRouteHandler,
     *  '/'  => RootHandler,
     * ]
     */
    private $routesHandlers;

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
        $this->host = $host ?: '0.0.0.0';
        $this->port = $port ?: 8080;
        $this->options = array_merge($this->options, $options);

        $this->wsHandlers = new \SplFixedArray(5);

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
        $this->ws->on(WebSocketServer::ON_HANDSHAKE, [$this, 'handleHandshake']);
        $this->ws->on(WebSocketServer::ON_OPEN, [$this, 'handleOpen']);
        $this->ws->on(WebSocketServer::ON_MESSAGE, [$this, 'handleMessage']);
        $this->ws->on(WebSocketServer::ON_CLOSE, [$this, 'handleClose']);
        $this->ws->on(WebSocketServer::ON_ERROR, [$this, 'handleError']);

        // if not register route, add root path route handler
        if ( 0 === count($this->routesHandlers) ) {
            $this->route('/', new RootHandler);
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
     * @param string          $rawData
     * @param resource        $socket
     * @param int             $index
     * @return bool
     */
    public function handleHandshake(WebSocketServer $ws, string $rawData, $socket, int $index)
    {
        $this->log( "Raw request data: \n". $rawData);
        $this->request = $request = Request::makeByParseData($rawData);
        $this->log('Parsed request data:');
        var_dump($request);

        // route not exists, response 404 error
        if ( !$rHandler = $this->activeRouteHandler($request, $index) ) {
            $resp = $ws->buildResponse(404, 'Not Found', 'You request path not found!', [
                // headers
                'Connection' => 'close',
            ]);

            $ws->writeTo($socket, $resp);

            return false;
        }

        $rHandler->onHandshake($request);

        return true;
    }

    /**
     * @param WebSocketServer $ws
     * @param string $rawData
     * @param int $index
     */
    public function handleOpen(WebSocketServer $ws, string $rawData, int $index)
    {
        $this->log('A new user connection. Now, connected user count: ' . $ws->count());
        // $this->log("SERVER Data: \n" . var_export($_SERVER, 1), 'info');

        if ( $openHandler = $this->wsHandlers[self::OPEN_HANDLER] ) {
            // $openHandler($request, $this);
            $openHandler($ws, $this);
        }

        $this->getRouteHandler()->onClose($this->request);

    }

    /**
     * @param WebSocketServer $ws
     */
    public function handleClose(WebSocketServer $ws)
    {
        $this->log('A user disconnected. Now, connected user count: ' . $ws->count());

        if ( $closeHandler = $this->wsHandlers[self::CLOSE_HANDLER] ) {
            $closeHandler($ws, $this);
        }

        $this->getRouteHandler()->onClose($this->request);
    }

    /**
     * @param WebSocketServer $ws
     * @param string $msg
     */
    public function handleError(string $msg, WebSocketServer $ws)
    {
        $this->log('Accepts a connection on a socket error: ' . $msg, 'error');

        if ( $errHandler = $this->wsHandlers[self::ERROR_HANDLER] ) {
            $errHandler($ws, $this);
        }
    }

    /**
     * @param string          $data
     * @param WebSocketServer $ws
     * @param int             $index
     */
    public function handleMessage(WebSocketServer $ws, string $data, int $index)
    {
        $goon = true;
        $this->request->setBody($data);
        $this->log("Received user [$index] sent message. MESSAGE: $data, LENGTH: " . mb_strlen($data));

        // call custom message handler
        if ( $msgHandler = $this->wsHandlers[self::MESSAGE_HANDLER] ) {
            $goon = $msgHandler($ws, $this);
        }

        // go on handle
        if ( false !== $goon ) {
            $rHandler = $this->getRouteHandler();
            $result = $rHandler->dispatch($data, $index);

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
        $this->wsHandlers[self::OPEN_HANDLER] = $openHandler;
    }

    /**
     * @param callable $closeHandler
     */
    public function onClose(callable $closeHandler)
    {
        $this->wsHandlers[self::CLOSE_HANDLER] = $closeHandler;
    }

    /**
     * @param callable $errorHandler
     */
    public function onError(callable $errorHandler)
    {
        $this->wsHandlers[self::ERROR_HANDLER] = $errorHandler;
    }

    /**
     * @param callable $messageHandler
     */
    public function onMessage(callable $messageHandler)
    {
        $this->wsHandlers[self::MESSAGE_HANDLER] = $messageHandler;
    }

    public function onRouteNotFound($index, $path)
    {
        $this->target($index)->respond('', "you request route path [$path] not found!");

        $this->ws->close($index);

        return null;
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// handle request route
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * register a route and it's handler
     * @param string        $path           route path
     * @param IRouteHandler $routeHandler   the route path handler
     * @param bool          $replace        replace exists's route
     * @return IRouteHandler
     */
    public function route(string $path, IRouteHandler $routeHandler, $replace = false)
    {
        $path = trim($path) ?: '/';
        $pattern = '/^\/[a-zA-Z][\w-]+$/';

        if ( $path !== '/' && preg_match($pattern, $path) ) {
            throw new \InvalidArgumentException("The route path format must be match: $pattern");
        }

        if ( $this->hasRoute($path) && !$replace ) {
            throw new \InvalidArgumentException("The route path [$path] have been registered!");
        }

        $this->routesHandlers[$path] = $routeHandler;

        return $routeHandler;
    }

    /**
     * @param Request $request
     * @param int $index
     * @return IRouteHandler|null
     */
    protected function activeRouteHandler(Request $request, int $index)
    {
        $path = $request->getPath();

        if ( !$this->hasRoute($path) ) {
            $this->log("The route handler not exists for the path: $path", 'error');

            // call custom route-not-found handler
            if ( $rnfHandler = $this->wsHandlers[self::MESSAGE_HANDLER] ) {
                return $rnfHandler($index, $path, $this);
            }

            return $this->onRouteNotFound($index, $path);
        }

        $rHandler = $this->routesHandlers[$path];
        $rHandler->setApp($this);
        $rHandler->setRequest($request);

        return $rHandler;
    }

    /**
     * @param string $path
     * @return IRouteHandler
     */
    public function getRouteHandler(string $path = ''): IRouteHandler
    {
        $path = $path ?: $this->request->getPath();

        if ( !$this->hasRoute($path) ) {
            throw new \RuntimeException("The route handler not exists for the path: $path");
        }

        return $this->routesHandlers[$path];
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

    private $sender;
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
     * @param int|array $indexes
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
     * @param mixed $data
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

            if ( $data && is_array($data) ) {
                $data =json_encode($data);
            }

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

    public function hasRoute($path)
    {
        return isset($this->routesHandlers[$path]);
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return array_keys($this->routesHandlers);
    }

    /**
     * @return array
     */
    public function getRoutesHandlers(): array
    {
        return $this->routesHandlers;
    }

    /**
     * @param array $routesHandlers
     */
    public function setRoutesHandlers(array $routesHandlers)
    {
        foreach ($routesHandlers as $route => $handler) {
            $this->route($route, $handler);
        }
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

        $this->print("[$date] [$type] $message " . ( $data ? json_encode($data) : '' ) );
    }

    /**
     * @param mixed $messages
     * @param bool $nl
     * @param null|int $exit
     */
    public function print($messages, $nl = true, $exit = null)
    {
        $text = is_array($messages) ? implode(($nl ? "\n" : ''), $messages) : $messages;

        fwrite(\STDOUT, $text . ($nl ? "\n" : ''));

        if ( $exit !== null ) {
            exit((int)$exit);
        }
    }
}
