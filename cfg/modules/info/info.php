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
 * This module allows to view PHP information.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.configuration
 */
class Info extends Module
{
  /**
   * Initializes the module.
   *
   * @access public
   */
  public function init()
  {
    if (isset($_GET['info']))
    {
      $this->process('get');
      exit;
    }
  }
  
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
      case 'get':
        phpinfo(isset($args['what']) ? $args['what'] : -1);
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
    return ['html' => 'info/html/info.html'];
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

\e[33mOutputs information about PHP's configuration.\e[0m

\e[36mUsage:\e[0m \e[32mcfg info get [--what WHAT]\e[0m
    
       Displays information about the current state of PHP.
       The output may be customized by passing constant \e[30;1mWHAT\e[0m which corresponds to the appropriate parameter of function phpinfo().

HELP;
  }
}