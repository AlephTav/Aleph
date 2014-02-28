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
        $file = pathinfo(self::normalizePath(Configurator::CORE_PATH), PATHINFO_DIRNAME) . '/_tests/test.php';
        if (file_exists($file)) require_once($file); 
        break;
      default:
        if (Configurator::isCLI()) echo $this->getCommandHelp();
        break;
    }
  }
  
  public function getData()
  {
    return ['js' => 'test/js/test.js', 'html' => 'test/html/test.html'];
  }
  
  public function getCommandHelp()
  {
    return <<<'HELP'

Allows to run the framework's auto tests.

Usage: cfg test run
    
       Launches the auto tests.

HELP;
  }
}