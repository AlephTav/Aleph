<?php

namespace Aleph\Configurator;

class Log extends Module
{
  public function process($command, array $args = null)
  {
    switch ($command)
    {
      case 'clean':
        self::removeFiles(\Aleph::dir('logs'), true, false);
        if (!Configurator::isCLI()) echo self::render(__DIR__ . '/html/list.html', ['logs' => []]);
        else self::write(PHP_EOL . 'The log files have been successfully removed.' . PHP_EOL);
        break;
      case 'show':
        if (Configurator::isCLI()) self::write(PHP_EOL . print_r($this->getLogDirectories(), true));
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
        if (Configurator::isCLI()) self::write(PHP_EOL . print_r($files, true));
        else echo self::render(__DIR__ . '/html/sublist.html', ['files' => $files]);
        break;
      case 'details':
        if (empty($args['dir']) || empty($args['file'])) break;
        $file = \Aleph::dir('logs') . '/' . $args['dir'] . '/' . $args['file'];
        $res = unserialize(file_get_contents($file));
        if (Configurator::isCLI()) self::write(PHP_EOL . print_r($res, true));
        else echo self::render(__DIR__ . '/html/details.html', ['log' => $res, 'file' => $args['dir'] . ' ' . $args['file']]);
        break;
      default:
        $this->showCommandHelp();
        break;
    }
  }
  
  public function getData()
  {
    return ['js' => 'log/js/log.js', 'html' => 'log/html/log.html', 'data' => ['logs' => $this->getLogDirectories()]];
  }
  
  public function getCommandHelp()
  {
    return <<<HELP

\e[33mAllows to review the logs and remove them.\e[0m

\e[36mThe use cases:\e[0m

    1. \e[32mcfg log show\e[0m
    
       Outputs the log directories.

    2. \e[32mcfg log files [--dir DIR]\e[0m

       Outputs log files from the given log directory.
    
    3. \e[32mcfg log details [--dir DIR] [--file FILE]\e[0m

       Outputs detailed information from the particular log file.

    4. \e[32mcfg log clean\e[0m

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