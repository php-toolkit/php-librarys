<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/1
 * Time: 下午9:03
 */

namespace inhere\library\process\ipc;

/**
 * class UnixDomain
 * @package inhere\library\process\ipc
 */
class UnixDomain extends BaseIpc
{
    protected function create()
    {
        // $socket = socket_create(AF_UNIX, SOCK_STREAM, SOL_TCP);
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, SOL_UDP);
        $bindRes = socket_bind($socket, $this->socketfile);
        $listenRes = socket_listen($socket, 9999);
    }
}
