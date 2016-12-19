<?php

use Aleph\Data\Structures\HashSet;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Aleph\Data\Structures\HashSet class.
 *
 * @group data-structures
 */
class DataStructureHashSetTest extends TestCase
{
    /**
     * @covers HashSet::isEmpty
     * @covers HashSet::count
     */
    public function testEmptySet()
    {
        $set = new HashSet();
        $this->assertTrue($set->isEmpty());
        $this->assertEquals(0, count($set));
    }

    /**
     * @covers HashSet::contains
     * @covers HashSet::add
     * @depends testEmptySet
     */
    public function testAdd()
    {
        $item1 = 'a';
        $item2 = 'a';
        $item3 = 'b';
        $item4 = ['b'];
        $item5 = new \stdClass;
        $item6 = new \stdClass;
        $set = new HashSet();
        $this->assertFalse($set->contains('a'));
        $set->add($item1, $item2, $item3, $item4, $item5);
        $this->assertTrue($set->contains($item4));
        $this->assertTrue($set->contains($item1, $item2, $item5));
        $this->assertFalse($set->contains($item3, $item6));
    }

    /**
     * @covers HashSet::remove
     * @depends testAdd
     */
    public function testRemove()
    {
        $items = [
            0,
            'a',
            'b',
            'c',
            [1, 2, 3],
            new \stdClass,
            ['a' => new \stdClass, ['b' => 2, 3]]
        ];
        $set = new HashSet(...$items);
        $this->assertTrue($set->contains(...$items));
        $set->remove($items[3]);
        $this->assertFalse($set->contains($items[3]));
        $set->remove(...$items);
        $this->assertTrue($set->isEmpty());
    }

    /**
     * @covers HashSet::toArray
     * @depends testAdd
     */
    public function testToArray()
    {
        $set = new HashSet();
        $this->assertEquals([], $set->toArray());
        $set->add(1);
        $this->assertEquals([1], $set->toArray());
        $set->add('a', ['b', 'c'], 123);
        $this->assertEquals([1, 'a', ['b', 'c'], 123], $set->toArray());
    }

    /**
     * @covers HashSet::toJson
     * @depends testAdd
     */
    public function testToJson()
    {
        $set = new HashSet();
        $this->assertEquals(json_encode([]), $set->toJson());
        $set->add(1);
        $this->assertEquals(json_encode([1]), $set->toJson());
        $set->add('a', ['b', 'c'], 123);
        $this->assertEquals(json_encode([1, 'a', ['b', 'c'], 123]), $set->toJson());
    }

    /**
     * @covers HashSet::union
     * @depends testAdd
     */
    public function testUnion()
    {
        $set1 = new HashSet('a', 'b', 'c', 'd');
        $set2 = new HashSet('c', 'd', 'e', 'f', 'g');
        $empty = new HashSet();
        $union = $set1->union($empty);
        $this->assertTrue($union->contains('a', 'b', 'c', 'd'));
        $this->assertEquals(4, $union->count());
        $union = $empty->union($set2);
        $this->assertTrue($union->contains('c', 'd', 'e', 'f', 'g'));
        $this->assertEquals(5, $union->count());
        $union = $empty->union($empty);
        $this->assertTrue($union->isEmpty());
        $union = $set1->union($set2);
        $this->assertTrue($union->contains('a', 'b', 'c', 'd', 'e', 'f', 'g'));
        $this->assertEquals(7, $union->count());
    }

    /**
     * @covers HashSet::intersect
     * @depends testAdd
     */
    public function testIntersect()
    {
        $set1 = new HashSet('a', 'b', 'c', 'd');
        $set2 = new HashSet('c', 'd', 'e', 'f', 'g');
        $empty = new HashSet();
        $intersect = $set1->intersect($empty);
        $this->assertTrue($intersect->isEmpty());
        $intersect = $empty->intersect($set2);
        $this->assertTrue($intersect->isEmpty());
        $intersect = $empty->intersect($empty);
        $this->assertTrue($intersect->isEmpty());
        $intersect = $set1->intersect($set2);
        $this->assertTrue($intersect->contains('c', 'd'));
        $this->assertEquals(2, $intersect->count());
        $intersect = $intersect->intersect(new HashSet('a', 'b'));
        $this->assertTrue($intersect->isEmpty());
    }

    /**
     * @covers HashSet::diff
     * @depends testAdd
     */
    public function testDiff()
    {
        $set1 = new HashSet('a', 'b', 'c', 'd');
        $set2 = new HashSet('c', 'd', 'e', 'f', 'g');
        $empty = new HashSet();
        $diff = $set1->diff($empty);
        $this->assertTrue($diff->contains('a', 'b', 'c', 'd'));
        $this->assertEquals(4, $diff->count());
        $diff = $empty->diff($set2);
        $this->assertTrue($diff->isEmpty());
        $diff = $empty->diff($empty);
        $this->assertTrue($diff->isEmpty());
        $diff = $set1->diff($set2);
        $this->assertTrue($diff->contains('a', 'b'));
        $this->assertEquals(2, $diff->count());
        $diff = $diff->diff($diff);
        $this->assertTrue($diff->isEmpty());
    }

    /**
     * @covers HashSet::symdiff
     * @depends testAdd
     */
    public function testSymdiff()
    {
        $set1 = new HashSet('a', 'b', 'c', 'd');
        $set2 = new HashSet('c', 'd', 'e', 'f', 'g');
        $empty = new HashSet();
        $diff = $set1->symdiff($empty);
        $this->assertTrue($diff->contains('a', 'b', 'c', 'd'));
        $this->assertEquals(4, $diff->count());
        $diff = $empty->symdiff($set2);
        $this->assertTrue($diff->contains('c', 'd', 'e', 'f', 'g'));
        $diff = $empty->symdiff($empty);
        $this->assertTrue($diff->isEmpty());
        $diff = $set1->symdiff($set2);
        $this->assertTrue($diff->contains('a', 'b', 'e', 'f', 'g'));
        $this->assertEquals(5, $diff->count());
        $diff = $diff->symdiff($diff);
        $this->assertTrue($diff->isEmpty());
    }

    /**
     * @covers HashSet::clean
     * @depends testAdd
     */
    public function testClean()
    {
        $set = new HashSet(1, 2, 3);
        $this->assertFalse($set->isEmpty());
        $set->clean();
        $this->assertTrue($set->isEmpty());
    }

    /**
     * @covers HashSet::grab
     * @depends testAdd
     * @depends testToArray
     */
    public function testGrab()
    {
        $set = new HashSet('a', 'b', 'c', 'd', 'e');
        $items = $set->toArray();
        for ($i = 0; $i < 3; ++$i) {
            $this->assertContains($set->grab(false), $items);
        }
        $this->assertEquals(5, $set->count());
        for ($i = 0; $i < 5; ++$i) {
            $this->assertContains($set->grab(true), $items);
        }
        $this->assertTrue($set->isEmpty());
    }

    /**
     * @covers HashSet::getIterator
     * @depends testAdd
     */
    public function testIterator()
    {
        $set = new HashSet('a', 'b', 'c', 'd', 'e', 'f');
        $items = $set->toArray();
        foreach ($set as $n => $item) {
            $this->assertEquals($items[$n], $item);
        }
    }
}