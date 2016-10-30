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
 * The class intended for caching of different data using the APC extension.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.3.0
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
        return extension_loaded('apcu');
    }

    /**
     * Constructor.
     *
     * @throws \RuntimeException If APC extension is not enabled.
     */
    public function __construct()
    {
        if (!ini_get('apc.enabled')) {
            throw new \RuntimeException(static::ERR_CACHE_APC_1);
        }
        if (PHP_SAPI === 'cli' && !ini_get('apc.enable_cli')) {
            throw new \RuntimeException(static::ERR_CACHE_APC_2);
        }
        parent::__construct();
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
    public function add($key, $content, int $expire = 0, array $tags = []) : bool
    {
        $k = $this->normalizeKey($key);
        $expire = $this->normalizeExpire($expire);
        if (apcu_add($k, $content, $expire)) {
            if (apcu_store($this->getMetaPrefix() . $k, [$expire, $tags], $expire)) {
                $this->saveKeyToVault($key, $expire, $tags);
                return true;
            } else {
                apcu_delete($k);
            }
        }
        return false;
    }

    /**
     * Checks whether cache lifetime is expired or not.
     *
     * @param string $key The data key.
     * @return bool
     */
    public function isExpired($key) : bool
    {
        return !apcu_exists($this->normalizeKey($key));
    }

    /**
     * Removes all previously stored data from the cache.
     *
     * @return void
     */
    public function clean()
    {
        apcu_clear_cache();
    }

    /**
     * Retrieves data from the cache.
     *
     * @param string $key The normalized data key.
     * @param mixed $success Set to TRUE in success and FALSE in failure.
     * @return mixed
     */
    protected function fetch(string $key, &$success = null)
    {
        $content = apcu_fetch($key, $success);
        return $success ? $content : null;
    }

    /**
     * Stores data in the cache.
     *
     * @param string $key The normalized data key.
     * @param mixed $content The serializable data.
     * @param int $expire The normalized cache expiration time.
     * @return bool TRUE on success and FALSE on failure.
     */
    protected function store(string $key, $content, int $expire) : bool
    {
        return apcu_store($key, $content, $expire);
    }

    /**
     * Removes a stored data from the cache.
     *
     * @param string $key The normalized data key.
     * @return bool TRUE on success and FALSE on failure.
     */
    protected function delete(string $key) : bool
    {
        return apcu_delete($key);
    }
}