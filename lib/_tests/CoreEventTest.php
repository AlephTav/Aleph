<?php

use Aleph\Core\Event;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Aleph\Core\Event class.
 *
 * @group core
 */
class CoreEventTest extends TestCase
{
    /**
     * Checks adding listeners to an event.
     *
     * @covers Event::listen
     * @covers Event::listeners
     */
    public function testListen()
    {
        Event::listen('foo1', function () {});
        Event::listen('foo2', function () {});
        $this->assertEquals(1, Event::listeners('foo1'));
        $this->assertEquals(1, Event::listeners('foo2'));
        $this->assertEquals(2, Event::listeners());
    }

    /**
     * Checks removing event listeners.
     *
     * @covers  Event::remove
     * @depends testListen
     */
    public function testRemove()
    {
        $empty = function () {};
        Event::listen('foo', $empty);
        Event::listen('foo', $empty);
        Event::remove();
        $this->assertEquals(0, Event::listeners());
        Event::listen('foo', $empty);
        Event::listen('foo', $empty);
        Event::remove('foo');
        $this->assertEquals(0, Event::listeners());
        Event::listen('foo', $empty);
        Event::listen('foo', function () {});
        Event::remove('foo', $empty);
        $this->assertEquals(1, Event::listeners());
        Event::listen('foo', $empty);
        Event::listen('foo', $empty);
        Event::remove(null, $empty);
        $this->assertEquals(1, Event::listeners());
    }

    /**
     * Checks removing event listeners.
     *
     * @covers  Event::remove
     * @depends testListen
     * @depends testRemove
     */
    public function testFire()
    {
        $this->assertFalse(Event::fire('some nonexistent event'));
        $tmp = [];
        $foo1 = function ($event) use (&$tmp) {
            $tmp[] = '1' . $event;
        };
        $foo2 = function ($event) use (&$tmp) {
            $tmp[] = '2' . $event;
        };
        $foo3 = function ($event) use (&$tmp) {
            $tmp[] = '3' . $event;
        };
        $foo4 = function ($event) use (&$tmp) {
            $tmp[] = '4' . $event;
            return false;
        };
        $foo5 = function ($event, array $args) use (&$tmp) {
            $tmp = [$event, $args];
        };
        Event::listen('event', $foo3, 10);
        Event::listen('event', $foo1, 0);
        Event::once('event', $foo2, 5);
        $this->assertTrue(Event::fire('event'));
        $this->assertEquals($tmp, ['1event', '2event', '3event']);
        $tmp = [];
        $this->assertTrue(Event::fire('event'));
        $this->assertEquals($tmp, ['1event', '3event']);
        $tmp = [];
        Event::remove();
        Event::listen('event', $foo1, 4);
        Event::listen('event', $foo2, 3);
        Event::listen('event', $foo3, 2);
        Event::listen('event', $foo4, 1);
        Event::fire('event');
        $this->assertEquals($tmp, ['4event']);
        Event::remove();
        Event::listen('event', $foo5);
        Event::fire('event', [[1, 2, 3]]);
        $this->assertEquals($tmp, ['event', [1, 2, 3]]);
    }
}