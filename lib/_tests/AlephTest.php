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
        require_once(__DIR__ . '/../Aleph.php');
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
     * @covers       Aleph::setConfig
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
     * @covers  Aleph::setConfig
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
     * @covers  Aleph::get
     * @depends testConfigMerge
     */
    public function testConfigAccessGet()
    {
        Aleph::setConfig($this->config3, false);
        $this->assertEquals(Aleph::get('var1'), 1);
        $this->assertEquals(Aleph::get('var4.4.var2'), 'test');
        $this->assertEquals(Aleph::get('section2'), ['var1' => 'abc']);
        $this->assertEquals(Aleph::get('var5', 'none'), 'none');
        $this->assertEquals(Aleph::get('var5.1.2.3.4', 'none'), 'none');
        $this->assertEquals(Aleph::get('var5|1|2|3|4', 'none', '|'), 'none');
        $this->assertEquals(Aleph::get('var4#4#var2', null, '#'), 'test');
    }

    /**
     * Checks "set" access to the config.
     *
     * @covers  Aleph::set
     * @depends testConfigAccessGet
     */
    public function testConfigAccessSet()
    {
        Aleph::setConfig($this->config3, false);
        Aleph::set('var1', []);
        $this->assertEquals(Aleph::get('var1'), []);
        Aleph::set('var4.4', ['var1' => 'abc'], true);
        $this->assertEquals(Aleph::get('var4.4'), ['var2' => 'test', 'var1' => 'abc']);
        Aleph::set('var3', 'no', true);
        $this->assertEquals(Aleph::get('var3'), 'no');
        Aleph::set('a.b.c', 123);
        $this->assertEquals(Aleph::get('a.b.c'), 123);
        Aleph::set('a%b%c', [1, 2, 3], true, '%');
        $this->assertEquals(Aleph::get('a.b.c'), [1, 2, 3]);
    }

    /**
     * Checks "has" access to the config.
     *
     * @covers  Aleph::has
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
     * @covers  Aleph::remove
     * @depends testConfigAccessGet
     */
    public function testConfigAccessRemove()
    {
        Aleph::setConfig($this->config3, false);
        Aleph::remove('var2');
        $this->assertNull(Aleph::get('var2'));
        Aleph::remove('section1.var1');
        $this->assertEquals(Aleph::get('section1'), ['var2' => 'abc']);
        Aleph::remove('section1/var2', '/');
        $this->assertEquals(Aleph::get('section1'), []);
        Aleph::remove('a.b.c');
        $this->assertNull(Aleph::get('a'));
    }
}