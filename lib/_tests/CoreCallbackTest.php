<?php

use Aleph\Core\Interfaces\ICallback;
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
     * Includes Aleph\Core\Callback class.
     */
    public static function setUpBeforeClass()
    {
        require_once(__DIR__ . '/../Core/Interfaces/ICallback.php');
        require_once(__DIR__ . '/../Core/Callback.php');
    }

    /**
     * Invalid callbacks provider.
     *
     * @return array
     */
    public function invalidCallbackProvider()
    {
        return [
            [0],
            [[1, 2, 3]],
            [['a' => 1, 2, 'b' => [], new \stdClass]],
            [['Test', 'test' => 'foo']],
            [['Test', new \stdClass]],
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
                'test[]->__construct'
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
                'test[321]->__construct'
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
            ]
        ];
    }

    /**
     * Checks callback parsing.
     *
     * @param mixed $callback
     * @dataProvider invalidCallbackProvider
     */
    public function testParseInvalidCallbacks($callback)
    {
        $this->expectException('\InvalidArgumentException');
        new Callback($callback);
    }

    /**
     * Checks callback parsing.
     *
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
}