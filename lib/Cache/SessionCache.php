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
 * @version 1.3.0
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
        if (!isset($_SESSION[$this->ns])) {
            $_SESSION[$namespace] = $_SESSION[$this->ns];
            unset($_SESSION[$this->ns]);
        }
        $this->ns = $namespace;
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
        if (isset($_SESSION[$this->ns][$key])) {
            $data = $_SESSION[$this->ns][$key];
            if (is_array($data)) {
                return $data[1] <= time();
            }
        }
        return true;
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
        if (isset($_SESSION[$this->ns]) && is_array($_SESSION[$this->ns])) {
            foreach ($_SESSION[$this->ns] as $key => $item) {
                if (strpos($key, $this->getMetaPrefix()) === false &&
                    (!is_array($item) || $item[1] <= time())) {
                    unset($_SESSION[$this->ns][$key]);
                    unset($_SESSION[$this->ns][$this->getMetaPrefix() . $key]);
                }
            }
        }
        parent::normalizeVault();
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
        if (isset($_SESSION[$this->ns][$key])) {
            $data = $_SESSION[$this->ns][$key];
            if (is_array($data)) {
                if  ($data[1] > time()) {
                    $success = true;
                    return unserialize($data[0]);
                }
            }
        }
        $success = false;
        return null;
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
        $_SESSION[$this->ns][$key] = [serialize($content), $expire + time()];
        return true;
    }

    /**
     * Sets a new expiration on an cached data.
     * Returns TRUE on success or FALSE on failure.
     *
     * @param string $key The normalized data key.
     * @param int $expire The new expiration time (in seconds).
     * @return bool
     */
    protected function expire(string $key, int $expire) : bool
    {
        if (isset($_SESSION[$this->ns][$key])) {
            $_SESSION[$this->ns][$key][1] = $expire + time();
            return true;
        }
        return false;
    }

    /**
     * Removes a stored data from the cache.
     *
     * @param string $key The normalized data key.
     * @return bool TRUE on success and FALSE on failure.
     */
    protected function delete(string $key) : bool
    {
        unset($_SESSION[$this->ns][$key]);
        return true;
    }
}