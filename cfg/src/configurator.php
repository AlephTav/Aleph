<?php

namespace Aleph;

use Aleph\Cache;

class Configurator
{ 
  private $config = null;
  
  public function __construct(array $config)
  {
    $this->config = $config;
  }

  public function init()
  {
    if (!self::isFirstRequest()) return;
    if (isset($_GET['info'])) 
    {
      phpinfo();
      exit;
    }
    if (isset($_GET['tests'])) 
    {
      $file = pathinfo($this->config['path']['aleph'], PATHINFO_DIRNAME) . '/_tests/test.php';
      if (file_exists($file)) require_once($file);
      exit;
    }
    set_time_limit(0);
    $errors = $cfg = array();
    if (!file_exists($this->config['path']['aleph'])) $errors[] = 'File aleph.php is not found.';
    if (!file_exists($this->config['path']['config'])) $errors[] = 'File config.php is not found.';
    if ($this->isPHPConfig()) 
    {
      $cfg = require_once($this->config['path']['config']);
    }
    else
    {
      $data = parse_ini_file($this->config['path']['config'], true);
      if ($data === false) $errors[] = 'Config file is corrupted.';
      $cfg = array();
      foreach ($data as $section => $properties)
      {
        if (is_array($properties)) 
        {
          foreach ($properties as $k => $v)
          {
            if (!is_array($v) && strlen($v) > 1 && ($v[0] == '[' || $v[0] == '{') && ($v[strlen($v) - 1] == ']' || $v[strlen($v) - 1] == '}'))
            {
              $tmp = json_decode($v, true);
              $v = $tmp !== null ? $tmp : $v;
            }
            $cfg[$section][$k] = $v;
          }
        }
        else $cfg[$section] = $properties;
      }
    }
    $this->show(array('errors' => $errors, 
                      'cfg' => $cfg, 
                      'common' => array('logging', 'debugging', 'dirs', 'templateDebug', 'templateBug', 'cache')));
  }
  
  public function process()
  {
    if (!self::isAjaxRequest()) return;
    $args = self::getArguments();
    if (empty($args['method'])) return;
    require_once($this->config['path']['aleph']);
    $a = \Aleph::init();
    \Aleph::debug(false);
    $a->config($this->isPHPConfig() ? require($this->config['path']['config']) : $this->config['path']['config']);
    $a->cache(Cache\Cache::getInstance());
    switch ($args['method'])
    {
      case 'cache.gc':
        $a->cache()->gc(100);
        break;
      case 'cache.clean':
        if (empty($args['group']) || $args['group'] == 'all') $a->cache()->clean();
        else
        {
          $group = $args['group'];
          if (!empty($args['custom'])) $a->cache()->cleanByGroup($group);
          else
          {
            $map = array('autoload' => '--autoload', 'localization' => '--localization', 'database' => '--db', 'pages' => '--pom');
            if (isset($map[$group])) $a->cache()->cleanByGroup($map[$group]);
          }
        }
        break;
      case 'config.save':
        $cfg = $args['config'];
        $cfg['debugging'] = (bool)$cfg['debugging'];
        $cfg['logging'] = (bool)$cfg['logging'];
        if ($cfg['cache']['type'] == 'memory')
        {
          if ($cfg['cache']['servers'] != '') $cfg['cache']['servers'] = json_decode($cfg['cache']['servers'], true);
        }
        $props = $cfg['custom'];
        unset($cfg['custom']);
        foreach ($props as $prop => $value)
        {
          $cfg[$prop] = $value != '' ? json_decode($value, true) : '';
        }
        $this->saveConfig($cfg);
        break;
      case 'config.restore':
        $cfg = array('debugging' => true,
                     'logging' => true,
                     'templateDebug' => 'lib/tpl/debug.tpl',
                     'cache' => array('gcProbability' => 33.333,
                                      'type' => 'file',
                                      'directory' => 'cache'),
                     'dirs' => array('application' => 'app',
                                     'framework' => 'lib',
                                     'logs' => 'app/tmp/logs',
                                     'cache' => 'app/tmp/cache',
                                     'temp' => 'app/tmp/null',
                                     'ar' => 'app/core/model/ar',
                                     'orm' => 'app/core/model/orm',
                                     'js' => 'app/inc/js',
                                     'css' => 'app/inc/css',
                                     'tpl' => 'app/inc/tpl',
                                     'elements' => 'app/inc/tpl/elements'));
        $this->saveConfig($cfg);
        break;
    }
  }
  
  private function saveConfig(array $cfg)
  {
    if ($this->isPHPConfig())
    {
      $res = '';
      $tokens = token_get_all(file_get_contents($this->config['path']['config']));
      foreach (array_reverse($tokens) as $i => $token) if ($token[0] == T_RETURN) break;
      $i = count($tokens) - $i - 1;
      foreach ($tokens as $j => $token)
      {
        if ($j == $i) break;
        $res .= is_array($token) ? $token[1] : $token;
      }
      $res .= 'return ' . $this->formArray($cfg, 13) . ';';
    }
    else
    {
      $res = $this->formINIFile($cfg);
    }
    file_put_contents($this->config['path']['config'], $res);
  }
  
  private function formINIFile(array $a)
  {
    $tmp1 = $tmp2 = array();
    foreach ($a as $k => $v)
    {
      if (is_array($v))
      {
        $tmp = array('[' . $k . ']');
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
  
  private function formArray(array $a, $indent = 6)
  {
    $tmp = array();
    foreach ($a as $k => $v)
    {
      if (is_string($k)) $k = "'" . addcslashes($k, "'") . "'";
      if (is_array($v)) $v = $this->formArray($v, $indent + strlen($k) + 10);
      else if (is_string($v)) $v = "'" . addcslashes($v, "'") . "'";
      else if (is_bool($v)) $v = $v ? 'true' : 'false';
      else if ($v === null) $v = 'null';
      $tmp[] = $k . ' => ' . $v;
    }
    return 'array(' . implode(',' . PHP_EOL . str_repeat(' ', $indent), $tmp) . ')';
  }
  
  private function isPHPConfig()
  {
    return strtolower(pathinfo($this->config['path']['config'], PATHINFO_EXTENSION)) == 'php';
  }
  
  private function show(array $vars)
  {
    extract($vars);
    require(__DIR__ . '/../html/configurator.html');
  }
  
  private static function getArguments()
  {
    return $_REQUEST;
  }
  
  private static function isAjaxRequest()
  {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
  }
  
  private static function isFirstRequest()
  {
    return $_SERVER['REQUEST_METHOD'] == 'GET' && !self::isAjaxRequest();
  }
}