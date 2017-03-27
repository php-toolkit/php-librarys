

## webSocket header example

### webSocket request header 

```
// parsed code
GET / HTTP/1.1
Host: 127.0.0.1:9501
Connection: Upgrade
Pragma: no-cache
Cache-Control: no-cache
Upgrade: websocket
Origin: http://localhost:63342
Sec-WebSocket-Version: 13
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3018.3 Safari/537.36
Accept-Encoding: gzip, deflate, sdch, br
Accept-Language: zh-CN,zh;q=0.8
Cookie: _ga=GA1.1.1542925283.1469426767
Sec-WebSocket-Key: Tak3+4p37S5EAltSHDxpTw==
Sec-WebSocket-Extensions: permessage-deflate; client_max_window_bits


// source code
GET ws://127.0.0.1:9501/ HTTP/1.1
Host: 127.0.0.1:9501
Connection: Upgrade
Pragma: no-cache
Cache-Control: no-cache
Upgrade: websocket
Origin: http://localhost:63342
Sec-WebSocket-Version: 13
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3018.3 Safari/537.36
Accept-Encoding: gzip, deflate, sdch, br
Accept-Language: zh-CN,zh;q=0.8
Cookie: _ga=GA1.1.1542925283.1469426767
Sec-WebSocket-Key: BmMdv63hr1D/eS7eTD59Vw==
Sec-WebSocket-Extensions: permessage-deflate; client_max_window_bits
```

### webSocket response header

```
// parsed code
Connection:Upgrade
Sec-WebSocket-Accept:BOVf/XCi92SSib4Ga+ltTsmHiWQ=
Upgrade:websocket

// source code
HTTP/1.1 101 Switching Protocol
Upgrade: websocket
Connection: Upgrade
Sec-WebSocket-Accept: BOVf/XCi92SSib4Ga+ltTsmHiWQ=
```
