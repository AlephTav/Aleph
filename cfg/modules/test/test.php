<?php

namespace Aleph\Configurator;

class Test extends Module
{ 
  public function init()
  {
    if (isset($_GET['test']))
    {
      $this->process('run');
      exit;
    }
  }
  
  public function process($command, array $args = null)
  {
    switch ($command)
    {
      case 'run':
        $file = pathinfo(self::normalizePath(Configurator::CORE), PATHINFO_DIRNAME) . '/_tests/test.php';
        if (file_exists($file)) require_once($file); 
        break;
      default:
        $this->showCommandHelp();
        break;
    }
  }
  
  public function getData()
  {
    return ['js' => 'test/js/test.js', 'html' => 'test/html/test.html'];
  }
  
  public function getCommandHelp()
  {
    return <<<HELP

\e[33mAllows to run the framework's auto tests.\e[0m

\e[36mUsage:\e[0m \e[32mcfg test run\e[0m
    
       Launches the auto tests.

HELP;
  }
}