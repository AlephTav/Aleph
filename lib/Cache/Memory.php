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

use Aleph\Core;

/**
 * The class is intended for caching of different data using the memcache extension.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.2
 * @package aleph.cache
 */
class Memory extends Cache
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
     * Checks whether the current type of cache is available or not.
     *
     * @param string $type The memory cache type. Valid values "memcache" or "memcached".
     * @return bool
     */
    public static function isAvailable(string $type = '') : bool
    {
        if ($type == 'memcache' || $type == 'memcached')
        {
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
     * @return void
     */
    public function __construct(string $type, array $servers, bool $compress = true)
    {
        parent::__construct();
        if ($type != 'memcache' && $type != 'memcached')
        {
            $type = extension_loaded('memcache') ? 'memcache' : 'memcached';
        }
        $this->mem = new $type();
        if (count($servers))
        {
            if ($type == 'memcache')
            {
                foreach ($servers as $server)
                {
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
            }
            else
            {
                foreach ($servers as $server)
                {
                    $this->mem->addServer(
                        $server['host'] ?? '127.0.0.1',
                        $server['port'] ?? 11211,
                        $server['weight'] ?? 0
                    );
                }
            }
        }
        else
        {
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
        if ($compress && !extension_loaded('zlib'))
        {
            throw new \RuntimeException(static::ERR_CACHE_MEMORY_1);
        }
        $this->compress = $compress ? MEMCACHE_COMPRESSED : 0;
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
     * Returns meta information (expiration time and tags) of the cached data.
     * It returns empty array if the meta data does not exist.
     *
     * @param mixed $key The data key.
     * @return array
     */
    public function getMeta($key) : array
    {
        $meta = $this->getValue(static::META_PREFIX . $this->normalizeKey($key));
        return is_array($meta) ? $meta : [];
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
    public function add($key, $content, int $expire = 0, array $tags = [])
    {
        $k = $this->normalizeKey($key);
        $expire = $this->normalizeExpire($expire);
        if ($this->setValue($k, serialize($content), $expire, 'add'))
        {
            $this->setValue(static::META_PREFIX . $k, [$expire, $tags], $expire);
            $this->saveKeyToVault($key, $expire, $tags);
            return true;
        }
        return false;
    }
    
    /**
     * Returns some data previously stored in the cache.
     *
     * @param mixed $key The data key.
     * @param mixed $isExpired It will be set to TRUE if the given cache is expired and FALSE otherwise.
     * @return mixed
     */
    public function get($key, &$isExpired = null)
    {
        $res = $this->getValue($this->normalizeKey($key));
        if ($res === false)
        {
            $isExpired = true;
            return;
        }
        $isExpired = false;
        return unserialize($res);
    }

    /**
     * Stores some data identified by a key in the cache.
     *
     * @param mixed $key The data key.
     * @param mixed $content The cached data.
     * @param int $expire The cache lifetime (in seconds). If it is FALSE or zero the cache life time is used.
     * @param string[] $tags An array of tags associated with the data.
     * @return void
     */
    public function set($key, $content, int $expire = 0, array $tags = [])
    {
        $k = $this->normalizeKey($key);
        $expire = $this->normalizeExpire($expire);
        $this->setValue($k, serialize($content), $expire);
        $this->setValue(static::META_PREFIX . $k, [$expire, $tags], $expire);
        $this->saveKeyToVault($key, $expire, $tags);
    }
    
    /**
     * Updates the previously stored data with new data.
     *
     * @param mixed $key The key of the data being updated.
     * @param mixed $content The new data.
     * @return bool Returns TRUE on success and FALSE on failure (if cache does not exist or expired).
     */
    public function update($key, $content) : bool
    {
        $key = $this->normalizeKey($key);
        $meta = $this->getValue(static::META_PREFIX . $key);
        return $meta ? $this->setValue($key, serialize($content), $meta[0]) : false;
    }
    
    /**
     * Sets a new expiration on an cached data.
     * Returns TRUE on success or FALSE on failure. 
     *
     * @param mixed $key The data key.
     * @param int $expire The new expiration time.
     * @return bool
     */
    public function touch($key, int $expire = 0) : bool
    {
        $key = $this->normalizeKey($key);
        $mkey = static::META_PREFIX . $key;
        $content = $this->getValue($key);
        if ($content === false)
        {
            return false;
        }
        $meta = $this->getValue($mkey);
        $expire = $this->normalizeExpire($expire);
        if (isset($meta[0]))
        {
            $meta[0] = $expire;
            $this->setValue($key, $content, $expire);
            $this->setValue($mkey, $meta, $expire);
            return true;
        }
        return false;
    }
    
    /**
     * Removes some data identified by a key from the cache.
     *
     * @param mixed $key The data key.
     * @return void
     */
    public function remove($key)
    {
        $key = $this->normalizeKey($key);
        $res = $this->mem->get($key);
        if (isset($res[$key]) && is_int($res[$key]))
        {
            for ($i = 0, $j = $res[$key]; $i <= $j; $i++)
            {
                $this->mem->delete($key . $i);
            }
        }
        $this->mem->delete($key);
        $this->mem->delete(static::META_PREFIX . $key);
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
     * Retrieves data from the cache. Returns FALSE on failure.
     *
     * @param string $key The normalized data key.
     * @return string|bool
     */
    protected function getValue($key)
    {
        $res = $this->mem->get($key);
        if ($res === false)
        {
            return false;
        }
        if (isset($res[$key]) && is_int($res[$key]))
        {
            for ($i = 0, $j = $res[$key], $res = ''; $i <= $j; $i++)
            {
                $part = $this->mem->get($key . $i);
                if ($part === false)
                {
                    return false;
                }
                $res .= $part;
            }
        }
        return $res;
    }

    /**
     * Stores data in the cache.
     *
     * @param string $key The normalized data key.
     * @param string $content The serialized data.
     * @param int $expire The normalized cache expiration time.
     * @param string $method The native method that used for storing. Valid values: "set" or "add".
     * @return bool TRUE on success and FALSE on failure.
     */
    protected function setValue($key, $content, int $expire, string $method = 'set') : bool
    {
        if (strlen($content) <= static::MAX_BLOCK_SIZE)
        {
            if ($this->mem instanceof \Memcache)
            {
                return $this->mem->{$method}($key, $content, $this->compress, $expire);
            }
            return $this->mem->{$method}($key, $content, $expire);
        }
        if ($this->mem instanceof \Memcache)
        {
            foreach (str_split($content, static::MAX_BLOCK_SIZE) as $n => $part)
            {
                if ($this->mem->{$method}($key . $n, $part, $this->compress, $expire) === false)
                {
                    return false;
                }
            }
            return $this->mem->{$method}($key, [$key => $n], $this->compress, $expire);
        }
        foreach (str_split($content, static::MAX_BLOCK_SIZE) as $n => $part)
        {
            if ($this->mem->{$method}($key . $n, $part, $expire) === false)
            {
                return false;
            }
        }
        return $this->mem->{$method}($key, [$key => $n], $expire);
    }
}