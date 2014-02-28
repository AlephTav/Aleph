<?php

namespace Aleph\Configurator;

class Info extends Module
{ 
  public function init()
  {
    if (isset($_GET['info']))
    {
      $this->process('get');
      exit;
    }
  }
  
  public function process($command, array $args = null)
  {
    switch ($command)
    {
      case 'get':
        phpinfo(isset($args['what']) ? $args['what'] : -1);
        break;
      default:
        if (Configurator::isCLI()) echo $this->getCommandHelp();
        break;
    }
  }
  
  public function getData()
  {
    return ['html' => 'info/html/info.html'];
  }
  
  public function getCommandHelp()
  {
    return <<<'HELP'

Allows to clean or regenerate classmap of the web application.

Usage: cfg info get [--what WHAT]
    
       Displays information about the current state of PHP.
       The output may be customized by passing constant WHAT which corresponds to the appropriate parameter of function phpinfo().

HELP;
  }
}