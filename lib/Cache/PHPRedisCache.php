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

use Redis;

/**
 * The class is intended for caching of different data using the PHP Redis extension.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.1
 * @package aleph.cache
 */
class PHPRedisCache extends Cache
{
    /**
     * The instance of \Redis class.
     *
     * @var \Redis $redis
     */
    private $redis = null;
  
    /**
     * Checks whether the current type of cache is available or not.
     *
     * @return bool
     */
    public static function isAvailable() : bool
    {
        return extension_loaded('redis');
    }

    /**
     * Constructor.
     *
     * @param string $host Host or path to a unix domain socket for a redis connection.
     * @param int $port Port for a connection, optional.
     * @param int $timeout The connection timeout, in seconds.
     * @param string|null $password Password for server authentication, optional.
     * @param int $database Number of the redis database to use.
     * @return void
     */
    public function __construct(string $host = '127.0.0.1', int $port = 6379, int $timeout = 0, string $password = null, int $database = 0)
    {
        parent::__construct();
        $this->redis = new Redis();
        $this->redis->connect($host, $port, $timeout);
        if ($password !== null)
        {
            $this->redis->auth($password);
        }
        $this->redis->select($database);
        $this->redis->setOption(Redis::OPT_SERIALIZER, defined('Redis::SERIALIZER_IGBINARY') ? Redis::SERIALIZER_IGBINARY : Redis::SERIALIZER_PHP);
    }

    /**
     * Returns the redis object.
     *
     * @return \Redis
     */
    public function getNativeObject() : Redis
    {
        return $this->redis;
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
        $meta = $this->redis->get(static::META_PREFIX . $this->normalizeKey($key));
        return is_array($meta) ? $meta : [];
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
        $key = $this->normalizeKey($key);
        $isExpired = !$this->redis->exists($key);
        return $isExpired ? null : $this->redis->get($key);
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
        $k = $this->normalizeKey($key)
        $expire = $this->normalizeExpire($expire);
        $this->redis->set($k, $content, $expire);
        $this->redis->set(static::META_PREFIX . $k, [$expire, $tags], $expire);
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
        $meta = $this->redis->get(static::META_PREFIX . $key);
        return $meta ? $this->redis->set($key, $content, $meta[0]) : false;
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
        $meta = $this->redis->get($mkey);
        if (isset($meta[0]) && $this->redis->exists($key))
        {
            $expire = $this->normalizeExpire($expire);
            $meta[0] = $expire;
            $this->redis->setTimeout($key, $expire);
            $this->redis->set($mkey, $meta, $expire);
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
        $this->redis->delete($key);
        $this->redis->delete(static::META_PREFIX . $key);
    }

    /**
     * Checks whether cache lifetime is expired or not.
     *
     * @param string $key The data key.
     * @return bool
     */
    public function isExpired($key) : bool
    {
        return !$this->redis->exists($key);
    }

    /**
     * Removes all previously stored data from the cache.
     *
     * @return void
     */
    public function clean()
    {
        $this->redis->flushDB();
    }
  
    /**
     * Closes the current connection with Redis.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->redis->close();
    }
}