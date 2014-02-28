<?php

namespace Aleph\Configurator;

class Cache extends Module
{ 
  public function init(){}
  
  public function process($command, array $args = null)
  {
    switch ($command)
    {
      case 'gc':
        \Aleph\Cache\Cache::getInstance()->gc(100);
        if (Configurator::isCLI()) echo PHP_EOL . 'The garbage collector has been successfully launched.' . PHP_EOL;
        break;
      case 'clean':
        $cache = \Aleph\Cache\Cache::getInstance();
        if (empty($args['group'])) $cache->clean();
        else $cache->cleanByGroup($args['group']);
        if (Configurator::isCLI()) echo PHP_EOL . 'The cache has been successfully cleaned' . PHP_EOL;
        break;
      default:
        if (Configurator::isCLI()) echo $this->getCommandHelp();
        break;
    }
  }
  
  public function getData()
  {
    return ['js' => 'cache/js/cache.js', 'html' => 'cache/html/cache.html'];
  }
  
  public function getCommandHelp()
  {
    return <<<'HELP'

Allows to run garbage collector and reset the cache according to the configuration file(s).

The use cases:

    1. cfg cache gc
    
       Launches the garbage collector.

    2. cfg cache clean [--group GROUP]

       Cleans the cache by the given group name.
       If no group name is defined, the all cache data will be removed.

HELP;
  }
}