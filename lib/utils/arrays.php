<?php
/**
 * Copyright (c) 2012 Aleph Tav
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
 * @copyright Copyright &copy; 2012 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Utils;

/**
 * Contains the set of static methods for facilitating the work with arrays.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.utils
 */
class Arrays
{
  /**
   * Swaps the two elements of the array. The elements are determined by their index numbers.
   *
   * @param array $array - the array in which the two elements will be swapped.
   * @param integer $n1 - the index number of the first element.
   * @param integer $n2 - the index number of the second element.
   * @access public
   */
  public static function swap(array &$array, $n1, $n2)
  {
    $keys = array_keys($array);
    static::aswap($array, $keys[$n1], $keys[$n2]);
  }
  
  /**
   * Swaps the two elements of the array. The elements are determined by their keys.
   *
   * @param array $array - the array in which the two elements will be swapped.
   * @param integer $key1 - the key of the first element.
   * @param integer $key2 - the key of the second element.
   * @access public
   */
  public static function aswap(array &$array, $key1, $key2)
  {
    $tmp = [];
    foreach ($array as $key => $value)
    {
      if ($key == $key1) $tmp[$key2] = $array[$key2];
      else if ($key == $key2) $tmp[$key1] = $array[$key1];
      else $tmp[$key] = $value;
    }
    $array = $tmp;
  }
}