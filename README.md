# php-librarys

php的一些有用的基础工具库实现。 

包含： 静态资源加载、认证、命令行应用、数据收集器、依赖注入、环境信息、事件调度、文件系统、html元素、http请求库、进程控制、队列、任务管理、各种帮助类库

> 这是基于 php7 的分支。 如果你使用的是 php5, 请查看 [php5](https://github.com/inhere/php-librarys/tree/php5) 分支。

## 安装

- composer命令行

```
composer require inhere/library
```

- 通过composer.json

在 "require" 下添加 

```json
"inhere/library": "dev-master" // 推荐
// OR 
"inhere/library": "^2.0"
```

然后执行: `composer update`

- 直接拉取

```
git clone https://github.com/inhere/php-librarys.git
```

## 工具库列表

基础库：

- `inhere\library\collections` 数据收集器. (数据收集/全局配置/语言包处理类)
- `inhere\library\di` 依赖注入容器，提供服务管理 
- `inhere\library\files` 文件系统操作(文件(夹)读取，检查，创建)；`json ini yml` 文件的扩展解析
- `inhere\library\helpers` 辅助类库(`string array object date url curl php format json`)
- `inhere\library\traits` 一些常用的traits(`ArrayAccess` `GetterSetterAccess` `SimpleAlias` `SimpleConfig` `SimpleEvent`)
- `inhere\library\utils` 一些独立的工具类(`autoloader logger`)
- `functions.php` 一些有用的函数

已迁移至 `inhere/library-plus`:

- `inhere\library\env` 环境信息收集, `Server`: 服务端信息. `Client`: 客户端信息 
- `inhere\library\event` 事件调度器 
- `inhere\library\files` 文件系统操作(文件(夹)读取，检查，创建)；文件上传/下载，图片处理(缩略图/水印)，图片验证码生成 
- `inhere\library\asset` 资源(css,js)管理,加载,发布 
- `inhere\library\html` html 元素创建, dom 创建
- `inhere\library\network` network 工具库(`telnet`)
- `inhere\library\utils` 一些独立的工具类(`memcache`)

[Document](doc/document.md)

## 已独立的工具库

### `inhere/console` [github](https://github.com/inhere/php-console) [git@osc](https://git.oschina.net/inhere/php-console)

轻量级的命令行应用，工具库

### `inhere/queue` [github](https://github.com/inhere/php-queue) [git@osc](https://git.oschina.net/inhere/php-queue)

php的队列使用包装(`DbQueue` `LevelDbQueue` `PhpQueue` `RedisQueue` `ShmQueue` `SSDBQueue` `SysVQueue`)

### `inhere/http` [github](https://github.com/inhere/php-http) [git@osc](https://git.oschina.net/inhere/php-http)

http 工具库(`request` 请求 `response` 响应 `curl` curl请求库，有简洁、完整和并发请求三个版本的类)

命令行应用 - 控制台的交互.

## 我的其他项目

### srouter [github](https://github.com/inhere/php-srouter)  [git@osc](https://git.oschina.net/inhere/php-srouter)
 
 轻量级但功能丰富的单文件路由.
 
### php-gearman-manager [github](https://github.com/inhere/php-gearman-manager) [git@osc](https://git.oschina.net/inhere/php-gearman-manager)

php 的 gearman workers 管理工具。同时启动并管理多个gearman worker，并会监控运行状态。可以自定义worker数量，也可以针对job设置worker数量。还可以让worker专注指定的job

### php-server [github](https://github.com/inhere/php-server)  [git@osc](https://git.oschina.net/inhere/php-server)

基于 swoole 的server实现
