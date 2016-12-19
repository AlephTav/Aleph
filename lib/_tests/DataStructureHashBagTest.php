<?php

use Aleph\Data\Structures\HashBag;
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
}