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
 * With this class you can log error messages, profile your code, catche any errors, 
 * load classes, configure your application. Also this class allows routing and 
 * can be stroting any global objects. 
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
  const TEMPLATE_DEBUG = '<!doctype html><html><head><meta content="text/html; charset=UTF-8" http-equiv="Content-Type" /><title>Bug Report</title><body bgcolor="gold">The following error <pre>[{message}]</pre> has been catched in file <b>[{file}]</b> on line [{line}]<br /><br />[{fragment}]<b style="font-size: 14px;">Stack Trace:</b><pre>[{stack}]</pre></body></html>';
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
  const ERR_GENERAL_7 = 'Delegate "[{var}]" is not callable.';
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
   * Instance of the class Aleph\Net\Request.
   *
   * @var Aleph\Net\Request $request
   * @access private
   */
  private $request = null;
  
  /**
   * Instance of the class Aleph\Net\Response.
   * 
   * @var Aleph\Net\Response $response
   * @access private
   */
  private $response = null;
  
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
   * Array of actions for the routing.
   * 
   * @var array $acts   
   */
  private $acts = array('methods' => array(), 'actions' => array());
  
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
   * Collects and stores information about some eval's code. 
   *
   * @param string $code - the code that will be executed by eval operator.
   * @return string
   * @access public
   * @static
   */
  public static function ecode($code)
  {
    $e = new \Exception();
    if (!count(self::$eval))
    {
      self::$eval['lines'] = 0;
      self::$eval['code'] = '';
    }
    self::$eval['trace'] = $e->getTrace();
    self::$eval['traceAsString'] = $e->getTraceAsString();
    self::$eval['rows'] = count(explode("\n", str_replace("\r\n", "\n", $code)));
    self::$eval['lines'] += self::$eval['rows'];
    self::$eval['code'] .= $code . "\n";
    return $code;
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
    error_reporting($errorLevel ?: E_ALL | E_STRICT);
    set_exception_handler(array(__CLASS__, 'exception'));
    set_error_handler(function($errno, $errstr, $errfile, $errline)
    {
      throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }, $errorLevel);
  }
  
  /**
   * Set the debug output for an exception.
   *
   * @param \Exception $e
   * @param boolean $isFatalError
   * @access public
   * @static
   */
  public static function exception(\Exception $e, $isFatalError = false)
  {
    restore_error_handler();
    restore_exception_handler();
    $info = self::analyzeException($e);
    $info['isFatalError'] = $isFatalError;
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
      self::delegate($config['customDebugMethod'], $e, $info);
      return;
    }
    if (PHP_SAPI == 'cli')
    {
      if ($isDebug)
      {
        if ($isFatalError) $output = $info['fragment'];
        else $output = $info['message'] . PHP_EOL . $info['fragment'] . PHP_EOL . $info['stack'];
        self::$output = strip_tags(html_entity_decode($output));
      }
      else
      {
        self::$output = self::TEMPLATE_BUG;
      }
      return;
    }
    if ($isDebug)
    {
      $tmp = array();
      $info['stack'] = htmlspecialchars($info['stack']);
      foreach ($info as $k => $v) $tmp['[{' . $k . '}]'] = $v;
      $templateDebug = strtr((is_file($templateDebug) && is_readable($templateDebug)) ? file_get_contents($templateDebug) : self::TEMPLATE_DEBUG, $tmp);
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
      self::debug(true, E_ALL | E_STRICT);
      if (!isset($_SERVER['DOCUMENT_ROOT'])) $_SERVER['DOCUMENT_ROOT'] = __DIR__;
      self::$root = rtrim($_SERVER['DOCUMENT_ROOT'], '\\/');
      self::$siteUniqueID = md5(self::$root);
      if (!defined('NO_GZHANDLER') && extension_loaded('zlib') && !ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 4096);
      ob_start(function($html)
      {
        if (!Aleph::isDebug() || !preg_match('/(Fatal|Parse) error:(.*) in (.*) on line (\d+)/', $html, $res)) return Aleph::getOutput() ?: $html;
        Aleph::exception(new \ErrorException($res[2], 0, 1, $res[3], $res[4]), true);
        return Aleph::getOutput();
      });
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
   * Analyzes an exception.
   *
   * @param \Exception $e
   * @return array - exception information.
   * @access private
   * @static  
   */
  private static function analyzeException(\Exception $e)
  {
    $msg = ucfirst(ltrim($e->getMessage()));
    $trace = $e->getTrace();
    $traceAsString = $e->getTraceAsString();
    $file = $e->getFile();
    $line = $e->getLine();
    if (self::$eval && (strpos($file, 'eval()\'d') !== false || strpos($msg, 'eval()\'d') !== false))
    {
      $findFunc = function($func, $code)
      {
        foreach (explode(PHP_EOL, $code) as $n => $row)
        {
          $tokens = token_get_all('<?php ' . $row . '?>');
          foreach ($tokens as $k => $token)
          {
            if ($token[0] != T_FUNCTION) continue;
            while ($token[0] != T_STRING)
            {
              $k++;
              $token = $tokens[$k];
            }
            if ($token[1] == $func) return $n + 1;
          }
        }
        return false;
      };
      $trace = self::$eval['trace'];
      $traceAsString = self::$eval['traceAsString'];
      $fragment = self::$eval['code'];
      if (preg_match('/([^\( ]+)\(\).*, called in ([^ ]+) on line (\d+)/', $msg, $matches))
      {
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        $foundLine = $findFunc($matches[1], $fragment);
        $frag1 = self::codeFragment($file, $line);
        $frag2 = self::codeFragment($matches[2], $matches[3]);
        $frag3 = self::codeFragment($fragment, $foundLine);
        $fragment = '<b>File in which the error has been catched:</b> ' . $file . $frag1 . '<b>File in which the callable is called:</b> ' . $matches[2] . $frag2 . '<b>eval()\'s code in which the callable is defined:</b> ' . $frag3;
        $msg .= ' in eval()\'s code on line ' . $foundLine;
      }
      else if (preg_match('/([^\( ]+)\(\).*, called in ([^\(]+)\((\d+)\) : eval\(\)\'d code on line (\d+)/', $msg, $matches))
      {
        $line = $findFunc($matches[1], $fragment);
        $matches[4] += self::$eval['lines'] - self::$eval['rows'];
        $msg = preg_replace('/called in [^ ]+ : eval\(\)\'d code on line \d+/', 'called in eval()\'s on line ' . $matches[4], $msg);
        $frag1 = self::codeFragment($matches[2], $matches[3]);
        $frag3 = self::codeFragment($fragment, $matches[4]);
        $tmp = '<b>File in which the error has been catched:</b> ' . $matches[2] . $frag1;
        if (strpos($file, 'eval()\'d') !== false)
        {
          $frag2 = self::codeFragment($fragment, $line);
          $tmp .= '<b>eval()\'s code in which the callable is defined:</b> ' . $frag2;
          $msg .= ' in eval()\'s code on line ' . $line;
        }
        else 
        {
          $frag2 = self::codeFragment($file, $line);
          $tmp .= '<b>File in which the callable is defined:</b> ' . $matches[2] . $frag2;
          $msg .= ' in ' . $file . ' on line ' . $line;
        }
        $tmp .= '<b>eval()\'s code in which the callable is called:</b> ' . $frag3;
        $fragment = $tmp;
        $file = $matches[2];
        $line = $matches[3];
      }
      else
      {
        $line += self::$eval['lines'] - self::$eval['rows'];
        $frag1 = self::codeFragment($trace[0]['file'], $trace[0]['line']);
        $frag2 = self::codeFragment($fragment, $line);
        $fragment = '<b>File in which the error has been catched:</b> ' . $trace[0]['file'] . $frag1 . '<b>eval()\'s code in which the error has been catched:</b> ' . $frag2;
        $msg .= ' in eval()\'s code on line ' . $line;
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
      }
    }
    else
    {
      if (preg_match('/([^\( ]+)\(\).*, called in ([^ ]+) on line (\d+)/', $msg, $matches))
      {
        $frag1 = self::codeFragment($file, $line);
        $frag2 = self::codeFragment($matches[2], $matches[3]);
        $fragment = '<b>File in which the callable is defined:</b> ' . $matches[2] . $frag1 . '<b>File in which the callable is called:</b> ' . $file . $frag2;
        $msg .= ' in ' . $file . ' on line ' . $line;
      }
      else
      {
        $fragment = self::codeFragment($file, $line);
      }
    }
    $info = array();
    if (method_exists($e, 'getClass')) $info['class'] = $e->getClass();
    if (method_exists($e, 'getToken')) $info['token'] = $e->getToken();
    $info['message'] = $msg;
    $info['stack'] = $traceAsString;
    $info['code'] = $e->getCode();
    $info['severity'] = method_exists($e, 'getSeverity') ? $e->getSeverity() : '';
    $info['file'] = $file;
    $info['line'] = $line;
    $info['fragment'] = $fragment;
    return $info;
  }
  
  /**
   * Returns the code fragment of the PHP script in which the error has occured.
   *
   * @param string $filename
   * @param integer $line
   * @return string
   * @access private
   * @static
   */
  private static function codeFragment($filename, $line)
  {
    $halfOfRows = 10;
    $minColumns = 100;
    $lines = explode("\n", str_replace("\r\n", "\n", (is_file($filename) && is_readable($filename)) ? file_get_contents($filename) : $filename));
    $count = count($lines);
    $total = 2 * $halfOfRows + 1;
    if ($count <= $total)
    {
      $start = 0;
      $end = $count - 1;
      $offset = 1;
    }
    else if ($line - $halfOfRows <= 0)
    {
      $start = 0;
      $end = $total - 1;
      $offset = 1;
    }
    else if ($line + $halfOfRows >= $count)
    {
      $end = $count - 1;
      $start = $count - $total;
      $offset = $start + 1;
    }
    else
    {
      $start = $line - $halfOfRows - 1;
      $end = $line + $halfOfRows - 1;
      $offset = $start + 1;
    }
    $lines = array_slice($lines, $start, $end - $start + 1);
    foreach ($lines as $k => &$str)
    {
      $str = rtrim(str_pad(($k + $offset) . '.', 6, ' ') . $str);
      if ($line == $k + $offset) 
      {
        $markedLine = $k + 1;
        $originalLength = strlen($str);
      }
      if (strlen($str) > $minColumns) $minColumns = strlen($str);
      $str = htmlspecialchars($str);
    }
    array_unshift($lines, $lines[] = str_repeat('-', $minColumns));
    if (isset($markedLine) && isset($lines[$markedLine])) $lines[$markedLine] = '<b style="background-color:red;color:white;"><i>' . str_pad($lines[$markedLine], $minColumns + strlen($lines[$markedLine]) - $originalLength, ' ',  STR_PAD_RIGHT). '</i></b>';
    return '<pre>' . implode("\n", $lines) . '</pre>';
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
    $this->acts = array('methods' => array(), 'actions' => array());
    $this->key = 'autoload_' . self::$siteUniqueID;
    $this->mask = '/^.*\.php$/i';
    $this->autoload = '';
    $this->cache = null;
    $this->request = null;
    $this->response = null;
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
   * Autoloads classes and intefaces.
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
   * Finds a class or inteface to include into your PHP script.
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
              while ($tkn != ';');
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
   * Parses URL templates for the routing.
   *
   * @param string $url
   * @param string $key
   * @param string $regex
   * @return array
   * @access private   
   */
  private function parseURLTemplate($url, &$key, &$regex)
  {
    $params = array();
    $url = (string)$url;
    $path = preg_split('/(?<!\\\)\/+/', $url);
    $path = array_map(function($p) use(&$params)
    {
      if ($p == '') return '';
      preg_match_all('/(?<!\\\)#((?:.(?!(?<!\\\)#))*.)./', $p, $matches);
      foreach ($matches[0] as $k => $match)
      {
        $m = $matches[1][$k];
        $n = strpos($m, '|');
        if ($n !== false) 
        {
          $name = substr($m, 0, $n);
          $m = substr($m, $n + 1);
          if ($m == '') $m = '[^\/]*';
        }
        else 
        {
          $m = '[^\/]*';
          $name = $matches[1][$k];
        }
        $params[$name] = $name;
        $p = str_replace($match, '(?P<' . $name . '>' . $m . ')', $p);
      }
      return str_replace('\#', '#', $p);
    }, $path);
    $key = $url ? md5($url) : 'default';
    $regex = '/^' . implode('\/', $path) . '$/';
    return $params;
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
    if ($this->request === null) $this->request = new Net\Request();
    return $this->request;
  }
  
  /**
   * Returns the instance of an Aleph\Net\Response object.
   *
   * @return Aleph\Net\Response
   * @access public
   */
  public function response()
  {
    if ($this->response === null) $this->response = new Net\Response();
    return $this->response;
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
   * Enables or disables HTTPS protocol for the given URL template.
   *
   * @param string $url - regex for the given URL.
   * @param boolean $flag
   * @param array | string $methods - HTTP request methods.
   * @access public
   */
  public function secure($url, $flag, $methods = 'GET|POST')
  {
    $action = function() use($flag)
    {
      $url = new Net\URL();
      if ($url->isSecured() != $flag) 
      {
        $url->secure($flag);
        Aleph::go($url->build());
      }
    };
    $key = $url ? md5($url) : 'default';
    $methods = is_array($methods) ? $methods : explode('|', $methods);
    $this->acts['actions'][$key] = array('params' => array(), 'action' => $action, 'regex' => $url);
    foreach ($methods as $method) $this->acts['methods'][strtolower($method)][$key] = 1;
  }
  
  /**
   * Sets the redirect for the given URL regex template.
   *
   * @param string $url - regex URL template.
   * @param string $redirect - URL to redirect.
   * @param array | string $methods - HTTP request methods.
   * @access public
   */
  public function redirect($url, $redirect, $methods = 'GET|POST')
  {
    $params = $this->parseURLTemplate($url, $key, $regex);
    $t = microtime(true);
    for ($k = 0, $n = count($params); $k < $n; $k++)
    {
      $redirect = preg_replace('/(?<!\\\)#((.(?!(?<!\\\)#))*.)./', md5($t + $k), $redirect);
    }
    $action = function() use($t, $redirect)
    {
      $url = $redirect;
      foreach (func_get_args() as $k => $arg)
      {
        $url = str_replace(md5($t + $k), $arg, $url);
      }
      Aleph::go($url);
    };
    $methods = is_array($methods) ? $methods : explode('|', $methods);
    $this->acts['actions'][$key] = array('params' => $params, 'action' => $action, 'regex' => $regex);
    foreach ($methods as $method) $this->acts['methods'][strtolower($method)][$key] = 1;
  }
  
  /**
   * Binds an URL regex template with some action.
   *
   * @param string $url - regex URL template.
   * @param closure | Aleph\Core\IDelegate | string $action
   * @param boolean $checkParameters
   * @param boolean $ignoreWrongDelegate
   * @param array | string $methods - HTTP request methods.
   * @access public
   */
  public function bind($url, $action, $checkParameters = false, $ignoreWrongDelegate = true, $methods = 'GET|POST')
  {
    $params = $this->parseURLTemplate($url, $key, $regex);
    $methods = is_array($methods) ? $methods : explode('|', $methods);
    $this->acts['actions'][$key] = array('params' => $params, 'action' => $action, 'regex' => $regex, 'checkParameters' => $checkParameters, 'ignoreWrongDelegate' => $ignoreWrongDelegate);
    foreach ($methods as $method) $this->acts['methods'][strtolower($method)][$key] = 1;
  }
  
  /**
   * Performs all actions matching all regex URL templates.
   *
   * @param string | array - HTTP request methods.
   * @param string $component - URL component.
   * @param string $url - regex URL template.
   * @return \StdClass with two properties: result - a result of the acted action, success - indication that the action was worked out.
   * @access public
   */
  public function route($methods = null, $component = 'path', $url = null)
  {
    $methods = is_array($methods) ? $methods : ($methods ? explode('|', $methods) : array());
    if (count($methods) == 0) 
    {
      if (!isset($this->request()->method)) return;
      $methods = array($this->request()->method);
    }
    if ($url === null)
    {
      if (isset($this->request()->url) && $this->request()->url instanceof Net\URL) 
      {
        $url = $this->request()->url->build($component);
      }
      else 
      {
        $url = foo(new Net\URL())->build($component);
      }
    }
    $res = new \StdClass();
    $res->success = false;
    $res->result = null;
    foreach ($methods as $method)
    {
      $method = strtolower($method);
      if (!isset($this->acts['methods'][$method])) continue;
      foreach ($this->acts['methods'][$method] as $key => $flag)
      {
        $action = $this->acts['actions'][$key];
        if (!preg_match_all($action['regex'], $url, $matches)) continue;
        if ($action instanceof Core\Delegate) $act = $action['action'];
        else 
        {
          foreach ($action['params'] as $k => $param)
          {
            $action['action'] = str_replace('#' . $param . '#', $matches[$param][0], $action['action'], $count);
            if ($count > 0) unset($action['params'][$k]);
          }
          $act = new Core\Delegate($action['action']);
        }
        if (!$act->isCallable())
        {
          if (!empty($action['ignoreWrongDelegate'])) continue;
          throw new Core\Exception($this, 'ERR_GENERAL_7', (string)$act);
        }
        if (!empty($action['checkParameters']))
        {
          $tmp = array();
          foreach ($act->getParameters() as $param) 
          {
            $name = $param->getName();
            if (isset($action['params'][$name])) $tmp[] = $name;
          }
          $action['params'] = $tmp;
        }
        $params = array();
        foreach ($action['params'] as $param) $params[] = $matches[$param][0];
        $res->success = true;
        $res->result = $act->call($params);
        return $res;
      }
    }
    return $res;
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
   * Returns whether the requested configuration variable exist.
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