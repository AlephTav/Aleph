<?php
/**
 * Copyright (c) 2014 Aleph Tav
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
 * @copyright Copyright &copy; 2014 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Cache;

use Aleph\Core;

/**
 * The class is intended for caching of different data using PHP sessions.
 * You can use this type of cache for testing caching in your applications.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.cache
 */
class Session extends Cache
{
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
    $_SESSION['__CACHE__'][$key] = [serialize($content), $expire + time()];
    $this->saveKeyToVault($key, $expire, $group);
  }

  /**
   * Returns some data previously conserved in cache.
   *
   * @param string $key - a data key.
   * @return mixed
   * @access public
   */
  public function get($key)
  {                    
    if (isset($_SESSION['__CACHE__'][$key])) return unserialize($_SESSION['__CACHE__'][$key][0]);
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
    return empty($_SESSION['__CACHE__'][$key]) || $_SESSION['__CACHE__'][$key][1] <= time();
  }

  /**
   * Removes some data identified by a key from cache.
   *
   * @param string $key - a data key.
   * @access public
   */
  public function remove($key)
  {             
    unset($_SESSION['__CACHE__'][$key]);
  }
  
  /**
   * Removes all previously conserved data from cache.
   *
   * @access public
   */
  public function clean()
  {
    unset($_SESSION['__CACHE__']);
  }
   
  /**
   * Garbage collector that should be used for removing of expired cache data.
   *
   * @param float $probability - probability of garbage collector performing.
   * @access public
   */
  public function gc($probability = 100)
  {
    if ((float)$probability * 1000 < rand(0, 99999)) return;
    if (empty($_SESSION['__CACHE__']) || !is_array($_SESSION['__CACHE__'])) return;
    foreach ($_SESSION['__CACHE__'] as $key => $item) if ($item[1] < time()) $this->remove($key);
    $this->normalizeVault();
  }
}