<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-30
 * Time: 11:56
 */

namespace inhere\library\traits;

use inhere\library\helpers\PhpHelper;

/**
 * Class AopProxyAwareTrait
 * - AOP 切面编程
 * @package inhere\library\traits
 * @property array $proxyMap 要经过AOP代理的方法配置
 * e.g:
 * [
 *   'XyzClass::methodBefore' => handler,
 *   'XyzClass::methodAfter'  => handler,
 * ]
 */
trait AopProxyAwareTrait
{
    /**
     * @var array
     */
    private static $proxyPoints = ['before', 'after'];

    /**
     * @var mixed the proxy target is a class name or a object
     */
    private $proxyTarget;

    public function proxy($class, $method = null, array $args = [])
    {
        $this->proxyTarget = $class;

        if ($method) {
            return $this->call($method, $args);
        }

        return $this;
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function call($method, array $args = [])
    {
        if (!$target = $this->proxyTarget) {
            return null;
        }

        if ($cb = $this->findProxyCallback($target, $method)) {
            PhpHelper::call($cb, [$target, $method, $args]);
        }

        $ret = PhpHelper::call([$target, $method], $args);

        if ($cb = $this->findProxyCallback($target, $method, 'after')) {
            PhpHelper::call($cb, [$target, $method, $args, $ret]);
        }

        // clear
        $this->proxyTarget = null;

        return $ret;
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, array $args = [])
    {
        return $this->call($method, $args);
    }

    /**
     * @param array ...$args
     * @return $this|mixed
     */
    public function __invoke(...$args)
    {
        $num = count($args);

        // only a object
        if ($num === 1) {
            return $this->proxy($args[0]);
        }

        // has object and method
        if ($num > 1) {
            $class = array_shift($args);
            $method = array_shift($args);

            return $this->proxy($class, $method, $args);
        }

        throw new \InvalidArgumentException('Missing parameters!');
    }

    /**
     * @param $target
     * @param string $method
     * @param string $prefix
     * @return null
     */
    protected function findProxyCallback($target, $method, $prefix = 'before')
    {
        $className = is_string($target) ? $target : get_class($target);

        // e.g XyzClass::methodAfter
        $key = $className . '::' . $method . ucfirst($prefix);

        return $this->proxyMap[$key] ?? null;
    }

    /**
     * @param string $key eg 'XyzClass::method'
     * @param callable $handler
     * @param string $position 'before' 'after'
     * @return $this
     */
    public function addProxy($key, $handler, $position = 'before')
    {
        if (!in_array($position, self::$proxyPoints, true)) {
            return $this;
        }

        $key .= ucfirst($position);
        $this->proxyMap[$key] = $handler;

        return $this;
    }

    /**
     * @param array $map
     * @return $this
     */
    public function addProxies(array $map)
    {
        foreach ($map as $key => $handler) {
            $position = 'before';

            if (is_array($handler)) {
                if (!isset($handler['handler'])) {
                    continue;
                }

                $position = $handler['position'] ?? 'before';
                $handler = $handler['handler'];
            }

            $this->addProxy($key, $handler, $position);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProxyTarget()
    {
        return $this->proxyTarget;
    }

    /**
     * @return array
     */
    public function getProxyMap(): array
    {
        return $this->proxyMap;
    }

    /**
     * @param array $proxyMap
     */
    public function setProxyMap(array $proxyMap)
    {
        $this->proxyMap = $proxyMap;
    }
}