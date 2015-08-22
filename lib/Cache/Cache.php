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
 * Base abstract class for building of classes that intended for caching different data.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.1
 * @package aleph.cache
 * @abstract
 */
abstract class Cache implements \Countable
{
    /**
     * Error message templates.
     */
    const ERR_CACHE_1 = 'Cache of type "%s" is not available.';
    
    /**
     * Prefix of all meta data's keys.
     */
    const META_PREFIX = 'meta_';

    /**
     * The vault key of all cached data.  
     *
     * @var string $vaultKey
     * @access private
     */
    private $vaultKey = null;
    
    /**
     * Prefix that will prepend every cache key.
     *
     * @var string $keyPrefix
     * @access private
     */
    private $keyPrefix = null;
  
    /**
     * The vault lifetime. Defined as 1 year by default.
     *
     * @var integer $vaultLifeTime - given in seconds.
     * @access protected
     */
    protected $vaultLifeTime = 31536000; // 1 year
  
    /**
     * Returns an instance of caching class according to configuration settings. 
     *
     * @param string $type - cache type.
     * @param array $params - configuration parameters for cache.
     * @access public
     * @static
     */
    public static function getInstance($type = null, array $params = null)
    {
        if ($type === null)
        {
            $params = \Aleph::get('cache');
            $type = isset($params['type']) ? $params['type'] : '';
        }
        $type = strtolower($type);
        switch ($type)
        {
            case 'memory':
            case 'memcache':
            case 'memcached':
                if (!Memory::isAvailable($type))
                {
                    throw new \RuntimeException(sprintf(static::ERR_CACHE_1, 'Memory'));
                }
                return new Memory($type,
                                  isset($params['servers']) ? (array)$params['servers'] : [], 
                                  isset($params['compress']) ? (bool)$params['compress'] : true);
            case 'apc':
                if (!APC::isAvailable())
                {
                    throw new \RuntimeException(sprintf(static::ERR_CACHE_1, 'APC'));
                }
                return new APC();
            case 'phpredis':
                if (!PHPRedis::isAvailable())
                {
                    throw new \RuntimeException(sprintf(static::ERR_CACHE_1, 'PHPRedis'));
                }
                return new PHPRedis(
                    isset($params['host']) ? $params['host'] : '127.0.0.1',
                    isset($params['port']) ? $params['port'] : 6379,
                    isset($params['timeout']) ? $params['timeout'] : 0,
                    isset($params['password']) ? $params['password'] : null,
                    isset($params['database']) ? $params['database'] : 0
                );
            case 'redis':
                return new Redis(
                    isset($params['host']) ? $params['host'] : '127.0.0.1',
                    isset($params['port']) ? $params['port'] : 6379,
                    isset($params['timeout']) ? $params['timeout'] : null,
                    isset($params['password']) ? $params['password'] : null,
                    isset($params['database']) ? $params['database'] : 0
                );
            case 'session':
                $cache = new Session();
                if (isset($params['namespace']))
                {
                    $cache->setNamespace($params['namespace']);
                }
            case 'file':
            default:
                $cache = new File();
                if (isset($params['directory']))
                {
                    $cache->setDirectory($params['directory']);
                }
                if (isset($params['directoryMode']))
                {
                    $cache->setDirectoryMode($params['directoryMode']);
                }
                if (isset($params['fileMode']))
                {
                    $cache->setFileMode($params['fileMode']);
                }
                return $cache;
        }
    }
  
    /**
     * Constructor of the class.
     * Initializes the key prefix and vault key.
     *
     * @access public
     */
    public function __construct()
    {
        $this->vaultKey = 'vault_' . \Aleph::getSiteUniqueID();
        $this->keyPrefix = \Aleph::get('cache.keyPrefix', \Aleph::getSiteUniqueID());
    }
  
    /**
     * Checks whether the current type of cache is available or not.
     *
     * @return boolean
     * @access public
     * @static
     */
    public static function isAvailable()
    {
        return true;
    }
    
    /**
     * Returns meta information (expiration time and group) of the cached data.
     * It returns FALSE if the data does not exist.
     *
     * @param mixed $key - the data key.
     * @return array
     * @access public
     * @abstract
     */
    abstract public function getMeta($key);
    
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
        if ($this->isExpired($key))
        {
            $this->set($key, $content, $expire, $group);
            return true;
        }
        return false;
    }
    
    /**
     * Call of this method is equivalent to the following code:
     * if ($cache->isExpired($key))
     * {
     *     $data = $callback($key);
     *     $cache->set($key, $data, $expire, $group);
     * }
     * else
     * {
     *     $data = $cache->get($key);
     * }
     *
     * @param mixed $key - the data key.
     * @param mixed $callback - the delegate that will be automatically invoked when the cache is expired. It should return data that will be cached.
     * @param integer $expire - the cache lifetime(in seconds). If it is FALSE or zero the cache life time is used.
     * @param string $group - the group of cached data.
     * @return mixed - the cached data.
     * @access public
     */
    public function rw($key, $callback, $expire = 0, $group = null)
    {
        $data = $this->get($key, $isExpired);
        if ($isExpired)
        {
            $data = \Aleph::delegate($callback, $key);
            $this->set($key, $data, $expire, $group);
        }
        return $data;
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
    abstract public function get($key, &$isExpired = null);
 
    /**
     * Stores some data identified by a key in the cache.
     *
     * @param mixed $key - the data key.
     * @param mixed $content - the cached data.
     * @param integer $expire - the cache lifetime (in seconds). If it is FALSE or zero the cache life time is used.
     * @param string $group - the group of data.
     * @access public
     * @abstract
     */
    abstract public function set($key, $content, $expire = 0, $group = null);
    
    /**
     * Updates the previously stored data with new data.
     *
     * @param mixed $key - the key of the data being updated.
     * @param mixed $content - the new data.
     * @return boolean - returns TRUE on success and FALSE on failure (if cache does not exist or expired).
     * @access public
     * @abstract
     */
    abstract public function update($key, $content);
    
    /**
     * Sets a new expiration on an cached data.
     * Returns TRUE on success or FALSE on failure. 
     *
     * @param mixed $key - the data key.
     * @param integer $expire - the new expiration time.
     * @return boolean
     * @access public
     * @abstract
     */
    abstract public function touch($key, $expire = 0);

    /**
     * Removes some data identified by a key from the cache.
     *
     * @param mixed $key - the data key.
     * @access public
     * @abstract
     */
    abstract public function remove($key);

    /**
     * Checks whether the cache is expired or not.
     *
     * @param string $key - the data key.
     * @return boolean
     * @access public
     * @abstract
     */
    abstract public function isExpired($key);

    /**
     * Removes all previously stored data from the cache.
     *
     * @access public
     * @abstract
     */
    abstract public function clean();
  
    /**
     * Garbage collector that should be used for removing of expired cache data.
     *
     * @param float $probability - probability of garbage collector performing.
     * @access public
     */
    public function gc($probability = null)
    {
        if ($probability === null)
        {
            $probability = \Aleph::get('cache.gcProbability', 100);
        }
        if ((float)$probability * 1000 >= rand(0, 99999))
        {
            $this->normalizeVault();
        }
    }
  
    /**
     * Returns the number of keys in the cache vault.
     *
     * @return integer
     * @access public
     */
    public function count()
    {
        $vault = $this->getVault();
        if (!is_array($vault))
        {
            return 0;
        }
        $count = 0;
        foreach ($vault as $keys)
        {
            $count += count($keys);
        }
        return $count;
    }
  
    /**
     * Returns the vault of data keys conserved in cache before.
     *
     * @return array - returns a key array or NULL if empty.
     * @access public
     */
    public function getVault()
    {
        return $this->get($this->vaultKey);
    }
    
    /**
     * Returns key prefix.
     *
     * @return string
     * @access public
     */
    public function getKeyPrefix()
    {
        return $this->keyPrefix;
    }
    
    /**
     * Sets new key prefix.
     *
     * @param string $prefix
     * @access public
     */
    public function setKeyPrefix($prefix)
    {
        $this->keyPrefix = $prefix;
    }
  
    /**
     * Returns the vault lifetime.
     *
     * @return integer
     * @access public
     */
    public function getVaultLifeTime()
    {
        return $this->vaultLifeTime;
    }
  
    /**
     * Sets the vault lifetime.
     *
     * @param integer $vaultLifeTime - new vault lifetime in seconds.
     * @access public
     */
    public function setVaultLifeTime($vaultLifeTime)
    {
        $this->vaultLifeTime = abs((int)$vaultLifeTime);
    }
  
    /**
     * Returns cached data by their group.
     *
     * @param string $group - the group of cached data.
     * @return array
     * @access public   
     */
    public function getByGroup($group)
    {
        $vault = $this->getVault();
        if (empty($vault[$group]) || !is_array($vault[$group]))
        {
            return [];
        }
        $tmp = [];
        foreach ($vault[$group] as $key => $expire)
        {
            $data = $this->get($key, $isExpired);
            if (!$isExpired)
            {
                $tmp[$key] = $data;
            }
        }
        return $tmp;
    }
  
    /**
     * Cleans cached data by their group.
     *
     * @param string $group - group of cached data.
     * @access public
     */
    public function cleanByGroup($group)
    {
        $vault = $this->getVault();
        if (empty($vault[$group]) || !is_array($vault[$group]))
        {
            return;
        }
        foreach ($vault[$group] as $key => $expire)
        {
            $this->remove($key);
        }
        unset($vault[$group]);
        $this->set($this->vaultKey, $vault, $this->vaultLifeTime, null);
    }
  
    /**
     * Saves the key of caching data in the key vault.
     *
     * @param mixed $key - a key to save.
     * @param integer $expire - cache lifetime of data defined by the key.
     * @param string $group - group of a key.
     * @access protected
     */
    protected function saveKeyToVault($key, $expire, $group)
    {
        if ($group !== null)
        {
            $vault = $this->getVault();
            $vault[$group][$key] = $expire;
            $this->set($this->vaultKey, $vault, $this->vaultLifeTime, null);
        }
    }
  
    /**
     * Normalizes expiration time value.
     *
     * @param integer $expire - cache lifetime (in seconds). If it is not defined the vault lifetime is used.
     * @return integer
     * @access protected
     */
    protected function normalizeExpire($expire)
    {
        $expire = abs((int)$expire);
        return $expire ?: $this->vaultLifeTime;
    }
    
    /**
     * Normalizes the data key.
     *
     * @param mixed $key - the key to be normalized.
     * @return string
     */
    protected function normalizeKey($key)
    {
        return md5($this->keyPrefix . $key);
    }
  
    /**
     * Removes keys of the expired data from the key vault.
     *
     * @access protected
     */
    protected function normalizeVault()
    {
        $vault = $this->getVault();
        if (!is_array($vault))
        {
            return;
        }
        foreach ($vault as $group => $keys)
        {
            foreach ($keys as $key => $expire)
            {
                if ($this->isExpired($key, $expire)) 
                {
                    $this->remove($key);
                    unset($vault[$group][$key]);
                }
            }
            if (count($vault[$group]) == 0)
            {
                unset($vault[$group]);
            }
        }
        $this->set($this->vaultKey, $vault, $this->vaultLifeTime, null);
    }
}