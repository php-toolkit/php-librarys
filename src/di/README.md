# Dependency injection container

## 注册服务

```php
Container public function set($id, $definition, array $opts = [])
```

```php
/**
 * @param string $id 服务组件注册id
 * @param mixed (string|array|object|callback) $definition 服务实例对象 | 服务信息定义
 * string:
 *  $definition = className
 * array:
 *  $definition = [
 *     // 1. 仅类名 $definition['_params']则传入对应构造方法
 *     'target' => 'className',
 *     // 2. 类的静态方法, $definition['params']则传入对应方法 className::staticMethod(_params...)
 *     'target' => 'className::staticMethod',
 *     // 3. 类的动态方法, $definition['params']则传入对应方法 (new className)->method(_params...)
 *     'target' => 'className->method',
 *
 *     '_options' => [...] 一些服务设置(别名,是否共享)
 *
 *     // 设置参数方式
 *     '_params' => [
 *         arg1,arg2,arg3,...
 *     ]
 *
 *     // 设置属性方式1
 *     '_props' => [
 *         arg1,arg2,arg3,...
 *     ]
 *     // 设置属性方式2， // prop1 prop2 prop3 将会被收集 到 _props[], 组成 方式1 的形式
 *     prop1 => arg1,
 *     prop2 => arg2,
 *     ... ...
 *  ]
 * object:
 *  $definition = new xxClass();
 * closure:
 *  $definition = function($di){ return xxx;};
 * @param array $opts
 * [
 *  'shared' => (bool), 是否共享
 *  'locked' => (bool), 是否锁定服务
 *  'aliases' => (array), 别名
 *  'activity' => (bool), 立即激活
 * ]
 * @return $this
 */
```