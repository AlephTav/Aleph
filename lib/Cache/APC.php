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
 * The class intended for caching of different data using the APC extension. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.0
 * @package aleph.cache
 */
class APC extends Cache
{
    /**
     * Error message templates.
     */
    const ERR_CACHE_APC_1 = 'APC cache extension is not enabled. Set option apc.enabled to 1 in php.ini';
    const ERR_CACHE_APC_2 = 'APC cache extension is not enabled cli. Set option apc.enable_cli to 1 in php.ini';
  
    /**
     * Checks whether the current type of cache is available or not.
     *
     * @return boolean
     * @access public
     * @static
     */
    public static function isAvailable()
    {
        return extension_loaded('apc');
    }

    /**
     * Constructor.
     * 
     * @access public
     */
    public function __construct()
    {
        parent::__construct();
        if (!ini_get('apc.enabled'))
        {
            throw new Core\Exception([$this, 'ERR_CACHE_APC_1']);
        }
        if (PHP_SAPI === 'cli' && !ini_get('apc.enable_cli'))
        {
            throw new Core\Exception([$this, 'ERR_CACHE_APC_2']);
        }
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
        return apc_fetch(self::META_PREFIX . $this->normalizeKey($key));
    }
    
    /**
     * Stores some data identified by a key in the cache, only if it's not already stored.
     *
     * @param mixed $key - the data key.
     * @param mixed $content - the cached data.
     * @param integer $expire - the cache lifetime (in seconds). If it is FALSE or zero the cache life time is used.
     * @param string $group - the group of data.
     * @return boolean - returns TRUE if something has effectively been added into the cache, FALSE otherwise.
     * @access public
     */
    public function add($key, $content, $expire = 0, $group = null)
    {
        $k = $this->normalizeKey($key);
        $expire = $this->normalizeExpire($expire);
        if (apc_add($k, $content, $expire))
        {
            apc_add(self::META_PREFIX . $k, [$expire, $group], $expire);
            $this->saveKeyToVault($key, $expire, $group);
        }
        return false;
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
        $content = apc_fetch($this->normalizeKey($key), $isExpired);
        $isExpired = !$isExpired;
        return $isExpired ? null : $content;
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
        $k = $this->normalizeKey($key);
        $expire = $this->normalizeExpire($expire);
        apc_store($k, $content, $expire);
        apc_store(self::META_PREFIX . $k, [$expire, $group], $expire);
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
        $meta = apc_fetch(self::META_PREFIX . $key, $success);
        return $success ? apc_store($key, $content, $meta[0]) : false;
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
     * @param mixed $key - the data key.
     * @access public
     */
    public function remove($key)
    {
        $key = $this->normalizeKey($key);
        apc_delete($key);
        apc_delete(self::META_PREFIX . $key);
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
        return !apc_exists($this->normalizeKey($key));
    }

    /**
     * Removes all previously stored data from the cache.
     *
     * @access public
     */
    public function clean()
    {
        apc_clear_cache('user');
    }
}