<?php

namespace Aleph\Configurator;

class Log extends Module
{ 
  public function init(){}
  
  public function process($command, array $args = null)
  {
    switch ($command)
    {
      case 'clean':
        self::removeFiles(\Aleph::dir('logs'), true, false);
        if (!Configurator::isCLI()) echo self::render(__DIR__ . '/html/list.html', ['logs' => []]);
        else echo PHP_EOL . 'The log files have been successfully removed.' . PHP_EOL;
        break;
      case 'show':
        if (Configurator::isCLI()) print_r($this->getLogDirectories());
        else echo self::render(__DIR__ . '/html/list.html', ['logs' => $this->getLogDirectories()]);
        break;
      case 'files':
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
        if (Configurator::isCLI()) print_r($files);
        else echo self::render(__DIR__ . '/html/sublist.html', ['files' => $files]);
        break;
      case 'details':
        if (empty($args['dir']) || empty($args['file'])) break;
        $file = \Aleph::dir('logs') . '/' . $args['dir'] . '/' . $args['file'];
        $res = unserialize(file_get_contents($file));
        if (Configurator::isCLI()) print_r($res);
        else echo self::render(__DIR__ . '/html/details.html', ['log' => $res, 'file' => $args['dir'] . ' ' . $args['file']]);
        break;
      default:
        if (Configurator::isCLI()) echo $this->getCommandHelp();
        break;
    }
  }
  
  public function getData()
  {
    return ['js' => 'log/js/log.js', 'html' => 'log/html/log.html', 'data' => ['logs' => $this->getLogDirectories()]];
  }
  
  public function getCommandHelp()
  {
    return <<<'HELP'

Allows to review the logs and remove them.

The use cases:

    1. cfg log show
    
       Outputs the log directories.

    2. cfg log files [--dir DIR]

       Outputs log files fron the given log directory.
    
    3. cfg log details [--dir DIR] [--file FILE]

       Outputs detailed information from the particular log file.

    4. cfg log clean

       Removes all log files.    

HELP;
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
}