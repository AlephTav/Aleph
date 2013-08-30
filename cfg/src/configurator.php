<?php

namespace Aleph;

use Aleph\Cache;

class Configurator
{ 
  private $config = null;
  private $common = ['logging', 'debugging', 'dirs', 'templateDebug', 'templateBug', 'customDebugMethod', 'customLogMethod', 'cache', 'db', 'ar']; 
  
  public function __construct(array $config)
  {
    $this->config = $config;
    $this->config['path']['config'] = (array)$this->config['path']['config'];
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
      $file = pathinfo(self::path($this->config['path']['aleph']), PATHINFO_DIRNAME) . '/_tests/test.php';
      if (file_exists($file)) require_once($file);
      exit;
    }
    set_time_limit(0);
    $errors = [];
    // Checks existing of general files.
    $path = self::path($this->config['path']['aleph']);
    if (!file_exists($path)) $errors[] = 'File ' . $this->config['path']['aleph'] . ' is not found.';
    foreach ($this->config['path']['config'] as $file => $editable)
    {
      $path = self::path($file);
      if (!file_exists($path)) $errors[] = 'File ' . $file . ' is not found.';
    }
    if (count($errors)) $this->show(['errors' => $errors]);
    // Initializes the framework.
    $a = $this->connect();
    // Shows page.
    $this->show(['errors' => [], 
                 'config' => $this->config['path']['config'],
                 'classes' => $a->getClasses(),
                 'logs' => $this->getLogDirectories(),
                 'cfg' => $a->config(self::path(key($this->config['path']['config'])), true)->config(),
                 'editable' => (bool)current($this->config['path']['config']),
                 'common' => $this->common]);
  }
  
  public function process()
  {
    if (!self::isAjaxRequest()) return;
    $args = self::getArguments();
    if (empty($args['method'])) return;
    // Initializes the framework.
    $a = $this->connect();
    // Sets default cache.
    $a->cache(Cache\Cache::getInstance());
    // Performs action.
    $res = false;
    switch ($args['method'])
    {
      case 'config.file':
        $res = $this->renderConfig($this->getConfigFile($args['file']));
        break;
      case 'config.save':
        $cfg = $args['config'];
        $cfg['debugging'] = (bool)$cfg['debugging'];
        $cfg['logging'] = (bool)$cfg['logging'];
        if ($cfg['cache']['type'] == 'memory')
        {
          if ($cfg['cache']['servers'] != '') $cfg['cache']['servers'] = json_decode($cfg['cache']['servers'], true);
        }
        $cfg['db']['logging'] = (bool)$cfg['db']['logging'];
        if (isset($cfg['db']['cacheExpire'])) $cfg['db']['cacheExpire'] = (int)$cfg['db']['cacheExpire'];
        if (isset($cfg['ar']['cacheExpire'])) $cfg['ar']['cacheExpire'] = (int)$cfg['ar']['cacheExpire'];
        $props = $cfg['custom'];
        unset($cfg['custom']);
        foreach ($props as $prop => $value)
        {
          $cfg[$prop] = $value != '' ? json_decode($value, true) : '';
        }
        self::saveConfig($cfg, $this->getConfigFile($args['file']));
        break;
      case 'config.restore':
        $cfg = ['debugging' => true,
                'logging' => true,
                'templateDebug' => 'lib/tpl/debug.tpl',
                'cache' => ['type' => 'file',
                            'directory' => 'cache',
                            'gcProbability' => 33.333],
                'dirs' => ['application' => 'app',
                           'framework' => 'lib',
                           'logs' => 'app/tmp/logs',
                           'cache' => 'app/tmp/cache',
                           'temp' => 'app/tmp/null',
                           'ar' => 'app/core/model/ar',
                           'orm' => 'app/core/model/orm',
                           'js' => 'app/inc/js',
                           'css' => 'app/inc/css',
                           'tpl' => 'app/inc/tpl',
                           'elements' => 'app/inc/tpl/elements'],
                'db' => ['logging' => true,
                         'log' => 'app/tmp/sql.log',
                         'cacheExpire' => 0,
                         'cacheGroup' => '--db'],
                'ar' => ['cacheExpire' => -1,
                         'cacheGroup' => '--ar']];
        $file = $this->getConfigFile($args['file']);
        self::saveConfig($cfg, $file);
        $res = $this->renderConfig($file);
        break;
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
            $map = ['templates' => '--templates',
                    'localization' => '--localization',
                    'database' => isset($a['db']['cacheGroup']) ? $a['db']['cacheGroup'] : '--db',
                    'ar' => isset($a['ar']['cacheGroup']) ? $a['ar']['cacheGroup'] : '--ar',
                    'pages' => '--pom'];
            if (isset($map[$group])) $a->cache()->cleanByGroup($map[$group]);
          }
        }
        break;
      case 'sql.search':
        $res = self::render('sqlsearch.html', ['data' => $this->searchSQL($args['keyword'], $args['options'])]);
        break;
      case 'log.refresh':
        $res = self::render('loglist.html', ['logs' => $this->getLogDirectories()]);
        break;
      case 'log.clean':
        self::removeFiles(\Aleph::dir('logs'), false);
        $res = self::render('loglist.html', ['logs' => []]);
        break;
      case 'log.files':
        if (empty($args['dir'])) break;
        $dir = \Aleph::dir('logs') . '/' . $args['dir'];
        if (!is_dir($dir)) break;
        $files = [];
        foreach (scandir($dir) as $item)
        {
          if ($item == '..' || $item == '.') continue;
          if (date_create_from_format('d H.i.s', explode('#', $item)[0]) instanceof \DateTime) $files[] = $item;
        }
        sort($files);
        $files = array_reverse($files);
        $res = self::render('logsublist.html', ['files' => $files]);
        break;
      case 'log.details':
        if (empty($args['dir']) || empty($args['file'])) break;
        $file = \Aleph::dir('logs') . '/' . $args['dir'] . '/' . $args['file'];
        $res = unserialize(file_get_contents($file));
        $res = self::render('logdetails.html', ['log' => $res, 'file' => $args['dir'] . ' ' . $args['file']]);
        break;
    }
    if ($res !== false) echo $res;
  }
  
  private function searchSQL($keyword, array $options)
  {
    if (isset(\Aleph::getInstance()['db']['log']))
    {
      $file = \Aleph::dir(\Aleph::getInstance()['db']['log']);
      if (!is_file($file)) return [];
    }
    $tmp = [];
    $fh = fopen($file, 'r');
    if (!$fh) return [];
    fgetcsv($fh);
    if ($keyword == '' && strlen($options['from']) == 0 && strlen($options['to']) == 0 && $options['mode'] != 'regexp')
    {
      while (($row = fgetcsv($fh)) !== false) $tmp[] = $row;
    }
    else
    {
      if ($options['mode'] == 'regexp' && @preg_match($keyword, '') === false) return ['error' => 'Regular expression is wrong'];
      $isMatched = function($n, $value) use ($keyword, $options)
      {
        if ($options['mode'] == 'regexp') return preg_match($keyword, $value);
        if ($n >= 4 && $n <= 7)
        {
          if (strlen($options['from']) == 0 && strlen($options['to']) == 0) return $keyword == $value;
          return (strlen($options['from']) == 0 || $options['from'] <= $value) && (strlen($options['to']) == 0 || $options['to'] >= $value);
        }
        if ($options['onlyWholeWord']) 
        {
          if ($options['caseSensitive']) return (string)$keyword === (string)$value;
          return strcasecmp($keyword, $value) == 0;
        }
        if ($options['caseSensitive']) return strpos($value, $keyword) !== false;
        return strripos($value, $keyword) !== false;
      };
      while (($row = fgetcsv($fh)) !== false)
      {
        foreach ($row as $n => $value)
        {
          if (($options['where'] < 0 || $n == $options['where']) && $isMatched($n, $value))
          {
            $tmp[] = $row;
            break;
          }
        }
      }
    }
    fclose($fh);
    return $tmp;
  }
  
  private function getConfigFile($n)
  {
    return array_keys($this->config['path']['config'])[$n];
  }
  
  private function renderConfig($file)
  {
    return self::render('config.html', ['cfg' => \Aleph::getInstance()->config(self::path($file), true)->config(), 
                                        'editable' => $this->config['path']['config'][$file],
                                        'common' => $this->common]);
  }
  
  private function getLogDirectories()
  {
    $logs = []; $dir = \Aleph::dir('logs');
    if (!is_dir($dir)) return [];
    foreach (scandir($dir) as $item)
    {
      if ($item == '..' || $item == '.') continue;
      if (date_create_from_format('Y F', $item) instanceof \DateTime) $logs[] = $item;
    }
    sort($logs);
    return array_reverse($logs);
  }
  
  private function connect()
  {
    require_once(self::path($this->config['path']['aleph']));
    $a = \Aleph::init();
    \Aleph::errorHandling(false);
    foreach ($this->config['path']['config'] as $file => $editable) $a->config(self::path($file));
    return $a;
  }
  
  private static function saveConfig(array $cfg, $file)
  {
    $file = self::path($file);
    if (self::isPHPFile($file))
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
      $res .= 'return ' . self::formArray($cfg, 8) . ';';
    }
    else
    {
      $res = self::formINIFile($cfg);
    }
    file_put_contents($file, $res);
  }
  
  private static function formINIFile(array $a)
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
  
  private static function formArray(array $a, $indent = 1)
  {
    $tmp = array();
    foreach ($a as $k => $v)
    {
      if (is_string($k)) $k = "'" . addcslashes($k, "'") . "'";
      if (!is_numeric($v))
      {
        if (is_array($v)) $v = self::formArray($v, $indent + strlen($k) + 5);
        else if (is_string($v)) $v = "'" . addcslashes($v, "'") . "'";
        else if (is_bool($v)) $v = $v ? 'true' : 'false';
        else if ($v === null) $v = 'null';
      }
      $tmp[] = $k . ' => ' . $v;
    }
    return '[' . implode(',' . PHP_EOL . str_repeat(' ', $indent), $tmp) . ']';
  }
  
  private static function removeFiles($dir, $removeDir = true)
  {
    if (!is_dir($dir)) return false;
    foreach (scandir($dir) as $item)
    {
      if ($item == '..' || $item == '.') continue;
      $file = $dir . '/' . $item;
      if (is_dir($file)) self::removeFiles($file, true);
      else unlink($file);   
    }
    if ($removeDir) rmdir($dir);
  }
  
  private static function show(array $vars)
  {
    extract($vars);
    require(__DIR__ . '/../html/configurator.html');
    exit;
  }
  
  private static function render($file, array $vars)
  {
    ${'(_._)'} = $file;
    extract($vars);
    ob_start();
    require(__DIR__ . '/../html/' . ${'(_._)'});
    return ob_get_clean();
  }
  
  private static function isPHPFile($file)
  {
    return strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'php';
  }
  
  private static function path($path)
  {
    if (strlen($path) == 0) return false;
    if ($path[0] != '/') $path = '/' . $path;
    return realpath($_SERVER['DOCUMENT_ROOT'] . $path);
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