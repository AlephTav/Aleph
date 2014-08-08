<?php
/**
 * Copyright (c) 2014 Aleph Tav
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
 * @copyright Copyright &copy; 2014 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Utils;

/**
 * This class contains some helpful methods to work with command line.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.utils
 */
class CLI
{
  /**
   * Returns values of command line options. Method returns FALSE if the script is not run from command line.
   *
   * @param array $options - names of the options. Each option can be an array of different names (aliases) of the same option.
   * @return array
   * @access public
   * @static
   */
  public static function getArguments(array $options)
  {
    if (PHP_SAPI !== 'cli') return false;
    $argv = $_SERVER['argv'];
    $argc = $_SERVER['argc'];
    $res = $opts = array();
    foreach ($options as $opt)
    {
      if (!is_array($opt)) $opts[$opt] = $opt;
      else foreach ($opt as $o) $opts[$o] = $o; 
    }
    for ($i = 1; $i < $argc; $i++)
    {
      if (isset($opts[$argv[$i]]))
      {
        $v = isset($argv[$i + 1]) && empty($opts[$argv[$i + 1]]) ? $argv[$i + 1] : '';
        foreach ((array)$opts[$argv[$i]] as $opt) $res[$opt] = $v;
      }
    }
    return $res;
  }
}