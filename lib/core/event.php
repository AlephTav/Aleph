<?php
/**
 * Copyright (c) 2014 Aleph Tav
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
 * @copyright Copyright &copy; 2014 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Core;

/**
 * The class provides a simple way of subscribing to events and notifying those subscribers whenever an event occurs.
 * 
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.core
 */
class Event
{
  /**
   * Array of events.
   *
   * @var array $events;
   * @access private
   * @static
   */   
  private static $events = [];

  /**
   * Subscribes to an event.
   *
   * @param string $event - event name.
   * @param mixed $listener - a delegate to invoke when the event is triggered.
   * @param integer $priority - priority of an event.
   * @param boolean $once - determines whether a listener should be called once.
   * @access public
   * @static
   */
  public static function listen($event, $listener, $priority = null, $once = false)
  {
    $listener = ['listener' => $listener, 'priority' => (int)$priority, 'once' => (bool)$once];
    if (empty(self::$events[$event])) self::$events[$event] = [];
    self::$events[$event][] = $listener;
  }
  
  /**
   * Adds a listener which is guaranteed to only be called once.
   *
   * @param string $event - event name.
   * @param mixed $listener - a delegate to invoke when the event is triggered.
   * @param integer $priority - priority of an event.
   * @access public
   */
  public static function once($event, $listener, $priority = null)
  {
    self::listen($event, $listener, $priority, true);
  }
  
  /**
   * Returns number of listeners of an event or total number of listeners.
   *
   * @param string $event - event name.
   * @return integer
   * @access public
   * @static
   */
  public static function listeners($event = null)
  {
    $count = 0;
    if ($event === null)
    {
      foreach (self::$events as $event => $listeners) $count += count($listeners);
    }
    else if (isset(self::$events[$event]))
    {
      $count = count(self::$events[$event]);
    }
    return $count;
  }
  
  /**
   * Removes a specific listener for a specific event, all listeners of a specific event or all listeners of all events.
   *
   * @param string $event - event name.
   * @param mixed $listener - a delegate which was previously added to an event.
   * @access public
   * @static
   */
  public static function remove($event = null, $listener = null)
  {
    if ($listener === null)
    {
      if ($event === null) self::$events = [];
      else unset(self::$events[$event]);
      return;
    }
    $events = [];
    if ($event !== null)
    {
      if (isset(self::$events[$event])) $events[$event] = self::$events[$event];
    }
    else
    {
      $events = self::$events;
    }
    $linfo = (new Delegate($listener))->getInfo();
    foreach ($events as $event => $listeners)
    {
      foreach ($listeners as $n => $info)
      {
        if ($info['listener'] instanceof \Closure || $listener instanceof \Closure)
        {
          if ($info['listener'] === $listener) unset(self::$events[$event][$n]);
        }
        else if ((new Delegate($info['listener']))->getInfo() === $linfo)
        {
          unset(self::$events[$event][$n]);
        }
      }
    }
  }
  
  /**
   * Triggers an event. Method returns FALSE if a specific event doesn't exist, and TRUE otherwise.
   *
   * @param string $event - event name.
   * @param array $args - arguments to pass to all listeners of an event.
   * @access public
   * @static
   */
  public static function fire($event, array $args = [])
  {
    if (empty(self::$events[$event])) return false;
    $listeners = self::$events[$event];
    uasort($listeners, function(array $a, array $b)
    {
      return $a['priority'] <= $b['priority'] ? 1 : -1;
    });
    foreach ($listeners as $n => $listener)
    {
      $res = (new Delegate($listener['listener']))->call(array_merge([$event], $args));
      if ($listener['once']) unset(self::$events[$event][$n]);
      if ($res === false) return true;
    }
    return true;
  }
}