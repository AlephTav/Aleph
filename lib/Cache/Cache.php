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

use Aleph,
    Aleph\Core;

/**
 * Base abstract class for building of classes that intended for caching different data.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.2
 * @package aleph.cache
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
     * @var string
     */
    private $vaultKey = '';
    
    /**
     * Prefix that will prepend every cache key.
     *
     * @var string
     */
    private $keyPrefix = '';
  
    /**
     * The vault lifetime (in seconds).
     * Defined as 10 year by default.
     *
     * @var int
     */
    protected $vaultLifeTime = 315360000;
  
    /**
     * Returns an instance of caching class according to configuration settings. 
     *
     * @param string $type The cache type.
     * @param array $params Configuration parameters for cache.
     * @return void
     * @throws \RuntimeException If the cache is not available.
     */
    public static function getInstance(string $type = '', array $params = [])
    {
        if ($type === '')
        {
            $params = Aleph::get('cache');
            $type = $params['type'] ?? '';
        }
        $type = strtolower($type);
        switch ($type)
        {
            case 'memory':
            case 'memcache':
            case 'memcached':
                if (!MemoryCache::isAvailable($type))
                {
                    throw new \RuntimeException(sprintf(static::ERR_CACHE_1, 'Memory'));
                }
                return new MemoryCache(
                    $type,
                    isset($params['servers']) ? (array)$params['servers'] : [], 
                    isset($params['compress']) ? (bool)$params['compress'] : true
                );
            case 'apc':
                if (!APCCache::isAvailable())
                {
                    throw new \RuntimeException(sprintf(static::ERR_CACHE_1, 'APC'));
                }
                return new APCCache();
            case 'phpredis':
                if (!PHPRedisCache::isAvailable())
                {
                    throw new \RuntimeException(sprintf(static::ERR_CACHE_1, 'PHPRedis'));
                }
                return new PHPRedisCache(
                    $params['host'] ?? '127.0.0.1',
                    $params['port'] ?? 6379,
                    $params['timeout'] ?? 0,
                    $params['password'] ?? null,
                    $params['database'] ?? 0
                );
            case 'redis':
                return new RedisCache(
                    $params['host'] ?? '127.0.0.1',
                    $params['port'] ?? 6379,
                    $params['timeout'] ?? null,
                    $params['password'] ?? null,
                    $params['database'] ?? 0
                );
            case 'session':
                $cache = new SessionCache();
                if (isset($params['namespace']))
                {
                    $cache->setNamespace($params['namespace']);
                }
            case 'file':
            default:
                $cache = new FileCache();
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
     * @return void
     */
    public function __construct()
    {
        $uid = Aleph::getAppUniqueID();
        $this->vaultKey = 'vault_' . $uid;
        $this->keyPrefix = Aleph::get('cache.keyPrefix', $uid);
    }
  
    /**
     * Checks whether the current type of cache is available or not.
     *
     * @return bool
     */
    public static function isAvailable() : bool
    {
        return true;
    }
    
    /**
     * Returns meta information (expiration time and tags) of the cached data.
     * It returns empty array if the meta data does not exist.
     *
     * @param mixed $key The data key.
     * @return array
     */
    abstract public function getMeta($key) : array;
    
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
        if ($this->isExpired($key))
        {
            $this->set($key, $content, $expire, $tags);
            return true;
        }
        return false;
    }
    
    /**
     * Call of this method is equivalent to the following code:
     * if ($cache->isExpired($key))
     * {
     *     $data = $callback($key);
     *     $cache->set($key, $data, $expire, $tags);
     * }
     * else
     * {
     *     $data = $cache->get($key);
     * }
     *
     * @param mixed $key The data key.
     * @param mixed $callback The callback that will be automatically invoked when the cache is expired. It should return data that will be cached.
     * @param int $expire The cache lifetime(in seconds). If it is FALSE or zero the cache life time is used.
     * @param string[] $tags An array of tags associated with the data.
     * @return mixed The cached data.
     */
    public function rw($key, $callback, int $expire = 0, array $tags = [])
    {
        $data = $this->get($key, $isExpired);
        if ($isExpired)
        {
            $data = Aleph::call($callback, $key);
            $this->set($key, $data, $expire, $tags);
        }
        return $data;
    }
    
    /**
     * Returns some data previously stored in the cache.
     *
     * @param mixed $key The data key.
     * @param mixed $isExpired It will be set to TRUE if the given cache is expired and FALSE otherwise.
     * @return mixed
     */
    abstract public function get($key, &$isExpired = null);
 
    /**
     * Stores some data identified by a key in the cache.
     *
     * @param mixed $key The data key.
     * @param mixed $content The cached data.
     * @param int $expire The cache lifetime (in seconds). If it is FALSE or zero the cache life time is used.
     * @param string[] $tags An array of tags associated with the data.
     * @return void
     */
    abstract public function set($key, $content, int $expire = 0, array $tags = []);
    
    /**
     * Updates the previously stored data with new data.
     *
     * @param mixed $key The key of the data being updated.
     * @param mixed $content The new data.
     * @return bool It returns TRUE on success and FALSE on failure (if cache does not exist or expired).
     */
    abstract public function update($key, $content) : bool;
    
    /**
     * Sets a new expiration on an cached data.
     * Returns TRUE on success or FALSE on failure. 
     *
     * @param mixed $key The data key.
     * @param int $expire The new expiration time.
     * @return bool
     */
    abstract public function touch($key, int $expire = 0) : bool;

    /**
     * Removes some data identified by a key from the cache.
     *
     * @param mixed $key The data key.
     * @return void
     */
    abstract public function remove($key);

    /**
     * Checks whether the cache is expired or not.
     *
     * @param mixed $key The data key.
     * @return bool
     */
    abstract public function isExpired($key) : bool;

    /**
     * Removes all previously stored data from the cache.
     *
     * @return void
     */
    abstract public function clean();
  
    /**
     * Garbage collector that should be used for removing of expired cache data.
     *
     * @param float $probability The probability of garbage collector performing.
     * @return void
     */
    public function gc(float $probability = null)
    {
        if ($probability === null)
        {
            $probability = (float)Aleph::get('cache.gcProbability', 100);
        }
        if ($probability * 1000 >= rand(0, 99999))
        {
            $this->normalizeVault();
        }
    }
  
    /**
     * Returns the number of keys in the cache vault.
     *
     * @return int
     */
    public function count() : int
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
     * @return array An array of cache keys or NULL if empty.
     */
    public function getVault()
    {
        return $this->get($this->vaultKey);
    }
    
    /**
     * Returns key prefix.
     *
     * @return string
     */
    public function getKeyPrefix() : string
    {
        return $this->keyPrefix;
    }
    
    /**
     * Sets new key prefix.
     *
     * @param string $prefix
     * @return void
     */
    public function setKeyPrefix(string $prefix)
    {
        $this->keyPrefix = $prefix;
    }
  
    /**
     * Returns the vault lifetime.
     *
     * @return int
     */
    public function getVaultLifeTime() : int
    {
        return $this->vaultLifeTime;
    }
  
    /**
     * Sets the vault lifetime.
     *
     * @param int $vaultLifeTime The new vault lifetime in seconds.
     * @return void
     */
    public function setVaultLifeTime(int $vaultLifeTime)
    {
        $this->vaultLifeTime = abs($vaultLifeTime);
    }
    
    /**
     * Returns cached data that associated with some tag.
     *
     * @param string $tags A tag associated with the data.
     * @return array
     */
    public function getByTag(string $tag) : array
    {
        return $this->getByTags([$tag]);
    }
  
    /**
     * Returns cached data that associated with some tags.
     *
     * @param string[] $tags An array of tags associated with the data.
     * @return array
     */
    public function getByTags(array $tags) : array
    {
        $res = [];
        $vault = $this->getVault();
        foreach ($tags as $tag)
        {
            if (isset($vault[$tag]) && is_array($vault[$tag]))
            {
                foreach ($vault[$tag] as $key => $expire)
                {
                    $data = $this->get(unserialize($key), $isExpired);
                    if (!$isExpired)
                    {
                        $res[$key] = $data;
                    }
                }
            }
        }
        return $res;
    }
    
    /**
     * Cleans cached data that associated with some tag.
     *
     * @param string $tag A tag associated with the data.
     * @return void
     */
    public function cleanByTag(string $tag)
    {
        $this->cleanByTags([$tag]);
    }
  
    /**
     * Cleans cached data that associated with some tags.
     *
     * @param string[] $tags An array of tags associated with the data.
     * @return void
     */
    public function cleanByTags(array $tags)
    {
        $changed = false;
        $vault = $this->getVault();
        foreach ($tags as $tag)
        {
            if (isset($vault[$tag]) && is_array($vault[$tag]))
            {
                foreach ($vault[$tag] as $key => $expire)
                {
                    $this->remove(unserialize($key));
                }
                unset($vault[$tag]);
                $changed = true;
            }
        }
        if ($changed)
        {
            $this->set($this->vaultKey, $vault, $this->vaultLifeTime);
        }
    }
  
    /**
     * Saves the key of caching data in the key vault.
     *
     * @param mixed $key A key to save.
     * @param int $expire The cache lifetime of data defined by the key.
     * @param string[] $tags An array of tags associated with the data.
     * @return void
     */
    protected function saveKeyToVault($key, int $expire, array $tags = [])
    {
        if ($tags)
        {
            $key = serialize($key);
            $vault = $this->getVault();
            foreach ($tags as $tag)
            {
                $vault[(string)$tag][$key] = $expire;
            }
            $this->set($this->vaultKey, $vault, $this->vaultLifeTime);
        }
    }
  
    /**
     * Normalizes expiration time value.
     *
     * @param int $expire The cache lifetime (in seconds). If it is zero the vault lifetime is used.
     * @return int
     */
    protected function normalizeExpire(int $expire)
    {
        return abs($expire) ?: $this->vaultLifeTime;
    }
    
    /**
     * Normalizes the data key.
     *
     * @param mixed $key The key to be normalized.
     * @return string
     */
    protected function normalizeKey($key) : string
    {
        return md5($this->keyPrefix . serialize($key));
    }
  
    /**
     * Removes keys of the expired data from the key vault.
     *
     * @return void
     */
    protected function normalizeVault()
    {
        $vault = $this->getVault();
        if (!is_array($vault))
        {
            return;
        }
        foreach ($vault as $tag => $keys)
        {
            foreach ($keys as $k => $expire)
            {
                $key = unserialize($k);
                if ($this->isExpired($key, $expire)) 
                {
                    $this->remove($key);
                    unset($vault[$tag][$k]);
                }
            }
            if (count($vault[$tag]) == 0)
            {
                unset($vault[$tag]);
            }
        }
        $this->set($this->vaultKey, $vault, $this->vaultLifeTime);
    }
}