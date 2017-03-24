<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/10/10
 * Time: 下午8:29
 */

namespace inhere\librarys\server;

/**
 * Class WebSocket
 */
class WebSocket
{
    const ON_CONNECT = 'connect';
    // const ON_RECEIVE = 'receive';
    const ON_MESSAGE = 'message';
    const ON_CLOSE = 'close';

    /**
     * @var resource
     */
    private $socket;

    private $host = '127.0.0.1';
    private $port = 8900;

    /**
     * settings
     * @var array
     */
    protected $settings = [
        'log_file' => '',
        'max_conn' => 25,
    ];

    /**
     * settings
     * @var array
     */
    protected $supportEvents = [
        'connect', 'message', 'close'
    ];

    /**
     * 连接的客户端
     * @var array
     */
    public  $accept = [];

    /**
     * 循环连接池
     * @var array
     */
    private $cycle = [];

    private $isHand = [];


    /**
     * 接受三个回调函数，分别在新用户连接、有消息到达、用户断开时触发
     * connect, message, close
     * @var array
     */
    private $callbacks = [];

    /**
     * WebSocket constructor.
     * @param string $host
     * @param int $port
     * @param array $settings
     */
    public function __construct(string $host = '', $port = 8900, array $settings = array())
    {
        $this->host = $host ?: '127.0.0.1';
        $this->port = $port ?: 8900;

        $this->setSettings($settings);
    }

    protected function beforeStart()
    {
        if ( !$this->callbacks || count($this->callbacks) < 3 ) {
            $sup = implode(',', $this->supportEvents);
            $this->write('[ERROR] Please register event handle callback before start. supported events: ' . $sup, true, -500);
        }
    }

    // 挂起socket
    public function start()
    {
        $this->beforeStart();

        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        // 允许使用本地地址
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, TRUE);
        socket_bind($this->socket, $this->host, $this->port);

        $this->log("Create WebSocket server on {$this->host}:{$this->port}");

        // 最多 $this->maxUser 个人连接，超过的客户端连接会返回 WSAECONNREFUSED 错误
        socket_listen($this->socket, $this->getSetting('max_conn'));

        while(TRUE) {
            $this->cycle = $this->accept;
            $this->cycle[] = $this->socket;

            //阻塞用，有新连接时才会结束
            socket_select($this->cycle, $write, $except, null);

            foreach ($this->cycle as $k => $v) {
                if($v === $this->socket) {
                    if (($accept = socket_accept($v)) < 0) {
                        continue;
                    }
                    // 如果请求来自监听端口那个套接字，则创建一个新的套接字用于通信
                    $this->addAccept($accept);
                    continue;
                }

                $index = array_search($v, $this->accept, true);

                if ($index === NULL) {
                    continue;
                }

                if (!@socket_recv($v, $data, 1024, 0) || !$data) {// 没消息的socket就跳过
                    $this->close($v);
                    continue;
                }

                if (!$this->isHand[$index]) {
                    $this->upgrade($v, $data, $index);

                    $this->log('A new user connection. Now, connected user count: ' . $this->getAcceptedCount());

                    // call connect handler
                    $this->call(self::ON_CONNECT, [$this]);
                    continue;
                }

                $data = $this->decode($data);

                $this->log("Received user [$index] sent message. MESSAGE: $data");

                // call on message handler
                $this->call(self::ON_MESSAGE, [$data, $index, $this]);
            }

            sleep(1);
        }
    }

    /**
     * @param $event
     * @param callable $cb
     * @return $this
     */
    public function on($event, callable $cb)
    {
        if ( !isset($this->callbacks[$event]) ) {
            $this->callbacks[$event] = $cb;
        }

        return $this;
    }

    /**
     * @param $event
     * @param array $args
     * @return mixed
     */
    protected function call($event, array $args = [])
    {
        if ( !isset($this->callbacks[$event]) || !($cb = $this->callbacks[$event]) ) {
            return null;
        }

        return call_user_func_array($cb, $args);
    }

    // 增加一个初次连接的用户
    private function addAccept($accept)
    {
        $this->accept[] = $accept;
        $index = array_keys($this->accept);
        $index = end($index);
        $this->isHand[$index] = false;
    }

    // 关闭一个连接
    private function close($accept)
    {
        $index = array_search($accept, $this->accept, true);
        socket_close($accept);

        unset($this->accept[$index], $this->isHand[$index]);

        $this->log('A user disconnected. Now, connected user count: ' . $this->getAcceptedCount());

        // call close handler
        $this->call(self::ON_CLOSE, [$this]);
    }

    // 响应升级协议
    private function upgrade($accept, $data, $index)
    {
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/",$data,$match)) {
            $key = base64_encode(sha1($match[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
            $upgrade  = "HTTP/1.1 101 Switching Protocol\r\n" .
                "Upgrade: websocket\r\n" .
                "Connection: Upgrade\r\n" .
                'Sec-WebSocket-Accept: ' . $key . "\r\n\r\n";  //必须以两个回车结尾

            socket_write($accept, $upgrade, strlen($upgrade));

            $this->isHand[$index] = TRUE;
        }
    }

    //体力活
    public function frame($s)
    {
        $a = str_split($s, 125);

        if (count($a) === 1){
            return "\x81" . chr(strlen($a[0])) . $a[0];
        }

        $ns = "";

        foreach ($a as $o){
            $ns .= "\x81" . chr(strlen($o)) . $o;
        }

        return $ns;
    }

    // 体力活
    public function decode($buffer)
    {
        /*$len = $masks = $data =*/ $decoded = null;
        $len = ord($buffer[1]) & 127;

        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } else if ($len === 127) {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        } else {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }

        $dataLen = strlen($data);
        for ($index = 0; $index < $dataLen; $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }

        return $decoded;
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

        $this->write("[$date] [$type] $message " . json_encode($data));
    }

    /**
     * @param $messages
     * @param bool $nl
     * @param null $exit
     */
    public function write($messages, $nl = true, $exit = null)
    {
        $text = is_array($messages) ? implode(($nl ? "\n" : ''), $messages) : $messages;

        fwrite(\STDOUT, $text . ($nl ? "\n" : ''));

        if ( $exit !== null ) {
            exit((int)$exit);
        }
    }

    /**
     * @return array
     */
    public function getAccept(): array
    {
        return $this->accept;
    }

    /**
     * @return int
     */
    public function getAcceptedCount(): int
    {
        return count($this->accept);
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
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings)
    {
        if ( $settings) {
            $this->settings = array_merge($this->settings, $settings);
        }
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $name, $default = null)
    {
        return $this->settings[$name] ?? $default;
    }
}
