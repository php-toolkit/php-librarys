<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-27
 * Time: 16:17
 */

namespace inhere\librarys\traits;

/**
 * Class TraitSimpleEvent
 * @package inhere\librarys\traits
 */
trait TraitSimpleEvent
{
    /**
     * @var array
     */
    protected $supportEvents = [];

    /**
     * registered Events
     * @var array
     */
    protected $events = [];

    /**
     * @var array
     */
    protected $eventHandlers = [];

    /**
     * add a event handler
     * @param $event
     * @param callable $handler
     */
    public function on($event, callable $handler)
    {
        if ( $this->isSupportedEvent($event) ) {
            $this->eventHandlers[$event][] = $handler;
        }
    }

    /**
     * trigger event
     * @param $event
     * @param array $args
     * @return bool
     */
    public function fire($event, array $args = [])
    {
        if ( $this->hasEvent($event) ) {
            return false;
        }

        foreach ($this->eventHandlers[$event] as $cb) {
            // return TRUE to stop go on handle.
            if ( $stop = call_user_func_array($cb, $args) ) {
                break;
            }
        }

        return true;
    }

    /**
     * @param $event
     */
    public function removeEvent($event)
    {
        if ( $this->isSupportedEvent($event) ) {
            unset($this->eventHandlers[$event]);
        }
    }

    /**
     * @param $event
     * @return bool
     */
    public function isSupportedEvent($event)
    {
        if ( !$event || !preg_match('/[a-zA-z][\w-]+/', $event) ) {
            return false;
        }

        if ( $ets = $this->supportEvents ) {
            return in_array($event, $ets);
        }

        return true;
    }

    /**
     * @return array
     */
    public function getSupportEvents()
    {
        return $this->supportEvents;
    }

    /**
     * @param array $supportEvents
     */
    public function setSupportEvents(array $supportEvents)
    {
        $this->supportEvents = $supportEvents;
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param $event
     * @return bool
     */
    public function hasEvent($event)
    {
        return isset($this->events[$event]);
    }
}