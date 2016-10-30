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
 * The class is intended for caching of different data using the direct connection to the Redis server.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.3.0
 * @package aleph.cache
 */
class RedisCache extends Cache
{
    /**
     * Error message templates.
     */
    const ERR_CACHE_REDIS_1 = 'Unable to connect to the Redis server. ERROR: %s - %s.';
    const ERR_CACHE_REDIS_2 = 'Failed reading data from Redis connection socket.';
    const ERR_CACHE_REDIS_3 = 'Redis error: %s.';

    /**
     * Redis socket connection.
     *
     * @var resource
     */
    private $rp = null;

    /**
     * Constructor.
     *
     * @param string $host Host or path to a unix domain socket for a redis connection.
     * @param int $port Port for a connection, optional.
     * @param int|null $timeout The connection timeout, in seconds.
     * @param string|null $password Password for server authentication, optional.
     * @param int $database Number of the redis database to use.
     */
    public function __construct(string $host = '127.0.0.1', int $port = 6379, int $timeout = null, string $password = null, int $database = 0)
    {
        parent::__construct();
        $this->rp = stream_socket_client($host . ':' . $port, $errno, $errstr, $timeout !== null ? $timeout : ini_get('default_socket_timeout'));
        if (!$this->rp) {
            throw new \RuntimeException(sprintf(static::ERR_CACHE_REDIS_1, $errno, $errstr));
        }
        if ($password !== null) {
            $this->execute('AUTH', [$password]);
        }
        $this->execute('SELECT', [$database]);
    }

    /**
     * Executes the given redis command.
     *
     * @param string $command The command name.
     * @param array $params The list of parameters for the command.
     * @return mixed
     * @throws \RuntimeException
     */
    public function execute(string $command, array $params = [])
    {
        array_unshift($params, $command);
        $command = '*' . count($params) . "\r\n";
        foreach ($params as $param) {
            $command .= '$' . strlen($param) . "\r\n" . $param . "\r\n";
        }
        fwrite($this->rp, $command);
        $parse = function () use (&$parse) {
            if (false === $line = fgets($this->rp)) {
                throw new \RuntimeException(static::ERR_CACHE_REDIS_2);
            }
            $type = $line[0];
            $line = substr($line, 1, -2);
            switch ($type) {
                case '+':
                    return true;
                case '-':
                    throw new \RuntimeException(sprintf(static::ERR_CACHE_REDIS_3, $line));
                case ':':
                    return $line;
                case '$':
                    if ($line == '-1') {
                        return null;
                    }
                    $length = $line + 2;
                    $data = '';
                    while ($length) {
                        if (false === $block = fread($this->rp, $length)) {
                            throw new \RuntimeException(static::ERR_CACHE_REDIS_2);
                        }
                        $data .= $block;
                        $length -= function_exists('mb_strlen') ? mb_strlen($block, '8bit') : strlen($block);
                    }
                    return substr($data, 0, -2);
                case '*':
                    $count = (int)$line;
                    $data = [];
                    for ($i = 0; $i < $count; $i++) {
                        $data[] = $parse();
                    }
                    return $data;
                default:
                    throw new \RuntimeException(static::ERR_CACHE_REDIS_2);
            }
        };
        return $parse();
    }

    /**
     * Checks whether cache lifetime is expired or not.
     *
     * @param string $key The data key.
     * @return bool
     */
    public function isExpired($key) : bool
    {
        return !$this->execute('EXISTS', [$this->normalizeKey($key)]);
    }

    /**
     * Removes all previously stored data from the cache.
     *
     * @return void
     */
    public function clean()
    {
        $this->execute('FLUSHDB');
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
        $value = $this->execute('GET', [$key]);
        if ($value !== null) {
            $success = true;
            return unserialize($value);
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
        return $this->execute('SETEX', [$key, $expire, serialize($content)]);
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
        return $this->execute('EXPIRE', [$key, $expire]);
    }

    /**
     * Removes a stored data from the cache.
     *
     * @param string $key The normalized data key.
     * @return bool TRUE on success and FALSE on failure.
     */
    protected function delete(string $key) : bool
    {
        return $this->execute('DEL', [$key]);
    }
}