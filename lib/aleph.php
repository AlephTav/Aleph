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

use Aleph\Core,
    Aleph\Cache,
    Aleph\Net;

/**
 * General class of the framework.
 * With this class you can log error messages, profile your code, catch any errors, load classes, configure your application and store any global objects. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.core
 * @final
 */
final class Aleph implements \ArrayAccess
{
  /**
   * Bug and debug templates.
   */
  const TEMPLATE_DEBUG = '<!doctype html><html><head><meta content="text/html; charset=UTF-8" http-equiv="Content-Type" /><title>Bug Report</title><body bgcolor="gold">The following error <pre>$message</pre> has been catched in file <b>$file</b> on line $line<br /><br /><b style="font-size: 14px;">Stack Trace:</b><pre>$traceAsString</pre><b>Execution Time:</b><pre>$executionTime sec</pre><b>Memory Usage:</b><pre>$memoryUsage Mb</pre></pre></body></html>';
  const TEMPLATE_BUG = 'Sorry, server is not available at the moment. Please wait. This site will be working very soon!';
  
  /**
   * Error message templates throwing by Aleph class.
   */
  const ERR_GENERAL_1 = 'Class "[{var}]" is not found.';
  const ERR_GENERAL_2 = 'Method "[{var}]" of class "[{var}]" doesn\'t exist.';
  const ERR_GENERAL_3 = 'Property "[{var}]" of class "[{var}]" doesn\'t exist.';
  const ERR_GENERAL_4 = 'Autoload callback can only be Aleph callback (string value), Closure object or Aleph\Core\IDelegate instance.';
  const ERR_GENERAL_5 = 'Class "[{var}]" found in file "[{var}]" is duplicated in file "[{var}]".';
  const ERR_GENERAL_6 = 'Class "[{var}]" is not found in "[{var}]". You should include this class manually in connect.php';
  const ERR_CONFIG_1 = 'File "[{var}]" is not correct ini file.';

  /**
   * The instance of this class.
   *
   * @var private $instance
   * @access private
   * @static
   */
  private static $instance = null;
  
  /**
   * Unique ID of the application (site).
   *
   * @var private $siteUniqueID
   * @access private
   * @static
   */
  private static $siteUniqueID = null;
  
  /**
   * Path to site root directory.
   *
   * @var string $root
   * @access private
   * @static
   */
  private static $root = null;
  
  /**
   * Array of timestamps.
   *
   * @var array @time
   * @access private
   * @static
   */
  private static $time = array();
  
  /**
   * Response body.
   *
   * @var string $output
   * @access private
   * @static
   */
  private static $output = null;
  
  /**
   * Array with information about some code that was executed by the operator eval.
   *
   * @var array $eval
   * @access private
   * @static
   */
  private static $eval = array();
  
  /**
   * Array of different global objects.
   *
   * @var array $registry
   * @access private
   * @static
   */
  private static $registry = array();
  
  /**
   * Marker of error handling.
   *
   * @var boolean $debug
   * @access private
   * @static
   */
  private static $debug = false;
  
  /**
   * Instance of the class Aleph\Cache\Cache (or its child).
   *
   * @var Aleph\Cache\Cache $cache
   * @access private
   */
  private $cache = null;
  
  /**
   * Instance of the class Aleph\Net\Router.
   * 
   * @var Aleph\Net\Router $router
   * @access private
   */
  private $router = null;
  
  /**
   * Array of configuration variables.
   *
   * @var array $config
   * @access private  
   */
  private $config = array();
  
  /**
   * Array of paths to all classes of the applcation and framework.
   *
   * @var array $classes
   * @access private
   */
  private $classes = array();
  
  /**
   * Array of paths to classes to exclude them from the class searching.
   *
   * @var array $exclusions
   * @access private  
   */
  private $exclusions = array();
  
  /**
   * Direcotires for class searching.
   *
   * @var array $dirs
   * @access private
   */
  private $dirs = array();
  
  /**
   * File search mask.
   *
   * @var string $mask
   * @access private
   */
  private $mask = null;
  
  /**
   * Cache key for storing of paths to including classes.
   *
   * @var string $key
   * @access private
   */
  private $key = null;
  
  /**
   * Autoload callback. Can be a closure, an instance of Aleph\Core\IDelegate or a string in Aleph callback format.
   *
   * @var string | closure | Aleph\Core\IDelegate
   * @access private
   */
  private $alCallBack = null;
  
  /**
   * Returns an instance of this class.
   *
   * @return self
   * @access public
   * @static
   */
  public static function getInstance()
  {
    return self::$instance;
  }
  
  /** 
   * Returns array of all previously stored global objects.
   *
   * @return array
   * @static
   */
  public static function all()
  {
    return self::$registry;
  }
  
  /**
   * Returns a global object by its key.
   *
   * @param string $key - key of a global object.
   * @return mixed
   * @access public
   * @static
   */
  public static function get($key)
  {
    return isset(self::$registry[$key]) ? self::$registry[$key] : null;
  }
  
  /**
   * Stores a global object.
   *
   * @param string $key - key of a global object.
   * @param mixed $value - value of a global object.
   * @access public
   */
  public static function set($key, $value)
  {
    self::$registry[$key] = $value;
  }
  
  /**
   * Checks whether an global object exist or not.
   *
   * @param string $key - key of a global object.
   */
  public static function has($key)
  {
    return isset(self::$registry[$key]);
  }
  
  /**
   * Removes a global object from the storage.
   *
   * @param string $key - key of a global object.
   * @access public
   * @static
   */
  public static function remove($key)
  {
    unset(self::$registry[$key]);
  }
  
  /**
   * Sets value of the response body.
   *
   * @param string $output - new response body
   * @access public
   * @static
   */
  public static function setOutput($output)
  {
    self::$output = $output;
  }
  
  /**
   * Returns value of the response body.
   *
   * @return string
   * @access public
   * @static
   */
  public static function getOutput()
  {
    return self::$output;
  }
  
  /**
   * Returns site root directory.
   *
   * @return string
   * @access public
   * @static
   */
  public static function getRoot()
  {
    return self::$root;
  }
  
  /**
   * Sets start time point for some code part.
   *
   * @param string $key - time mark for some code part.
   * @access public
   * @static
   */
  public static function pStart($key)
  {
    self::$time[$key] = microtime(true);
  }
  
  /**
   * Returns execution time of some code part by its time mark.
   * If a such time mark doesn't exit then the method return false.
   *
   * @param string $key - time mark of some code part.
   * @return boolean | float
   * @static
   */
  public static function pStop($key)
  {
    if (!isset(self::$time[$key])) return false;
    return number_format(microtime(true) - self::$time[$key], 6);
  }

  /**
   * Returns the amount of memory, in bytes, that's currently being allocated to your PHP script.
   *
   * @return integer
   * @access public
   * @static
   */
  public static function getMemoryUsage()
  {
    return memory_get_usage(true);
  }
  
  /**
   * Returns the peak of memory, in bytes, that's been allocated to your PHP script.
   *
   * @return integer
   * @access public
   * @static
   */
  public static function getPeakMemoryUsage()
  {
    return memory_get_peak_usage(true);
  }
  
  /**
   * Returns the execution time (in seconds) of your PHP script. 
   *
   * @return integer
   * @access public
   * @static
   */
  public static function getExecutionTime()
  {
    return self::pStop('script_execution_time');
  }
  
  /**
   * Returns the request time (in seconds) of your PHP script. 
   *
   * @return integer
   * @access public
   * @static
   */
  public static function getRequestTime()
  {
    return number_format(microtime(true) - $_SERVER['REQUEST_TIME'], 6);
  }
  
  /**
   * Returns the unique ID of your application (site).
   *
   * @return string
   * @access public
   * @static
   */
  public static function getSiteUniqueID()
  {
    return self::$siteUniqueID;
  }
  
  /**
   * Creates and executes a delegate.
   *
   * @param string $callback - the Aleph callback string or closure.
   * @params arguments of the callback.
   * @return mixed
   * @access public
   * @static
   */
  public static function delegate(/* $callback, $arg1, $arg2, ... */)
  {
    $params = func_get_args();
    $method = array_shift($params);
    return foo(new Core\Delegate($method))->call($params);
  }
  
  /**
   * Returns an error message by its token.
   *
   * @param string $class - class with the needed error message constant.
   * @param string $token - name of the needed error message constant.
   * @params values of parameters of the error message.
   * @return string
   * @access public
   * @static
   */
  public static function error(/* $class, $token, $var1, $var2, ... */)
  {
    $params = func_get_args();
    $class = array_shift($params);
    $class = is_object($class) ? get_class($class) : $class;
    $token = array_shift($params);
    $err = $token;
    if ($class != '')
    {
      $err = constant($class . '::' . $token);
      $token = $class . '::' . $token;
    }
    foreach ($params as $value)
    { 
      $err = preg_replace('/\[{var}\]/', $value, $err, 1);
    }
    return $class ? $err . ' (Token: ' . $token . ')' : $err;
  }
  
  /**
   * Checks whether the error handing is set or not.
   *
   * @return boolean
   * @access public
   * @static
   */
  public static function isDebug()
  {
    return self::$debug;
  }
  
  /**
   * Enables and disables the debug mode.
   *
   * @param boolean $enable - if it equals TRUE then the debug mode is enabled and it is disabled otherwise.
   * @param integer $errorLevel - new error reporting level.
   * @access public
   * @static
   */
  public static function debug($enable = true, $errorLevel = null)
  {
    self::$debug = $enable;
    restore_error_handler();
    restore_exception_handler();
    if (!$enable)
    {
      error_reporting($errorLevel ?: ini_get('error_reporting'));
      return;
    }
    error_reporting($errorLevel ?: E_ALL);
    set_exception_handler(array(__CLASS__, 'exception'));
    set_error_handler(function($errno, $errstr, $errfile, $errline)
    {
      self::exception(new \ErrorException($errstr, 0, $errno, $errfile, $errline));
    }, $errorLevel);
  }
  
  /**
   * This method is automatically invoked when a fatal error occurred.
   *
   * @access public
   * @static
   */
  public static function fatal()
  {
    if (self::isDebug() && preg_match('/(Fatal|Parse) error:(.*) in (.*) on line (\d+)/', ob_get_contents(), $res)) 
    {
      self::exception(new \ErrorException($res[2], 999, 1, $res[3], $res[4]));
    }
  }
  
  /**
   * Set the debug output for an exception.
   *
   * @param \Exception $e
   * @access public
   * @static
   */
  public static function exception(\Exception $e)
  {
    restore_error_handler();
    restore_exception_handler();
    $info = self::analyzeException($e);
    $config = (self::$instance !== null) ? self::$instance->config : array();
    $isDebug = isset($config['debugging']) ? (bool)$config['debugging'] : true;
    foreach (array('templateDebug', 'templateBug') as $var) $$var = isset($config[$var]) ? self::dir($config[$var]) : null;
    try
    {
      if (!empty($config['logging']))
      {
        if (!empty($config['customLogMethod']) && self::$instance instanceof Aleph && self::$instance->load('Aleph\Core\Delegate'))
        {
          self::delegate($config['customLogMethod'], $info);
        }
        else
        {
          self::log($info);
        }
      }
    }
    catch (\Exception $e){}
    if ($isDebug && !empty($config['customDebugMethod']) && self::$instance instanceof Aleph && self::$instance->load('Aleph\Core\Delegate'))
    {
      if (!self::delegate($config['customDebugMethod'], $e, $info)) return;
    }
    if (PHP_SAPI == 'cli' || empty($_SERVER['REMOTE_ADDR']))
    {
      if ($isDebug)
      {
        $output = PHP_EOL . PHP_EOL . 'BUG REPORT' . PHP_EOL . PHP_EOL;
        $output .= 'The following error [[ ' . $info['message'] . ' ]] has been catched in file ' . $info['file'] . ' on line ' . $info['line'] . PHP_EOL . PHP_EOL;
        $output .= 'Stack Trace:' . PHP_EOL . $info['traceAsString'] . PHP_EOL . PHP_EOL;
        $output .= 'Execution Time: ' . $info['executionTime'] . ' sec' . PHP_EOL . 'Memory Usage: ' . $info['memoryUsage'] . ' Mb' . PHP_EOL . PHP_EOL;
        self::$output = $output;
      }
      else
      {
        self::$output = self::TEMPLATE_BUG . PHP_EOL;
      }
      return;
    }
    if ($isDebug)
    {
      $render = function($tpl, $info)
      {
        ${'(_._)'} = $tpl; unset($tpl);
        if (is_file(${'(_._)'})) 
        {
          extract($info);
          return require(${'(_._)'});
        }
        $info['traceAsString'] = htmlspecialchars($info['traceAsString']);
        extract($info);
        eval('$res = "' . str_replace('"', '\"', ${'(_._)'}) . '";');
        return $res;
      };
      if (!is_file($templateDebug) || !is_readable($templateDebug)) $templateDebug = self::TEMPLATE_DEBUG;
      $templateDebug = $render($templateDebug, $info);
      if (isset($_SESSION))
      {
        $hash = md5(microtime() . uniqid('', true));
        $_SESSION['__DEBUG_INFORMATION__'][$hash] = $templateDebug;
        $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
        $url .= ((strpos($url, '?') !== false) ? '&' : '?') . '__DEBUG_INFORMATION__=' . $hash;
        self::go($url, true, false);
      }
      else 
      {
        self::$output = $templateDebug;
      }
    }
    else
    {
      self::$output = (is_file($templateBug) && is_readable($templateBug)) ? file_get_contents($templateBug) : self::TEMPLATE_BUG;
    }
  }
  
  /**
   * Analyzes an exception.
   *
   * @param \Exception $e
   * @return array - exception information.
   * @access public
   * @static  
   */
  public static function analyzeException(\Exception $e)
  {
    $reduceObject = function($obj) use(&$reduceObject)
    {
      if ($obj === null) return 'null';
      if (is_bool($obj)) return $obj ? 'true' : 'false';
      if (is_object($obj)) return '${\'' . get_class($obj) . '\'}';
      if (is_resource($obj)) return '${\'' . $obj . '\'}';
      if (is_array($obj))
      {
        if (count($obj) == 0) return '[]';
        $tmp = array(); 
        foreach ($obj as $k => $v) 
        {
          $k = (string)$k;
          if ($k == '__DEBUG_INFORMATION__') continue;
          if ($k == 'GLOBALS') $tmp[] = 'GLOBALS => *RECURSION*';
          else $tmp[] = $k . ' => ' . $reduceObject($v);        
        }
        return '[ ' . implode(', ', $tmp) . ' ]';
      }
      if (is_string($obj)) 
      {
        if (strlen($obj) > 1024) $obj = substr($obj, 0, 512) . ' ... [fragment missing] ... ' . substr($obj, -512);
        return '"' . addcslashes($obj, '"') . '"';
      }
      return $obj;
    };
    $reducePath = function($file)
    {
      if (strpos($file, \Aleph::getRoot()) === 0) $file = substr($file, strlen(\Aleph::getRoot()) + 1);
      return str_replace((DIRECTORY_SEPARATOR == '\\') ? '/' : '\\', DIRECTORY_SEPARATOR, $file);
    };
    $request = function()
    {
      if (function_exists('apache_request_headers')) return apache_request_headers();
      $headers = array();
      foreach ($_SERVER as $key => $value) 
      {
        if (strpos($key, 'HTTP_') === 0) 
        {
          $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
        }
      }
      return $headers;
    };
    $response = function()
    {
      if (function_exists('apache_response_headers')) return apache_response_headers();
      $headers = array();
      foreach (headers_list() as $header) 
      {
        $header = explode(':', $header);
        $headers[array_shift($header)] = trim(implode(':', $header));
      }
      return $headers;
    };
    $fragment = function($file, $line, &$index, &$command = null, $half = 10)
    {
      $lines = explode("\n", str_replace("\r\n", "\n", (is_file($file) && is_readable($file)) ? file_get_contents($file) : $file));
      $count = count($lines); $line--;
      if ($line + $half > $count)
      {
        $min = max(0, $line - $half);
        $max = $count;
      } 
      else
      {
        $min = max(0, $line - $half);
        $max = min($line + $half, $count);
      }
      $lines = array_splice($lines, $min, $max - $min + 1);
      $index = $line - $min;
      $command = empty($lines[$index]) ? '' : $lines[$index];
      return implode("\n", $lines);
    };
    $findFunc = function($func, $line, $code)
    {
      $line--;
      foreach (array_reverse($code) as $part)
      {
        $row = explode("\n", $part);
        if (empty($row[$line])) continue;
        $row = $row[$line];
        $tokens = token_get_all('<?php ' . $row . '?>');
        $k = 0; $n = count($tokens);
        while ($k < $n) 
        {
          $token = $tokens[$k++];
          if (is_array($token) && $token[0] == T_STRING && $token[1] == $func) return $part;
        }
      }
      return end($code);
    };
    $flag = false; $trace = $e->getTrace();
    $info = array();
    $info['isFatalError'] = $e->getCode() == 999;
    $message = $e->getMessage();
    $file = $e->getFile();
    $line = $e->getLine();
    if (self::$eval && (strpos($file, 'eval()\'d') !== false || strpos($message, 'eval()\'d') !== false))
    {
      if (preg_match('/, called in ([^ ]+) on line (\d+)/', $message, $matches))
      {
        $line = $matches[2];
        $message = substr($message, 0, strpos($message, ', called in'));
      }
      else if (preg_match('/, called in ([^\(]+)\((\d+)\) : eval\(\)\'d code on line (\d+)/', $message, $matches))
      {
        $line = $matches[3];
        $message = substr($message, 0, strpos($message, ', called in'));
      }
      $file = 'eval()\'s code';
    }
    else if (preg_match('/, called in ([^ ]+) on line (\d+)/', $message, $matches))
    {
      $file = $matches[1];
      $line = $matches[2];
      $message = substr($message, 0, strpos($message, ', called in'));
    }
    $push = true; $reducedFile = $reducePath($file);
    foreach ($trace as $k => &$item)
    {
      $item['command'] = isset($item['class']) ? $item['class'] . $item['type'] : '';
      $item['command'] .= $item['function'] . '( ';
      if (isset($item['args']))
      {
        $tmp = array();
        foreach ($item['args'] as $arg) $tmp[] = $reduceObject($arg);
        $item['command'] .= implode(', ', $tmp);
      }
      $item['command'] .= ' )';
      if (isset($item['file']))
      {
        if (self::$eval && strpos($item['file'], 'eval()\'d') !== false)
        {
          $item['file'] = 'eval()\'s code';
          if ($item['function'] == '{closure}' && isset($item['args'][0]) && $item['args'][0] == 4096)
          {
            $item['code'] = $fragment($findFunc($trace[$k + 1]['function'], $item['line'], self::$eval), $item['line'], $index);
          }
          else
          {
            $item['code'] = $fragment($findFunc($item['function'], $item['line'], self::$eval), $item['line'], $index);
          }
        }
        else
        {
          $item['code'] = $fragment($item['file'], $item['line'], $index);
          $item['file'] = $reducePath($item['file']);
        }
      }
      else
      {
        $index = 0; $item['code'] = '';
        if ($file != 'eval()\'s code') 
        {
          if (is_file($file)) $item['code'] = $fragment($file, $line, $index);
        }
        else if (self::$eval) $item['code'] = $fragment(array_pop(self::$eval), $line, $index);
        $item['file'] = '[Internal PHP]';
      }
      $item['index'] = $index;
      if ($item['file'] == $reducedFile && $item['line'] == $line) $push = false;
    }
    if ($push && !$info['isFatalError'])
    {
      $code = $fragment($file, $line, $index, $command);
      array_unshift($trace, array('file' => $reducedFile, 'line' => $line, 'command' => $command, 'code' => $code, 'index' => $index));
    }
    $info['host'] = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : false;
    $info['root'] = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : false; 
    $info['memoryUsage'] = number_format(self::getMemoryUsage() / 1048576, 4);
    $info['executionTime'] = self::getExecutionTime();
    $info['message'] = ltrim($message);
    $info['file'] = $reducedFile;
    $info['line'] = $line;
    $info['trace'] = $trace;
    if (method_exists($e, 'getClass')) $info['class'] = $e->getClass();
    if (method_exists($e, 'getToken')) $info['token'] = $e->getToken();
    $info['severity'] = method_exists($e, 'getSeverity') ? $e->getSeverity() : '';
    $info['traceAsString'] = $e->getTraceAsString();
    $info['request'] = $request();
    $info['response'] = $response();
    $info['GET'] = isset($_GET) ? $_GET : array();
    $info['POST'] = isset($_POST) ? $_POST : array();
    $info['COOKIE'] = isset($_COOKIE) ? $_COOKIE : array();
    $info['FILES'] = isset($_FILES) ? $_FILES : array();
    $info['SERVER'] = isset($_SERVER) ? $_SERVER : array();
    $info['SESSION'] = isset($_SESSION) ? $_SESSION : array();
    unset($info['SESSION']['__DEBUG_INFORMATION__']);
    return $info;
  }
  
  /**
   * Collects and stores information about some eval's code. 
   *
   * @param string $code - the code that will be executed by eval operator.
   * @return string
   * @access public
   * @static
   */
  public static function ecode($code)
  {
    self::$eval[md5($code)] = $code;
    return $code;
  }
  
  /**
   * Executes PHP code that inserted into HTML.
   *
   * @param string $code - the PHP inline code.
   * @param array $vars - variables to extract to the PHP code.
   * @return string
   * @access public
   * @static
   */
  public static function exe($code, array $vars = null)
  {
    ${'(_._)'} = ' ?>' . $code . '<?php '; unset($code);
    if ($vars) extract($vars);
    ob_start();
    eval(\Aleph::ecode(${'(_._)'}));
    $res = ob_get_clean();
    if (strpos($res, 'eval()\'d') !== false) exit($res);  
    return $res;
  }
  
  /**
   * Returns the full path to a directory specified by its alias.
   * 
   * @param string $dir - directory alias.
   * @return string
   * @access public
   * @static
   */
  public static function dir($dir)
  {
    if (self::$instance !== null)
    {
      $a = self::$instance;
      $dir = isset($a['dirs'][$dir]) ? $a['dirs'][$dir] : $dir;
      if (substr($dir, 0, strlen(self::$root)) != self::$root) $dir = self::$root . DIRECTORY_SEPARATOR . $dir;
    }
    return str_replace((DIRECTORY_SEPARATOR == '\\') ? '/' : '\\', DIRECTORY_SEPARATOR, $dir);
  }
  
  /**
   * Returns a directory url relative to the site root.
   *
   * @param string $url - directory alias.
   * @return string
   */
  public static function url($url)
  {
    if (self::$instance !== null) $url = isset(self::$instance['dirs'][$url]) ? self::$instance['dirs'][$url] : $url;
    return '/' . str_replace('\\', '/', ltrim($url, '\\/'));
  }
  
  /**
   * Logs some data into log files.
   *
   * @param mixed $data - some data to log.
   * @access public
   * @static  
   */
  public static function log($data)
  {
    $path = self::dir('logs') . '/' . date('Y F');
    if (!is_dir($path)) mkdir($path, 0775, true);
    $file = $path . '/' . date('d H.i.s#') . microtime(true) . '.log';
    if (isset($_SERVER)) $url = ((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF']);
    else $url = false;
    $info = array('IP' => $_SERVER['REMOTE_ADDR'],
                  'ID' => session_id(),
                  'time' => date('m/d/Y H:i:s:u'),
                  'url' => $url,
                  'SESSION' => $_SESSION,
                  'COOKIE' => $_COOKIE,
                  'GET' => $_GET,
                  'POST' => $_POST,
                  'FILES' => $_FILES,
                  'data' => $data);
    unset($info['SESSION']['__DEBUG_INFORMATION__']);
    file_put_contents($file, serialize($info));
  }
  
  /**
   * Performs redirect to given URL.
   *
   * @param string $url
   * @param boolean $isNewWindow
   * @param boolean $immediately
   * @access public
   * @static
   */
  public static function go($url, $inNewWindow = false, $immediately = true)
  {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
    {
      if ($inNewWindow) self::$output = 'window.open(\'' . addslashes($url) . '\');';
      else self::$output = 'window.location.assign(\'' . addslashes($url) . '\');';
    }
    else
    {
      if ($inNewWindow) self::$output = '<script type="text/javascript">window.open(\'' . addslashes($url) . '\');</script>';
      else self::$output = '<script type="text/javascript">window.location.assign(\'' . addslashes($url) . '\');</script>';
    } 
    if ($immediately) exit;
  }
  
  /**
   * Performs the page reloading.
   *
   * @param boolean $immediately
   * @access public
   * @static
   */
  public static function reload($immediately = true)
  {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
    {
      self::$output = 'window.location.reload();';
    }
    else 
    {
      self::$output = '<script type="text/javascript">window.location.reload();</script>';
    }
    if ($immediately) exit;
  }

  /**
   * Initializes the Aleph framework.
   * The method returns new instance of this class.
   *
   * @return self
   * @access public
   * @static
   */
  public static function init()
  {
    if (self::$instance === null)
    {
      self::$time['script_execution_time'] = microtime(true);
      ini_set('display_errors', 1);
      ini_set('html_errors', 0);
      if (!defined('NO_GZHANDLER') && extension_loaded('zlib') && !ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 4096);
      ob_start(function($output)
      {
        return strlen(Aleph::getOutput()) ? Aleph::getOutput() : $output;
      });
      register_shutdown_function(array(__CLASS__, 'fatal'));
      self::debug(true, E_ALL);
      if (!isset($_SERVER['DOCUMENT_ROOT'])) $_SERVER['DOCUMENT_ROOT'] = __DIR__;
      self::$root = rtrim($_SERVER['DOCUMENT_ROOT'], '\\/');
      self::$siteUniqueID = md5(self::$root);
      $lib = self::$root . '/' . pathinfo(__DIR__, PATHINFO_BASENAME) . '/';
      $files = array('core/exception.php' => 'Aleph\Core\Exception', 
                     'cache/cache.php' => 'Aleph\Cache\Cache');
      foreach ($files as $path => $class)
      {
        if (class_exists($class, false)) continue;
        if (is_file($lib . $path)) require_once($lib . $path);
        if (!class_exists($class, false)) throw new \Exception(self::error('Aleph', 'ERR_GENERAL_6', $class, $lib . $path));
      }
      ini_set('unserialize_callback_func', 'spl_autoload_call');
      if (session_id() == '') session_start();
      else session_regenerate_id(true);
      if (isset($_GET['__DEBUG_INFORMATION__']) && isset($_SESSION['__DEBUG_INFORMATION__'][$_GET['__DEBUG_INFORMATION__']]))
      {
        self::$output = $_SESSION['__DEBUG_INFORMATION__'][$_GET['__DEBUG_INFORMATION__']];
        exit;
      }
      if (get_magic_quotes_gpc()) 
      {
        $func = function ($value) use (&$func) {return is_array($value) ? array_map($func, $value) : stripslashes($value);};
        $_GET = array_map($func, $_GET);
        $_POST = array_map($func, $_POST);
        $_COOKIE = array_map($func, $_COOKIE);
      }
      if (date_default_timezone_set(date_default_timezone_get()) === false) date_default_timezone_set('UTC');
      eval('function foo($foo) {return $foo;}');
      set_time_limit(0);
    }
    return self::$instance = new self();
  }
  
  /**
   * Constructor.
   *
   * @access private
   */
  private function __construct()
  {
    if (!self::$instance) spl_autoload_register(array($this, 'al'));
    $this->config = array();
    $this->classes = $this->dirs = $this->exclusions = array();
    $this->key = 'autoload_' . self::$siteUniqueID;
    $this->mask = '/^.*\.php$/i';
    $this->autoload = '';
    $this->cache = null;
    $this->router = null;
  }
  
  /**
   * Private __clone() method prevents this object cloning.
   *
   * @access private
   */
  private function __clone(){}
  
  /**
   * Private __wakeup() method prevents unserialization of this object.
   *
   * @access private
   */
  private function __wakeup(){}
  
  /**
   * Autoloads classes, interfaces and traits.
   *
   * @param string $class
   * @param boolean $auto
   * @return boolean
   * @access private   
   */
  private function al($class, $auto = true)
  {
    $classes = $this->getClasses();
    if ($auto && $this->alCallBack)
    {
      $info = $this->alCallBack->getInfo();
      if ($info['type'] == 'class') $this->al($info['class'], false);
      $this->alCallBack->call(array($class, $classes));
      return true;
    }
    $cs = strtolower($class);
    if ($cs[0] != '\\') $cs = '\\' . $cs;
    if (class_exists($cs, false) || interface_exists($cs, false) || function_exists('trait_exists') && trait_exists($cs, false)) return true;
    if (isset($classes[$cs]) && is_file($classes[$cs]))
    {
      require_once($classes[$cs]);
      if (class_exists($cs, false) || interface_exists($cs, false) || function_exists('trait_exists') && trait_exists($cs, false)) return true;
    }
    if ($this->find($cs) === false)
    {
      if ($auto) 
      {
        self::exception(new Core\Exception($this, 'ERR_GENERAL_1', $class));
        exit;
      }
      return false;
    }
    return true;
  }
  
  /**
   * Finds a class or interface to include into your PHP script.
   *
   * @param string $class
   * @param string $path
   * @return integer | boolean
   * @access private
   */
  private function find($class = null, $path = null)
  {
    if ($path) $paths = array($path => true);
    else
    {
      $paths = $this->dirs ?: array(self::$root => true);
      $this->classes = array();
      $first = true;
    }
    foreach ($paths as $path => $isRecursion)
    {
      foreach (scandir($path) as $item)
      {
        if ($item == '.' || $item == '..' || $item == '.svn' || $item == '.hg' || $item == '.git') continue; 
        $file = $path . '/' . $item;
        if (isset($this->exclusions[$file]) || array_search($file, (array)$this->exclusions) !== false) continue;
        if (is_file($file))
        {
          if (!preg_match($this->mask, $item)) continue;
          $tokens = token_get_all(file_get_contents($file));
          $namespace = null;
          foreach ($tokens as $n => $token)
          {
            if ($token[0] == T_NAMESPACE) 
            {
              $ns = ''; $tks = $tokens; $k = $n;
              do
              {
                $tkn = $tks[++$k];
                if ($tkn[0] == T_STRING || $tkn[0] == T_NS_SEPARATOR) $ns .= $tkn[1];
              }
              while ($tkn != ';' && $tkn != '{');
              $namespace = $ns . '\\';
            }
            else if ($token[0] == T_CLASS || $token[0] == T_INTERFACE || (defined('T_TRAIT') && $token[0] == T_TRAIT))
            {
              $tks = $tokens; $k = $n;
              do
              {
                $tkn = $tks[++$k];
              }
              while ($tkn[0] != T_STRING);
              $cs = strtolower('\\' . $namespace . $tkn[1]);
              if (isset($this->classes[$cs])) 
              {
                $normalize = function($dir)
                {
                  return str_replace((DIRECTORY_SEPARATOR == '\\') ? '/' : '\\', DIRECTORY_SEPARATOR, $dir);
                };
                self::exception(new Core\Exception($this, 'ERR_GENERAL_5', '\\' . $namespace . $tkn[1], $normalize($this->classes[$cs]), $normalize($file)));
                exit;
              }
              $this->classes[$cs] = $file;
            }
          }
        }
        else if ($isRecursion && is_dir($file)) $this->find($class, $file);
      }
    }
    $flag = false;
    if (isset($first)) 
    {
      $this->setClasses($this->classes);
      if ($class !== null)
      {
        foreach ($this->classes as $cs => $file)
        {
          if ($cs == $class)
          {
            require_once($file);
            return (class_exists($class, false) || interface_exists($class, false) || (function_exists('trait_exists') && trait_exists($class, false)));
          }
        }
      }
    }
    return count($this->classes);
  }
  
  /**
   * Loads or returns configuration data.
   *
   * @param string | array $param
   * @param boolean $replace
   * @return array | self
   * @access public
   */
  public function config($param = null, $replace = false)
  {
    if ($param === null) return $this->config;
    if (is_array($param)) 
    {
      if ($replace) 
      {
        $this->config = $param;
        return $this;
      }
      $data = $param;
    }
    else
    {
      $data = parse_ini_file($param, true);
      if ($data === false) throw new Exception($this, 'ERR_CONFIG_1', $param);
    }
    if ($replace) $this->config = array();
    foreach ($data as $section => $properties)
    {
      if (is_array($properties)) foreach ($properties as $k => $v) $this->config[$section][$k] = $v;
      else $this->config[$section] = $properties;
    }
    return $this;
  }
  
  /**
   * Sets or returns the cache object.
   *
   * @param Aleph\Cache\Cache $cache
   * @return Aleph\Cache\Cache
   * @access public
   */
  public function cache(Cache\Cache $cache = null)
  {
    if ($cache === null)
    {
      if ($this->cache === null) $this->cache = Cache\Cache::getInstance('file');
      return $this->cache;
    }
    return $this->cache = $cache;
  }
  
  /**
   * Returns the instance of an Aleph\Net\Request object.
   *
   * @return Aleph\Net\Request
   * @access public
   */
  public function request()
  {
    return Net\Request::getInstance();
  }
  
  /**
   * Returns the instance of an Aleph\Net\Response object.
   *
   * @return Aleph\Net\Response
   * @access public
   */
  public function response()
  {
    return Net\Response::getInstance();
  }
  
  /**
   * Returns the instance of an Aleph\Net\Router object.
   *
   * @return Aleph\Net\Router
   * @access public
   */
  public function router()
  {
    if ($this->router === null) $this->router = new Net\Router();
    return $this->router;
  }
  
  /**
   * Sets array of class paths.
   *
   * @param array $classes
   * @access public
   */
  public function setClasses(array $classes)
  {
    $this->classes = $classes;
    $this->cache()->set($this->key, $this->classes, $this->cache()->getVaultLifeTime());
  }
  
  /**
   * Returns array of class paths.
   *
   * @return array
   * @access public
   */
  public function getClasses()
  {
    if (!$this->classes) $this->classes = (array)$this->cache()->get($this->key); 
	   return $this->classes;
  }
  
  /**
   * Sets array of classes that shouldn't be included in the class searching.
   *
   * @param array $exclusions
   * @access public
   */
  public function setExclusions(array $exclusions)
  {
    $this->exclusions = $exclusions;
  }
  
  /**
   * Returns array of classes that shouldn't be included in the class searching.
   *
   * @return array
   * @access public
   */
  public function getExclusions()
  {
    return $this->exclusions;
  }
  
  /**
   * Sets list of directories for the class searching.
   * List of directories should be an associative array 
   * in which its keys are directory paths and its values are boolean values 
   * determining whether recursive search is possible (TRUE) or not (FALSE).
   *
   * @param array $directories
   * @access public
   */
  public function setDirectories(array $directories)
  {
    $this->dirs = $directories;
  }
  
  /**
   * Returns list of directories for the class searching.
   *
   * @return array
   * @access public
   */
  public function getDirectories()
  {
    return $this->dirs;
  }
  
  /**
   * Sets search file mask.
   *
   * @param string $mask
   * @access public
   */
  public function setMask($mask)
  {
    $this->mask = $mask;
  }
  
  /**
   * Returns search file mask.
   *
   * @return string
   * @access public
   */
  public function getMask()
  {
    return $this->mask;
  }
  
  /**
   * Sets autoload callback. Callback can be a closure, an instance of Aleph\Core\IDelegate or Aleph callback string.
   *
   * @param string | closure | Aleph\Core\IDelegate
   * @access public
   */
  public function setAutoload($callback)
  {
    if (is_array($callback) || is_object($callback) && !($callback instanceof \Closure) && !($callback instanceof Core\IDelegate)) throw new Exception($this, 'ERR_GENERAL_4');
    $this->alCallBack = new Core\Delegate($callback);
  }
  
  /**
   * Returns autoload callback.
   *
   * @return closure | Aleph\Core\IDelegate
   * @access public
   */
  public function getAutoload()
  {
    return $this->alCallBack;
  }
  
  /**
   * Searches all classes of the application or only a single class.
   *
   * @param string $class - a single class to search and include.
   * @return integer | boolean
   * @access public
   */
  public function load($class = null)
  {
    if ($class === null) return $this->find();
    return $this->al($class, false);
  }
  
  /**
   * Sets new value of the configuration variable.
   *
   * @param mixed $var - the configuration variable name.
   * @param mixed $value - new value of a configuration variable.
   * @access public
   */
  public function offsetSet($var, $value)
  {
    $this->config[$var] = $value;
  }

  /**
   * Checks whether the requested configuration variable exist.
   *
   * @param mixed $var - name of the configuration variable.
   * @return boolean
   * @access public   
   */
  public function offsetExists($var)
  {
    return isset($this->config[$var]);
  }

  /**
   * Removes the requested configuration variable.
   *
   * @param mixed $var - name of the configuration variable.
   * @access public
   */
  public function offsetUnset($var)
  {
    unset($this->config[$var]);
  }

  /**
   * Returns value of the configuration variable.
   *
   * @param mixed $var - name of the configuration variable.
   * @return mixed
   * @access public
   */
  public function &offsetGet($var)
  {
    if (!isset($this->config[$var])) $this->config[$var] = null;
    return $this->config[$var];
  }
}