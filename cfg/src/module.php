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
 * The base class of the configuration modules.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.configuration
 */
abstract class Module
{
  /**
   * The configurator instance.
   *
   * @var Aleph\Configuration\Configurator $cfg
   * @access protected
   */
  protected $cfg = null;

  /**
   * Constructor.
   *
   * @param Aleph\Configuration\Configurator $cfg - an instance of the configurator.
   * @access public
   */
  public function __construct(Configurator $cfg)
  {
    $this->cfg = $cfg;
  }

  /**
   * Initializes the module.
   *
   * @access public
   */
  public function init(){}

  /**
   * Performs the given command.
   *
   * @param string $command - the command name.
   * @param array $args - the command arguments.
   * @access public
   * @abstract
   */
  abstract public function process($command, array $args = null);
  
  /**
   * Returns HTML/CSS/JS data for the module GUI.
   *
   * @access public
   * @return array
   */
  abstract public function getData();
  
  /**
   * Returns command help of the module.
   *
   * @return string
   * @access public
   */
  abstract public function getCommandHelp();
  
  /**
   * Outputs the command help of the module.
   *
   * @access protected
   */
  protected function showCommandHelp()
  {
    $this->cfg->write($this->getCommandHelp());
  }
  
  /**
   * Removes files from the directory.
   *
   * @param string $dir - the directory where files will be removed.
   * @param boolean $recursively - determines whether files should also be removed from subdirectories.
   * @param boolean $removeDirectory - determines whether the given directory should also be removed after deleting all files.
   * @access protected
   */
  protected function removeFiles($dir, $recursively = true, $removeDirectory = true)
  {
    if (!is_dir($dir)) return false;
    $has = false;
    foreach (scandir($dir) as $item)
    {
      if ($item == '..' || $item == '.') continue;
      $file = $dir . '/' . $item;
      if (is_dir($file))
      {
        if ($recursively) $this->removeFiles($file, true, true);
        else $has = true;
      }
      else unlink($file);   
    }
    if ($removeDirectory && !$has) rmdir($dir);
  }
  
  /**
   * Renders the module GUI.
   *
   * @param string $file - the HTML template of the module.
   * @param array $vars - the template variables.
   * @return string - rendered HTML.
   * @access protected
   */
  protected function render($file, array $vars)
  {
    ${'(_._)'} = $file;
    unset($file);
    extract($vars);
    ob_start();
    require(${'(_._)'});
    return ob_get_clean();
  }
  
  /**
   * Outputs the text information.
   *
   * @param string $text - any text data.
   * @access protected
   */
  protected function write($text)
  {
    $this->cfg->write($text);
  }
  
  /**
   * Outputs the error message.
   *
   * @param string $message - the error message.
   * @access protected
   */
  protected function error($message)
  {
    if (Configurator::isCLI()) $this->cfg->write(PHP_EOL . "\e[31mERROR:\e[0m " . $message . PHP_EOL);
    else throw new \Exception($message);
  }
}