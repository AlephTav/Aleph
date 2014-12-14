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

namespace Aleph\Configuration;

require_once(__DIR__ . '/module.php');

/**
 * The utility class that intended for automation of developers' routine operations such as: 
 * configuring of your application, cache management, scaffolding, generating site classmap etc.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.configuration
 */
final class Configurator
{
  /**
   * The root directory of the application.
   *
   * @var string $root
   * @access private
   */
  private $root = null;
  
  /**
   * The path to the main class (Aleph) of the framework.
   *
   * @var string $core
   * @access private
   */
  private $core = null;

  /**
   * List of the configuration files.
   *
   * @var array $configs
   * @access private
   */
  private $configs = [];
  
  /**
   * List of configurator's modules.
   *
   * @var array $modules
   * @access private
   */
  private $modules = [];
  
  /**
   * Returns TRUE if the current request is Ajax one and FALSE otherwise.
   *
   * @return boolean
   * @access public
   * @static
   */
  public static function isAjaxRequest()
  {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
  }
  
  /**
   * Returns TRUE if the current request is the first one (not Ajax) or if the application is launched as a console one.
   * Otherwise the methods returns FALSE.
   *
   * @return boolean
   * @access public
   * @static
   */
  public static function isFirstRequest()
  {
    return self::isCLI() || $_SERVER['REQUEST_METHOD'] == 'GET' && !self::isAjaxRequest();
  }
  
  /**
   * Returns TRUE if the application is launched as a console one and FALSE otherwise.
   *
   * @return boolean
   * @access public
   * @static
   */
  public static function isCLI()
  {
    return PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server';
  }
  
  /**
   * Initializes the configurator.
   *
   * @param array $config - parameters of the configurator.
   * @access public
   */
  public function __construct(array $config)
  {
    set_time_limit(0);
    $this->root = realpath(isset($config['root']) ? $config['root'] : __DIR__);
    $this->core = isset($config['core']) ? $config['core'] : '/lib/Aleph';
    $config = isset($config['configs']) ? (array)$config['configs'] : [];
    // Reads all configuration files of the application.
    foreach ($config as $file => $editable) $this->configs[$this->normalizePath($file)] = $editable;
    // Creates module objects.
    $list = __DIR__ . '/../modules/list.txt';
    if (file_exists($list))
    {
      $list = file($list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      $base = __DIR__ . '/../modules/';
      foreach ($list as $name)
      {
        if (file_exists($base . $name))
        {
          $php = $base . $name . '/' . $name . '.php';
          if (file_exists($php))
          {
            require_once($php);
            $class = 'Aleph\Configuration\\' . $name;
            if (class_exists($class)) $this->modules[$name] = new $class($this); 
          }
        }
      }
    }
  }
  
  /**
   * Returns the site root directory.
   *
   * @return boolean
   * @access public
   */
  public function getRoot()
  {
    return $this->root;
  }
  
  /**
   * Returns the path to the main framework class.
   *
   * @return string
   * @access public
   */
  public function getCore()
  {
    return $this->core;
  }
  
  /**
   * Returns list of the configuration files.
   *
   * @return array
   * @access public
   * @static
   */
  public function getConfigs()
  {
    return $this->configs;
  }
  
  /**
   * Initializes and launches the configuration modules.
   *
   * @access public
   */
  public function process()
  {
    // Initializes all modules.
    foreach ($this->modules as $name => $module) $module->init();
    // Checks if we have an error with including of the core class.
    if (self::isFirstRequest())
    {
      if (!file_exists($this->root . $this->core))
      {
        $this->show(['errors' => 'The core class of the framework is not found.']);
      }
      if (!self::isCLI())
      {
        $this->connect();
        $data = [];
        foreach ($this->modules as $name => $module) $data[$name] = $module->getData();
        $this->show(['errors' => [], 'modules' => $data]);
      }
    }
    // Launches the modules.
    if (self::isCLI() || self::isAjaxRequest())
    {
      list($module, $command, $args) = $this->parseParameters();
      $this->connect();
      if (empty($this->modules[$module]))
      {
        $this->write($this->getCommandHelp());
      }
      else
      {
        $this->modules[$module]->process($command, $args);
      }
    }
  }
  
  /**
   * Sends text data to the console.
   *
   * @param string $text - any text data.
   * @access public
   */
  public function write($text)
  {
    if (self::isCLI())
    {
      if (!$this->hasColorSupport())
      {
        $text = preg_replace("/\e\[[\d;]+m/i", '', $text);
      }
      echo $text;
    }
  }
  
  /**
   * Normalizes the directory path.
   *
   * @return string
   * @access public
   */
  public function normalizePath($path)
  {
    if (strlen($path) == 0) return false;
    if ($path[0] != '/') $path = '/' . $path;
    return $this->root . $path;
  }
  
  /**
   * Returns TRUE if SAPI is an interactive console with color support and FALSE otherwise.
   *
   * @return boolean
   * @access private
   */
  private function hasColorSupport()
  {
    static $hasColorSupport;
    if ($hasColorSupport === null)
    {
      if (DIRECTORY_SEPARATOR == '\\') 
      {
        $hasColorSupport = getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON';
      }
      else if (!function_exists('posix_isatty')) 
      {
        $hasColorSupport = false;
      }
      else
      {
        $stream = fopen('php://output', 'w');
        $hasColorSupport = posix_isatty($stream);
        fclose($stream);
      }
    }
    return $hasColorSupport;
  }
  
  /**
   * Returns the command line help of the configurator.
   *
   * @return string
   * @access private   
   */
  private function getCommandHelp()
  {
    $help = <<<HELP

\e[33;1mUtility to configure a web application and perform the common developer tasks.\e[0m

\e[37;1mGeneral usage:\e[0m \e[32mcfg [MODULE] [COMMAND] [OPTIONS]...\e[0m
    \e[30;1mMODULE\e[0m    name of the configurator module.
    \e[30;1mCOMMAND\e[0m   some action of the module to be performed. The list of command is defined by the particular module.   
    \e[30;1mOPTIONS\e[0m   some additional parameters that defined by the given command and module.

\e[36;1mThe module list:\e[0m

HELP;
    foreach ($this->modules as $name => $module) $help .= PHP_EOL . "\e[33m[$name]\e[0m" . PHP_EOL . $module->getcommandHelp();
    return $help;
  }
  
  /**
   * Parses command line parameters passed to the script into array.
   *
   * @return array
   * @access private
   */
  private function parseParameters()
  {
    if (self::isCLI())
    {
      $argv = $_SERVER['argv'];
      $argc = $_SERVER['argc'];
      $args = [];
      $command = null;
      $module = isset($argv[1]) ? strtolower($argv[1]) : '';
      if ($module && substr($module, 0, 2) != '--')
      {
        $command = isset($argv[2]) ? strtolower($argv[2]) : '';
        if ($command && substr($command, 0, 2) != '--')
        {
          $args = [];
          for ($i = 3, $key = ''; $i < $argc; $i++)
          {
            if (substr($argv[$i], 0, 2) == '--') 
            {
              $key = substr($argv[$i], 2);
              $args[$key] = '';
            }
            else if ($key)
            {
              $args[$key][] = $argv[$i];
            }
          }
          foreach ($args as &$arg) if (count($arg) == 1) $arg = $arg[0];
        }
      }
    }
    else
    {
      $args = $_REQUEST;
      $module = strtolower($args['module']);
      $command = strtolower($args['command']);
      $args = isset($args['args']) ? $args['args'] : [];
    }
    return [$module, $command, $args];
  }
  
  /**
   * Initializes the framework.
   *
   * @access private
   */
  private function connect()
  {
    require_once($this->root . $this->core);
    $a = \Aleph::init($this->root);
    \Aleph::errorHandling(false);
    foreach ($this->configs as $file => $editable) $a->setConfig($file);
  }
  
  /**
   * Renders the configurator's GUI.
   *
   * @access private
   */
  private function show(array $vars)
  {
    extract($vars);
    require(__DIR__ . '/../html/configurator.html');
    exit;
  }
}