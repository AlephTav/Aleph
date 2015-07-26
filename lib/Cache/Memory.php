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

use Aleph\Core;

/**
 * The class is intended for caching of different data using the memcache extension.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.0
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
     * @var boolean $compress
     * @access protected
     */
    protected $compress = true;
  
    /**
     * The vault lifetime. Defined as 1 year by default.
     *
     * @var integer $vaultLifeTime - given in seconds.
     * @access protected
     */
    protected $vaultLifeTime = 2592000; // 1 month

    /**
     * The instance of Memcache or Memcached class.
     *
     * @var Memcache|Memcached $mem
     * @access private
     */
    private $mem = null;
  
    /**
     * Checks whether the current type of cache is available or not.
     *
     * @return boolean
     * @access public
     * @static
     */
    public static function isAvailable($type = null)
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
     * @param string $type - determines type of cache extensions. The valid values: memcache, memcached.
     * @param array $servers - hosts for a memcache connection.
     * @param boolean $compress - if value of this parameter is TRUE any data will be compressed before placing in a cache, otherwise data will not be compressed.
     * @access public
     */
    public function __construct($type, array $servers, $compress = true)
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
                    $this->mem->addServer(isset($server['host']) ? $server['host'] : '127.0.0.1',
                                          isset($server['port']) ? $server['port'] : 11211,
                                          isset($server['persistent']) ? $server['persistent'] : true,
                                          isset($server['weight']) ? $server['weight'] : 1,
                                          isset($server['timeout']) ? $server['timeout'] : 1,
                                          isset($server['retryInterval']) ? $server['retryInterval'] : 15,
                                          isset($server['status']) ? $server['status'] : true);
                }
            }
            else
            {
                foreach ($servers as $server)
                {
                    $this->mem->addServer(isset($server['host']) ? $server['host'] : '127.0.0.1',
                                          isset($server['port']) ? $server['port'] : 11211,
                                          isset($server['weight']) ? $server['weight'] : 0);
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
     * @param boolean $flag
     * @access public
     */
    public function compress($flag = true)
    {
        if ($compress && !extension_loaded('zlib'))
        {
            throw new Core\Exception([$this, 'ERR_CACHE_MEMORY_1']);
        }
        $this->compress = $compress ? MEMCACHE_COMPRESSED : 0;
    }

    /**
     * Gets the native caching object.
     *
     * @return Memcache|Memcached
     * @access public
     */
    public function getNativeObject()
    {
        return $this->mem;
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
        $res = $this->getValue($key);
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
     * @param mixed $key - the data key.
     * @param mixed $content - the cached data.
     * @param integer $expire - the cache lifetime (in seconds). If it is FALSE or zero the cache life time is used.
     * @param string $group - the group of data.
     * @access public
     */
    public function set($key, $content, $expire = null, $group = null)
    {
        $k = $this->normalizeKey($key);
        $expire = $this->normalizeExpire($expire);
        $this->setValue($k, serialize($content), $expire);
        $this->setValue(self::META_PREFIX . $k, [$expire, $group], $expire);
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
        $mkey = self::META_PREFIX . $key;
        $meta = $this->getValue($mkey);
        if ($meta === false)
        {
            return false;
        }
        $this->setValue($key, serialize($content), $meta[0]);
        return true;
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
        $content = $this->getValue($key);
        if ($content === false)
        {
            return false;
        }
        $meta = $this->getValue($mkey);
        $expire = $this->normalizeExpire($expire);
        $meta[0] = $expire;
        $this->setValue($key, $content, $expire);
        $this->setValue($mkey, $meta, $expire);
        return true;
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
        $res = $this->mem->get($key);
        if (isset($res[$key]) && is_int($res[$key]))
        {
            for ($i = 0, $j = $res[$key]; $i <= $j; $i++)
            {
                $this->mem->delete($key . $i);
            }
        }
        $this->mem->delete($key);
        $this->mem->delete(self::META_PREFIX . $key);
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
        return $this->mem->get($this->normalizeKey($key)) === false;
    }

    /**
     * Removes all previously stored data from the cache.
     *
     * @access public
     */
    public function clean()
    {
        $this->mem->flush();
    }

    /**
     * Retrieves data from the cache. Returns FALSE on failure.
     *
     * @param string $key - the normalized data key.
     * @return string
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
     * @param string $key - the normalized data key.
     * @param string $content - the serialized data.
     * @param integer $expire - the normalized cache expiration time.
     * @access protected
     */
    protected function setValue($key, $content, $expire)
    {
        if (strlen($content) <= self::MAX_BLOCK_SIZE)
        {
            if ($this->mem instanceof \Memcache)
            {
                $this->mem->set($key, $content, $this->compress, $expire);
            }
            else
            {
                $this->mem->set($key, $content, $expire);
            }
        }
        else
        {
            if ($this->mem instanceof \Memcache)
            {
                foreach (str_split($content, self::MAX_BLOCK_SIZE) as $n => $part)
                {
                    $this->mem->set($key . $n, $part, $this->compress, $expire);
                }
                $this->mem->set($key, [$key => $n], $this->compress, $expire);
            }
            else
            {
                foreach (str_split($content, self::MAX_BLOCK_SIZE) as $n => $part)
                {
                    $this->mem->set($key . $n, $part, $expire);
                }
                $this->mem->set($key, [$key => $n], $expire);
            }
        }
    }
}