<?php

use Aleph\Cache\Cache;
use Aleph\Cache\RedisCache;
use Aleph\Cache\PHPRedisCache;
use Aleph\Cache\MemoryCache;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Aleph\Core\Event class.
 *
 * @group cache
 */
class CacheTest extends TestCase
{
    /**
     * Cache instances.
     *
     * @var \Aleph\Cache\Cache[]
     */
    protected static $cacheInstances = [];

    /**
     * Includes Aleph\Cache\Cache class.
     */
    public static function setUpBeforeClass()
    {
        require_once(__DIR__ . '/../Cache/Cache.php');
        require_once(__DIR__ . '/../Cache/FileCache.php');
        require_once(__DIR__ . '/../Cache/SessionCache.php');
        require_once(__DIR__ . '/../Cache/RedisCache.php');
        require_once(__DIR__ . '/../Cache/PHPRedisCache.php');
        require_once(__DIR__ . '/../Cache/MemoryCache.php');
        $params = [
            'directory' => __DIR__ . '/_cache/' . uniqid('dir', true)
        ];
        self::$cacheInstances['file'] = Cache::getInstance('file', $params);
        self::$cacheInstances['session'] = Cache::getInstance('session');
        if (RedisCache::isAvailable()) {
            self::$cacheInstances['redis'] = Cache::getInstance('redis');
        }
        if (PHPRedisCache::isAvailable()) {
            self::$cacheInstances['phpredis'] = Cache::getInstance('phpredis');
        }
        if (MemoryCache::isAvailable('memcache')) {
            self::$cacheInstances['memcache'] = Cache::getInstance('memcache');
        }
        if (MemoryCache::isAvailable('memcached')) {
            self::$cacheInstances['memcached'] = Cache::getInstance('memcached');
        }
    }

    /**
     * Removes unnecessary files.
     */
    public static function tearDownAfterClass()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $dir = self::$cacheInstances['file']->getDirectory();
        if (file_exists($dir)) {
            foreach (scandir($dir) as $item) {
                if ($item == '..' || $item == '.') {
                    continue;
                }
                $file = $dir . '/' . $item;
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($dir);
        }
    }

    /**
     * @covers Cache::get
     * @covers Cache::set
     */
    public function testGetSet()
    {
        $keys = [
            [1, 2, 3],
            'foo123',
            ['a', [], null, false]
        ];
        $content = str_repeat('#', 1024 * 1024) . str_repeat('@', 10);
        foreach (self::$cacheInstances as $cache) {
            $this->assertNull($cache->get(uniqid('', true), $isExpired));
            $this->assertTrue($isExpired);
            foreach ($keys as $key) {
                $this->assertTrue($cache->set($key, $content, 2));
                $this->assertEquals($cache->get($key, $isExpired), $content);
                $this->assertFalse($isExpired);
            }
        }
        sleep(1);
        foreach (self::$cacheInstances as $cache) {
            foreach ($keys as $key) {
                $this->assertEquals($cache->get($key, $isExpired), $content);
                $this->assertFalse($isExpired);
            }
        }
        sleep(1);
        foreach (self::$cacheInstances as $cache) {
            foreach ($keys as $key) {
                $this->assertNull($cache->get($key, $isExpired));
                $this->assertTrue($isExpired);
            }
        }
    }

    /**
     * @covers Cache::getMeta
     * @depends testGetSet
     */
    public function testGetMeta()
    {
        foreach (self::$cacheInstances as $cache) {
            $key = uniqid('', true);
            $this->assertTrue($cache->set($key, 'content', 1));
            $this->assertEquals($cache->getMeta($key), [1, []]);
        }
    }

    /**
     * @covers Cache::update
     * @depends testGetSet
     */
    public function testUpdate()
    {
        $keys = [];
        foreach (self::$cacheInstances as $type => $cache) {
            $keys[$type] = uniqid($type, true);
            $this->assertTrue($cache->set($keys[$type], 'abc', 2));
            $this->assertEquals($cache->get($keys[$type], $isExpired), 'abc');
            $this->assertFalse($isExpired);
        }
        sleep(1);
        foreach (self::$cacheInstances as $type => $cache) {
            $this->assertTrue($cache->update($keys[$type], '123'));
            $this->assertEquals($cache->get($keys[$type], $isExpired), '123');
            $this->assertFalse($isExpired);
        }
        sleep(1);
        foreach (self::$cacheInstances as $type => $cache) {
            $this->assertEquals($cache->get($keys[$type], $isExpired), '123');
            $this->assertFalse($isExpired);
        }
        sleep(1);
        foreach (self::$cacheInstances as $type => $cache) {
            $this->assertNull($cache->get($keys[$type], $isExpired));
            $this->assertTrue($isExpired);
        }
    }

    /**
     * @covers Cache::isExpired
     * @depends testGetSet
     */
    public function testIsExpired()
    {
        $keys = [];
        foreach (self::$cacheInstances as $type => $cache) {
            $keys[$type] = uniqid('', true);
            $this->assertTrue($cache->isExpired($keys[$type]));
            $this->assertTrue($cache->set($keys[$type], 'content', 1));
            $this->assertFalse($cache->isExpired($keys[$type]));
        }
        sleep(1);
        foreach (self::$cacheInstances as $type => $cache) {
            $this->assertTrue($cache->isExpired($keys[$type]));
        }
    }

    /**
     * @covers Cache::add
     * @depends testGetSet
     */
    public function testAdd()
    {
        $keys = [];
        foreach (self::$cacheInstances as $type => $cache) {
            $keys[$type] = uniqid('', true);
            $this->assertTrue($cache->set($keys[$type], 'content', 1));
            $this->assertEquals($cache->get($keys[$type], $isExpired), 'content');
            $this->assertFalse($isExpired);
            $this->assertFalse($cache->add($keys[$type], 'abc', 1));
        }
        usleep(1100000);
        foreach (self::$cacheInstances as $type => $cache) {
            $this->assertTrue($cache->add($keys[$type], 'abc', 1));
            $this->assertEquals($cache->get($keys[$type], $isExpired), 'abc');
            $this->assertFalse($isExpired);
        }
    }

    /**
     * @covers Cache::rw
     * @depends testGetSet
     */
    public function testRw()
    {
        $x = [];
        $keys = [];
        $dataProviders = [];
        foreach (self::$cacheInstances as $type => $cache) {
            $x[$type] = 0;
            $dataProviders[$type] = function () use (&$x, $type) {
                return ++$x[$type];
            };
            $keys[$type] = uniqid('', true);
            $this->assertEquals($cache->rw($keys[$type], $dataProviders[$type], 1), 1);
            $this->assertEquals($cache->get($keys[$type], $isExpired), 1);
            $this->assertFalse($isExpired);
            $this->assertEquals($cache->rw($keys[$type], $dataProviders[$type], 1), 1);
        }
        usleep(1100000);
        foreach (self::$cacheInstances as $type => $cache) {
            $this->assertEquals($cache->rw($keys[$type], $dataProviders[$type], 1), 2);
        }
    }

    /**
     * @covers Cache::touch
     * @depends testGetSet
     */
    public function touch()
    {
        $keys = [];
        foreach (self::$cacheInstances as $type => $cache) {
            $keys[$type] = uniqid('', true);
            $this->assertFalse($cache->touch($keys[$type], 1));
            $this->assertTrue($cache->set($keys[$type], 'content', 1));
        }
        usleep(500000);
        foreach (self::$cacheInstances as $type => $cache) {
            $this->assertTrue($cache->touch($keys[$type], 1));
        }
        usleep(600000);
        foreach (self::$cacheInstances as $type => $cache) {
            $this->assertEquals($cache->get($keys[$type], $isExpired), 'content');
            $this->assertFalse($isExpired);
        }
    }

    /**
     * @covers Cache::remove
     * @depends testGetSet
     */
    public function testRemove()
    {
        foreach (self::$cacheInstances as $cache) {
            $key = uniqid('', true);
            $this->assertTrue($cache->set($key, 'content', 10));
            $this->assertEquals($cache->get($key, $isExpired), 'content');
            $this->assertFalse($isExpired);
            $this->assertTrue($cache->remove($key));
            $this->assertNull($cache->get($key, $isExpired));
            $this->assertTrue($isExpired);
        }
    }

    /**
     * @covers Cache::clean
     * @depends testGetSet
     */
    public function testClean()
    {
        foreach (self::$cacheInstances as $cache) {
            for ($i = 0; $i < 10; ++$i) {
                $cache->set($i, $i, 10);
                $this->assertEquals($cache->get($i, $isExpired), $i);
                $this->assertFalse($isExpired);
            }
            $cache->clean();
            for ($i = 0; $i < 10; ++$i) {
                $this->assertNull($cache->get($i, $isExpired));
                $this->assertTrue($isExpired);
            }
        }
    }

    /**
     * @covers Cache::set
     * @covers Cache::getByTags
     * @covers Cache::getByTag
     * @depends testGetSet
     */
    public function testGetSetWithTags()
    {
        $keys = [
            [1, 2, 3],
            'foo123',
            ['a', [], null, false]
        ];
        $tags = [
            ['a', 'b'],
            ['a'],
            ['b', 'c']
        ];
        foreach (self::$cacheInstances as $cache) {
            foreach ($keys as $n => $key) {
                $this->assertTrue($cache->set($key, $tags[$n], 2, $tags[$n]));
                $this->assertEquals($cache->get($key, $isExpired), $tags[$n]);
                $this->assertFalse($isExpired);
            }
        }
        sleep(1);
        foreach (self::$cacheInstances as $cache) {
            $this->assertEquals($cache->getByTag('a'), [
                serialize($keys[0]) => $tags[0],
                serialize($keys[1]) => $tags[1]
            ]);
            $this->assertEquals($cache->getByTag('b'), [
                serialize($keys[0]) => $tags[0],
                serialize($keys[2]) => $tags[2]
            ]);
            $this->assertEquals($cache->getByTag('c'), [
                serialize($keys[2]) => $tags[2]
            ]);
        }
        sleep(2);
        foreach (self::$cacheInstances as $cache) {
            $this->assertEquals($cache->getByTag('a'), []);
            $this->assertEquals($cache->getByTag('b'), []);
            $this->assertEquals($cache->getByTag('c'), []);
        }
    }

    /**
     * @covers Cache::getVault
     * @covers Cache::getKeyCount
     * @depends testGetSetWithTags
     * @depends testClean
     */
    public function testGetVault()
    {
        foreach (self::$cacheInstances as $cache) {
            $cache->clean();
            for ($i = 0; $i < 3; ++$i) {
                $this->assertTrue($cache->set('key' . $i, 'content' . $i, 10, [$i]));
                $this->assertEquals($cache->get('key' . $i, $isExpired), 'content' . $i);
                $this->assertFalse($isExpired);
            }
            $this->assertEquals($cache->getVault(), [
                [
                    serialize('key0') => 10
                ],
                [
                    serialize('key1') => 10
                ],
                [
                    serialize('key2') => 10
                ]
            ]);
            $this->assertEquals($cache->getKeyCount(), 3);
        }
    }

    /**
     * @covers Cache::cleanByTags
     * @covers Cache::cleanByTag
     * @depends testGetVault
     */
    public function testCleanByTags()
    {
        foreach (self::$cacheInstances as $cache) {
            $cache->clean();
            for ($i = 0; $i <= 5; ++$i) {
                $this->assertTrue($cache->set('key' . $i, 'content' . $i, 10, [$i]));
                $this->assertEquals($cache->get('key' . $i, $isExpired), 'content' . $i);
                $this->assertFalse($isExpired);
            }
            $cache->cleanByTag(0);
            $cache->cleanByTags([1, 2, 3]);
            for ($i = 0; $i <= 3; ++$i) {
                $this->assertNull($cache->get('key' . $i, $isExpired));
                $this->assertTrue($isExpired);
            }
            $this->assertEquals($cache->getVault(), [
                4 => [
                    serialize('key4') => 10
                ],
                5 => [
                    serialize('key5') => 10
                ]
            ]);
        }
    }
}