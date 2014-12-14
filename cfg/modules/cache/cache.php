<?php
/**
 * Copyright (c) 2013 - 2015 Aleph Tav
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated 
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, 
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO 
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, 
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @link http://www.4leph.com
 * @copyright Copyright &copy; 2013 - 2015 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Configuration;

/**
 * Module for cache management.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.configuration
 */
class Cache extends Module
{
  /**
   * Performs the given command.
   *
   * @param string $command - the command name.
   * @param array $args - the command arguments.
   * @access public
   * @abstract
   */
  public function process($command, array $args = null)
  {
    switch ($command)
    {
      case 'gc':
        \Aleph\Cache\Cache::getInstance()->gc(100);
        $this->write(PHP_EOL . 'The garbage collector has been successfully launched.' . PHP_EOL);
        break;
      case 'clean':
        $a = \Aleph::getInstance();
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
        $this->write(PHP_EOL . 'The cache has been successfully cleaned' . PHP_EOL);
        break;
      default:
        $this->showCommandHelp();
        break;
    }
  }
  
  /**
   * Returns HTML/CSS/JS data for the module GUI.
   *
   * @access public
   * @return array
   */
  public function getData()
  {
    return ['js' => 'cache/js/cache.js', 'html' => 'cache/html/cache.html'];
  }
  
  /**
   * Returns command help of the module.
   *
   * @return string
   * @access public   
   */
  public function getCommandHelp()
  {
    return <<<HELP

\e[33mAllows to run garbage collector and reset the cache according to the configuration file(s).\e[0m

\e[36mThe use cases:\e[0m

    1. \e[32mcfg cache gc\e[0m
    
       Launches the garbage collector.

    2. \e[32mcfg cache clean [--group GROUP]\e[0m

       Cleans the cache by the given group name.
       If no group name is defined, the all cache data will be removed.

HELP;
  }
}