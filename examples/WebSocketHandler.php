<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/24 0024
 * Time: 23:13
 */

/**
 * Class WebSocketHandler
 */
class WebSocketHandler
{
    /**
     * @param WebSocket $ws
     */
    public function onConnect(WebSocket $ws)
    {
        $this->sendToAll($ws->getAcceptedCount(), 'num', $ws);
    }

    /**
     * @param $msg
     * @param $index
     * @param WebSocket $ws
     */
    public function onMessage($msg, $index, WebSocket $ws)
    {
        $data = json_encode(array(
            'text' => $msg,
            'user' => $index,
        ));

        $ws->log("Receive: Received user [$index] sent message: $msg");

        $this->sendToAll($data, 'text', $ws);
    }

    /**
     * @param WebSocket $ws
     */
    public function onClose(WebSocket $ws)
    {
        $this->sendToAll($ws->getAcceptedCount(), 'num', $ws);
    }

    /**
     * @param $data
     * @param $type
     * @param WebSocket $ws
     */
    public function sendToAll($data, $type, WebSocket $ws)
    {
        $res = array(
            'msg' => $data,
            'type' => $type,
        );

        $res = json_encode($res);
        $res = $ws->frame($res);

        foreach ($ws->accept as $key => $value) {
            socket_write($value, $res, strlen($res));
        }
    }
}
