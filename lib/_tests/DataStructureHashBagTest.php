<?php

use Aleph\Data\Structures\{HashBag, HashSet};
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Aleph\Data\Structures\HashBag class.
 *
 * @group data-structures
 */
class DataStructureHashBagTest extends TestCase
{
    /**
     * @covers HashBag::isEmpty
     * @covers HashBag::count
     */
    public function testEmptyBag()
    {
        $bag = new HashBag();
        $this->assertTrue($bag->isEmpty());
        $this->assertEquals(0, count($bag));
    }

    /**
     * @covers HashBag::contains
     * @covers HashBag::add
     * @covers HashBag::multiplicity
     * @depends testEmptyBag
     */
    public function testAdd()
    {
        $item1 = 'a';
        $item2 = 'b';
        $item3 = ['b'];
        $item4 = new \stdClass;
        $item5 = new \stdClass;

        $bag = new HashBag();
        $this->assertFalse($bag->contains('a'));

        $bag->add($item1, $item1, $item1, $item2, $item3, $item3, $item4, $item4);
        $this->assertTrue($bag->contains($item4));
        $this->assertTrue($bag->contains($item1, $item2, $item4));
        $this->assertFalse($bag->contains($item3, $item5));

        $this->assertEquals(3, $bag->multiplicity($item1));
        $this->assertEquals(1, $bag->multiplicity($item2));
        $this->assertEquals(2, $bag->multiplicity($item3));
        $this->assertEquals(2, $bag->multiplicity($item4));
        $this->assertEquals(0, $bag->multiplicity($item5));
    }

    /**
     * @covers HashBag::addInstances
     * @depends testAdd
     */
    public function testAddInstances()
    {
        $bag = new HashBag('c', 'c');

        $bag->addInstances('a', 3);
        $this->assertEquals(3, $bag->multiplicity('a'));

        $bag->addInstances('b', 2);
        $this->assertEquals(2, $bag->multiplicity('b'));

        $bag->addInstances('c', 5);
        $this->assertEquals(7, $bag->multiplicity('c'));

        $bag->addInstances('c', 0);
        $bag->addInstances('c', -5);
        $this->assertEquals(7, $bag->multiplicity('c'));
    }

    /**
     * @covers HashBag::addInstances
     * @depends testAddInstances
     */
    public function testRemove()
    {
        $bag = new HashBag();
        $bag->addInstances('a', 1);
        $bag->addInstances('b', 2);
        $bag->addInstances('c', 3);

        $bag->remove('a');
        $bag->remove('b');
        $bag->remove('c');
        $bag->remove('d');
        $this->assertTrue($bag->isEmpty());
    }

    /**
     * @covers HashBag::removeInstances
     * @depends testAddInstances
     */
    public function testRemoveInstances()
    {
        $bag = new HashBag();
        $bag->addInstances('a', 5);
        $this->assertEquals(5, $bag->multiplicity('a'));

        $bag->removeInstances('a', 3);
        $this->assertEquals(2, $bag->multiplicity('a'));

        $bag->removeInstances('a', 5);
        $this->assertEquals(0, $bag->multiplicity('a'));
        $this->assertTrue($bag->isEmpty());
    }

    /**
     * @covers HashBag::union
     * @depends testAdd
     */
    public function testUnion()
    {
        $bag1 = new HashBag('a', 'b', 'b', 'c', 'c', 'c');
        $bag2 = new HashBag('a', 'a', 'a', 'b', 'b', 'c', 'd');
        $empty = new HashBag();

        $union = $bag1->union($empty);
        $this->assertEquals(1, $union->multiplicity('a'));
        $this->assertEquals(2, $union->multiplicity('b'));
        $this->assertEquals(3, $union->multiplicity('c'));
        $this->assertTrue($union->contains('a', 'b', 'c'));

        $union = $empty->union($bag2);
        $this->assertEquals(3, $union->multiplicity('a'));
        $this->assertEquals(2, $union->multiplicity('b'));
        $this->assertEquals(1, $union->multiplicity('c'));
        $this->assertEquals(1, $union->multiplicity('d'));
        $this->assertTrue($union->contains('a', 'b', 'c', 'd'));

        $union = $empty->union($empty);
        $this->assertTrue($union->isEmpty());

        $union = $bag1->union($bag2);
        $this->assertEquals(3, $union->multiplicity('a'));
        $this->assertEquals(2, $union->multiplicity('b'));
        $this->assertEquals(3, $union->multiplicity('c'));
        $this->assertEquals(1, $union->multiplicity('d'));
        $this->assertTrue($union->contains('a', 'b', 'c', 'd'));
        $this->assertEquals(9, $union->count());

        $union = $bag2->union($bag1);
        $this->assertEquals(3, $union->multiplicity('a'));
        $this->assertEquals(2, $union->multiplicity('b'));
        $this->assertEquals(3, $union->multiplicity('c'));
        $this->assertEquals(1, $union->multiplicity('d'));
        $this->assertTrue($union->contains('a', 'b', 'c', 'd'));
        $this->assertEquals(9, $union->count());
    }

    /**
     * @covers HashBag::intersect
     * @depends testAdd
     */
    public function testIntersect()
    {
        $bag1 = new HashBag('a', 'b', 'b', 'c', 'c', 'c');
        $bag2 = new HashBag('a', 'a', 'a', 'b', 'b', 'c', 'd');
        $empty = new HashBag();

        $intersect = $bag1->intersect($empty);
        $this->assertTrue($intersect->isEmpty());

        $intersect = $empty->intersect($bag2);
        $this->assertTrue($intersect->isEmpty());

        $intersect = $empty->intersect($empty);
        $this->assertTrue($intersect->isEmpty());

        $intersect = $bag1->intersect($bag2);
        $this->assertEquals(1, $intersect->multiplicity('a'));
        $this->assertEquals(2, $intersect->multiplicity('b'));
        $this->assertEquals(1, $intersect->multiplicity('c'));
        $this->assertEquals(0, $intersect->multiplicity('d'));
        $this->assertEquals(4, $intersect->count());

        $intersect = $bag2->intersect($bag1);
        $this->assertEquals(1, $intersect->multiplicity('a'));
        $this->assertEquals(2, $intersect->multiplicity('b'));
        $this->assertEquals(1, $intersect->multiplicity('c'));
        $this->assertEquals(0, $intersect->multiplicity('d'));
        $this->assertEquals(4, $intersect->count());
    }

    /**
     * @covers HashBag::sum
     * @depends testAdd
     */
    public function testSum()
    {
        $bag1 = new HashBag('a', 'b', 'b', 'c', 'c', 'c');
        $bag2 = new HashBag('a', 'a', 'a', 'b', 'b', 'c', 'd');
        $empty = new HashBag();

        $sum = $bag1->sum($empty);
        $this->assertEquals(1, $sum->multiplicity('a'));
        $this->assertEquals(2, $sum->multiplicity('b'));
        $this->assertEquals(3, $sum->multiplicity('c'));
        $this->assertTrue($sum->contains('a', 'b', 'c'));

        $sum = $empty->sum($bag2);
        $this->assertEquals(3, $sum->multiplicity('a'));
        $this->assertEquals(2, $sum->multiplicity('b'));
        $this->assertEquals(1, $sum->multiplicity('c'));
        $this->assertEquals(1, $sum->multiplicity('d'));
        $this->assertTrue($sum->contains('a', 'b', 'c', 'd'));

        $sum = $empty->sum($empty);
        $this->assertTrue($sum->isEmpty());

        $sum = $bag1->sum($bag2);
        $this->assertEquals(4, $sum->multiplicity('a'));
        $this->assertEquals(4, $sum->multiplicity('b'));
        $this->assertEquals(4, $sum->multiplicity('c'));
        $this->assertEquals(1, $sum->multiplicity('d'));
        $this->assertTrue($sum->contains('a', 'b', 'c', 'd'));
        $this->assertEquals(13, $sum->count());

        $sum = $bag2->sum($bag1);
        $this->assertEquals(4, $sum->multiplicity('a'));
        $this->assertEquals(4, $sum->multiplicity('b'));
        $this->assertEquals(4, $sum->multiplicity('c'));
        $this->assertEquals(1, $sum->multiplicity('d'));
        $this->assertTrue($sum->contains('a', 'b', 'c', 'd'));
        $this->assertEquals(13, $sum->count());
    }

    /**
     * @covers HashBag::diff
     * @depends testAdd
     */
    public function testDiff()
    {
        $bag1 = new HashBag('a', 'b', 'b', 'c', 'c', 'c');
        $bag2 = new HashBag('a', 'a', 'a', 'b', 'b', 'c', 'd');
        $empty = new HashBag();

        $diff = $bag1->sum($empty);
        $this->assertEquals(1, $diff->multiplicity('a'));
        $this->assertEquals(2, $diff->multiplicity('b'));
        $this->assertEquals(3, $diff->multiplicity('c'));
        $this->assertTrue($diff->contains('a', 'b', 'c'));

        $diff = $empty->diff($bag2);
        $this->assertTrue($diff->isEmpty());

        $diff = $empty->diff($empty);
        $this->assertTrue($diff->isEmpty());

        $diff = $bag1->diff($bag2);
        $this->assertEquals(0, $diff->multiplicity('a'));
        $this->assertEquals(0, $diff->multiplicity('b'));
        $this->assertEquals(2, $diff->multiplicity('c'));
        $this->assertEquals(0, $diff->multiplicity('d'));
        $this->assertTrue($diff->contains('c'));
        $this->assertEquals(2, $diff->count());

        $diff = $bag2->diff($bag1);
        $this->assertEquals(2, $diff->multiplicity('a'));
        $this->assertEquals(0, $diff->multiplicity('b'));
        $this->assertEquals(0, $diff->multiplicity('c'));
        $this->assertEquals(1, $diff->multiplicity('d'));
        $this->assertTrue($diff->contains('a', 'd'));
        $this->assertEquals(3, $diff->count());
    }

    /**
     * @covers HashBag::symdiff
     * @depends testAdd
     */
    public function testSymdiff()
    {
        $bag1 = new HashBag('a', 'b', 'b', 'c', 'c', 'c');
        $bag2 = new HashBag('a', 'a', 'a', 'b', 'b', 'c', 'd');
        $empty = new HashBag();

        $symdiff = $bag1->symdiff($empty);
        $this->assertEquals(1, $symdiff->multiplicity('a'));
        $this->assertEquals(2, $symdiff->multiplicity('b'));
        $this->assertEquals(3, $symdiff->multiplicity('c'));
        $this->assertTrue($symdiff->contains('a', 'b', 'c'));

        $symdiff = $empty->symdiff($bag2);
        $this->assertEquals(3, $symdiff->multiplicity('a'));
        $this->assertEquals(2, $symdiff->multiplicity('b'));
        $this->assertEquals(1, $symdiff->multiplicity('c'));
        $this->assertEquals(1, $symdiff->multiplicity('d'));
        $this->assertTrue($symdiff->contains('a', 'b', 'c', 'd'));

        $symdiff = $empty->symdiff($empty);
        $this->assertTrue($symdiff->isEmpty());

        $symdiff = $bag1->symdiff($bag2);
        $this->assertEquals(2, $symdiff->multiplicity('a'));
        $this->assertEquals(0, $symdiff->multiplicity('b'));
        $this->assertEquals(2, $symdiff->multiplicity('c'));
        $this->assertEquals(1, $symdiff->multiplicity('d'));
        $this->assertTrue($symdiff->contains('a', 'c', 'd'));
        $this->assertEquals(5, $symdiff->count());

        $symdiff = $bag2->symdiff($bag1);
        $this->assertEquals(2, $symdiff->multiplicity('a'));
        $this->assertEquals(0, $symdiff->multiplicity('b'));
        $this->assertEquals(2, $symdiff->multiplicity('c'));
        $this->assertEquals(1, $symdiff->multiplicity('d'));
        $this->assertTrue($symdiff->contains('a', 'c', 'd'));
        $this->assertEquals(5, $symdiff->count());

        $symdiff = $symdiff->symdiff($symdiff);
        $this->assertTrue($symdiff->isEmpty());
    }

    /**
     * @covers HashBag::clean
     * @depends testAdd
     */
    public function testClean()
    {
        $bag = new HashBag(1, 2, 2, 3, 3, 3);
        $this->assertFalse($bag->isEmpty());

        $bag->clean();
        $this->assertEquals(0, $bag->multiplicity(1));
        $this->assertEquals(0, $bag->multiplicity(2));
        $this->assertEquals(0, $bag->multiplicity(3));
        $this->assertTrue($bag->isEmpty());
    }

    /**
     * @covers HashBag::toArray
     * @depends testAdd
     */
    public function testToArray()
    {
        $bag = new HashBag();
        $this->assertEquals([], $bag->toArray());

        $bag->add(1);
        $this->assertEquals([1], $bag->toArray());

        $bag->add('a', 'a', ['b', 'c'], 2, 2, 3, 3, 3);
        $this->assertEquals([1, 'a', 'a', ['b', 'c'], 2, 2, 3, 3, 3], $bag->toArray());
    }

    /**
     * @covers HashBag::toJson
     * @depends testAdd
     */
    public function testToJson()
    {
        $bag = new HashBag();
        $this->assertEquals(json_encode([]), $bag->toJson());

        $bag->add(1);
        $this->assertEquals(json_encode([1]), $bag->toJson());

        $bag->add('a', 'a', ['b', 'c'], 2, 2, 3, 3, 3);
        $this->assertEquals(json_encode([1, 'a', 'a', ['b', 'c'], 2, 2, 3, 3, 3]), $bag->toJson());
    }

    /**
     * @covers HashBag::toSet
     * @depends testAdd
     */
    public function testToSet()
    {
        $bag = new HashBag();
        $this->assertEquals((new HashSet())->toArray(), $bag->toSet()->toArray());

        $bag->add(1, 2, 2, 3, 3, 3);
        $this->assertEquals((new HashSet(1, 2, 3))->toArray(), $bag->toSet()->toArray());
    }

    /**
     * @covers HashBag::grab
     * @depends testToArray
     */
    public function testGrab()
    {
        $bag = new HashBag(1, 2, 2, 3, 3, 3);
        $items = $bag->toArray();

        for ($i = 0; $i < 3; ++$i) {
            $this->assertContains($bag->grab(false), $items);
        }
        $this->assertEquals(6, $bag->count());

        for ($i = 0; $i < 6; ++$i) {
            $this->assertContains($bag->grab(true), $items);
        }
        $this->assertTrue($bag->isEmpty());
    }

    /**
     * @covers HashBag::getIterator
     * @depends testToArray
     */
    public function testIterator()
    {
        $bag = new HashBag('a', 'b', 'b', 'c', 'c', 'c', 'd');
        $items = $bag->toArray();

        foreach ($bag as $n => $item) {
            $this->assertEquals($items[$n], $item);
        }
    }
}