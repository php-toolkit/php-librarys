# php-librarys

> this is for php5

If you are using a php7, please see [master](https://github.com/inhere/php-librarys/tree/master)

## install

- at command

```
composer require inhere/library@dev-php5
```

- at composer.json

at "require" add:

```
"inhere/library": "dev-php5" // recommended
// OR
"inhere/library": "~1.0"
```

RUN: `composer update`

### tool library list

- `inhere\librarys\asset` 资源(css,js)管理,加载,发布 
- `inhere\librarys\collections` 数据收集器. 通常用于全局配置
- `inhere\librarys\console` 控制台的一些简单交互. **removed** please use `inhere\console` [github](https://github.com/inhere/php-console)
- `inhere\librarys\di` 依赖注入容器，提供服务管理 
- `inhere\librarys\env` 环境信息收集, `Server`: 服务端信息. `Client`: 客户端信息 
- `inhere\librarys\files` 文件系统操作(文件(夹)读取，检查，创建)；文件上传/下载，图片处理(缩略图/水印)，图片验证码生成 
- `inhere\librarys\html` html 元素创建, dom 创建
- `inhere\librarys\helpers` 辅助类库(`string array object date url curl php`)
- `inhere\librarys\language` 语言包处理类
- `inhere\librarys\utils` 一些独立的工具类(`curl logger`)
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
