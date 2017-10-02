<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-30
 * Time: 14:20
 */

namespace Inhere\Library\Components;

use Inhere\Library\Traits\AopProxyAwareTrait;

/**
 * Class AopProxy
 * @package Inhere\Library\Components
 * $aop = new AopProxy();
 * $aop->addProxy('FileLogger::log', function() {
 *      echo 'before add log';
 * }, 'before');
 * $aop->addProxy('FileLogger::log', function() {
 *      echo 'after add log';
 * }, 'after');
 * $logger = new FileLogger;
 * // not use:
 * // $logger->log('message');
 * // should:
 * $aop->proxy($logger, 'log', ['message']);
 * // equal
 * $aop->proxy($logger)->call('log', ['message']);
 * // equal
 * $aop->proxy($logger)->log('message'); // by __call
 * // equal
 * $aop($logger)->log('message'); // by __invoke
 * // equal
 * $aop($logger, 'log', ['message']); // by __invoke
 */
class AopProxy
{
    use AopProxyAwareTrait;

    /**
     * @var array
     */
    protected $proxyMap = [];

    /**
     * AopProxy constructor.
     * @param array $proxyMap
     */
    public function __construct(array $proxyMap = [])
    {
        $this->setProxyMap($proxyMap);
    }
}
