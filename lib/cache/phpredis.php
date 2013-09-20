<?php
/**
 * Copyright (c) 2012 Aleph Tav
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
 * @copyright Copyright &copy; 2012 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Cache;

/**
 * The class is intended for caching of different data using the PHP Redis extension.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.cache
 */
class PHPRedis extends Cache
{
  /**
   * The instance of \Redis class.
   *
   * @var \Redis $redis
   */
  private $redis;
  
  /**
   * Checks whether the current type of cache is available or not.
   *
   * @return boolean
   * @access public
   * @static
   */
  public static function isAvailable()
  {
    return extension_loaded('redis');
  }

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
  public function __construct($host = '127.0.0.1', $port = 6379, $timeout = 0, $password = null, $database = 0)
  {
    parent::__construct();
    $this->redis = new \Redis();
    $this->redis->connect($host, $port, $timeout);
    if ($password !== null) $this->redis->auth($password);
    $this->redis->select($database);
    $this->redis->setOption(\Redis::OPT_SERIALIZER, defined('Redis::SERIALIZER_IGBINARY') ? \Redis::SERIALIZER_IGBINARY : \Redis::SERIALIZER_PHP);
  }

  /**
   * Returns the redis object.
   *
   * @return \Redis
   * @access public
   */
  public function getRedis()
  {
    return $this->redis;
  }

  /**
   * Conserves some data identified by a key into cache.
   *
   * @param string $key - a data key.
   * @param mixed $content - some data.
   * @param integer $expire - cache lifetime (in seconds).
   * @param string $group - group of a data key.
   * @access public
   */
  public function set($key, $content, $expire, $group = '')
  {
    $expire = abs((int)$expire);
    $result = $this->redis->set($key, $content, $expire);
    $this->saveKey($key, $expire, $group);
  }

  /**
   * Returns some data previously conserved in cache.
   *
   * @param string $key - a data key.
   * @return boolean
   * @access public
   */
  public function get($key)
  {
    return $this->redis->get($key);
  }

  /**
   * Checks whether cache lifetime is expired or not.
   *
   * @param string $key - a data key.
   * @return boolean
   * @access public
   */
  public function isExpired($key)
  {
    $flag = !$this->redis->exists($key);
    if ($flag) $this->removeKey($key);
    return $flag;
  }

  /**
   * Removes some data identified by a key from cache.
   *
   * @param string $key - a data key.
   * @access public
   */
  public function remove($key)
  {
    $this->redis->delete($key);
    $this->removeKey($key);
  }

  /**
   * Removes all previously conserved data from cache.
   *
   * @access public
   */
  public function clean()
  {
    $this->redis->flushDB();
  }
  
  /**
   * Closes the current connection with redis.
   *
   * @access public
   */
  public function __destruct()
  {
    $this->redis->close();
  }
}