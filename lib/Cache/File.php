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
 * The class is intended for caching of different data using the file system.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.cache
 */
class File extends Cache
{  
  /**
   * Error message templates.
   */
  const ERR_CACHE_FILE_1 = 'Cache directory "%s" is not writable.';
  
  /**
   * Permissions for newly created cache directory.
   *
   * @var integer $directoryMode
   * @access public
   */
  public $directoryMode = 0777;
  
  /**
   * Permissions for newly created cache files.
   *
   * @var integer $fileMode
   * @access public
   */
  public $fileMode = 0666;

  /**
   * The directory in which cache files will be stored.
   *
   * @var string $dir
   * @access private   
   */
  private $dir = null;
   
  /**
   * Sets new directory for storing of cache files.
   * If this directory doesn't exist it will be created.
   *
   * @param string $path
   * @access public
   */
  public function setDirectory($path = null)
  {
    $dir = \Aleph::dir($path ?: 'cache');
    if (!is_dir($dir)) mkdir($dir, $this->directoryMode, true);
    if (!is_writable($dir) && !chmod($dir, $this->directoryMode)) throw new Core\Exception($this, 'ERR_CACHE_FILE_1', $dir);
    $this->dir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
  }
  
  /**
   * Returns the current cache directory.
   *
   * @return string
   * @access public
   */
  public function getDirectory()
  {
    return $this->dir;
  }
   
  /**
   * Conserves some data identified by a key into cache.
   *
   * @param string $key - a data key.
   * @param mixed $content - some data.
   * @param integer $expire - cache lifetime (in seconds). If it is not defined the vault lifetime is used.
   * @param string $group - group of a data key.
   * @access public
   */  
  public function set($key, $content, $expire = null, $group = null)
  {          
    if (!$this->dir) $this->setDirectory();
    $expire = $this->normalizeExpire($expire);
    $file = $this->dir . md5($key);
    file_put_contents($file, serialize($content), LOCK_EX);
    $enabled = \Aleph::isErrorHandlingEnabled();
    $level = \Aleph::errorHandling(false, E_ALL & ~E_WARNING);
    chmod($file, $this->fileMode);
    touch($file, $expire + time());
    \Aleph::errorHandling($enabled, $level);
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
    if (!$this->dir) $this->setDirectory();
    $file = $this->dir . md5($key);
    if (file_exists($file)) 
    {
      $enabled = \Aleph::isErrorHandlingEnabled();
      $level = \Aleph::errorHandling(false, E_ALL & ~E_WARNING);
      while ('' === $content = file_get_contents($file)) usleep(100);
      \Aleph::errorHandling($enabled, $level);
      return $content === false ? null : unserialize($content);
    }
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
    if (!$this->dir) $this->setDirectory();
    $file = $this->dir . md5($key);
    if (!file_exists($file)) return true;
    $enabled = \Aleph::isErrorHandlingEnabled();
    $level = \Aleph::errorHandling(false, E_ALL & ~E_WARNING);
    $flag = filemtime($file) <= time();
    \Aleph::errorHandling($enabled, $level);
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
    if (!$this->dir) $this->setDirectory();
    $file = $this->dir . md5($key);
    if (file_exists($file))
    {
      $enabled = \Aleph::isErrorHandlingEnabled();
      $level = \Aleph::errorHandling(false, E_ALL & ~E_WARNING);
      unlink($file);
      \Aleph::errorHandling($enabled, $level);
    }
  }
  
  /**
   * Removes all previously conserved data from cache.
   *
   * @access public
   */
  public function clean()
  {
    if (!$this->dir) $this->setDirectory();
    if (strtolower(substr(PHP_OS, 0, 3)) == 'win') exec('del /Q /F ' . escapeshellarg($this->dir));
    else exec('find ' . escapeshellarg($this->dir) . ' -maxdepth 1 -type f -delete');
  }
   
  /**
   * Garbage collector that should be used for removing of expired cache data.
   *
   * @param float $probability - probability of garbage collector performing.
   * @access public
   */
  public function gc($probability = 100)
  {
    if (!$this->dir) $this->setDirectory();
    if ((float)$probability * 1000 < rand(0, 99999)) return;
    if (strtolower(substr(PHP_OS, 0, 3)) == 'win') exec('forfiles /P ' . escapeshellarg(rtrim($this->dir, '/\\')) . ' /D -0 /C "cmd /c IF @ftime LEQ %TIME:~0,-3% del @file"');
    else exec('find ' . escapeshellarg($this->dir) . ' -type f -mmin +0 -delete');
    $this->normalizeVault();
  }
}