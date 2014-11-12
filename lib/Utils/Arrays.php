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
 * Contains the set of static methods for facilitating the work with arrays.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
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
   * @param mixed $key1 - the key of the first element.
   * @param mixed $key2 - the key of the second element.
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
  
  /**
   * Inserts a value or an array to the input array at the specified position.
   *
   * @param array $array - the input array in which a value will be inserted.
   * @param mixed $value - the inserting value.
   * @param integer $offset - the position in the first array.
   * @access public
   */
  public static function insert(array &$array, $value, $offset = 0)
  {
    $array = array_slice($array, 0, $offset, true) + (array)$value + array_slice($array, $offset, null, true);
  }
  
  /**
   * Merges two arrays recursively without formation of duplicate values having the same keys.
   *
   * @param array $a1 - the first array to merge.
   * @param array $a2 - the second array to merge.
   * @return array
   * @access public
   * @static
   */
  public static function merge(array $a1, array $a2)
  {
    foreach ($a2 as $k => $v)
    {
      if (is_array($v) && isset($a1[$k]) && is_array($a1[$k]))
      {
        $a1[$k] = static::merge($a1[$k], $v);
      }
      else
      {
        $a1[$k] = $v;
      }
    }
    return $a1;
  }
  
  /**
   * Completely removes the element of a multidimensional array, defined by its keys.
   *
   * @param array $array - the array from which an element will be removed.
   * @param array $keys - the element keys.
   * @access public
   */
  public static function unsetByKeys(array &$array, array $keys)
  {
    $key = array_shift($keys);
    if (array_key_exists($key, $array))
    {
      if ($keys && is_array($array[$key])) 
      {
        static::unsetByKeys($array[$key], $keys);
        if (!$array[$key]) unset($array[$key]);
      }
      else unset($array[$key]);
    }
  }
  
  /**
   * Converts tree-like structure of the following form
   *
   * [
   *   'node_1' => ['node' => 'node 1'],
   *   'node_2' => ['parent' => 'node_1', 'node' => 'node 2'],
   *   'node_3' => ['parent' => 'node_2', 'node' => 'node 3'],
   *   ...
   *   'node_N' => ['parent' => 'node_N-1', 'node' => 'node_N']
   * ]
   *
   * into nested form
   *
   * [
   *   ['node_1'] => 
   *     [
   *       ['node'] => 'node 1',
   *       ['children'] =>
   *         [
   *           ['node_2'] =>
   *             [
   *               ['parent'] => 'node_1',
   *               ['node'] => 'node 2',
   *               ['children'] => 
   *                 [
   *                   ['node_3'] => 
   *                     [     
   *                       ['parent'] => 'node_2',
   *                       ['node'] => 'node 3',
   *                       ['children'] =>
   *                         [
   *                           ...
   *                             ['node_N'] => 
   *                               [
   *                                 ['parent'] => 'node_3',
   *                                 [node] => 'node_N'
   *                               ]
   *                           ...
   *                         ]
   *                     ]
   *                 ]
   *             ]
   *         ]
   *     ]
   * ]
   *
   * @param array $nodes - the original flat tree-like structure.
   * @param string $parent - the element key of a node which is parent identifier of the parent node.
   * @param string $children - the element key of a node which will contain all node children.
   * @return array
   * @access public
   * @static
   */
  public static function makeNested(array $nodes, $parent = 'parent', $children = 'children')
  {
    $tree = [];
    foreach ($nodes as $ID => &$node)
    {
      if (isset($node[$parent])) $nodes[$node[$parent]][$children][$ID] = &$node;
      else $tree[$ID] = &$node; 
    }
    return $tree;
  }
  
  /**
   * Converts the nested tree-like structure into the flat tree-like structure.
   * The result of performing this method is opposite of the result of performing makeNested() method.
   *
   * @param array $nodes - the original nested tree-like structure.
   * @param string $parent - the element key of a node which will be parent identifier of the parent node.
   * @param string $children - the element key of a node which contains all node children.
   */
  public static function makeFlat(array $nodes, $parent = 'parent', $children = 'children')
  {
    $tree = [];
    $reduce = function(array $nodes, $parentID = null) use(&$reduce, &$tree, $parent, $children)
    {
      foreach ($nodes as $ID => $node)
      {
        if ($parentID !== null) $node[$parent] = $parentID;
        $tree[$ID] = $node;
        if (isset($node[$children]))
        {
          $reduce($node[$children], $ID);
          unset($tree[$ID][$children]);
        }
      }
    };
    $reduce($nodes);
    return $tree;
  }
}