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

use Aleph\Core;

/**
 * The class is intended for caching of different data using the direct connection to the Redis server.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.cache
 */
class Redis extends Cache
{
  /**
   * Error message templates.
   */
  const ERR_CACHE_REDIS_1 = 'Unable to connect to the Redis server. ERROR: [{var}] - [{var}].';
  const ERR_CACHE_REDIS_2 = 'Failed reading data from Redis connection socket.';
  const ERR_CACHE_REDIS_3 = 'Redis error: [{var}].';
  
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
    $this->rp = stream_socket_client($host . ':' . $port, $errno, $errstr, $timeout !== null ? $timeout :  ini_get('default_socket_timeout'));
    if (!$this->rp) throw new Core\Exception($this, 'ERR_CACHE_REDIS_1', $errno, $errstr);
    if ($password !== null) $this->execute('AUTH', [$password]);
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
    foreach ($params as $param) $command .= '$' . strlen($param) . "\r\n" . $param . "\r\n";
    fwrite ($this->rp, $command);
    $parse = function() use (&$parse)
    {
      if (($line = fgets($this->rp)) === false) throw new Core\Exception($this, 'ERR_CACHE_REDIS_2');
      $type = $line[0];
      $line = substr($line, 1, -2);
      switch ($type)
      {
        case '+':
          return true;
        case '-':
          throw new Core\Exception($this, 'ERR_CACHE_REDIS_3', $line);
        case ':':
          return $line;
        case '$':
          if ($line == '-1') return null;
          $length = $line + 2;
          $data = '';
          while ($length)
          {
            if (($block = fread($this->rp, $length)) === false) throw new Core\Exception($this, 'ERR_CACHE_REDIS_2');
            $data .= $block;
            $length -= function_exists('mb_strlen') ? mb_strlen($block, '8bit') : strlen($block);
          }
          return substr($data, 0, -2);
        case '*':
          $count = (int)$line;
          $data = [];
          for ($i = 0; $i < $count; $i++) $data[] = $parse();
          return $data;
        default:
          throw new Core\Exception($this, 'ERR_CACHE_REDIS_2');
      }
    };
    return $parse();
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
  public function set($key, $content, $expire, $group = null)
  {
    $expire = abs((int)$expire);
    if ($expire == 0) $this->execute('SET', [$key, serialize($content)]);
    else $this->execute('SETEX', [$key, $expire, serialize($content)]);
    $this->saveKeyToVault($key, $expire, $group);
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
    $res = $this->execute('GET', [$key]);
    return $res === null ? null : unserialize($res);
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
    return !$this->execute('EXISTS', [$key]);
  }

  /**
   * Removes some data identified by a key from cache.
   *
   * @param string $key - a data key.
   * @access public
   */
  public function remove($key)
  {
    $this->execute('DEL', [$key]);
  }

  /**
   * Removes all previously conserved data from cache.
   *
   * @access public
   */
  public function clean()
  {
    $this->execute('FLUSHDB');
  }
}