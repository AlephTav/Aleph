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
 * The class intended for caching of different data using the APC extension. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.cache
 */
class APC extends Cache
{
  /**
   * Error message templates.
   */
  const ERR_CACHE_APC_1 = 'APC cache extension is not enabled. Set option apc.enabled to 1 in php.ini';
  const ERR_CACHE_APC_2 = 'APC cache extension is not enabled cli. Set option apc.enable_cli to 1 in php.ini';
  
  /**
   * Checks whether the current type of cache is available or not.
   *
   * @return boolean
   * @access public
   * @static
   */
  public static function isAvailable()
  {
    return extension_loaded('apc');
  }

  /**
   * Constructor.
   * 
   * @access public
   */
  public function __construct()
  {
    parent::__construct();
    if (!ini_get('apc.enabled')) throw new Core\Exception($this, 'ERR_CACHE_APC_1');
    if (php_sapi_name() === 'cli' && !ini_get('apc.enable_cli')) throw new Core\Exception('ERR_CACHE_APC_2');
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
    apc_store($key, $content, $expire);
    $this->saveKey($key, $expire, $group);
  }
   
  /**
   * Returns some data perviously conserved in cache.
   *
   * @param string $key - a data key.
   * @return mixed
   * @access public
   */
  public function get($key)
  {
    return apc_fetch($key);
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
    $flag = (apc_exists($key) === false);
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
    apc_delete($key);  
  }
   
  /**
   * Removes all previously conserved data from cache.
   *
   * @access public
   */
  public function clean()
  {
    apc_clear_cache('user');
  }
}