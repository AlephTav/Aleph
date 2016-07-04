<?php
/**
 * Copyright (c) 2013 - 2016 Aleph Tav
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated 
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, 
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO 
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, 
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @link http://www.4leph.com
 * @copyright Copyright &copy; 2013 - 2016 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Core;

/**
 * The class provides a simple way of subscribing to events and notifying those subscribers whenever an event occurs.
 * 
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.2
 * @package aleph.core
 */
class Event
{
    /**
     * Array of events.
     *
     * @var array
     */   
    private static $events = [];
    
    /**
     * Determines if events are sorted according to their priorities.
     *
     * @var bool
     */
    private static $sorted = true;

    /**
     * Subscribes to an event.
     *
     * @param string $event The event name.
     * @param mixed $listener The framework callback to invoke when the event is triggered.
     * @param int $priority The priority of the event.
     * @param bool $once Determines whether a listener should be called once.
     * @return void
     */
    public static function listen(string $event, $listener, int $priority = 0, bool $once = false)
    {
        self::$events[$event][] = [
            'listener' => $listener,
            'priority' => $priority,
            'once' => $once
        ];
        self::$sorted = false;
    }
  
    /**
     * Adds a listener which is guaranteed to only be called once.
     *
     * @param string $event The event name.
     * @param mixed $listener The callback to invoke when the event is triggered.
     * @param int $priority The priority of the event.
     * @return void
     */
    public static function once(string $event, $listener, int $priority = 0)
    {
        static::listen($event, $listener, $priority, true);
    }
  
    /**
     * Returns number of listeners of an event or total number of listeners.
     *
     * @param string|null $event The event name.
     * @return int
     */
    public static function listeners(string $event = null) : int
    {
        $count = 0;
        if ($event === null)
        {
            foreach (self::$events as $event => $listeners)
            {
                $count += count($listeners);
            }
        }
        else if (isset(self::$events[$event]))
        {
            $count = count(self::$events[$event]);
        }
        return $count;
    }
  
    /**
     * Removes a specific listener for a specific event,
     * all listeners of a specific event or all listeners of all events.
     *
     * @param string $event The event name.
     * @param mixed $listener The callback which was previously added to an event.
     * @return void
     */
    public static function remove(string $event = null, $listener = null)
    {
        if ($listener === null)
        {
            if ($event === null)
            {
                self::$events = [];
                self::$sorted = true;
            }
            else
            {
                unset(self::$events[$event]);
                self::$sorted = false;
            }
            return;
        }
        $events = [];
        if ($event !== null)
        {
            if (isset(self::$events[$event]))
            {
                $events[$event] = self::$events[$event];
            }
        }
        else
        {
            $events = self::$events;
        }
        $linfo = (new Callback($listener))->getInfo();
        foreach ($events as $event => $listeners)
        {
            foreach ($listeners as $n => $info)
            {
                if ($info['listener'] instanceof \Closure || $listener instanceof \Closure)
                {
                    if ($info['listener'] === $listener)
                    {
                        unset(self::$events[$event][$n]);
                        self::$sorted = false;
                    }
                }
                else if ((new Callback($info['listener']))->getInfo() === $linfo)
                {
                    unset(self::$events[$event][$n]);
                    self::$sorted = false;
                }
            }
        }
    }
  
    /**
     * Triggers an event.
     * It returns FALSE if a specific event doesn't exist, and TRUE otherwise.
     *
     * @param string $event The event name.
     * @param array $args Arguments to pass to all listeners of an event.
     * @return bool
     */
    public static function fire(string $event, array $args = []) : bool
    {
        if (empty(self::$events[$event]))
        {
            return false;
        }
        $listeners = self::$events[$event];
        if (!self::$sorted)
        {
            uasort($listeners, function(array $a, array $b)
            {
                return $a['priority'] <= $b['priority'] ? 1 : -1;
            });
            self::$sorted = true;
        }
        foreach ($listeners as $n => $listener)
        {
            $res = (new Callback($listener['listener']))->call(array_merge([$event], $args));
            if ($listener['once'])
            {                
                unset(self::$events[$event][$n]);
            }
            if ($res === false)
            {
                return true;
            }
        }
        return true;
    }
}