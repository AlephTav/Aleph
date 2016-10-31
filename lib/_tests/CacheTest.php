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
                $this->assertEquals($content, $cache->get($key, $isExpired));
                $this->assertFalse($isExpired);
            }
        }
        usleep(500000);
        foreach (self::$cacheInstances as $cache) {
            foreach ($keys as $key) {
                $this->assertEquals($content, $cache->get($key, $isExpired));
                $this->assertFalse($isExpired);
            }
        }
        sleep(2);
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
            $this->assertEquals([1, []], $cache->getMeta($key));
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
            $this->assertEquals('abc', $cache->get($keys[$type], $isExpired));
            $this->assertFalse($isExpired);
        }
        sleep(1);
        foreach (self::$cacheInstances as $type => $cache) {
            $this->assertTrue($cache->update($keys[$type], '123'));
            $this->assertEquals('123', $cache->get($keys[$type], $isExpired));
            $this->assertFalse($isExpired);
        }
        sleep(1);
        foreach (self::$cacheInstances as $type => $cache) {
            $this->assertEquals('123', $cache->get($keys[$type], $isExpired));
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
            $this->assertEquals('content', $cache->get($keys[$type], $isExpired));
            $this->assertFalse($isExpired);
            $this->assertFalse($cache->add($keys[$type], 'abc', 1));
        }
        usleep(1100000);
        foreach (self::$cacheInstances as $type => $cache) {
            $this->assertTrue($cache->add($keys[$type], 'abc', 1));
            $this->assertEquals('abc', $cache->get($keys[$type], $isExpired));
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
            $this->assertEquals(1, $cache->rw($keys[$type], $dataProviders[$type], 1));
            $this->assertEquals(1, $cache->get($keys[$type], $isExpired));
            $this->assertFalse($isExpired);
            $this->assertEquals(1, $cache->rw($keys[$type], $dataProviders[$type], 1));
        }
        usleep(1100000);
        foreach (self::$cacheInstances as $type => $cache) {
            $this->assertEquals(2, $cache->rw($keys[$type], $dataProviders[$type], 1));
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
            $this->assertEquals('content', $cache->get($keys[$type], $isExpired));
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
            $this->assertEquals('content', $cache->get($key, $isExpired));
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
                $this->assertEquals($i, $cache->get($i, $isExpired));
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
                $this->assertEquals($tags[$n], $cache->get($key, $isExpired));
                $this->assertFalse($isExpired);
            }
        }
        sleep(1);
        foreach (self::$cacheInstances as $cache) {
            $this->assertEquals([
                serialize($keys[0]) => $tags[0],
                serialize($keys[1]) => $tags[1]
            ], $cache->getByTag('a'));
            $this->assertEquals([
                serialize($keys[0]) => $tags[0],
                serialize($keys[2]) => $tags[2]
            ], $cache->getByTag('b'));
            $this->assertEquals([
                serialize($keys[2]) => $tags[2]
            ], $cache->getByTag('c'));
        }
        sleep(2);
        foreach (self::$cacheInstances as $cache) {
            $this->assertEquals([], $cache->getByTag('a'));
            $this->assertEquals([], $cache->getByTag('b'));
            $this->assertEquals([], $cache->getByTag('c'));
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
                $this->assertEquals('content' . $i, $cache->get('key' . $i, $isExpired));
                $this->assertFalse($isExpired);
            }
            $this->assertEquals([
                [
                    serialize('key0') => 10
                ],
                [
                    serialize('key1') => 10
                ],
                [
                    serialize('key2') => 10
                ]
            ], $cache->getVault());
            $this->assertEquals(3, $cache->getKeyCount());
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
                $this->assertEquals('content' . $i, $cache->get('key' . $i, $isExpired));
                $this->assertFalse($isExpired);
            }
            $cache->cleanByTag(0);
            $cache->cleanByTags([1, 2, 3]);
            for ($i = 0; $i <= 3; ++$i) {
                $this->assertNull($cache->get('key' . $i, $isExpired));
                $this->assertTrue($isExpired);
            }
            $this->assertEquals([
                4 => [
                    serialize('key4') => 10
                ],
                5 => [
                    serialize('key5') => 10
                ]
            ], $cache->getVault());
        }
    }
}