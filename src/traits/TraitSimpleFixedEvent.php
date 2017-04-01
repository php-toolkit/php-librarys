<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/30 0030
 * Time: 23:47
 */

namespace inhere\library\traits;

/**
 * Class TraitSimpleFixedEvent
 * @package inhere\library\traits
 */
trait TraitSimpleFixedEvent
{
    /**
     * @var \SplFixedArray
     */
    protected $callbacks;

    /**
     * @return array
     */
    public function getSupportedEvents(): array
    {
        // return [ self::ON_CONNECT, self::ON_HANDSHAKE, self::ON_OPEN, self::ON_MESSAGE, self::ON_CLOSE, self::ON_ERROR];

        return [];
    }

    /**
     * @param string $event
     * @return bool
     */
    public function isSupportedEvent(string $event): bool
    {
        return in_array($event, $this->getSupportedEvents(), true);
    }

    /**
     * @return \SplFixedArray
     */
    public function getCallbacks(): \SplFixedArray
    {
        return $this->callbacks;
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// events method
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * register a event callback
     * @param string    $event    event name
     * @param callable  $cb       event callback
     * @param bool      $replace  replace exists's event cb
     * @return $this
     */
    public function on(string $event, callable $cb, bool $replace = false)
    {
        if ( false === ($key = array_search($event, $this->getSupportedEvents(), true)) ) {
            $sup = implode(',', $this->getSupportedEvents());

            throw new \InvalidArgumentException("The want registered event [$event] is not supported. Supported: $sup");
        }

        // init property
        if ( $this->callbacks === null ) {
            $this->callbacks = new \SplFixedArray( count($this->getSupportedEvents()) );
        }

        if ( !$replace && isset($this->callbacks[$key]) ) {
            throw new \InvalidArgumentException("The want registered event [$event] have been registered! don't allow replace.");
        }

        $this->callbacks[$key] = $cb;

        return $this;
    }

    /**
     * remove event handler
     * @param $event
     * @return bool
     */
    public function off($event)
    {
        if ( false === ($key = array_search($event, $this->getSupportedEvents(), true)) ) {
            return false;
        }

        if ( !isset($this->callbacks[$key]) || !($cb = $this->callbacks[$key]) ) {
            return true;
        }

        $this->callbacks[$key] = null;

        return true;
    }

    /**
     * @param string $event
     * @param array $args
     * @return mixed
     */
    protected function trigger(string $event, array $args = [])
    {
        if ( false === ($key = array_search($event, $this->getSupportedEvents(), true)) ) {
            throw new \InvalidArgumentException("Trigger a not exists's event: $event.");
        }

        if ( !isset($this->callbacks[$key]) || !($cb = $this->callbacks[$key]) ) {
            return null;
        }

        return call_user_func_array($cb, $args);
    }

}
