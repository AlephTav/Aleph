<?php

use PHPUnit\Framework\TestCase;

/**
 * Test cases for Aleph class.
 *
 * @group core
 */
class AlephTest extends TestCase
{
    /**
     * The old framework's config.
     *
     * @var array
     */
    private static $config = [];

    /**
     * Config examples to test.
     */
    private $config1 = [
        'var1' => '1',
        'var2' => 'a',
        'var4' => [
            1,
            2,
            3 => [
                'var1' => 'test'
            ]
        ],
        'section1' => [
            'var1' => 'test'
        ],
        'var3' => [
            1,
            2,
            3
        ],
        'section2' => [
            'var1' => [
                1,
                2,
                3
            ]
        ]
    ];
    private $config2 = [
        'var1' => 1,
        'var4' => [
            3 => [
                'var2' => 'test'
            ],
            1,
            2,
            3
        ],
        'section1' => [
            'var2' => 'abc'
        ],
        'section2' => [
            'var1' => 'abc'
        ],
        1,
        2,
        3
    ];
    private $config3 = [
        'var1' => 1,
        'var2' => 'a',
        'var4' => [
            1,
            2,
            3 => [
                'var1' => 'test'
            ],
            4 => [
                'var2' => 'test'
            ],
            1,
            2,
            3
        ],
        'section1' => [
            'var1' => 'test',
            'var2' => 'abc'
        ],
        'var3' => [
            1,
            2,
            3
        ],
        'section2' => [
            'var1' => 'abc'
        ],
        1,
        2,
        3
    ];

    /**
     * Includes Aleph class.
     */
    public static function setUpBeforeClass()
    {
        self::$config = Aleph::getConfig();
    }

    /**
     * Restores the frameworks's config.
     */
    public static function tearDownAfterClass()
    {
        Aleph::setConfig(self::$config);
    }

    /**
     * Data provider for invalid configs.
     *
     * @return array
     */
    public function invalidConfigs()
    {
        return [
            [123],
            [''],
            [str_repeat('@', 65536)]
        ];
    }

    /**
     * @param mixed $config Invalid config
     * @covers Aleph::setConfig
     * @dataProvider invalidConfigs
     */
    public function testConfigInvalidLoad($config)
    {
        $this->expectException(InvalidArgumentException::class);
        Aleph::setConfig($config);
    }

    /**
     * Checks configuration loading.
     *
     * @covers Aleph::getConfig
     * @covers Aleph::setConfig
     */
    public function testConfigLoad()
    {
        Aleph::setConfig(__DIR__ . '/_resources/config.php', false);
        $this->assertEquals($this->config1, Aleph::getConfig());
        Aleph::setConfig([], false);
        $this->assertEquals([], Aleph::getConfig());
    }

    /**
     * Checks configs merge.
     *
     * @covers Aleph::setConfig
     * @depends testConfigLoad
     */
    public function testConfigMerge()
    {
        Aleph::setConfig($this->config1, false);
        Aleph::setConfig($this->config2, true);
        $this->assertEquals($this->config3, Aleph::getConfig());
        Aleph::setConfig([], true);
        $this->assertEquals($this->config3, Aleph::getConfig());
        Aleph::setConfig([], false);
        Aleph::setConfig($this->config3, true);
        $this->assertEquals($this->config3, Aleph::getConfig());
    }

    /**
     * Checks "get" access to the config.
     *
     * @covers Aleph::get
     * @depends testConfigMerge
     */
    public function testConfigAccessGet()
    {
        Aleph::setConfig($this->config3, false);
        $this->assertEquals(1, Aleph::get('var1'));
        $this->assertEquals('test', Aleph::get('var4.4.var2'));
        $this->assertEquals(['var1' => 'abc'], Aleph::get('section2'));
        $this->assertEquals('none', Aleph::get('var5', 'none'));
        $this->assertEquals('none', Aleph::get('var5.1.2.3.4', 'none'));
        $this->assertEquals('none', Aleph::get('var5|1|2|3|4', 'none', '|'));
        $this->assertEquals('test', Aleph::get('var4#4#var2', null, '#'));
    }

    /**
     * Checks "set" access to the config.
     *
     * @covers Aleph::set
     * @depends testConfigAccessGet
     */
    public function testConfigAccessSet()
    {
        Aleph::setConfig($this->config3, false);
        Aleph::set('var1', []);
        $this->assertEquals([], Aleph::get('var1'));
        Aleph::set('var4.4', ['var1' => 'abc'], true);
        $this->assertEquals(['var2' => 'test', 'var1' => 'abc'], Aleph::get('var4.4'));
        Aleph::set('var3', 'no', true);
        $this->assertEquals('no', Aleph::get('var3'));
        Aleph::set('a.b.c', 123);
        $this->assertEquals(123, Aleph::get('a.b.c'));
        Aleph::set('a%b%c', [1, 2, 3], true, '%');
        $this->assertEquals([1, 2, 3], Aleph::get('a.b.c'));
    }

    /**
     * Checks "has" access to the config.
     *
     * @covers Aleph::has
     * @depends testConfigAccessGet
     */
    public function testConfigAccessHas()
    {
        Aleph::setConfig($this->config3, false);
        $this->assertTrue(Aleph::has('var1'));
        $this->assertTrue(Aleph::has('var4.4.var2'));
        $this->assertTrue(Aleph::has('section1,var2', ','));
        $this->assertFalse(Aleph::has('var4.4.var1'));
        $this->assertFalse(Aleph::has('a.b.c'));
    }

    /**
     * Checks "remove" access to the config.
     *
     * @covers Aleph::remove
     * @depends testConfigAccessGet
     */
    public function testConfigAccessRemove()
    {
        Aleph::setConfig($this->config3, false);
        Aleph::remove('var2');
        $this->assertNull(Aleph::get('var2'));
        Aleph::remove('section1.var1');
        $this->assertEquals(['var2' => 'abc'], Aleph::get('section1'));
        Aleph::remove('section1/var2', '/');
        $this->assertEquals([], Aleph::get('section1'));
        Aleph::remove('a.b.c');
        $this->assertNull(Aleph::get('a'));
    }
}