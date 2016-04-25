<?php
/**
 * @author Inhere
 * @version v1.0
 * Use : this
 * Date : 2015-1-10
 * 提供依赖注入服务的容器，
 * 注册、管理容器的服务。
 * 共享服务初次激活服务后将会被保存，即后期获取时若不特别声明，都是获取已激活的服务实例
 * File: Container.php
 */

namespace inhere\tools\di;

class Container implements InterfaceContainer, \ArrayAccess, \IteratorAggregate
{
    /**
     * 当前容器名称，初始时即固定
     * @var string
     */
    public $name;

    /**
     * 动态的存储当前正在设置或获取的服务 id, 用于支持实时的链式操作(设置多个别名时)
     * @var string
     */
    protected $tempId;

    /**
     * @see getNew()
     * true 强制获取服务的新实例，不管它是否有已激活的实例
     * @var bool
     */
    protected $getNewInstance = false;

    protected $state;

    /**
     * 当前容器的父级容器
     * @var Container
     */
    protected $parent =null;

    /**
     * 服务别名
     * @var array
     */
    protected $aliases = [];

    /**
     * $services 已注册的服务
     * $services = [
     *       'id' => Service Object
     *       ... ...
     *   ];
     * @var Service[]
     */
    protected $services = [];

    /**
     * 服务参数设置 @see setArgument()
     * @var array
     */
    protected $arguments = [];

    /**
     * 后期绑定服务参数方式 (参数组成的数组。没有key,不能用合并)
     * 1. 用传入的覆盖 (默认)
     * 2. 传入指定了位置的参数来替换掉原有位置的参数
     * [
     *    //pos=>arguments
     *      0 => arg1, // 第一个参数
     *      1 => arg2,
     *      4 => arg3, //第五个参数
     * ]
     * 3. 在后面追加参数
     */
    const OVERLOAD_PARAM = 1;
    const REPLACE_PARAM  = 2;
    const APPEND_PARAM   = 3;

    public function __construct(Container $container=null)
    {
        $this->parent = $container;
    }

///////////////////////////////////////// Service Add /////////////////////////////////////////

    /**
     * 在容器注册服务
     * @param  string $id 服务组件注册id
     * @param mixed(string|array|object|callback) $definition 服务实例对象 | 服务信息
     * sting:
     *  $definition = className
     * array:
     *  $definition = [
     *     // 1. 仅类名 $definition['params']则传入对应构造方法
     *     'target' => 'className',
     *
     *     // 2. 类的静态方法, $definition['params']则传入对应方法 className::staticMethod(params..)
     *     // 'target' => 'className::staticMethod',
     *
     *     // 3. 类的动态方法, $definition['params']则传入对应方法 (new className)->method(params...)
     *     // 'target' => 'className->method',
     *
     *     'idAliases' => [...] 设置别名
     *
     *     // 设置参数方式一
     *     'params' => [
     *         arg1,arg2,arg3,...
     *     ]
     *
     *     // 设置参数方式二， // arg1 2 3 将会被收集 到 params[], 组成 方式一 的形式
     *     arg1,
     *     arg2,
     *     arg3,
     *     ...
     *
     *  ]
     * object:
     *  $definition = new xxClass();
     * closure:
     *  $definition = function(){ return xxx;};
     * @param bool $shared 是否共享
     * @param bool $locked 是否锁定服务
     * @return object
     * @throws \NotFoundException
     * @throws \InvalidArgumentException
     * @throws \InvalidArgumentException
     */
    public function set($id, $definition, $shared=false, $locked=false)
    {
        $this->tempId = $this->_checkServiceId($id);

        // 已锁定的服务，不能更改
        if ( $this->isLocked($id) ) {
            return $this;
        }

        // Log::record();

        // 已经是个服务实例 object 不是闭包 closure
        if ( is_object($definition) && !is_callable($definition)) {
            $callback = function() use ($definition) {
                return $definition;
            };

            // 是个回调 | a string; is target
        } elseif ( is_callable($definition) || is_string($definition) ) {

            $callback = $this->createCallback($definition);

            // a Array 详细设置服务信息
        } elseif ( is_array($definition) ) {
            if (!isset($definition['target'])) {
                throw new \InvalidArgumentException('配置有误，必须有注册目标(请在服务配置中设置\'target\'项。e.g. 通常是类名)！');
            }

            $target = $definition['target'];

            // 在配置中 直接设置别名
            if (isset($definition['idAliases'])) {
                $idAliases = (array)$definition['idAliases'];
                unset($definition['idAliases']);

                $this->aliases($idAliases, $id);
            }

            // 在配置中 直接设置 shared
            if ( isset($definition['shared']) ) {
                $shared = (bool)$definition['shared'];
                unset($definition['shared']);
            }

            // 在配置中 直接设置 locked
            if ( isset($definition['locked']) ) {
                $locked = (bool)$definition['locked'];
                unset($definition['locked']);
            }

            // 设置参数
            if ( isset($definition['params']) ) {
                $params = $definition['params'];
            } else {
                unset($definition['target']);
                $params = $definition;
            }

            $callback = $this->createCallback($target, (array)$params);
        } else {
            throw new \InvalidArgumentException('无效的参数！');
        }

        $config = [
            'callback' => $callback,
            'instance' =>  null,
            'shared'   => (bool) $shared,
            'locked'   => (bool) $locked
        ];

        $this->services[$id] = new Service($callback, isset($params) ? $params : [], $shared, $locked);
        unset($config, $callback, $definition);

        return $this;
    }

    /**
     * 通过设置配置的多维数组 注册多个服务. 服务详细设置请看{@see self::set()}
     * @param array $services
     * @example
     * $services = [
     *      'service1 id'  => 'xx\yy\className',
     *      'service2 id'  => ... ,
     *      'service3 id'  => ...
     * ]
     * @return $this
     * @throws \InvalidArgumentException
     * @throws \InvalidArgumentException
     */
    public function sets(array $services)
    {
        $IServiceProvider = __NAMESPACE__.'\InterfaceServiceProvider';

        foreach ($services as $id=>$definition) {
            if (!$definition) {
                continue;
            }

            $id               = trim($id);

            // string. is a Service Provider class name
            if ( is_string($definition) && is_subclass_of($definition, $IServiceProvider) ) {
                $this->registerServiceProvider(new $definition);

                continue;
                // array.  definition a Service Provider class
            } elseif (is_array($definition)) {

                if (!isset($definition['target'])){
                    throw new \InvalidArgumentException('配置有误，必须有注册目标(请在服务配置中设置\'target\'项。e.g. 通常是类名)！');
                }

                // $definition['target'] is a Service Provider class. 目标类 是服务提供者类
                if ( is_subclass_of($definition['target'], $IServiceProvider) ) {
                    $providerClass = $definition['target'];
                    $idAliases = isset($definition['idAliases']) ? $definition['idAliases'] : [];
                    unset( $definition['target'], $definition['idAliases']);

                    if (isset($definition['params'])) {
                        $params = $definition['params'];
                    } else {
                        $params = isset($definition[0]) ? $definition[0] : $definition;
                    }

                    // if ($id =='db1' || $id=='db') d($definition,$params, $id, $idAliases);

                    $provider  = new $providerClass($params, $id, $idAliases);

                    $this->registerServiceProvider($provider);

                    continue;
                }

            }

            // set service
            $this->set( $id, $definition);

        }// end foreach

        unset($services);

        return $this;
    }

    /**
     * 注册一项服务(可能含有多个服务)提供者到容器中
     * @param  InterfaceServiceProvider $provider 在提供者内添加需要的服务到容器
     * @return $this
     */
    public function registerServiceProvider(InterfaceServiceProvider $provider)
    {
        $provider->register($this);

        return $this;
    }

    /**
     * 注册共享的服务
     * @param $id
     * @param $definition
     * @return object
     * @throws \InvalidArgumentException
     */
    public function share($id, $definition)
    {
        return $this->set($id, $definition, true);
    }

    /**
     * 注册受保护的服务 like class::lock()
     * @param  string $id [description]
     * @param $definition
     * @param $share
     * @return $this
     */
    public function protect($id, $definition, $share=false)
    {
        return $this->lock($id, $definition, $share);
    }

    /**
     * (注册)锁定的服务，也可在注册后锁定,防止 getNew()强制重载
     * @param  string $id [description]
     * @param $definition
     * @param $share
     * @return $this
     */
    public function lock($id, $definition, $share=false)
    {
        return $this->set($id, $definition, $share, true);
    }

    /**
     * 从类名创建服务实例对象，会尽可能自动补完构造函数依赖
     * @from windWalker https://github.com/ventoviro/windwalker
     * @param string $id a className
     * @param  boolean $shared [description]
     * @throws \DependencyResolutionException
     * @return object
     */
    public function createObject($id, $shared = false)
    {
        try {
            $reflection = new \ReflectionClass($id);
        } catch (\ReflectionException $e) {
            return false;
        }

        $constructor = $reflection->getConstructor();

        // If there are no parameters, just return a new object.
        if (is_null($constructor)) {
            $callback = function () use ($id)
            {
                return new $id;
            };
        } else {
            $newInstanceArgs = $this->getMethodArgs($constructor);

            // Create a callable for the dataStorage
            $callback = function () use ($reflection, $newInstanceArgs)
            {
                return $reflection->newInstanceArgs($newInstanceArgs);
            };
        }

        return $this->set($id, $callback, $shared)->get($id);
    }

    /**
     * 创建(类实例/类的方法)回调
     * @param $target
     * @param array $arguments
     * @return callable
     * @throws \DependencyResolutionException
     * @throws \NotFoundException
     */
    public function createCallback($target, array $arguments=[])
    {
        // have been a callback
        if (is_callable($target)) {
            if ( $target instanceof \Closure ) { // a Closure
                $callback = $target;
            } else {
                $callback = function () use ($target)
                {
                    return call_user_func($target);
                };
            }

            return $callback;
        }

        /**
         * @see $this->set() $definition is array ,
         */
        $target = trim( str_replace(' ','',$target), '.');

        if ( ($pos=strpos($target,'::'))!==false ) {
            // $class  = substr($target, 0, $pos);
            // $method = substr($target, $pos+2);

            $callback     = function (Container $self) use ($target)
            {
                $arguments = $self->getArgument($self->tempId);

                return !$arguments ? call_user_func($target) : call_user_func_array($target, $arguments);
            };
        } elseif ( ($pos=strpos($target,'->'))!==false ) {
            $class  = substr($target, 0, $pos);
            $method = substr($target, $pos+2);

            $callback     = function (Container $self) use ($class, $method)
            {
                $arguments = $self->getArgument($self->tempId);
                $object = new $class;

                return !$arguments ? $object->$method() : call_user_func_array([$object, $method ], $arguments);
            };
        } else {

            // 仅是个 class name
            $class = $target;

            try {
                $reflection = new \ReflectionClass($class);
            } catch (\ReflectionException $e) {
                throw new \NotFoundException($e->getMessage());
            }

            /**
             * @var \ReflectionMethod
             */
            $reflectionMethod = $reflection->getConstructor();

            // If there are no parameters, just return a new object.
            if (is_null($reflectionMethod)) {
                $callback = function () use ($class)
                {
                    return new $class;
                };
            } else {
                $arguments = $arguments ? : $this->getMethodArgs($reflectionMethod);

                // Create a callable
                $callback = function (Container $self) use ($reflection)
                {
                    $arguments = $self->getArgument($self->tempId);
                    //if ($this->tempId =='request') d($self->tempId,$self->getArguments(),$arguments);
                    return $reflection->newInstanceArgs( $arguments );
                };

                unset($reflection,$reflectionMethod);
            }
        }

        $this->setArgument($this->tempId, $arguments ? : [] );

        return $callback;
    }

    /**
     * @from windwalker https://github.com/ventoviro/windwalker
     * Build an array of constructor parameters.
     * @param   \ReflectionMethod $method Method for which to build the argument array.
     * @throws \DependencyResolutionException
     * @return  array  Array of arguments to pass to the method.
     */
    protected function getMethodArgs(\ReflectionMethod $method)
    {
        $methodArgs = array();

        foreach ($method->getParameters() as $param) {
            $dependency = $param->getClass();
            $dependencyVarName = $param->getName();

            // If we have a dependency, that means it has been type-hinted.
            if (!is_null($dependency)) {
                $dependencyClassName = $dependency->getName();

                // If the dependency class name is registered with this container or a parent, use it.
                if ($this->exists($dependencyClassName) !== null) {
                    $depObject = $this->get($dependencyClassName);
                } else {
                    $depObject = $this->createObject($dependencyClassName);
                }

                if ($depObject instanceof $dependencyClassName) {
                    $methodArgs[] = $depObject;

                    continue;
                }
            }

            // Finally, if there is a default parameter, use it.
            if ($param->isOptional()) {
                $methodArgs[] = $param->getDefaultValue();

                continue;
            }

            // Couldn't resolve dependency, and no default was provided.
            throw new \DependencyResolutionException(sprintf('Could not resolve dependency: %s', $dependencyVarName));
        }

        return $methodArgs;
    }

/////////////////////////////////////////  Service(Instance) Get /////////////////////////////////////////

    /**
     * get 获取已注册的服务组件实例，
     * 共享服务总是获取已存储的实例，
     * 其他的则总是返回新的实例
     * @param  string $id 要获取的服务组件id
     * @param  array $params 如果参数为空，则会默认将 容器($this) 传入回调，可以在回调中直接接收
     * @param int $bindType @see $this->setArgument()
     * @throws \NotFoundException
     * @return object | null
     */
    public function get($id, array $params= [], $bindType=self::OVERLOAD_PARAM)
    {
        if ( empty($id) || !is_string($id) ) {
            throw new \InvalidArgumentException(sprintf(
                'The 1 parameter must be of type string is not empty, %s given',
                gettype($id)
            ));
        }

        $this->tempId = $id = $this->resolveAlias($id);
        $this->setArgument($id, $params, $bindType);

        if ( !($config = $this->getService($id,false)) ) {
            throw new \NotFoundException(sprintf('服务id: %s不存在，还没有注册！',$id));
        }

        $callback = $config['callback'];

        // 是共享服务
        if( (bool) $config['shared'] ) {

            if ( !$config['instance'] || $this->getNewInstance) {
                $this->services[$id]['instance'] = $config['instance'] = call_user_func($callback, $this);
            }

            $this->getNewInstance = false;

            return $config['instance'];
        }

        return call_user_func($callback,$this);
    }

    /**
     * getShared 总是获取同一个实例
     * @param $id
     * @throws \NotFoundException
     * @return mixed
     */
    public function getShared($id)
    {
        $this->getNewInstance = false;

        return $this->get($id);

    }

    /**
     * 强制获取服务的新实例，针对共享服务
     * @param $id
     * @param array $params
     * @param int $bindType
     * @return null|object
     */
    public function getNew($id, array $params= [], $bindType=self::OVERLOAD_PARAM)
    {
        $this->getNewInstance = true;

        return $this->get($id, (array) $params, $bindType);
    }

    /**
     * 强制获取新的服务实例 like getNew()
     * @param string $id
     * @param array $params
     * @param int $bindType
     * @return mixed|void
     */
    public function make($id, array $params= [], $bindType=self::OVERLOAD_PARAM)
    {
        $this->getNewInstance = true;

        return $this->get($id, $params, $bindType);
    }


////////////////////////////////////////// Service Arguments //////////////////////////////////////////

    // self::setArgument() 别名方法
    public function setParam($id, array $params, $bindType=self::OVERLOAD_PARAM)
    {
        return $this->setArgument($id, $params, $bindType);
    }

    /**
     * 给服务设置参数，在获取服务实例前
     * @param string $id 服务id
     * @param array $params 设置参数
     * 通常无key值，按默认顺序传入服务回调中
     * 当 $bindType = REPLACE_PARAM
     * [
     * // pos => args
     *  0 => arg1,
     *  1 => arg2,
     *  3 => arg3,
     * ]
     * @param int $bindType 绑定参数方式
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setArgument($id, array $params, $bindType=self::OVERLOAD_PARAM)
    {
        if (!$params) {
            return false;
        }

        $id = $this->resolveAlias($id);

        if ( ! $this->exists($id) && ($id != $this->tempId) ) {
            throw new \InvalidArgumentException("此容器 {$this->name} 中还没有注册服务 {$id}, 不能绑定参数 ！");
        }

        $this->services[$id]->setArguments($params, $bindType);

        return $this;
    }

    public function getArgument($id, $useAlias=true)
    {
        return $this->getParam($id, $useAlias);
    }

    public function getParam($id, $useAlias=true)
    {
        $useAlias && $id = $this->resolveAlias($id);

        return isset($this->arguments[$id])  ? $this->arguments[$id] : [];
    }

    public function getArguments()
    {
        return $this->arguments;
    }
    public function getParams()
    {
        return $this->arguments;
    }

///////////////////////////////////// Service ID Alias /////////////////////////////////////

    /**
     * 给一个服务ID设置别名
     * 若没有传入ID, 则取当前正在操作(获取/设置)的服务ID
     * @param $alias
     * @param string $id
     * @return $this
     */
    public function alias($alias, $id='')
    {
        if (empty($id)) {
            $id = $this->tempId;
        }

        if ( !isset($this->aliases[$alias]) ) {
            $this->aliases[$alias] = $id;
        }

        return $this;
    }

    /**
     * 给一个服务ID设置多个别名
     * @param  array  $aliases
     * @param  string $id
     * @return self
     */
    public function aliases(array $aliases, $id='')
    {
        if (empty($id)) {
            $id = $this->tempId;
        }

        foreach ($aliases as $alias) {
            $this->alias($alias, $id);
        }

        return $this;
    }

    /**
     * @param $alias
     * @return mixed
     */
    public function resolveAlias($alias)
    {
        return isset($this->aliases[$alias]) ? $this->aliases[$alias]: $alias;
    }

    /**
     * @return array $aliases
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    public function getTempId()
    {
        return $this->tempId;
    }

    public function clearTempId()
    {
        $this->tempId = null;
    }

//////////////////////////////////// Service Info ////////////////////////////////////

    /**
     * 删除服务
     * @param $id
     * @internal param $ [type] $id [description]
     * @return void [type]       [description]
     */
    public function delete($id)
    {
        $id = $this->resolveAlias($id);

        if ( isset($this->services[$id]) ) {
            unset($this->services[$id]);
        }

    }

    public function clear()
    {
        $this->services = [];
    }

    /**
     * 获取某一个服务的信息
     * @param $id
     * @param bool $useAlias
     * @return array
     */
    public function getService($id, $useAlias=true)
    {
        $useAlias && $id = $this->resolveAlias($id);

        return !empty( $this->services[$id] ) ? $this->services[$id] : [];
    }

    /**
     * 获取全部服务信息
     * @return array
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * 获取全部服务名
     * @return array
     */
    public function getServiceNames()
    {
        return array_keys($this->services);
    }

    /**
     * 获取全部服务id
     * @param bool $toArray
     * @return array
     */
    public function getIds($toArray=true)
    {
        $ids =  array_keys($this->services);

        return $toArray ? $ids : implode(', ', $ids);
    }

    public function isShared($id)
    {
        $config = $this->getService($id);

        return isset($config['shared']) ? (bool) $config['shared'] : false;
    }

    public function isLocked($id)
    {
        $config = $this->getService($id);

        return isset($config['locked']) ? (bool) $config['locked'] : false;
    }

    // 是已注册的服务
    public function isService($id)
    {
        $id = $this->resolveAlias($id);

        return !empty( $this->services[$id] ) ? true : false;
    }

    public function has($id)
    {
        return $this->isService($id);
    }

    public function exists($id)
    {
        return $this->isService($id);
    }

//////////////////////////////////////// Helper ////////////////////////////////////////

    /**
     * state description lock free protect shared
     * @return string $state
     */
    public function state()
    {
        return $this->state;
    }

    /**
     * @return static
     */
    public function createChild()
    {
        return new static($this);
    }

    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Method to set property parent
     * @param   Container $parent  Parent container.
     * @return  static  Return self to support chaining.
     */
    public function setParent(Container $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    protected function _checkServiceId(&$id)
    {
        if ( empty($id) ) {
            throw new \InvalidArgumentException( '必须设置服务Id名称！' );
        }

        if ( !is_string($id) || strlen($id)>30 ) {
            throw new \InvalidArgumentException( '设置服务Id只能是不超过30个字符的字符串！');
        }

        //去处空白和前后的'.'
        $id = trim( str_replace(' ','',$id), '.');

        if ( !preg_match("/^\w{1,20}(?:\.\w{1,20})*$/i", $id) ) {
            throw new \InvalidArgumentException( "服务Id {$id} 是无效的字符串！");
        }

        return $id;
    }

    public function __get($name)
    {
        if ($service = $this->get($name)) {
            return $service;
        }

        $method = 'get'.ucfirst( $name );

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new \NotFoundException("Getting a Unknown property! ".get_class($this)."::{$name}", 'get');
    }

    /**
     * Defined by IteratorAggregate interface
     * Returns an iterator for this object, for use with foreach
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->services);
    }


    /**
     * Checks whether an offset exists in the iterator.
     * @param   mixed  $offset  The array offset.
     * @return  boolean  True if the offset exists, false otherwise.
     */
    public function offsetExists($offset)
    {
        return (boolean) $this->exists($offset);
    }

    /**
     * Gets an offset in the iterator.
     * @param   mixed  $offset  The array offset.
     * @return  mixed  The array value if it exists, null otherwise.
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Sets an offset in the iterator.
     * @param   mixed  $offset  The array offset.
     * @param   mixed  $value   The array value.
     * @return  $this
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Unset an offset in the iterator.
     * @param   mixed  $offset  The array offset.
     * @return  void
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

}
