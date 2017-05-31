# php-librarys

some useful tool library of the php

> this is for php7 branch. If you are using php5, please see [php5](https://github.com/inhere/php-librarys/tree/php5)

## install

- at command

```
composer require inhere/library
```

- at composer.json

at "require" add 

```
"inhere/library": "dev-master" // recommended
OR 
"inhere/library": "^2.0"
```

RUN: `composer update`

### tool library list

- `inhere\library\asset` 资源(css,js)管理,加载,发布 
- `inhere\library\collections` 数据收集器. (数据收集/全局配置/语言包处理类)
-  控制台的一些简单交互. **removed** please use `inhere\console` [github](https://github.com/inhere/php-console)
- `inhere\library\di` 依赖注入容器，提供服务管理 
- `inhere\library\env` 环境信息收集, `Server`: 服务端信息. `Client`: 客户端信息 
- `inhere\library\files` 文件系统操作(文件(夹)读取，检查，创建)；文件上传/下载，图片处理(缩略图/水印)，图片验证码生成 
- `inhere\library\html` html 元素创建, dom 创建
- `inhere\library\helpers` 辅助类库(`string array object date url curl php`)
- `inhere\library\utils` 一些独立的工具类(`curl logger`)
- `functions.php` 一些有用的函数

[Document](doc/document.md)

### 收集的一些库（推荐使用） 

- `symfony/filesystem` 文件系统(文件文件夹操作) [Documentation](https://symfony.com/doc/current/components/filesystem/index.html)
- `symfony/polyfill-mbstring` 字符串兼容处理 
- `symfony/yaml` Yaml文件解析 
- `symfony/console` cli命令行应用 
- `matthiasmullie/minify` 资源(css,js)压缩合并 
- `robmorgan/phinx` database migrations 
- `monolog/monolog` 日志记录
