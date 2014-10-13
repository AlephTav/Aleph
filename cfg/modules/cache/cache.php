<?php

namespace Aleph\Configurator;

class Cache extends Module
{
  public function process($command, array $args = null)
  {
    switch ($command)
    {
      case 'gc':
        \Aleph\Cache\Cache::getInstance()->gc(100);
        if (Configurator::isCLI()) echo PHP_EOL . 'The garbage collector has been successfully launched.' . PHP_EOL;
        break;
      case 'clean':
        $a = Configurator::getAleph();
        $cache = $a->getCache();
        if (isset($args['group'])) $cache->cleanByGroup($args['group']);
        else if (!empty($args['section']))
        {
          $group = $args['section'];
          if (isset($a[$group]))
          {
            $group = $a[$group];
            if (isset($group['cacheGroup'])) $cache->cleanByGroup($group['cacheGroup']);
          }
        }
        else
        {
          $cache->clean();
        }
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