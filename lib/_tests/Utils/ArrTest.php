<?php

use Aleph\Utils\Arr;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Aleph\Utils\Arr class.
 *
 * @group utils
 */
class ArrTest extends TestCase
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
     * @covers Arr::isSequential
     */
    public function testIsSequential()
    {
        $this->assertTrue(Arr::isSequential(['a']));
        $this->assertTrue(Arr::isSequential(['a', 'b']));
        $this->assertTrue(Arr::isSequential(['a', 'b', 'c']));

        $this->assertFalse(Arr::isSequential([]));
        $this->assertFalse(Arr::isSequential([1 => 'a']));
        $this->assertFalse(Arr::isSequential(['a', 'b', 3 => 'c']));
        $this->assertFalse(Arr::isSequential(['a', 'b' => 2, 'c']));
    }

    /**
     * @covers Arr::isNumeric
     */
    public function testIsNumeric()
    {
        $this->assertTrue(Arr::isNumeric(['a']));
        $this->assertTrue(Arr::isNumeric(['a', 2 => 'b']));
        $this->assertTrue(Arr::isNumeric(['a', 'b', 'c']));
        $this->assertTrue(Arr::isNumeric([7 => 'a', 6 => 'b', 9 => 'c']));

        $this->assertFalse(Arr::isNumeric([]));
        $this->assertFalse(Arr::isNumeric(['a' => 'b']));
        $this->assertFalse(Arr::isNumeric([1 => 'a', 'b' => 'c']));
        $this->assertFalse(Arr::isNumeric(['a', 'b', 'c' => 2]));
    }

    /**
     * @covers Arr::isAssoc
     */
    public function testIsAssoc()
    {
        $this->assertTrue(Arr::isAssoc(['a' => 1]));
        $this->assertTrue(Arr::isAssoc(['a' => 1, 'b' => 2]));
        $this->assertTrue(Arr::isAssoc(['a' => 1, 'b' => 2, 'c' => 3]));

        $this->assertFalse(Arr::isAssoc([]));
        $this->assertFalse(Arr::isAssoc(['a']));
        $this->assertFalse(Arr::isAssoc([1 => 'a', 'c']));
        $this->assertFalse(Arr::isAssoc(['a' => 1, 2 => 'b', 'c' => 3]));
    }

    /**
     * @covers Arr::isMixed
     */
    public function testIsMixed()
    {
        $this->assertTrue(Arr::isMixed(['a' => 1]));
        $this->assertTrue(Arr::isMixed(['a']));
        $this->assertTrue(Arr::isMixed(['a' => 1, 2 => 'b']));
        $this->assertTrue(Arr::isMixed([2 => 'a', 'b' => 2, 3 => 'c']));

        $this->assertFalse(Arr::isMixed([]));
        $this->assertFalse(Arr::isMixed(['a', 'b']));
        $this->assertFalse(Arr::isMixed([1 => 'a', 'c']));
        $this->assertFalse(Arr::isMixed(['a' => 1, 'b' => 2, 'c' => 3]));
    }

    /**
     * @covers Arr::get
     */
    public function testGet()
    {
        $this->assertEquals([1, 2, 3], Arr::get($this->arr, 'a'));
        $this->assertEquals(1, Arr::get($this->arr, 'b.d'));
        $this->assertEquals(2, Arr::get($this->arr, 'b.f.1'));

        $this->assertNull(Arr::get($this->arr, 'c'));
        $this->assertNull(Arr::get($this->arr, 'a.b.c'));

        $this->assertEquals(1, Arr::get($this->arr, 'a.b.c', 1));
        $this->assertNotEquals(1, Arr::get($this->arr, 'c', 1));

        $this->assertEquals(1, Arr::get($this->arr, 'a:b:c', 1, ':'));
    }

    /**
     * @covers Arr::set
     * @depends testGet
     */
    public function testSet()
    {
        Arr::set($this->arr, 'c', '123');
        $this->assertEquals('123', Arr::get($this->arr, 'c'));

        Arr::set($this->arr, 'f.b.c', 123);
        $this->assertEquals(123, Arr::get($this->arr, 'f.b.c'));

        Arr::set($this->arr, 'a.0.c', []);
        $this->assertEquals([], Arr::get($this->arr, 'a.0.c'));
        $this->assertEquals([['c' => []], 2, 3], Arr::get($this->arr, 'a'));

        Arr::set($this->arr, 'b.f', 'test');
        $this->assertEquals('test', Arr::get($this->arr, 'b.f'));

        Arr::set($this->arr, 'b', ['f' => ['a', 'b', 'c']], true);
        $this->assertEquals(['a', 'b', 'c'], Arr::get($this->arr, 'b.f'));

        Arr::set($this->arr, 'b.f', ['c', 'd', 'e'], true);
        $this->assertEquals(['a', 'b', 'c', 'c', 'd', 'e'], Arr::get($this->arr, 'b.f'));

        Arr::set($this->arr, '1/2/3', 0, true, '/');
        $this->assertEquals(0, Arr::get($this->arr, '1.2.3'));
    }

    /**
     * @covers Arr::has
     */
    public function testHas()
    {
        $this->assertTrue(Arr::has($this->arr, 'a'));
        $this->assertTrue(Arr::has($this->arr, 'b.d'));
        $this->assertTrue(Arr::has($this->arr, 'b.f.1'));
        $this->assertTrue(Arr::has($this->arr, 'c'));

        $this->assertFalse(Arr::has($this->arr, 'a.b.c'));
        $this->assertFalse(Arr::has($this->arr, 'a+b+c', '+'));

        $this->assertTrue(Arr::has($this->arr, 'b-f-2', '-'));
    }

    /**
     * @covers Arr::remove
     * @depends testHas
     */
    public function testRemove()
    {
        Arr::remove($this->arr, 'd');
        $this->assertFalse(Arr::has($this->arr, 'd'));

        Arr::remove($this->arr, 'a.1');
        $this->assertFalse(Arr::has($this->arr, 'a.1'));

        Arr::remove($this->arr, 'a.0', true);
        Arr::remove($this->arr, 'a.2', true);
        $this->assertFalse(Arr::has($this->arr, 'a'));

        Arr::remove($this->arr, 'b.f.0');
        Arr::remove($this->arr, 'b.f.1');
        Arr::remove($this->arr, 'b.f.2');
        $this->assertTrue(Arr::has($this->arr, 'b.f'));

        Arr::remove($this->arr, 'b~f', false, '~');
        $this->assertFalse(Arr::has($this->arr, 'b.f'));

        Arr::remove($this->arr, 'a.b.c');
        $this->assertFalse(Arr::has($this->arr, 'a.b.c'));
    }

    /**
     * Arr::swap
     */
    public function testSwap()
    {
        // Swap values for the given element keys.
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        Arr::swap($arr, 'b', 'b');
        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $arr);
        Arr::swap($arr, 'b', 'c');
        $this->assertEquals(['a' => 1, 'b' => 3, 'c' => 2], $arr);
        Arr::swap($arr, 'a', 'c');
        $this->assertEquals(['a' => 2, 'b' => 3, 'c' => 1], $arr);
        Arr::swap($arr, 'b', 'd');
        $this->assertEquals(['a' => 2, 'b' => null, 'c' => 1, 'd' => 3], $arr);
        Arr::swap($arr, 'a', 'e');
        $this->assertEquals(['a' => null, 'b' => null, 'c' => 1, 'd' => 3, 'e' => 2], $arr);

        // Swap values for the given element indexes.
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        Arr::swap($arr, 1, 1, true);
        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $arr);
        Arr::swap($arr, 1, 2, true);
        $this->assertEquals(['a' => 1, 'b' => 3, 'c' => 2], $arr);
        Arr::swap($arr, 0, 2, true);
        $this->assertEquals(['a' => 2, 'b' => 3, 'c' => 1], $arr);
        Arr::swap($arr, 1, 3, true);
        $this->assertEquals(['a' => 2, 'b' => null, 'c' => 1, 3 => 3], $arr);
        Arr::swap($arr, 0, 4, true);
        $this->assertEquals(['a' => null, 'b' => null, 'c' => 1, 3 => 3, 4 => 2], $arr);

        // Swap values with keys for the given element keys.
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        Arr::swap($arr, 'c', 'c', false, true);
        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $arr);
        Arr::swap($arr, 'b', 'c', false, true);
        $this->assertEquals(['a' => 1, 'c' => 3, 'b' => 2], $arr);
        Arr::swap($arr, 'a', 'c', false, true);
        $this->assertEquals(['c' => 3, 'a' => 1, 'b' => 2], $arr);
        Arr::swap($arr, 'b', 'd', false, true);
        $this->assertEquals(['c' => 3, 'a' => 1, 'd' => null], $arr);
        Arr::swap($arr, 'a', 'e', false, true);
        $this->assertEquals(['c' => 3, 'e' => null, 'd' => null], $arr);

        // Swap values with keys for the given element indexes.
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        Arr::swap($arr, 3, 3, true, true);
        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $arr);
        Arr::swap($arr, 1, 2, true, true);
        $this->assertEquals(['a' => 1, 'c' => 3, 'b' => 2], $arr);
        Arr::swap($arr, 0, 2, true, true);
        $this->assertEquals(['b' => 2, 'c' => 3, 'a' => 1], $arr);
        Arr::swap($arr, 1, 3, true, true);
        $this->assertEquals(['b' => 2, 3 => null, 'a' => 1], $arr);
        Arr::swap($arr, 0, 4, true, true);
        $this->assertEquals([4 => null, 3 => null, 'a' => 1], $arr);
    }

    /**
     * @covers Arr::insert
     */
    public function testInsert()
    {
        $arr = [];
        Arr::insert($arr, 1);
        $this->assertEquals([1], $arr);

        $arr = [];
        Arr::insert($arr, 'a', 3);
        $this->assertEquals(['a'], $arr);
        Arr::insert($arr, 'b', 1);
        $this->assertEquals(['a', 'b'], $arr);
        Arr::insert($arr, 'c', 5);
        $this->assertEquals(['a', 'b', 'c'], $arr);
        Arr::insert($arr, '@', -1);
        $this->assertEquals(['@', 'a', 'b', 'c'], $arr);
        Arr::insert($arr, ['d', 'e'], 2);
        $this->assertEquals(['@', 'a', 'd', 'e', 'b', 'c'], $arr);

        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        Arr::insert($arr, ['d' => 4], 3);
        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], $arr);
        Arr::insert($arr, ['@' => 0], -1);
        $this->assertEquals(['@' => 0, 'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], $arr);

        $arr = ['a' => 1];
        Arr::insert($arr, 1);
        $this->assertEquals([1, 'a' => 1], $arr);
        Arr::insert($arr, 2, 3);
        $this->assertEquals([1, 'a' => 1, 1 => 2], $arr);
    }

    /**
     * @covers Arr::merge
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

        $a = Arr::merge([], $a1);
        $this->assertEquals($a1, $a);
        $a = Arr::merge($a2, []);
        $this->assertEquals($a2, $a);

        $a = Arr::merge($a1, $a2);
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
        $this->assertEquals($r, $a);

        $a = Arr::merge($a2, $a1);
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
        $this->assertEquals($r, $a);
    }

    /**
     * @covers Arr::makeNested
     */
    public function testMakeNested()
    {
        $a = Arr::makeNested([]);
        $this->assertEquals([], $a);

        $a = [
            'n5' => [
                'parent' => 'n1',
                'node' => 'node5'
            ],
            'n3' => [
                'parent' => 'n0',
                'node' => 'node3'
            ],
            'n4' => [
                'parent' => 'n1',
                'node' => 'node4'
            ],
            'n0' => [
                'node' => 'node0'
            ],
            'n1' => [
                'parent' => null,
                'node' => 'node1'
            ],
            'n7' => [
                'parent' => 'n6',
                'node' => 'node7'
            ],
            'n2' => [
                'parent' => 'n0',
                'node' => 'node2'
            ],
            'n6' => [
                'parent' => 'n2',
                'node' => 'node6'
            ]
        ];
        $a = Arr::makeNested($a);
        $r = [
            'n0' => [
                'node' => 'node0',
                'children' => [
                    'n3' => [
                        'parent' => 'n0',
                        'node' => 'node3'
                    ],
                    'n2' => [
                        'parent' => 'n0',
                        'node' => 'node2',
                        'children' => [
                            'n6' => [
                                'parent' => 'n2',
                                'node' => 'node6',
                                'children' => [
                                    'n7' => [
                                        'parent' => 'n6',
                                        'node' => 'node7'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'n1' => [
                'parent' => null,
                'node' => 'node1',
                'children' => [
                    'n5' => [
                        'parent' => 'n1',
                        'node' => 'node5'
                    ],
                    'n4' => [
                        'parent' => 'n1',
                        'node' => 'node4'
                    ]
                ]
            ]
        ];
        $this->assertEquals($r, $a);
    }

    /**
     * @covers Arr::makeFlat
     */
    public function testMakeFlat()
    {
        $a = [
            'n0' => [
                'node' => 'node0',
                'children' => [
                    'n3' => [
                        'parent' => 'n0',
                        'node' => 'node3'
                    ],
                    'n2' => [
                        'parent' => 'n0',
                        'node' => 'node2',
                        'children' => [
                            'n6' => [
                                'parent' => 'n2',
                                'node' => 'node6',
                                'children' => [
                                    'n7' => [
                                        'parent' => 'n6',
                                        'node' => 'node7'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'n1' => [
                'parent' => null,
                'node' => 'node1',
                'children' => [
                    'n5' => [
                        'parent' => 'n1',
                        'node' => 'node5'
                    ],
                    'n4' => [
                        'parent' => 'n1',
                        'node' => 'node4'
                    ]
                ]
            ]
        ];
        $a = Arr::makeFlat($a);
        $r = [
            'n0' => [
                'node' => 'node0'
            ],
            'n3' => [
                'parent' => 'n0',
                'node' => 'node3'
            ],
            'n2' => [
                'parent' => 'n0',
                'node' => 'node2'
            ],
            'n6' => [
                'parent' => 'n2',
                'node' => 'node6'
            ],
            'n7' => [
                'parent' => 'n6',
                'node' => 'node7'
            ],
            'n1' => [
                'parent' => null,
                'node' => 'node1'
            ],
            'n5' => [
                'parent' => 'n1',
                'node' => 'node5'
            ],
            'n4' => [
                'parent' => 'n1',
                'node' => 'node4'
            ]
        ];
        $this->assertEquals($r, $a);
    }

    /**
     * @covers Arr::iterate
     */
    public function testIterate()
    {
        $a = [
            'a' => 1,
            'b' => 2,
            'c' => [
                'd' => 3,
                'e' => [
                    'f' => 4
                ]
            ]
        ];
        $r = [
            'a' => 1,
            'b' => 2,
            'c' => [
                'd' => 3,
                'e' => [
                    'f' => 4
                ]
            ],
            'd' => 3,
            'e' => [
                'f' => 4
            ],
            'f' => 4
        ];
        foreach (Arr::iterate($a) as $k => $v) {
            $this->assertEquals($v, $r[$k]);
        }
    }
}