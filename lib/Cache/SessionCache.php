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
 * The class is intended for caching of different data using PHP sessions.
 * You can use this type of cache for testing caching in your applications.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.1
 * @package aleph.cache
 */
class SessionCache extends Cache
{
    /**
     * The key of the session array's element that contains all cached data.
     *
     * @var string
     */
    protected $ns = '__CACHE__';
    
    /**
     * Checks whether the current type of cache is available or not.
     *
     * @return bool
     */
    public static function isAvailable() : bool
    {
        return session_id() != '';
    }
    
    /**
     * Returns the key of session array's element that stores all cached data.
     *
     * @return string
     */
    public function getNamespace() : string
    {
        return $this->ns;
    }
    
    /**
     * Sets the key of of session array's element that stores all cached data.
     *
     * @param string $namespace
     * @return void
     */
    public function setNamespace(string $namespace)
    {
        $this->ns = $namespace;
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
        $mkey = static::META_PREFIX . $this->normalizeKey($key);
        return $_SESSION[$this->ns][$mkey] ?? [];
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
        if (isset($_SESSION[$this->ns][$key]))
        {
            $data = $_SESSION[$this->ns][$key];
            if (is_array($data))
            {
                $isExpired = $data[1] <= time();
                return unserialize($data[0]);
            }
        }
        $isExpired = true;
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
        $_SESSION[$this->ns][$k] = [serialize($content), $expire + time()];
        $_SESSION[$this->ns][static::META_PREFIX . $k] = [$expire, $tags];
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
        $mkey = static::META_PREFIX . $key;
        if (!empty($_SESSION[$this->ns][$mkey]))
        {
            $meta = $_SESSION[$this->ns][$mkey];
            if (is_array($meta))
            {
                $_SESSION[$this->ns][$key] = [serialize($content), $meta[0] + time()];
                return true;
            }
        }
        return false;
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
        if (!empty($_SESSION[$this->ns][$key]))
        {
            if (is_array($_SESSION[$this->ns][$key]))
            {
                $expire = $this->normalizeExpire($expire);
                $_SESSION[$this->ns][$key][1] = $expire + time();
                $_SESSION[$this->ns][static::META_PREFIX . $key][0] = $expire;
                return true;
            }
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
        unset($_SESSION[$this->ns][$key]);
        unset($_SESSION[$this->ns][static::META_PREFIX . $key]);
    }

    /**
     * Checks whether cache lifetime is expired or not.
     *
     * @param string $key The data key.
     * @return bool
     */
    public function isExpired($key) : bool
    {                      
        $key = $this->normalizeKey($key);
        if (!empty($_SESSION[$this->ns][$key]))
        {
            $data = $_SESSION[$this->ns][$key];
            if (is_array($data))
            {
                return $data[1] <= time();
            }
        }
        return false;
    }
  
    /**
     * Removes all previously stored data from the cache.
     *
     * @return void
     */
    public function clean()
    {
        unset($_SESSION[$this->ns]);
    }
   
    /**
     * Removes keys of the expired data from the key vault.
     *
     * @return void
     */
    protected function normalizeVault()
    {
        if (isset($_SESSION[$this->ns]) && is_array($_SESSION[$this->ns]))
        {
            foreach ($_SESSION[$this->ns] as $key => $item)
            {
                if (strpos($key, static::META_PREFIX) === false && (!is_array($item) || $item[1] < time()))
                {
                    unset($_SESSION[$this->ns][$key]);
                    unset($_SESSION[$this->ns][static::META_PREFIX . $key]);
                }
            }
        }
        parent::normalizeVault();
    }
}