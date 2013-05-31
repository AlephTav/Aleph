<?php

namespace Aleph\Core;

require_once(__DIR__ . '/../core/event.php');

/**
 * Test for Aleph\Core\Event;
 */
function test_event()
{
  if (!test_event_listen()) return 'Method "listen" doesn\'t work.';
  if (!test_event_remove()) return 'Method "remove" doesn\'t work.';
  if (!test_event_fire()) return 'Method "fire" doesn\'t work.';
  return true;
}

/**
 * Test for Aleph\Core\Event::fire method.
 */
function test_event_fire()
{
  $tmp = [];
  $foo1 = function($event) use (&$tmp) {$tmp[] = '1' . $event;};
  $foo2 = function($event) use (&$tmp) {$tmp[] = '2' . $event;};
  $foo3 = function($event) use (&$tmp) {$tmp[] = '3' . $event; return false;};
  $foo4 = function($event, array $args) use (&$tmp) {$tmp = [$event, $args];};
  Event::once('event', $foo2, 10);
  Event::listen('event', $foo1, 5);
  Event::fire('event');
  if ($tmp !== ['2event', '1event']) return false;
  $tmp = [];
  Event::fire('event');
  if ($tmp !== ['1event']) return false;
  Event::remove();
  $tmp = [];
  Event::listen('event', $foo3);
  Event::listen('event', $foo2, 1);
  Event::listen('event', $foo1);
  Event::fire('event');
  if ($tmp !== ['2event', '3event']) return false;
  Event::remove();
  Event::listen('event', $foo4);
  Event::fire('event', [[1, 2, 3]]);
  if ($tmp !== ['event', [1, 2, 3]]) return false;
  return true;
}

/**
 * Test for Aleph\Core\Event::remove method.
 */
function test_event_remove()
{
  $empty1 = function($event){};
  $empty2 = 'MyClass->test';
  Event::listen('foo', $empty1);
  Event::listen('foo', $empty2);
  Event::remove();
  if (Event::listeners() != 0) return false;
  Event::listen('foo', $empty1);
  Event::listen('foo', $empty2);
  Event::remove('foo');
  if (Event::listeners('foo') != 0) return false;
  Event::listen('foo', $empty1);
  Event::listen('foo', $empty2);
  Event::remove('foo', $empty1);
  if (Event::listeners('foo') != 1) return false;
  Event::listen('foo', $empty1);
  Event::listen('foo', $empty1);
  Event::remove(null, $empty1);
  if (Event::listeners('foo') != 1) return false;
  return true;
}

/**
 * Test for Aleph\Core\Event::listen method.
 */
function test_event_listen()
{
  $empty1 = function($event){};
  $empty2 = function($event){};
  Event::listen('foo1', $empty1);
  Event::listen('foo2', $empty2);
  if (Event::listeners('foo1') != 1 && Event::listeners() != 2) return false;
  return true;
}

return test_event();