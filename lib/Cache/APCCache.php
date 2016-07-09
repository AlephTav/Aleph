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
 * The class intended for caching of different data using the APC extension. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.2
 * @package aleph.cache
 */
class APCCache extends Cache
{
    /**
     * Error message templates.
     */
    const ERR_CACHE_APC_1 = 'APC cache extension is not enabled. Set option apc.enabled to 1 in php.ini';
    const ERR_CACHE_APC_2 = 'APC cache extension is not enabled cli. Set option apc.enable_cli to 1 in php.ini';
  
    /**
     * Checks whether the current type of cache is available or not.
     *
     * @return bool
     */
    public static function isAvailable() : bool
    {
        return extension_loaded('apc');
    }

    /**
     * Constructor.
     * 
     * @return void
     * @throws \RuntimeException If APC extension is not enabled.
     */
    public function __construct()
    {
        parent::__construct();
        if (!ini_get('apc.enabled'))
        {
            throw new \RuntimeException(static::ERR_CACHE_APC_1);
        }
        if (PHP_SAPI === 'cli' && !ini_get('apc.enable_cli'))
        {
            throw new \RuntimeException(static::ERR_CACHE_APC_2);
        }
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
        $meta = apc_fetch(static::META_PREFIX . $this->normalizeKey($key));
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
        if (apc_add($k, $content, $expire))
        {
            apc_add(static::META_PREFIX . $k, [$expire, $tags], $expire);
            $this->saveKeyToVault($key, $expire, $tags);
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
        $content = apc_fetch($this->normalizeKey($key), $isExpired);
        $isExpired = !$isExpired;
        return $isExpired ? null : $content;
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
        apc_store($k, $content, $expire);
        apc_store(static::META_PREFIX . $k, [$expire, $tags], $expire);
        $this->saveKeyToVault($key, $expire, $tags);
    }
   
    /**
     * Updates the previously stored data with new data.
     *
     * @param mixed $key The key of the data being updated.
     * @param mixed $content The new data.
     * @return bool Returns TRUE on success and FALSE on failure (if cache does not exist or expired).
     * @return void
     */
    public function update($key, $content) : bool
    {
        $key = $this->normalizeKey($key);
        $meta = apc_fetch(static::META_PREFIX . $key, $success);
        return $success ? apc_store($key, $content, $meta[0]) : false;
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
        $content = apc_fetch($key, $success);
        if ($success === false)
        {
            return false;
        }
        $meta = apc_fetch($mkey);
        if (isset($meta[0]))
        {
            $expire = $this->normalizeExpire($expire);
            $meta[0] = $expire;
            apc_store($key, $content, $expire);
            apc_store($mkey, $meta, $expire);
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
        apc_delete($key);
        apc_delete(static::META_PREFIX . $key);
    }

    /**
     * Checks whether cache lifetime is expired or not.
     *
     * @param string $key The data key.
     * @return bool
     */
    public function isExpired($key) : bool
    {                  
        return !apc_exists($this->normalizeKey($key));
    }

    /**
     * Removes all previously stored data from the cache.
     *
     * @return void
     */
    public function clean()
    {
        apc_clear_cache('user');
    }
}