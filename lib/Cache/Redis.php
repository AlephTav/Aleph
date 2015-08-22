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
 * The class is intended for caching of different data using the direct connection to the Redis server.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.1
 * @package aleph.cache
 */
class Redis extends Cache
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
     * @var resource $rp
     * @access private
     */
    private $rp;
  
    /**
     * Constructor.
     *
     * @param string $host - host or path to a unix domain socket for a redis connection.
     * @param integer $port - port for a connection, optional.
     * @param integer $timeout - the connection timeout, in seconds.
     * @param string $password - password for server authentication, optional.
     * @param integer $database - number of the redis database to use.
     * @access public
     */
    public function __construct($host = '127.0.0.1', $port = 6379, $timeout = null, $password = null, $database = 0)
    {
        parent::__construct();
        $this->rp = stream_socket_client($host . ':' . $port, $errno, $errstr, $timeout !== null ? $timeout : ini_get('default_socket_timeout'));
        if (!$this->rp)
        {
            throw new \RuntimeException(sprintf(static::ERR_CACHE_REDIS_1, $errno, $errstr));
        }
        if ($password !== null)
        {
            $this->execute('AUTH', [$password]);
        }
        $this->execute('SELECT', [$database]);
    }
  
    /**
     * Executes the given redis command.
     *
     * @param string $command - the command name.
     * @param array $params - the list of parameters for the command.
     * @return mixed
     * @access public
     */
    public function execute($command, array $params = [])
    {
        array_unshift($params, $command);
        $command = '*' . count($params) . "\r\n";
        foreach ($params as $param)
        {
            $command .= '$' . strlen($param) . "\r\n" . $param . "\r\n";
        }
        fwrite ($this->rp, $command);
        $parse = function() use (&$parse)
        {
            if (false === $line = fgets($this->rp))
            {
                throw new \RuntimeException(static::ERR_CACHE_REDIS_2);
            }
            $type = $line[0];
            $line = substr($line, 1, -2);
            switch ($type)
            {
                case '+':
                    return true;
                case '-':
                    throw new \RuntimeException(sprintf(static::ERR_CACHE_REDIS_3, $line));
                case ':':
                    return $line;
                case '$':
                    if ($line == '-1')
                    {
                        return null;
                    }
                    $length = $line + 2;
                    $data = '';
                    while ($length)
                    {
                        if (false === $block = fread($this->rp, $length))
                        {
                            throw new Core\Exception([$this, 'ERR_CACHE_REDIS_2']);
                        }
                        $data .= $block;
                        $length -= function_exists('mb_strlen') ? mb_strlen($block, '8bit') : strlen($block);
                    }
                    return substr($data, 0, -2);
                case '*':
                    $count = (int)$line;
                    $data = [];
                    for ($i = 0; $i < $count; $i++)
                    {
                        $data[] = $parse();
                    }
                    return $data;
                default:
                    throw new Core\Exception([$this, 'ERR_CACHE_REDIS_2']);
            }
        };
        return $parse();
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
        $res = $this->execute('GET', [self::META_PREFIX . $this->normalizeKey($key)]);
        return $res === null ? null : unserialize($res);
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
        $res = $this->execute('GET', [$this->normalizeKey($key)]);
        if ($res === null)
        {
            $isExpired = true;
            return;
        }
        $isExpired = false;
        return unserialize($res);
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
        $this->execute('SETEX', [$k, $expire, serialize($content)]);
        $this->execute('SETEX', [self::META_PREFIX . $k, $expire, serialize([$expire, $group])]);
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
        $meta = $this->execute('GET', [self::META_PREFIX . $key]);
        return $meta ? $this->execute('SETEX', [$key, $meta[0], serialize($content)]) : false;
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
        $meta = $this->execute('GET', [$mkey]);
        if (isset($meta[0]) && $this->execute('EXISTS', [$key]))
        {
            $expire = $this->normalizeExpire($expire);
            $meta[0] = $expire;
            $this->execute('EXPIRE', [$key, $expire]);
            $this->execute('SETEX', [$mkey, $meta, $expire]);
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
        $this->execute('DEL', [$key]);
        $this->execute('DEL', [self::META_PREFIX . $key]);
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
        return !$this->execute('EXISTS', [$this->normalizeKey($key)]);
    }

    /**
     * Removes all previously stored data from the cache.
     *
     * @access public
     */
    public function clean()
    {
        $this->execute('FLUSHDB');
    }
}