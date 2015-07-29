<?php
/**
 * Copyright (c) 2013 - 2015 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2015 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Cache;

/**
 * The class is intended for caching of different data using the PHP Redis extension.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.0
 * @package aleph.cache
 */
class PHPRedis extends Cache
{
    /**
     * The instance of \Redis class.
     *
     * @var \Redis $redis
     */
    private $redis;
  
    /**
     * Checks whether the current type of cache is available or not.
     *
     * @return boolean
     * @access public
     * @static
     */
    public static function isAvailable()
    {
        return extension_loaded('redis');
    }

    /**
     * Constructor.
     *
     * @param string $host - host or path to a unix domain socket for a redis connection.
     * @param integer $port - port for a connection, optional.
     * @param integer $timeout - the connection timeout, in seconds.
     * @param string $password - password for server authentication, optional.
     * @param integer $database - number of the redis database to use.
     * @access public
     */
    public function __construct($host = '127.0.0.1', $port = 6379, $timeout = 0, $password = null, $database = 0)
    {
        parent::__construct();
        $this->redis = new \Redis();
        $this->redis->connect($host, $port, $timeout);
        if ($password !== null)
        {
            $this->redis->auth($password);
        }
        $this->redis->select($database);
        $this->redis->setOption(\Redis::OPT_SERIALIZER, defined('Redis::SERIALIZER_IGBINARY') ? \Redis::SERIALIZER_IGBINARY : \Redis::SERIALIZER_PHP);
    }

    /**
     * Returns the redis object.
     *
     * @return \Redis
     * @access public
     */
    public function getNativeObject()
    {
        return $this->redis;
    }
    
    /**
     * Returns meta information (expiration time and group) of the cached data.
     * It returns FALSE if the data does not exist.
     *
     * @param mixed $key - the data key.
     * @return array
     * @access public
     */
    public function getMeta($key)
    {
        return $this->redis->get(self::META_PREFIX . $this->normalizeKey($key));
    }
    
    /**
     * Returns some data previously stored in the cache.
     *
     * @param mixed $key - the data key.
     * @param boolean $isExpired - will be set to TRUE if the given cache is expired and FALSE otherwise.
     * @return mixed
     * @access public
     * @abstract
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
     * @param mixed $key - the data key.
     * @param mixed $content - the cached data.
     * @param integer $expire - the cache lifetime (in seconds). If it is FALSE or zero the cache life time is used.
     * @param string $group - the group of data.
     * @access public
     */
    public function set($key, $content, $expire = 0, $group = null)
    {
        $k = $this->normalizeKey($key)
        $expire = $this->normalizeExpire($expire);
        $this->redis->set($k, $content, $expire);
        $this->redis->set(self::META_PREFIX . $k, [$expire, $group], $expire);
        $this->saveKeyToVault($key, $expire, $group);
    }
    
    /**
     * Updates the previously stored data with new data.
     *
     * @param mixed $key - the key of the data being updated.
     * @param mixed $content - the new data.
     * @return boolean - returns TRUE on success and FALSE on failure (if cache does not exist or expired).
     * @access public
     */
    public function update($key, $content)
    {
        $key = $this->normalizeKey($key);
        $meta = $this->redis->get(self::META_PREFIX . $key);
        return $meta ? $this->redis->set($key, $content, $meta[0]) : false;
    }
    
    /**
     * Sets a new expiration on an cached data.
     * Returns TRUE on success or FALSE on failure. 
     *
     * @param mixed $key - the data key.
     * @param integer $expire - the new expiration time.
     * @return boolean
     * @access public
     */
    public function touch($key, $expire = 0)
    {
        $key = $this->normalizeKey($key);
        $mkey = self::META_PREFIX . $key;
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
     * @param mixed $key - the data key.
     * @access public
     */
    public function remove($key)
    {
        $key = $this->normalizeKey($key);
        $this->redis->delete($key);
        $this->redis->delete(self::META_PREFIX . $key);
    }

    /**
     * Checks whether cache lifetime is expired or not.
     *
     * @param string $key - the data key.
     * @return boolean
     * @access public
     */
    public function isExpired($key)
    {
        return !$this->redis->exists($key);
    }

    /**
     * Removes all previously stored data from the cache.
     *
     * @access public
     */
    public function clean()
    {
        $this->redis->flushDB();
    }
  
    /**
     * Closes the current connection with Redis.
     *
     * @access public
     */
    public function __destruct()
    {
        $this->redis->close();
    }
}