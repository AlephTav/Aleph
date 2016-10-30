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

use Redis;

/**
 * The class is intended for caching of different data using the PHP Redis extension.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.1
 * @package aleph.cache
 */
class PHPRedisCache extends Cache
{
    /**
     * The instance of \Redis class.
     *
     * @var \Redis $redis
     */
    private $redis = null;

    /**
     * Checks whether the current type of cache is available or not.
     *
     * @return bool
     */
    public static function isAvailable() : bool
    {
        return extension_loaded('redis');
    }

    /**
     * Constructor.
     *
     * @param string $host Host or path to a unix domain socket for a redis connection.
     * @param int $port Port for a connection, optional.
     * @param int $timeout The connection timeout, in seconds.
     * @param string|null $password Password for server authentication, optional.
     * @param int $database Number of the redis database to use.
     */
    public function __construct(string $host = '127.0.0.1', int $port = 6379,
                                int $timeout = 0,string $password = null, int $database = 0)
    {
        parent::__construct();
        $this->redis = new Redis();
        $this->redis->connect($host, $port, $timeout);
        if ($password !== null) {
            $this->redis->auth($password);
        }
        $this->redis->select($database);
        $this->redis->setOption(Redis::OPT_SERIALIZER,
            defined('Redis::SERIALIZER_IGBINARY') ? Redis::SERIALIZER_IGBINARY : Redis::SERIALIZER_PHP);
    }

    /**
     * Returns the redis object.
     *
     * @return \Redis
     */
    public function getNativeObject() : Redis
    {
        return $this->redis;
    }

    /**
     * Checks whether cache lifetime is expired or not.
     *
     * @param string $key The data key.
     * @return bool
     */
    public function isExpired($key) : bool
    {
        return !$this->redis->exists($this->normalizeKey($key));
    }

    /**
     * Removes all previously stored data from the cache.
     *
     * @return void
     */
    public function clean()
    {
        $this->redis->flushDB();
    }

    /**
     * Closes the current connection with Redis.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->redis->close();
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
        $success = $this->redis->exists($key);
        return $success ? $this->redis->get($key) : null;
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
        return $this->redis->set($key, $content, $expire);
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->redis->setTimeout($key, $expire);
    }

    /**
     * Removes a stored data from the cache.
     *
     * @param string $key The normalized data key.
     * @return bool TRUE on success and FALSE on failure.
     */
    protected function delete(string $key) : bool
    {
        return $this->redis->delete($key) == 1;
    }
}