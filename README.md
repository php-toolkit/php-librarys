# php librarys

php的一些有用的基础工具库实现和搜集 

包含： 静态资源加载、认证、命令行应用、数据收集器、依赖注入、环境信息、事件调度、文件系统、html元素、http请求库、进程控制、队列、任务管理、各种帮助类库

> 这是基于 php7 的分支。 如果你使用的是 php5, 请查看 [php5](https://github.com/inhere/php-librarys/tree/php5) 分支(不再维护)。

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
git clone https://git.oschina.net/inhere/php-librarys.git // git@osc
git clone https://github.com/inhere/php-librarys.git // github
```

## 工具库列表

- `Inhere\Library\Collections` 数据收集器. (数据收集/全局配置 管理)
- `Inhere\Library\Components` 一些有用的组件(有些可能是提供思路参考)
    - `AopProxy.php` 简单的 AOP 实现
    - `DataProxy.php` 简单的数据访问代理实现
    - `ErrorHandler.php` 错误处理
    - `Language.php` 提供语言管理,语言包处理类
    - `MemcacheClient.php` 一个简单的memcache(d)封装
    - `Pipeline.php` 一个简单的Pipeline实现封装
- **`Inhere\Library\DI`** 依赖注入容器，提供全局服务管理 
- `Inhere\Library\Files` 文件系统操作(文件(夹)读取，检查，创建)；
    - `FileFinder.php` 文件查找
    - `Parsers/*` 常用的 `json ini yml` 文件解析工具封装
- **`Inhere\Library\Helpers`** 涵盖了各个方面的辅助类库(`string array object date url curl php format json cli data env` ... ...)
- `Inhere\Library\Traits` 各种常用的traits(`ArrayAccess` `GetterSetterAccess` `SimpleAlias` `SimpleConfig` `SimpleEvent` ... ...)
- `Inhere\Library\Utils` 一些独立的工具类(`autoloader logger token uuid` ... ...)
- `Inhere\Library\Web` web相关工具类(`session cookie Environment ViewRenderer`)
- `functions.php` 一些有用的函数

### 已迁移至 [inhere/library-plus](https://github.com/inhere/php-library-plus) (主要是一些不常用的、测试性的功能库):

- `Inhere\LibraryPlus\auth` 用户认证管理，权限检查实现参考
- `Inhere\LibraryPlus\env` 环境信息收集, `Server`: 服务端信息. `Client`: 客户端信息 
- `Inhere\LibraryPlus\Files` 文件系统功能扩展。 文件系统操作(文件(夹)读取，检查，创建)；文件上传/下载，图片处理(缩略图/水印)，图片验证码生成 
- `Inhere\LibraryPlus\asset` 资源(css,js)管理,加载,发布 
- `Inhere\LibraryPlus\html` html 元素创建, dom 创建
- `Inhere\LibraryPlus\network` network 工具库(`telnet`)

[Document](doc/document.md)

## 已独立的工具库

### `inhere/validate` [github](https://github.com/inhere/php-validate) [git@osc](https://git.oschina.net/inhere/php-validate)

一个简洁小巧且功能完善的php验证库。仅有几个文件，无依赖。

### `inhere/event` [github](https://github.com/inhere/php-event-manager) [git@osc](https://git.oschina.net/inhere/php-event-manager)

php事件管理器,事件调度器 psr-14实现

### `inhere/http` [github](https://github.com/inhere/php-http) [git@osc](https://git.oschina.net/inhere/php-http)

php http消息库, 实现psr7 http消息接口

### `inhere/http-client` [github](https://github.com/inhere/php-http-client) [git@osc](https://git.oschina.net/inhere/php-http-client)

http 工具库(`request` 请求 `response` 响应 `curl` curl请求库，有简洁、完整和并发请求三个版本的类)

### `inhere/queue` [github](https://github.com/inhere/php-queue) [git@osc](https://git.oschina.net/inhere/php-queue)

php的队列实现，使用包装(`DbQueue` `LevelDbQueue` `PhpQueue` `RedisQueue` `ShmQueue` `SSDBQueue` `SysVQueue`)

### 更多

- [inhere/php-lock](https://github.com/inhere/php-lock) php 锁实现
- [inhere/php-shared-memory](https://github.com/inhere/php-shared-memory) php 共享内存操作实现

## 我的其他项目

### `inhere/console` [github](https://github.com/inhere/php-console) [git@osc](https://git.oschina.net/inhere/php-console)

轻量级的命令行应用，工具库, 控制台交互.

### srouter [github](https://github.com/inhere/php-srouter)  [git@osc](https://git.oschina.net/inhere/php-srouter)
 
 轻量级且快速的路由器实现.

### php-server [github](https://github.com/inhere/php-server)  [git@osc](https://git.oschina.net/inhere/php-server)

基于 swoole 的server实现， 方便快速的构建和管理自己的 swoole 服务器

### php-gearman-manager [github](https://github.com/inhere/php-gearman-manager) [git@osc](https://git.oschina.net/inhere/php-gearman-manager)

php 的 gearman workers 管理工具。同时启动并管理多个gearman worker，并会监控运行状态。可以自定义worker数量，也可以针对job设置worker数量。还可以让worker专注指定的job


## license

MIT