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

/**
 * The configurations module that allows to configure your application.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.configuration
 */
class Config extends Module
{
  /**
   * The default configuration of the application.
   *
   * @var array $defaultConfiguration
   * @access private
   */
  private $defaultConfiguration = [
    'debugging' => true,
    'logging' => true,
    'templateDebug' => 'lib/_templates/debug.tpl',
    'templateBug' => 'lib/_templates/bug.tpl',
    'autoload' => [
      'type' => 'PSR-4',
      'namespaces' => [
        'Aleph\MVC' => [
          'app/pages'
        ]
      ]
    ],
    'cache' => [
      'type' => 'file',
      'directory' => 'cache',
      'gcProbability' => 33.333
    ],
    'dirs' => [
      'application' => 'app',
      'framework' => 'lib',
      'logs' => 'app/tmp/logs',
      'cache' => 'app/tmp/cache',
      'temp' => 'app/tmp/null',
      'js' => 'app/inc/js',
      'css' => 'app/inc/css',
      'tpl' => 'app/inc/tpl'
    ],
    'db' => [
      'logging' => true,
      'log' => 'app/tmp/sql.log',
      'cacheExpire' => 0,
      'cacheGroup' => 'db'
    ],
    'ar' => [
      'cacheExpire' => -1,
      'cacheGroup' => 'ar'
    ],
    'mvc' => [
      'locked' => false,
      'unlockKey' => 'iwanttosee',
      'unlockKeyExpire' => 108000,
      'templateLock' => 'lib/_templates/bug.tpl'
    ],
    'pom' => [
      'cacheEnabled' => false,
      'cacheGroup' => 'pom',
      'charset' => 'utf-8',
      'namespaces' => [
        'c' => 'Aleph\Web\POM'
      ],
      'ppOpenTag' => '<![PP[',
      'ppCloseTag' => ']PP]!>',
    ]
  ];
  
  /**
   * The basic configuration variables.
   */
  private $common = [
    'logging',
    'debugging',
    'templateDebug',
    'templateBug',
    'customDebugMethod',
    'customLogMethod',
    'dirs',
    'autoload',
    'cache',
    'db',
    'ar',
    'mvc',
    'pom'
  ];
  
  /**
   * Initializes the module.
   *
   * @access public
   */
  public function init()
  {
    if (Configurator::isFirstRequest())
    {
      $n = 0; $nums = [];
      foreach ($this->cfg->getConfigs() as $file => $editable)
      {
        if (!file_exists($file))
        {
          $pinfo = pathinfo($file);
          if (!file_exists($pinfo['dirname'])) mkdir($pinfo['dirname'], 07775, true);
          if (strtolower($pinfo['extension']) == 'php') file_put_contents($file, '<?php' . PHP_EOL . PHP_EOL . 'return [];');
          else file_put_contents($file, '');
          $nums[] = $n;
        }
        $n++;
      }
      if ($nums) foreach ($nums as $n) $this->saveConfig($this->defaultConfiguration, ['file' => $n], false);
    }
  }
  
  /**
   * Performs the given command.
   *
   * @param string $command - the command name.
   * @param array $args - the command arguments.
   * @access public
   * @abstract
   */
  public function process($command, array $args = null)
  {
    switch ($command)
    {
      case 'show':
        $n = isset($args['file']) ? (int)$args['file'] : 0;
        $file = $this->getConfigFile($n);
        if ($file === false) $this->error('The configuration file of number ' . $n . ' does not exist.');
        else
        {
          if (Configurator::isCLI()) $this->write(PHP_EOL . print_r(\Aleph::getInstance()->setConfig($file, null, true), true));
          else echo $this->renderConfig($file);
        }
        break;
      case 'save':
        if (Configurator::isCLI())
        {
          $this->showCommandHelp();
          break;
        }
        $cfg = $args['config'];
        $cfg['debugging'] = (bool)$cfg['debugging'];
        $cfg['logging'] = (bool)$cfg['logging'];
        $cfg['autoload']['search'] = (bool)$cfg['autoload']['search'];
        $cfg['autoload']['unique'] = (bool)$cfg['autoload']['unique'];
        if (isset($cfg['autoload']['timeout']) && (int)$cfg['autoload']['timeout'] > 0) $cfg['autoload']['timeout'] = (int)$cfg['autoload']['timeout'];
        if (isset($cfg['autoload']['namespaces'])) 
        {
          $cfg['autoload']['namespaces'] = json_decode($cfg['autoload']['namespaces'], true);
          if (!is_array($cfg['autoload']['namespaces'])) unset($cfg['autoload']['namespaces']); 
        }
        if (isset($cfg['autoload']['directories'])) 
        {
          $cfg['autoload']['directories'] = json_decode($cfg['autoload']['directories'], true);
          if (!is_array($cfg['autoload']['directories'])) unset($cfg['autoload']['directories']); 
        }
        if (isset($cfg['autoload']['exclusions'])) 
        {
          $cfg['autoload']['exclusions'] = json_decode($cfg['autoload']['exclusions'], true);
          if (!is_array($cfg['autoload']['exclusions'])) unset($cfg['autoload']['exclusions']); 
        }
        if ($cfg['cache']['type'] == 'memory')
        {
          if (isset($cfg['cache']['servers'])) 
          {
            $cfg['cache']['servers'] = json_decode($cfg['cache']['servers'], true);
            if (!is_array($cfg['cache']['servers'])) unset($cfg['cache']['servers']);
          }
        }
        $cfg['mvc']['locked'] = (bool)$cfg['mvc']['locked'];
        $cfg['pom']['cacheEnabled'] = (bool)$cfg['pom']['cacheEnabled'];
        if (isset($cfg['pom']['namespaces'])) 
        {
          $cfg['pom']['namespaces'] = json_decode($cfg['pom']['namespaces'], true);
          if (!is_array($cfg['pom']['namespaces'])) unset($cfg['pom']['namespaces']); 
        }
        $cfg['db']['logging'] = (bool)$cfg['db']['logging'];
        if (isset($cfg['db']['cacheExpire'])) $cfg['db']['cacheExpire'] = (int)$cfg['db']['cacheExpire'];
        if (isset($cfg['ar']['cacheExpire'])) $cfg['ar']['cacheExpire'] = (int)$cfg['ar']['cacheExpire'];
        $props = isset($cfg['custom']) ? $cfg['custom'] : [];
        unset($cfg['custom']);
        foreach ($props as $prop => $value)
        {
          $cfg[$prop] = $value != '' ? json_decode($value, true) : '';
        }
        $this->saveConfig($cfg, $args);
        break;
      case 'update':
        $cfg = [];
        if (false !== $file = $this->getConfigFile(isset($args['file']) ? (int)$args['file'] : 0))
        {
          $cfg = \Aleph::getInstance()->setConfig($file, null, true);
          if (isset($args['property']) && is_array($args['property']))
          {
            for ($i = 0, $len = count($args['property']); $i < $len; $i += 2)
            {
              $c = &$cfg;
              foreach (explode('.', $args['property'][$i]) as $prop) $c = &$c[$prop];
              $c = $args['property'][$i + 1];
            }
          }
        }
        if ($this->saveConfig($cfg, $args)) $this->write(PHP_EOL . 'The configuration file has been successfully updated.' . PHP_EOL);
        break;
      case 'restore':
        if ($this->saveConfig($this->defaultConfiguration, $args)) $this->write(PHP_EOL . 'The configuration file has been successfully restored.' . PHP_EOL);
        break;
      default:
        $this->showCommandHelp();
        break;
    }
  }
  
  /**
   * Returns HTML/CSS/JS data for the module GUI.
   *
   * @access public
   * @return array
   */
  public function getData()
  {
    $a = \Aleph::getInstance();
    $current = $a->getConfig();
    $cfg = $a->setConfig(key($this->cfg->getConfigs()), null, true);
    $a->setConfig($current, null, true);
    $data = ['configs' => array_keys($this->cfg->getConfigs()),
             'editable' => (bool)current($this->cfg->getConfigs()),
             'common' => $this->common,
             'cfg' => $cfg];
    return ['js' => 'config/js/config.js', 'html' => 'config/html/config.html', 'data' => $data];
  }
  
  /**
   * Returns command help of the module.
   *
   * @return string
   * @access public   
   */
  public function getCommandHelp()
  {
    return <<<HELP

\e[33mAllows to change one or more configuration properties or restore the entire config file to the default settings.\e[0m

\e[36mThe use cases:\e[0m

    1. \e[32mcfg config show [--file FILE_NUMBER]\e[0m
    
       Outputs the configuration data of the given configuration file.

    2. \e[32mcfg config restore [--file FILE_NUMBER]\e[0m
    
       Restores the configuraton file of number \e[30;1mFILE_NUMBER\e[0m. The list of the configuration files is located in models/config/config.ini file.

    3. \e[32mcfg config update [--file FILE_NUMBER] [--property PROPERTY_NAME PROPERTY_VALUE ...]\e[0m

       Changes value of the configuration property \e[30;1mPROPERTY_NAME\e[0m to new value \e[30;1mPROPERTY_VALUE\e[0m in the configuration file of number \e[30;1mFILE_NUMBER.\e[0m
       You can change more than one property at once.

HELP;
  }
  
  /**
   * Saves the configuration data in file.
   *
   * @param array $cfg - the configuration data.
   * @param array $args - the command parameters.
   * @param boolean $render - determines whether the GUI should be updated or not.
   * @return boolean - returns TRUE on success and FALSE on failure.
   * @access private
   */
  private function saveConfig(array $cfg, array $args, $render = true)
  {
    $n = isset($args['file']) ? (int)$args['file'] : 0;
    $file = $this->getConfigFile($n);
    if ($file && (!$render || $this->cfg->getConfigs()[$file]))
    {
      if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'php')
      {
        $res = '';
        $tokens = token_get_all(file_get_contents($file));
        foreach (array_reverse($tokens) as $i => $token) if ($token[0] == T_RETURN) break;
        $i = count($tokens) - $i - 1;
        foreach ($tokens as $j => $token)
        {
          if ($j == $i) break;
          $res .= is_array($token) ? $token[1] : $token;
        }
        $res .= 'return ' . $this->formArray($cfg) . ';';
      }
      else
      {
        $res = $this->formINIFile($cfg);
      }
      file_put_contents($file, $res);
      if ($render && !Configurator::isCLI()) echo $this->renderConfig($file);
      return true;
    }
    $this->error('The configuration file of number ' . $n . ' does not exist or cannot be modified.');
    return false;
  }
  
  /**
   * Generates configuration file in INI format.
   *
   * @param array $a - the configuration data.
   * @return string - the content of the configuration file.
   * @access private
   */
  private function formINIFile(array $a)
  {
    $tmp1 = $tmp2 = [];
    foreach ($a as $k => $v)
    {
      if (is_array($v))
      {
        $tmp = ['[' . $k . ']'];
        foreach ($v as $kk => $vv)
        {
          if (!is_numeric($vv))
          {
            if (is_array($vv)) $vv = '"' . addcslashes(json_encode($vv), '"') . '"';
            else if (is_bool($vv)) $vv = $vv ? 1 : 0;
            else if ($vv === null) $vv = '""';
            else if (is_string($vv)) $vv = '"' . addcslashes($vv, '"') . '"';
          }
          $tmp[] = str_pad($kk, 30) . ' = ' . $vv;
        }
        $tmp2[] = PHP_EOL . implode(PHP_EOL, $tmp);
      }
      else
      {
        if (!is_numeric($v))
        {
          if ($v === null) $v = '""';
          else if (is_bool($v)) $v = $v ? 1 : 0;
          else if (is_string($v)) $v = '"' . addcslashes($v, '"') . '"';
        }
        $tmp1[] = str_pad($k, 30) . ' = ' . $v;
      }
    }
    return implode(PHP_EOL, array_merge($tmp1, $tmp2));
  }
  
  /**
   * Returns the PHP string representation of the given array.
   *
   * @param array $a - the given array.
   * @param integer $indent
   * @param integer $tab
   * @return string
   * @access private
   */
  private function formArray(array $a, $indent = 0, $tab = 2)
  {
    $tmp = [];
    $indent += $tab;
    $isInteger = array_keys($a) === range(0, count($a) - 1);
    foreach ($a as $k => $v)
    {
      if (is_string($k)) $k = $this->formString($k);
      if (is_array($v)) $v = $this->formArray($v, $indent, $tab);
      else if (is_string($v)) $v = $this->formString($v);
      else if (is_bool($v)) $v = $v ? 'true' : 'false';
      else if (is_float($v)) $v = str_replace(',', '.', $v);
      else if ($v === null) $v = 'null';
      $tmp[] = $isInteger ? $v : $k . ' => ' . $v;
    }
    $space = PHP_EOL . str_repeat(' ', $indent);
    return '[' . $space . implode(', ' . $space, $tmp) . PHP_EOL . str_repeat(' ', $indent - $tab) . ']';
  }
  
  /**
   * Returns PHP string representation of the variable.
   *
   * @var string $value - some string value.
   * @return string
   * @access private
   */
  private function formString($value)
  {
    $flag = false;
    $rep = ['\\' => '\\\\', "\n" => '\n', "\r" => '\r', "\t" => '\t', "\v" => '\v', "\e" => '\e', "\f" => '\f'];
    $value = preg_replace_callback('/([^\x20-\x7e]|\\\\)/', function($m) use($rep, &$flag)
    {
      $m = $m[0];
      if ($m == '\\') return '\\\\';
      $flag = true;
      return isset($rep[$m]) ? $rep[$m] : '\x' . str_pad(dechex(ord($m)), 2, '0', STR_PAD_LEFT);
    }, $value);
    if ($flag) return '"' . str_replace('"', '\"', $value) . '"';
    return "'" . str_replace("'", "\\'", $value) . "'";
  }
  
  /**
   * Returns the configuration file by its number.
   *
   * @param integer $n - the number of the configuration file.
   * @return boolean
   * @access private
   */
  private function getConfigFile($n)
  {
    $files = array_keys($this->cfg->getConfigs());
    return isset($files[$n]) ? $files[$n] : false;
  }
  
  /**
   * Renders the module GUI.
   *
   * @param integer $file - the configuration file.
   * @return string - the rendered HTML.
   * @access private
   */
  private function renderConfig($file)
  {
    $data = ['cfg' => \Aleph::getInstance()->setConfig($file, null, true), 
             'editable' => $this->cfg->getConfigs()[$file],
             'common' => $this->common];
    return $this->render(__DIR__ . '/html/settings.html', $data);
  }
}