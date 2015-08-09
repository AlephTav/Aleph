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

namespace Aleph\Utils;

use Aleph\Core,
    Aleph\Utils\PHP;

/**
 * Allows to launch independent PHP processes in background.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.cache
 */
class Process
{
  /**
   * Error message templates.
   */
  const ERR_PROCESS_1 = 'Command should be a delegate or path to a PHP script.';
  const ERR_PROCESS_2 = 'Closure cannot be parallelized.';
  const ERR_PROCESS_3 = 'Process has been already started.';
  const ERR_PROCESS_4 = 'Path to the PHP executable file is not set or cannot be found.';
  
  /**
   * Path to the PHP executable file.
   *
   * @var string $php
   * @access public
   * @static
   */
  public static $php = null;
  
  /**
   * Expiration time of the process cache.
   *
   * @var integer $cacheExpire
   * @access public
   * @static
   */
  public static $cacheExpire = null;
  
  /**
   * Group name of the process cache.
   *
   * @var string $cacheGroup
   * @access public
   * @static
   */
  public static $cacheGroup = null;
  
  /**
   * Any data that where sent to the child process.
   *
   * @var mixed $data
   * @access public
   */
  public $data = null;
  
  /**
   * Path to a PHP script or a delegate object to execute.
   *
   * @var string|Aleph\Core\Delegate $cmd
   * @access protected
   */
  protected $cmd = null;
  
  /**
   * The process identifier.
   *
   * @var integer $pid
   * @access protected
   */
  protected $pid = null;
  
  /**
   * Prefix of the unique identifiers of the process cache.
   *
   * @var string $uid
   * @access protected
   */
  protected $uid = null;
  
  /**
   * The cache object.
   * Currently the default cache is used. 
   *
   * @var Aleph\Cache\Cache $cache
   * @access protected
   */
  protected $cache = null;
  
  /**
   * Mark of the child process.
   *
   * @var boolean $child
   * @access protected
   */
  protected $child = false;  

  /**
   * This method should always be invoked in child processes.
   * It launches the given delegate to execute and passes to it the process instance. In this case method returns execution result of the delegate.
   * If no delegate was sent the method just returns the process instance.
   *
   * @return mixed
   * @access public
   * @static
   */
  public static function operate()
  {
    if (!isset($_SERVER['argv'][1]) || $_SERVER['argv'][1] != md5($_SERVER['SCRIPT_FILENAME'])) return false;
    $cache = \Aleph::getCache();
    $uid = $_SERVER['argv'][2];
    \Aleph::setErrorHandler(function(\Exception $e, array $info) use($cache, $uid)
    {
      $cache->set($uid . 'out', $info, static::$cacheExpire, static::$cacheGroup);
      $cache->remove($uid . 'in');
      exit;
    });
    register_shutdown_function(function() use($cache, $uid)
    {
      $cache->remove($uid . 'in');
    });
    $process = new static(null);
    $data = $process->read();
    $process->data = $data['data'];
    if ($data['delegate']) return \Aleph::delegate($data['delegate'], $process);
    return $process;
  }
  
  /**
   * Constructor. Determines which type of process (child or parent) is created.
   *
   * @param string | Aleph\Core\Delegate $command - the path to a script or a delegate to execute.
   * @access public
   */
  public function __construct($command)
  {
    $this->cache = \Aleph::getCache();
    if ($command === null && isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == md5($_SERVER['SCRIPT_FILENAME']))
    {
      $this->child = true;
      $this->pid = getmypid();
      $this->uid = $_SERVER['argv'][2];
    }
    else
    {
      if ($command instanceof Core\Delegate)
      {
        if ($command->getType() == 'closure') throw new Core\Exception([$this, 'ERR_PROCESS_2']);
      }
      else if (!is_string($command) || strlen($command) == 0)
      {
        throw new Core\Exception([$this, 'ERR_PROCESS_1']);
      }
      $this->cmd = $command;
    }
    if (!static::$php)
    {
      static::$php = PHP\Tools::getPHPBinary();
      if (static::$php === false) throw new Core\Exception([$this, 'ERR_PROCESS_4']);
    }
  }

  /**
   * Checks whether the process is started or not.
   *
   * @return boolean
   * @access public
   */
  public function isStarted()
  {
    return $this->pid !== null;
  }
  
  /**
   * Returns TRUE if the child process is running and FALSE otherwise.
   *
   * @return boolean
   * @access public
   */
  public function isRunning()
  {
    if ($this->child) return true;
    if (!$this->isStarted()) return false;
    if (static::isWindows()) 
    {
      exec('tasklist /FI "IMAGENAME EQ php.exe" | find /N "' . $this->pid . '"', $output);
      return count($output) > 0;
    }
    exec('ps -p ' . $this->pid, $output);
    return count($output) > 1;
  }
  
  /**
   * Returns TRUE if the child process is terminated in case of an error.
   * For child process the method always returns FALSE.
   *
   * @return boolean
   * @access public
   */
  public function isError()
  {
    if ($this->child) return false;
    if (!$this->isStarted()) return false;
    $data = $this->cache->get($this->uid . 'out');
    return is_array($data) && isset($data['isFatalError']) && isset($data['traceAsString']) && isset($data['severity']);
  }
  
  /**
   * Returns the process ID if it is started and FALSE otherwise.
   *
   * @return integer|boolean
   * @access public
   */
  public function ID()
  {
    return $this->isStarted() ? $this->pid : false;
  }
  
  /**
   * Launches the child process.
   * Returns TRUE on success and FALSE on failure.
   *
   * @param mixed $data
   * @return boolean
   * @access public
   */
  public function start($data = null)
  {
    if ($this->isStarted()) throw new Core\Exception([$this, 'ERR_PROCESS_3']);
    if ($this->cmd instanceof Core\Delegate || !file_exists($this->cmd)) 
    {
      $data = ['data' => $data, 'delegate' => (string)$this->cmd];
      $mark = realpath($_SERVER['SCRIPT_FILENAME']);
    }
    else
    {
      $data = ['data' => $data, 'delegate' => false];
      $mark = realpath($this->cmd);
    }
    $this->uid = md5($mark . microtime(true) . mt_rand(0, 100000000));
    $this->clean();
    if ($data !== null) $this->write($data);
    $cmd = static::$php . ' ' . escapeshellarg($mark) . ' ' . md5($mark) . ' ' . $this->uid;
    $res = proc_open('start /b ' . $cmd, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    if (!is_resource($res))
    {
      $this->clean();
      return false;
    }
    $this->pid = proc_get_status($res)['pid'];
    if (static::isWindows())
    {
      $output = array_filter(explode(' ', trim(shell_exec('wmic process get parentprocessid,processid | find "' . $this->pid . '"'))));
      $this->pid = end($output);
    }
    proc_close($res);
    return true;
  }
  
  /**
   * Stops the process and cleans its cache.
   *
   * @access public
   */
  public function stop()
  {
    if ($this->child)
    {
      $this->clean();
      exit;
    }
    exec((static::isWindows() ? 'taskkill /F /PID ' : 'kill ') . $this->pid);
    $this->clean();
    $this->uid = $this->pid = null;
  }
  
  /**
   * Writes data to the output cache of the process.
   *
   * @param mixed $data
   * @access public
   */
  public function write($data)
  {
    $this->cache->set($this->uid . ($this->child ? 'out' : 'in'), $data, static::$cacheExpire, static::$cacheGroup);
  }
  
  /**
   * Reads input data of the process.
   * After the data is received, the input cache is cleaned.
   *
   * @return mixed
   */
  public function read()
  {
    $key = $this->uid . ($this->child ? 'in' : 'out');
    $data = $this->cache->get($key);
    $this->cache->remove($key);
    return $data;
  }
  
  /**
   * Cleans the process cache.
   *
   * @access public
   */
  public function clean()
  {
    $this->cache->remove($this->uid . 'out');
    $this->cache->remove($this->uid . 'in');
  }
  
  /**
   * Returns TRUE if the current script is run under MS Windows OS, and FALSE otherwise.
   *
   * @return boolean
   * @access protected
   * @static
   */
  protected static function isWindows()
  {
    return strtolower(substr(PHP_OS, 0, 3)) == 'win';
  }
}