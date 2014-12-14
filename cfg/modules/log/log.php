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
 * Module for reviewing and management of the application logs. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.configuration
 */
class Log extends Module
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
      case 'clean':
        $this->removeFiles(\Aleph::dir('logs'), true, false);
        if (!Configurator::isCLI()) echo $this->render(__DIR__ . '/html/list.html', ['logs' => []]);
        else $this->write(PHP_EOL . 'The log files have been successfully removed.' . PHP_EOL);
        break;
      case 'show':
        if (Configurator::isCLI()) $this->write(PHP_EOL . print_r($this->getLogDirectories(), true));
        else echo $this->render(__DIR__ . '/html/list.html', ['logs' => $this->getLogDirectories()]);
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
        if (Configurator::isCLI()) $this->write(PHP_EOL . print_r($files, true));
        else echo $this->render(__DIR__ . '/html/sublist.html', ['files' => $files]);
        break;
      case 'details':
        if (empty($args['dir']) || empty($args['file'])) break;
        $file = \Aleph::dir('logs') . '/' . $args['dir'] . '/' . $args['file'];
        $res = unserialize(file_get_contents($file));
        if (Configurator::isCLI()) $this->write(PHP_EOL . print_r($res, true));
        else echo $this->render(__DIR__ . '/html/details.html', ['log' => $res, 'file' => $args['dir'] . ' ' . $args['file']]);
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
    return ['js' => 'log/js/log.js', 'html' => 'log/html/log.html', 'data' => ['logs' => $this->getLogDirectories()]];
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
  
  /**
   * Returns list of all log directories.
   *
   * @return array
   * @access private
   */
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