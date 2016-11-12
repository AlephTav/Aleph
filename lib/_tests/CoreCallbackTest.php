<?php

use Aleph\Core\Callback;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Aleph\Core\Callback class.
 *
 * @group core
 */
class CoreCallbackTest extends TestCase
{
    /**
     * Invalid callbacks provider.
     *
     * @return array
     */
    public function invalidCallbackProvider()
    {
        return [
            [''],
            [0],
            [[1, 2, 3]],
            [['a' => 1, 2, 'b' => [], new \stdClass]],
            [['Test', 'test' => 'foo']],
            [['Test', new \stdClass]],
            [['ReflectionClass', '__construct']],
            ['test::parent::foo::boo'],
            ['test[]->self::parent::foo'],
            ['class[123]::foo::boo'],
            ['some[]->self->foo']
        ];
    }

    /**
     * Valid callbacks provider.
     *
     * @return array
     */
    public function validCallbackProvider()
    {
        CoreCallbackTest::setUpBeforeClass();
        return [
            [
                'foo',
                [
                    'type' => 'function',
                    'class' => '',
                    'method' => 'foo',
                    'static' => false,
                    'numargs' => 0
                ],
                'foo'
            ],
            [
                'test::foo',
                [
                    'type' => 'class',
                    'class' => 'test',
                    'method' => 'foo',
                    'static' => true,
                    'numargs' => 0
                ],
                'test::foo'
            ],
            [
                'test->foo',
                [
                    'type' => 'class',
                    'class' => 'test',
                    'method' => 'foo',
                    'static' => false,
                    'numargs' => 0
                ],
                'test[]->foo'
            ],
            [
                'test[123]->foo',
                [
                    'type' => 'class',
                    'class' => 'test',
                    'method' => 'foo',
                    'static' => false,
                    'numargs' => 123
                ],
                'test[123]->foo'
            ],
            [
                'test[]',
                [
                    'type' => 'class',
                    'class' => 'test',
                    'method' => '__construct',
                    'static' => false,
                    'numargs' => 0
                ],
                'test[]'
            ],
            [
                '\test[321]',
                [
                    'type' => 'class',
                    'class' => 'test',
                    'method' => '__construct',
                    'static' => false,
                    'numargs' => 321
                ],
                'test[321]'
            ],
            [
                'test[7]::parent::foo',
                [
                    'type' => 'class',
                    'class' => 'test',
                    'method' => 'parent::foo',
                    'static' => true,
                    'numargs' => 7
                ],
                'test::parent::foo'
            ],
            [
                'test[7]->self::foo',
                [
                    'type' => 'class',
                    'class' => 'test',
                    'method' => 'self::foo',
                    'static' => false,
                    'numargs' => 7
                ],
                'test[7]->self::foo'
            ],
            [
                new \stdClass,
                [
                    'type' => 'class',
                    'class' => 'stdClass',
                    'method' => '__construct',
                    'static' => false,
                    'numargs' => 0
                ],
                'stdClass[]'
            ],
            [
                new \ReflectionClass('stdClass'),
                [
                    'type' => 'class',
                    'class' => 'ReflectionClass',
                    'method' => '__construct',
                    'static' => false,
                    'numargs' => 1
                ],
                'ReflectionClass[1]'
            ],
            [
                [new \ReflectionClass('stdClass'), 'isAbstract'],
                [
                    'type' => 'class',
                    'class' => 'ReflectionClass',
                    'method' => 'isAbstract',
                    'static' => false,
                    'numargs' => 1
                ],
                'ReflectionClass[1]->isAbstract'
            ],
            [
                ['ReflectionClass', 'export'],
                [
                    'type' => 'class',
                    'class' => 'ReflectionClass',
                    'method' => 'export',
                    'static' => true,
                    'numargs' => 0
                ],
                'ReflectionClass::export'
            ],
            [
                function() {},
                [
                    'type' => 'closure',
                    'class' => 'Closure',
                    'method' => '',
                    'static' => false,
                    'numargs' => 0
                ],
                'Closure'
            ],
            [
                new Callback('Test[3]->foo'),
                [
                    'type' => 'class',
                    'class' => 'Test',
                    'method' => 'foo',
                    'static' => false,
                    'numargs' => 3
                ],
                'Test[3]->foo'
            ]
        ];
    }

    /**
     * @covers Callback::parse
     * @param mixed $callback
     * @dataProvider invalidCallbackProvider
     */
    public function testParseInvalidCallbacks($callback)
    {
        $this->expectException('\InvalidArgumentException');
        new Callback($callback);
    }

    /**
     * @covers Callback::parse
     * @param mixed $callback
     * @param array $info
     * @param string $toString
     * @dataProvider validCallbackProvider
     */
    public function testParseValidCallbacks($callback, array $info, string $toString)
    {
        $callable = new Callback($callback);
        $this->assertEquals($info, $callable->getInfo());
        $this->assertEquals($toString, (string)$callable);
    }

    /**
     * @covers Callback::isPermitted
     */
    public function testIsPermitted()
    {
        $this->assertTrue((new Callback('foo'))->isPermitted([]));
        $c1 = new Callback('Test->foo');
        $c2 = new Callback('Test::foo');
        $c3 = new Callback('A->__set');
        $c4 = new Callback('B->__isset');
        $c5 = new Callback('C->__isset');
        $permissions = [
            'forbidden' => [
                '/^Test\[\d*\]->foo$/i',
                '/^(A|B)\[\d*\]->__(unset|isset)$/'
            ]
        ];
        $this->assertFalse($c1->isPermitted($permissions));
        $this->assertTrue($c2->isPermitted($permissions));
        $this->assertTrue($c3->isPermitted($permissions));
        $this->assertFalse($c4->isPermitted($permissions));
        $this->assertTrue($c5->isPermitted($permissions));
        $permissions = [
            'permitted' => [
                '/^Test::foo$/i',
                '/^(A|B)\[\d*\]->__[a-zA-Z]{3,5}$/i'
            ]
        ];
        $this->assertFalse($c1->isPermitted($permissions));
        $this->assertTrue($c2->isPermitted($permissions));
        $this->assertTrue($c3->isPermitted($permissions));
        $this->assertTrue($c4->isPermitted($permissions));
        $this->assertFalse($c5->isPermitted($permissions));
        $permissions = [
            'permitted' => [
                '/^Test::foo$/i',
                '/^(A|B)\[\d*\]->__[a-zA-Z]{3,5}$/i'
            ],
            'forbidden' => [
                '/^Test\[\d*\]->foo$/i',
                '/^(A|B)\[\d*\]->__(unset|isset)$/'
            ]
        ];
        $this->assertFalse($c1->isPermitted($permissions));
        $this->assertTrue($c2->isPermitted($permissions));
        $this->assertTrue($c3->isPermitted($permissions));
        $this->assertFalse($c4->isPermitted($permissions));
        $this->assertFalse($c5->isPermitted($permissions));
    }
}