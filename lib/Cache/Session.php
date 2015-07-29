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
 * The class is intended for caching of different data using PHP sessions.
 * You can use this type of cache for testing caching in your applications.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.0
 * @package aleph.cache
 */
class Session extends Cache
{
    /**
     * The key of the session array's element that contains all cached data.
     *
     * @var string|integer $ns
     * @protected
     */
    protected $ns = '__CACHE__';
    
    /**
     * Checks whether the current type of cache is available or not.
     *
     * @return boolean
     * @access public
     * @static
     */
    public static function isAvailable()
    {
        return session_id() != '';
    }
    
    /**
     * Returns the key of session array's element that stores all cached data.
     *
     * @return string|integer
     * @access public
     */
    public function getNamespace()
    {
        return $this->ns;
    }
    
    /**
     * Sets the key of of session array's element that stores all cached data.
     *
     * @param string|integer $namespace
     * @access public
     */
    public function setNamespace($namespace)
    {
        $this->ns = $namespace;
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
        $mkey = self::META_PREFIX . $this->normalizeKey($key);
        return isset($_SESSION[$this->ns][$mkey]) ? $_SESSION[$this->ns][$mkey] : false;
    }
    
    /**
     * Returns some data previously stored in the cache.
     *
     * @param mixed $key - the data key.
     * @param boolean $isExpired - will be set to TRUE if the given cache is expired and FALSE otherwise.
     * @return mixed
     * @access public
     */
    public function get($key, &$isExpired = null)
    {                    
        $key = $this->normalizeKey($key);
        if (!empty($_SESSION[$this->ns][$key]))
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
        $_SESSION[$this->ns][$k] = [serialize($content), $expire + time()];
        $_SESSION[$this->ns][self::META_PREFIX . $k] = [$expire, $group];
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
     * @param mixed $key - the data key.
     * @param integer $expire - the new expiration time.
     * @return boolean
     * @access public
     */
    public function touch($key, $expire = 0)
    {
        $key = $this->normalizeKey($key);
        if (!empty($_SESSION[$this->ns][$key]))
        {
            if (is_array($_SESSION[$this->ns][$key]))
            {
                $expire = $this->normalizeExpire($expire);
                $_SESSION[$this->ns][$key][1] = $expire + time();
                $_SESSION[$this->ns][self::META_PREFIX . $key][0] = $expire;
                return true;
            }
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
        unset($_SESSION[$this->ns][$key]);
        unset($_SESSION[$this->ns][self::META_PREFIX . $key]);
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
     * @access public
     */
    public function clean()
    {
        unset($_SESSION[$this->ns]);
    }
   
    /**
     * Removes keys of the expired data from the key vault.
     *
     * @access protected
     */
    protected function normalizeVault()
    {
        if (isset($_SESSION[$this->ns]) && is_array($_SESSION[$this->ns]))
        {
            foreach ($_SESSION[$this->ns] as $key => $item)
            {
                if (strpos($key, self::META_PREFIX) === false && (!is_array($item) || $item[1] < time()))
                {
                    unset($_SESSION[$this->ns][$key]);
                    unset($_SESSION[$this->ns][self::META_PREFIX . $key]);
                }
            }
        }
        parent::normalizeVault();
    }
}