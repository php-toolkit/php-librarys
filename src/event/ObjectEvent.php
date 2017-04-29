<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-04-27
 * Time: 9:30
 */

namespace inhere\library\event;

/**
 * Class ClassEvent
 *  the Class Level Event
 *
 * @reference yii2 Event
 *
 * @package inhere\library\event
 */
class ObjectEvent
{
    /**
     * registered Events
     * @var array
     * [
     *  'event' => [ handler, data],
     * ]
     */
    private $_events = [];

    /**
     * @var array
     */
    protected static $supportedEvents = [];

    /**
     * register a event handler
     * @param string|object $class
     * @param $name
     * @param callable $handler
     */
    public function on($class, $name, callable $handler)
    {
        $class = ltrim($class, '\\');

        if (self::isSupportedEvent($name)) {
            $this->_events[$name][$class] = $handler;
        }
    }

    /**
     * trigger event
     * @param $name
     * @param array $args
     * @return bool
     */
    public function fire($name, array $args = [])
    {
        if (!isset($this->_events[$name])) {
            return false;
        }

        // call event handlers of the event.
        foreach ((array)$this->_events[$name] as $cb) {
            // return FALSE to stop go on handle.
            if (false === call_user_func_array($cb, $args)) {
                break;
            }
        }

        // is a once event, remove it
        if ($this->_events[$name]) {
            return $this->removeEvent($name);
        }

        return true;
    }

    /**
     * remove event and it's handlers
     * @param $name
     * @return bool
     */
    public function off($name)
    {
        return $this->removeEvent($name);
    }

    public function removeEvent($name)
    {
        if ($this->hasEvent($name)) {
            unset($this->_events[$name]);

            return true;
        }

        return false;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasEvent($name)
    {
        return isset($this->_events[$name]);
    }

    /**
     * check $name is a supported event name
     * @param $name
     * @return bool
     */
    public function isSupportedEvent($name)
    {
        if (!$name || !preg_match('/[a-zA-z][\w-]+/', $name)) {
            return false;
        }

        if ($ets = self::$supportedEvents) {
            return in_array($name, $ets, true);
        }

        return true;
    }

    /**
     * @return array
     */
    public static function getSupportEvents()
    {
        return self::$supportedEvents;
    }

    /**
     * @param array $supportedEvents
     */
    public static function setSupportEvents(array $supportedEvents)
    {
        self::$supportedEvents = $supportedEvents;
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        return $this->_events;
    }

    /**
     * @return int
     */
    public function countEvents()
    {
        return count($this->_events);
    }
}