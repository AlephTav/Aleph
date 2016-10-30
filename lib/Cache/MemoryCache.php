<?php
/**
 * Copyright (c) 2013 - 2016 Aleph Tav
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @link http://www.4leph.com
 * @copyright Copyright &copy; 2013 - 2016 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Cache;

/**
 * The class is intended for caching of different data using the memcache extension.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.3.0
 * @package aleph.cache
 */
class MemoryCache extends Cache
{
    /**
     * Error message templates.
     */
    const ERR_CACHE_MEMORY_1 = 'It\'s impossible to use compression data in memcache because zlib extension has not loaded.';

    /**
     * Maximum size in bytes of caching data.
     */
    const MAX_BLOCK_SIZE = 1048576;

    /**
     * Determines whether the data will be compressed before placing in the cache.
     *
     * @var int
     */
    protected $compress = 0;

    /**
     * The vault lifetime (in seconds).
     * Defined as 1 month by default.
     *
     * @var int
     */
    protected $vaultLifeTime = 2592000;

    /**
     * The instance of Memcache or Memcached class.
     *
     * @var \Memcache|\Memcached
     */
    private $mem = null;

    /**
     * Native method that is used to store data in the cache.
     *
     * @var string
     */
    private $storeMethod = 'set';

    /**
     * Checks whether the current type of cache is available or not.
     *
     * @param string $type The memory cache type. Valid values "memcache" or "memcached".
     * @return bool
     */
    public static function isAvailable(string $type = '') : bool
    {
        if ($type == 'memcache' || $type == 'memcached') {
            return extension_loaded($type);
        }
        return extension_loaded('memcache') || extension_loaded('memcached');
    }

    /**
     * Constructor.
     *
     * @param string $type Determines type of cache extensions. The valid values: memcache, memcached.
     * @param array $servers Hosts for a memcache connection.
     * @param bool $compress If value of this parameter is TRUE any data will be compressed before placing in a cache, otherwise data will not be compressed.
     * @throws \UnexpectedValueException If wrong cache type specified.
     */
    public function __construct(string $type, array $servers = [], bool $compress = true)
    {
        parent::__construct();
        if ($type != 'memcache' && $type != 'memcached') {
            throw new \UnexpectedValueException(sprintf(static::ERR_CACHE_2, $type));
        }
        $this->mem = new $type();
        if (count($servers)) {
            if ($type == 'memcache') {
                foreach ($servers as $server) {
                    $this->mem->addServer(
                        $server['host'] ?? '127.0.0.1',
                        $server['port'] ?? 11211,
                        $server['persistent'] ?? true,
                        $server['weight'] ?? 1,
                        $server['timeout'] ?? 1,
                        $server['retryInterval'] ?? 15,
                        $server['status'] ?? true
                    );
                }
            } else {
                foreach ($servers as $server) {
                    $this->mem->addServer(
                        $server['host'] ?? '127.0.0.1',
                        $server['port'] ?? 11211,
                        $server['weight'] ?? 0
                    );
                }
            }
        } else {
            $this->mem->addServer('127.0.0.1', 11211);
        }
        $this->compress($compress);
    }

    /**
     * Turns on or turns off the data compression for memcache.
     *
     * @param bool $flag
     * @return void
     * @throws \RuntimeException If zlib extension is not loaded.
     */
    public function compress(bool $flag = true)
    {
        if ($flag && !extension_loaded('zlib')) {
            throw new \RuntimeException(static::ERR_CACHE_MEMORY_1);
        }
        $this->compress = $flag ? MEMCACHE_COMPRESSED : 0;
    }

    /**
     * Gets the native caching object.
     *
     * @return \Memcache|\Memcached
     */
    public function getNativeObject()
    {
        return $this->mem;
    }

    /**
     * Stores some data identified by a key in the cache, only if it's not already stored.
     *
     * @param mixed $key The data key.
     * @param mixed $content The cached data.
     * @param int $expire The cache lifetime (in seconds). If it is FALSE or zero the cache life time is used.
     * @param string[] $tags An array of tags associated with the data.
     * @return bool Returns TRUE if something has effectively been added into the cache, FALSE otherwise.
     */
    public function add($key, $content, int $expire = 0, array $tags = []) : bool
    {
        $k = $this->normalizeKey($key);
        $expire = $this->normalizeExpire($expire);
        $this->storeMethod = 'add';
        if ($this->store($k, $content, $expire)) {
            $this->storeMethod = 'set';
            if ($this->store($this->getMetaPrefix() . $k, [$expire, $tags], $expire)) {
                $this->saveKeyToVault($key, $expire, $tags);
                return true;
            } else {
                $this->mem->delete($k);
            }
        }
        $this->storeMethod = 'set';
        return false;
    }

    /**
     * Checks whether cache lifetime is expired or not.
     *
     * @param string $key The data key.
     * @return bool
     */
    public function isExpired($key) : bool
    {
        return $this->mem->get($this->normalizeKey($key)) === false;
    }

    /**
     * Removes all previously stored data from the cache.
     *
     * @return void
     */
    public function clean()
    {
        $this->mem->flush();
    }

    /**
     * Retrieves data from the cache.
     *
     * @param string $key The normalized data key.
     * @param mixed $success Set to TRUE in success and FALSE in failure.
     * @return mixed
     */
    protected function fetch(string $key, &$success = null)
    {
        $res = $this->mem->get($key);
        if ($res === false) {
            $success = false;
            return null;
        }
        if (isset($res[$key]) && is_int($res[$key])) {
            for ($i = 0, $j = $res[$key], $res = ''; $i <= $j; ++$i) {
                $part = $this->mem->get($key . $i);
                if ($part === false) {
                    $success = false;
                    return false;
                }
                $res .= $part;
            }
        }
        $success = true;
        return unserialize($res);
    }

    /**
     * Stores data in the cache.
     *
     * @param string $key The normalized data key.
     * @param mixed $content The serializable data.
     * @param int $expire The normalized cache expiration time.
     * @return bool TRUE on success and FALSE on failure.
     */
    protected function store(string $key, $content, int $expire) : bool
    {
        $method = $this->storeMethod;
        $content = serialize($content);
        if (strlen($content) <= static::MAX_BLOCK_SIZE) {
            if ($this->mem instanceof \Memcache) {
                return $this->mem->{$method}($key, $content, $this->compress, $expire);
            }
            return $this->mem->{$method}($key, $content, $expire);
        }
        $n = 0;
        if ($this->mem instanceof \Memcache) {
            foreach (str_split($content, static::MAX_BLOCK_SIZE) as $n => $part) {
                if ($this->mem->{$method}($key . $n, $part, $this->compress, $expire) === false) {
                    return false;
                }
            }
            return $this->mem->{$method}($key, [$key => $n], $this->compress, $expire);
        }
        foreach (str_split($content, static::MAX_BLOCK_SIZE) as $n => $part) {
            if ($this->mem->{$method}($key . $n, $part, $expire) === false) {
                return false;
            }
        }
        return $this->mem->{$method}($key, [$key => $n], $expire);
    }

    /**
     * Sets a new expiration on an cached data.
     * Returns TRUE on success or FALSE on failure.
     *
     * @param string $key The normalized data key.
     * @param int $expire The new expiration time (in seconds).
     * @return bool
     */
    protected function expire(string $key, int $expire) : bool
    {
        if ($this->mem instanceof \Memcache) {
            return parent::expire($key, $expire);
        }
        return $this->mem->touch($key, $expire);
    }

    /**
     * Removes a stored data from the cache.
     *
     * @param string $key The normalized data key.
     * @return bool TRUE on success and FALSE on failure.
     */
    protected function delete(string $key) : bool
    {
        return $this->mem->delete($key);
    }
}