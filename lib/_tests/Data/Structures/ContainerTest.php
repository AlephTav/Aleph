<?php

use Aleph\Data\Structures\Interfaces\IContainer;
use Aleph\Data\Structures\Container;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Aleph\Data\Structures\Container class.
 *
 * @group data-structures
 */
class ContainerTest extends TestCase
{
    /**
     * The array to test composite keys.
     *
     * @var array
     */
    private $arr = [
        'a' => [
            1,
            2,
            3
        ],
        'b' => [
            'd' => 1,
            'e' => 2,
            'f' => [
                1,
                2,
                3
            ]
        ],
        'c' => null,
        'd' => 'a',
        'e' => []
    ];

    /**
     * @covers Container::isEmpty
     * @covers Container::count
     */
    public function testEmpty()
    {
        $c = new Container();
        $this->assertTrue($c->isEmpty());
        $this->assertEquals(0, $c->count());
        $c = new Container([1, 2, 3]);
        $this->assertFalse($c->isEmpty());
        $this->assertEquals(3, $c->count());
    }

    /**
     * @covers Container::toArray
     */
    public function testToArray()
    {
        $c = new Container([]);
        $this->assertEquals([], $c->toArray());
        $a = ['a', 'b', 'c' => 1];
        $c = new Container($a);
        $this->assertEquals($a, $c->toArray());
    }

    /**
     * @covers Container::toJson
     */
    public function testToJson()
    {
        $c = new Container([]);
        $this->assertEquals(json_encode([]), $c->toJson());
        $a = ['a', 'b', 'c' => 1];
        $c = new Container($a);
        $this->assertEquals(json_encode($a), $c->toJson());
    }

    /**
     * @covers Container::keys
     */
    public function testKeys()
    {
        $c = new Container([]);
        $this->assertEquals([], $c->keys());
        $a = ['a', 'b', 'c' => 1];
        $c = new Container($a);
        $this->assertEquals([0, 1, 'c'], $c->keys());
    }

    /**
     * @covers Container::values
     */
    public function testValues()
    {
        $c = new Container([]);
        $this->assertEquals([], $c->values());
        $a = ['a', 'b', 'c' => 1];
        $c = new Container($a);
        $this->assertEquals(['a', 'b', 1], $c->values());
    }

    /**
     * @covers Container::replace
     * @depends testToArray
     */
    public function testReplace()
    {
        $a = ['a' => 1, 'b' => 2, 'c' => 3];
        $c = new Container($a);
        $a = array_flip($a);
        $this->assertEquals($c, $c->replace($a));
        $this->assertEquals($a, $c->toArray());
        $this->assertEquals($c, $c->replace([]));
        $this->assertEquals([], $c->toArray());
    }

    /**
     * @covers Container::add
     * @depends testToArray
     */
    public function testAdd()
    {
        $a = ['a' => 1, 'b' => 2, 'c' => 3];
        $b = ['b' => 3, 'c' => 4, 'd' => 5];
        $r = array_replace($a, $b);
        $c = new Container($a);
        $this->assertEquals($c, $c->add($b));
        $this->assertEquals($r, $c->toArray());
        $this->assertEquals($c, $c->add([]));
        $this->assertEquals($r, $c->toArray());
    }

    /**
     * @covers Container::merge
     * @depends testToArray
     */
    public function testMerge()
    {
        $a1 = [
            1,
            2,
            'a' => [
                'b' => 1,
                'c' => 2,
                3,
                4
            ],
            'b' => [
                'c' => [1]
            ]
        ];
        $a2 = [
            'a' => '123',
            3,
            4,
            'b' => [
                1,
                2,
                3,
                'c' => [1]
            ]
        ];

        $c = new Container();
        $this->assertEquals($c, $c->merge($a1));
        $this->assertEquals($a1, $c->toArray());
        $this->assertEquals($c, $c->merge([]));
        $this->assertEquals($a1, $c->toArray());

        $this->assertEquals($c, $c->merge($a2));
        $r = [
            1,
            2,
            'a' => '123',
            'b' => [
                'c' => [1, 1],
                1,
                2,
                3
            ],
            3,
            4
        ];
        $this->assertEquals($r, $c->toArray());

        $c = new Container($a2);
        $this->assertEquals($c, $c->merge($a1));
        $r = [
            'a' => [
                'b' => 1,
                'c' => 2,
                3,
                4
            ],
            3,
            4,
            'b' => [
                1,
                2,
                3,
                'c' => [1, 1]
            ],
            1,
            2
        ];
        $this->assertEquals($r, $c->toArray());
    }

    /**
     * @covers Container::clean
     * @depends testToArray
     */
    public function testClean()
    {
        $c = new Container([1, 2, 3]);
        $this->assertEquals($c, $c->clean());
        $this->assertEquals([], $c->toArray());
    }

    /**
     * @covers Container::copy
     * @depends testToArray
     */
    public function testCopy()
    {
        $c = new Container([]);
        $copy = $c->copy();
        $this->assertEquals([], $copy->toArray());
        $a = ['a', 'b', 'c'];
        $c = new Container($a);
        $copy = $c->copy();
        $this->assertEquals($a, $copy->toArray());
    }

    /**
     * @covers Container::each
     */
    public function testEach()
    {
        $a = ['a', 'b', 'c', 'd'];
        $count = 0;
        $callback = function($item, $key) use($a, &$count) {
            $this->assertEquals($a[$key], $item);
            ++$count;
            if ($item == 'c') {
                return false;
            }
            return true;
        };
        $c = new Container($a);
        $c->each($callback);
        $this->assertEquals(3, $count);
    }

    /**
     * @covers Container::first
     * @depends testToArray
     */
    public function testFirst()
    {
        $c = new Container([7, 8, 9]);
        $this->assertEquals(7, $c->first());
        $this->assertEquals([7, 8, 9], $c->toArray());
    }

    /**
     * @covers Container::last
     * @depends testToArray
     */
    public function testLast()
    {
        $c = new Container([7, 8, 9]);
        $this->assertEquals(9, $c->last());
        $this->assertEquals([7, 8, 9], $c->toArray());
    }

    /**
     * @covers Container::push
     * @depends testToArray
     */
    public function testPush()
    {
        $c = new Container();
        $this->assertEquals($c, $c->push(1));
        $this->assertEquals($c, $c->push(2));
        $this->assertEquals([1, 2], $c->toArray());
        $this->assertEquals($c, $c->push(3, 'a'));
        $this->assertEquals([1, 2, 'a' => 3], $c->toArray());
        $this->assertEquals($c, $c->push(4, 'b'));
        $this->assertEquals([1, 2, 'a' => 3, 'b' => 4], $c->toArray());
    }

    /**
     * @covers Container::pop
     * @depends testToArray
     */
    public function testPop()
    {
        $c = new Container(['a' => 1, 2, 3]);
        $this->assertEquals(3, $c->pop());
        $this->assertEquals(['a' => 1, 2], $c->toArray());
        $this->assertEquals(2, $c->pop());
        $this->assertEquals(['a' => 1], $c->toArray());
        $this->assertEquals(1, $c->pop());
        $this->assertEquals([], $c->toArray());
    }

    /**
     * @covers Container::prepend
     * @depends testToArray
     */
    public function testPrepend()
    {
        $c = new Container();
        $this->assertEquals($c, $c->prepend(1));
        $this->assertEquals($c, $c->prepend(2));
        $this->assertEquals([2, 1], $c->toArray());
        $this->assertEquals($c, $c->prepend(3, 'a'));
        $this->assertEquals(['a' => 3, 2, 1], $c->toArray());
        $this->assertEquals($c, $c->prepend(4, 'b'));
        $this->assertEquals(['b' => 4, 'a' => 3, 2, 1], $c->toArray());
    }

    /**
     * @covers Container::shift
     * @depends testToArray
     */
    public function testShift()
    {
        $c = new Container(['a' => 1, 2, 3]);
        $this->assertEquals(1, $c->shift());
        $this->assertEquals([2, 3], $c->toArray());
        $this->assertEquals(2, $c->shift());
        $this->assertEquals([3], $c->toArray());
        $this->assertEquals(3, $c->shift());
        $this->assertEquals([], $c->toArray());
    }

    /**
     * @covers Container::get
     *
     */
    public function testGet()
    {
        $c = new Container($this->arr);
        $this->assertEquals([1, 2, 3], $c->get('a'));
        $this->assertEquals(1, $c->get('b.d'));
        $this->assertEquals(2, $c->get('b.f.1'));

        $this->assertNull($c->get('c'));
        $this->assertNull($c->get('a.b.c'));

        $this->assertEquals(1, $c->get('a.b.c', 1));
        $this->assertNotEquals(1, $c->get('c', 1));

        $this->assertEquals(1, $c->get('a:b:c', 1, ':'));
    }

    /**
     * @covers Container::set
     * @depends testGet
     */
    public function testSet()
    {
        $c = new Container($this->arr);
        $this->assertEquals($c, $c->set('c', '123'));
        $this->assertEquals('123', $c->get('c'));

        $this->assertEquals($c, $c->set('f.b.c', 123));
        $this->assertEquals(123, $c->get('f.b.c'));

        $this->assertEquals($c, $c->set('a.0.c', []));
        $this->assertEquals([], $c->get('a.0.c'));
        $this->assertEquals([['c' => []], 2, 3], $c->get('a'));

        $this->assertEquals($c, $c->set('b.f', 'test'));
        $this->assertEquals('test', $c->get('b.f'));

        $this->assertEquals($c, $c->set('b', ['f' => ['a', 'b', 'c']], true));
        $this->assertEquals(['a', 'b', 'c'], $c->get('b.f'));

        $this->assertEquals($c, $c->set('b.f', ['c', 'd', 'e'], true));
        $this->assertEquals(['a', 'b', 'c', 'c', 'd', 'e'], $c->get('b.f'));

        $this->assertEquals($c, $c->set('1/2/3', 0, true, '/'));
        $this->assertEquals(0, $c->get('1.2.3'));
    }

    /**
     * @covers Container::has
     */
    public function testHas()
    {
        $c = new Container($this->arr);
        $this->assertTrue($c->has('a'));
        $this->assertTrue($c->has('b.d'));
        $this->assertTrue($c->has('b.f.1'));
        $this->assertTrue($c->has('c'));

        $this->assertFalse($c->has('a.b.c'));
        $this->assertFalse($c->has('a+b+c', '+'));

        $this->assertTrue($c->has('b-f-2', '-'));
    }

    /**
     * @covers Container::remove
     * @depends testHas
     */
    public function testRemove()
    {
        $c = new Container($this->arr);
        $this->assertEquals($c, $c->remove('d'));
        $this->assertFalse($c->has('d'));

        $this->assertEquals($c, $c->remove('a.1'));
        $this->assertFalse($c->has('a.1'));

        $this->assertEquals($c, $c->remove('a.0', true));
        $this->assertEquals($c, $c->remove('a.2', true));
        $this->assertFalse($c->has('a'));

        $c->remove('b.f.0');
        $c->remove('b.f.1');
        $c->remove('b.f.2');
        $this->assertTrue($c->has('b.f'));

        $c->remove('b~f', false, '~');
        $this->assertFalse($c->has('b.f'));

        $c->remove('a.b.c');
        $this->assertFalse($c->has('a.b.c'));
    }
}