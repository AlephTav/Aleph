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
    set_time_limit(0);
    $errors = $cfg = array();
    if (!file_exists($this->config['path']['aleph'])) $errors[] = 'File aleph.php is not found.';
    if (!file_exists($this->config['path']['config'])) $errors[] = 'File config.php is not found.';
    if (strtolower(pathinfo($this->config['path']['config'], PATHINFO_EXTENSION)) == 'php') 
    {
      $cfg = require_once($this->config['path']['config']);
    }
    else
    {
      $cfg = parse_ini_file($this->config['path']['config'], true);
      if ($data === false) $errors[] = 'Config file is corrupted.';
    }
    $this->show(array('errors' => $errors, 'cfg' => $cfg));
  }
  
  public function process()
  {
    if (!self::isAjaxRequest()) return;
    $args = self::getArguments();
    if (empty($args['method'])) return;
    require_once($this->config['path']['aleph']);
    $a = \Aleph::init();
    \Aleph::debug(false);
    $a->config(require($this->config['path']['config']));
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
    }
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