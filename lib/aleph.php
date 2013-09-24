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
  const ERR_GENERAL_4 = 'Class "[{var}]" found in file "[{var}]" is duplicated in file "[{var}]".';
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
  private static $time = [];
  
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
  private static $eval = [];
  
  /**
   * Array of different global objects.
   *
   * @var array $registry
   * @access private
   * @static
   */
  private static $registry = [];
  
  /**
   * Marker of the error handling mode.
   *
   * @var boolean $errHandling
   * @access private
   * @static
   */
  private static $errHandling = false;
  
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
  private $config = [];
  
  /**
   * Array of paths to all classes of the applcation and framework.
   *
   * @var array $classes
   * @access private
   */
  private $classes = [];
  
  /**
   * Array of paths to classes to exclude them from the class searching.
   *
   * @var array $exclusions
   * @access private  
   */
  private $exclusions = [];
  
  /**
   * Direcotires for class searching.
   *
   * @var array $dirs
   * @access private
   */
  private $dirs = [];
  
  /**
   * File search mask.
   *
   * @var string $mask
   * @access private
   */
  private $mask = null;
  
  /**
   * Path to the class map file.
   *
   * @var string $key
   * @access private
   */
  private $classmap = null;
  
  /**
   * Autoload callback.
   *
   * @var mixed $alCallback
   * @access private
   */
  private $alCallback = null;
  
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
    return array_key_exists($key, self::$registry);
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
   * @return float
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
   * @return float
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
   * @param mixed $callback - a delegate.
   * @params arguments of the callback.
   * @return mixed
   * @access public
   * @static
   */
  public static function delegate(/* $callback, $arg1, $arg2, ... */)
  {
    $params = func_get_args();
    return (new Core\Delegate(array_shift($params)))->call($params);
  }
  
  /**
   * Returns an error message by its token.
   *
   * @param string | object $class - class with the needed error message constant.
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
    if (is_object($class))
    {
      $class = get_class($class);
      $token = array_shift($params);
    }
    else
    {
      $class = explode('::', $class);
      $token = isset($class[1]) ? $class[1] : array_shift($params);
      $class = ltrim($class[0], '\\');
    }
    $err = $token;
    if ($class)
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
   * Checks whether the error handling is turned on.
   *
   * @return boolean
   * @access public
   * @static
   */
  public static function isErrorHandlingEnabled()
  {
    return self::$errHandling;
  }
  
  /**
   * Enables and disables the error handling mode.
   *
   * @param boolean $enable - if it equals TRUE then the debug mode is enabled and it is disabled otherwise.
   * @param integer $errorLevel - new error reporting level.
   * @access public
   * @static
   */
  public static function errorHandling($enable = true, $errorLevel = null)
  {
    self::$errHandling = $enable;
    restore_error_handler();
    restore_exception_handler();
    if (!$enable)
    {
      error_reporting($errorLevel ?: ini_get('error_reporting'));
      return;
    }
    error_reporting($errorLevel ?: E_ALL);
    set_exception_handler([__CLASS__, 'exception']);
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
    if (self::isErrorHandlingEnabled() && preg_match('/(Fatal|Parse) error:(.*) in (.*) on line (\d+)/', ob_get_contents(), $res)) 
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
    $config = (self::$instance !== null) ? self::$instance->config : [];
    $debug = isset($config['debugging']) ? (bool)$config['debugging'] : true;
    foreach (['templateDebug', 'templateBug'] as $var) $$var = isset($config[$var]) ? self::dir($config[$var]) : null;
    $delegateExists = false;
    if (self::$instance instanceof \CB && (!empty($config['logging']) && !empty($config['customLogMethod']) || $isDebug && !empty($config['customDebugMethod'])))
    {
      $classes = self::$instance->getClasses();
      if (isset($classes['aleph\core\delegate']) && file_exists($classes['aleph\core\delegate']))
      {
        require($classes['aleph\core\delegate']);
        $delegateExists = class_exists('Aleph\Core\Delegate');
      }
    }
    try
    {
      if (!empty($config['logging']))
      {
        if (!empty($config['customLogMethod']) && $delegateExists)
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
    if ($debug && !empty($config['customDebugMethod']) && $delegateExists)
    {
      if (!self::delegate($config['customDebugMethod'], $e, $info)) return;
    }
    if (PHP_SAPI == 'cli' || empty($_SERVER['REMOTE_ADDR']))
    {
      if ($debug)
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
    if ($debug)
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
    exit;
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
        $tmp = []; 
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
      $headers = [];
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
      $headers = [];
      foreach (headers_list() as $header) 
      {
        $header = explode(':', $header);
        $headers[array_shift($header)] = trim(implode(':', $header));
      }
      return $headers;
    };
    $fragment = function($file, $line, &$index, &$command = null, $half = 10)
    {
      $lines = explode("\n", preg_replace('/\r\n|\r/', "\n", (is_file($file) && is_readable($file)) ? file_get_contents($file) : $file));
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
    $info = [];
    $info['time'] = date('Y-m-d H:i:s:u');
    $info['sessionID'] = session_id();
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
        $tmp = [];
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
      array_unshift($trace, ['file' => $reducedFile, 'line' => $line, 'command' => $command, 'code' => $code, 'index' => $index]);
    }
    $info['memoryUsage'] = number_format(self::getMemoryUsage() / 1048576, 4);
    $info['executionTime'] = self::getExecutionTime();
    $info['message'] = ltrim($message);
    $info['file'] = $reducedFile;
    $info['line'] = $line;
    $info['trace'] = $trace;
    $info['class'] = method_exists($e, 'getClass') ? $e->getClass() : '';
    $info['token'] = method_exists($e, 'getToken') ? $e->getToken() : '';
    $info['severity'] = method_exists($e, 'getSeverity') ? $e->getSeverity() : '';
    $info['traceAsString'] = $e->getTraceAsString();
    $info['request'] = $request();
    $info['response'] = $response();
    $info['GET'] = isset($_GET) ? $_GET : [];
    $info['POST'] = isset($_POST) ? $_POST : [];
    $info['COOKIE'] = isset($_COOKIE) ? $_COOKIE : [];
    $info['FILES'] = isset($_FILES) ? $_FILES : [];
    $info['SERVER'] = isset($_SERVER) ? $_SERVER : [];
    $info['SESSION'] = isset($_SESSION) ? $_SESSION : [];
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
    return str_replace(DIRECTORY_SEPARATOR == '\\' ? '/' : '\\', DIRECTORY_SEPARATOR, $dir);
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
    if (is_dir($path) || mkdir($path, 0775, true)) file_put_contents($path . '/' . date('d H.i.s#') . microtime(true) . '.log', serialize($data));
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
      if (!defined('NO_GZHANDLER') && extension_loaded('zlib') && !ini_get('zlib.output_compression'))
      {
        ini_set('output_buffering', 1);
        ini_set('zlib.output_compression', 4096);
      }
      ob_start(function($output)
      {
        return strlen(\Aleph::getOutput()) ? \Aleph::getOutput() : $output;
      });
      register_shutdown_function([__CLASS__, 'fatal']);
      self::errorHandling(true, E_ALL);
      if (!isset($_SERVER['DOCUMENT_ROOT'])) $_SERVER['DOCUMENT_ROOT'] = __DIR__;
      self::$root = realpath($_SERVER['DOCUMENT_ROOT']);
      self::$siteUniqueID = md5(self::$root);
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
    if (!self::$instance) spl_autoload_register([$this, 'al']);
    $this->config = $this->classes = $this->exclusions = [];
    $this->dirs = [self::$root => true];
    $this->classmap = self::$root . '/classmap.php';
    $this->mask = '/.+\.php$/i';
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
    if ($auto && $this->alCallback)
    {
      $info = $this->alCallback->getInfo();
      if ($info['type'] == 'class') $this->al($info['class'], false);
      $this->alCallback->call([$class, $classes]);
      return true;
    }
    $cs = strtolower(ltrim($class, '\\'));
    if (class_exists($cs, false) || interface_exists($cs, false) || trait_exists($cs, false)) return true;
    if (isset($classes[$cs]) && is_file($classes[$cs]))
    {
      require_once($classes[$cs]);
      if (class_exists($cs, false) || interface_exists($cs, false) || trait_exists($cs, false)) return true;
    }
    if (empty($this->config['autoload']['enabled']))
    {
      self::exception(new Core\Exception($this, 'ERR_GENERAL_1', $class));
      exit;
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
    if ($path) $paths = [$path => true];
    else
    {
      if (file_exists($this->classmap) && (require($this->classmap)) === false)
      {
        $seconds = 0; $timeout = isset($this->config['autoload']['timeout']) ? (int)$this->config['autoload']['timeout'] : 900;
        while (($classes = require($this->classmap)) === false && ++$seconds <= $timeout) sleep(1);
        if ($seconds <= $timeout)
        {
          if (isset($classes[$class]) && is_file($classes[$class]))
          {
            require_once($classes[$class]);
            return class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false);
          }
          return false;
        }
        // if we wait more than $timeout seconds then it's probably something went wrong and we should try to perform searching again.
        file_put_contents($this->classmap, '<?php return [];');
      }
      else file_put_contents($this->classmap, '<?php return false;');
      $paths = $this->dirs ?: [self::$root => true];
      $this->classes = [];
      $first = true;
    }
    foreach ($paths as $path => $isRecursion)
    {
      foreach (scandir($path) as $item)
      {
        if ($item == '.' || $item == '..' || $item == '.svn' || $item == '.hg' || $item == '.git') continue; 
        $file = str_replace(DIRECTORY_SEPARATOR == '\\' ? '/' : '\\', DIRECTORY_SEPARATOR, $path . '/' . $item);
        if (array_search($file, $this->exclusions) !== false) continue;
        if (is_file($file))
        {
          if (!preg_match($this->mask, $item)) continue;
          $tokens = token_get_all(file_get_contents($file));
          for ($i = 0, $max = count($tokens), $namespace = ''; $i < $max; $i++)
          {
            $token = $tokens[$i];
            if (is_string($token)) continue;
            switch ($token[0])            
            {
              case T_NAMESPACE:
                $namespace = '';
                for (++$i; $i < $max; $i++)
                {
                  $t = $tokens[$i];
                  if (is_string($t)) break;
                  if ($t[0] == T_STRING || $t[0] == T_NS_SEPARATOR) $namespace .= $t[1];
                }
                $namespace .= '\\';
                break;
              case T_CLASS:
              case T_INTERFACE;
              case T_TRAIT:
                for (++$i; $i < $max; $i++)
                {
                  $t = $tokens[$i];
                  if ($t[0] == T_STRING) break;
                }
                $cs = strtolower(ltrim($namespace . $t[1], '\\'));
                if (isset($this->classes[$cs])) 
                {
                  $normalize = function($dir)
                  {
                    return str_replace((DIRECTORY_SEPARATOR == '\\') ? '/' : '\\', DIRECTORY_SEPARATOR, $dir);
                  };
                  file_put_contents($this->classmap, '<?php return [];');
                  self::exception(new \Exception(self::error('Aleph::ERR_GENERAL_4', ltrim($namespace . $t[1], '\\'), $normalize($this->classes[$cs]), $normalize($file))));
                  exit;
                }
                $this->classes[$cs] = $file;
                break;
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
            return (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false));
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
    $initAutoload = function()
    {
      if (empty($this->config['autoload'])) return;
      $config = $this->config['autoload'];
      if (isset($config['directories'])) $this->setDirectories($config['directories']);
      if (isset($config['exclusions'])) $this->setExclusions($config['exclusions']);
      if (isset($config['mask'])) $this->setMask($config['mask']);
      if (isset($config['callback'])) $this->setAutoload($config['callback']);
    };
    if (is_array($param))
    {
      if ($replace)
      {
        $this->config = $param;
        $initAutoload();
        return $this;
      }
      $data = $param;
      $ini = false;
    }
    else
    {
      $ini = false;
      if (strtolower(pathinfo($param, PATHINFO_EXTENSION)) == 'php') $data = require($param);
      else
      {
        $data = parse_ini_file($param, true);
        if ($data === false) throw new Core\Exception($this, 'ERR_CONFIG_1', $param);
        $ini = true;
      }
    }
    if ($replace) $this->config = [];
    $convert = function($v) use ($ini)
    {
      if (!$ini || is_array($v) || is_object($v)) return $v;
      if (strlen($v) > 1 && ($v[0] == '[' || $v[0] == '{') && ($v[strlen($v) - 1] == ']' || $v[strlen($v) - 1] == '}'))
      {
        $tmp = json_decode($v, true);
        $v = $tmp !== null ? $tmp : $v;
      }
      return $v;
    };
    foreach ($data as $section => $properties)
    {
      if (is_array($properties)) 
      {
        if (empty($this->config[$section]) || !is_array($this->config[$section])) $this->config[$section] = [];
        foreach ($properties as $k => $v) $this->config[$section][$k] = $convert($v);
      }
      else 
      {
        $this->config[$section] = $convert($properties);
      }
    }
    $initAutoload();
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
      if ($this->cache === null) $this->cache = Cache\Cache::getInstance();
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
    $code = [];
    foreach ($classes as $class => $path) $code[] = "'" . $class . "' => '" . str_replace("'", "\'", $path) . "'";
    file_put_contents($this->classmap, '<?php return [' . implode(',' . PHP_EOL . '              ', $code) . '];');
    chmod($this->classmap, 0775);
    $this->classes = $classes;
  }
  
  /**
   * Returns array of class paths.
   *
   * @return array
   * @access public
   */
  public function getClasses()
  {
    if (!$this->classes) $this->classes = file_exists($this->classmap) ? (array)require($this->classmap) : [];
	   return $this->classes;
  }
  
  /**
   * Sets array of files that shouldn't be included in the class searching.
   *
   * @param array $exclusions
   * @access public
   */
  public function setExclusions(array $exclusions)
  {
    foreach ($exclusions as &$item) $item = realpath($item);
    $this->exclusions = $exclusions;
  }
  
  /**
   * Returns array of files that shouldn't be included in the class searching.
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
   * Sets autoload callback.
   *
   * @param mixed $callback - a delegate.
   * @access public
   */
  public function setAutoload($callback)
  {
    $this->alCallback = new Core\Delegate($callback);
  }
  
  /**
   * Returns autoload callback.
   *
   * @return Aleph\Core\IDelegate
   * @access public
   */
  public function getAutoload()
  {
    return $this->alCallback;
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