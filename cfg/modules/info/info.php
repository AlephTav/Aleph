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
    return <<<HELP

\e[33mOutputs information about PHP's configuration.\e[0m

\e[36mUsage:\e[0m \e[32mcfg info get [--what WHAT]\e[0m
    
       Displays information about the current state of PHP.
       The output may be customized by passing constant \e[30;1mWHAT\e[0m which corresponds to the appropriate parameter of function phpinfo().

HELP;
  }
}