# 一些socket知识


## socket 函数

doc: http://php.net/manual/zh/ref.sockets.php

## socket_create

创建一个套接字（通讯节点）

``` 
resource socket_create ( int $domain , int $type , int $protocol )
```

创建并返回一个套接字，也称作一个通讯节点。
一个典型的网络连接由 2 个套接字构成，一个运行在客户端，另一个运行在服务器端。

eg:

``` 
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
```