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

namespace Aleph\Utils;

/**
 * Contains the set of static methods for facilitating the work with arrays.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.1.2
 * @package aleph.utils
 */
class Arr
{
    /**
     * Default key delimiter in composite array keys.
     */
    const DEFAULT_KEY_DELIMITER = '.';
    
    /**
     * Returns TRUE if the given array is numeric and FALSE otherwise.
     *
     * @param array $array
     * @return boolean
     * @access public
     * @static
     */
    public static function isNumeric(array $array)
    {
        return array_keys($array) === range(0, count($array) - 1);
    }
    
    /**
     * Returns element value of a multidimensional array, defined by its compound key.
     *
     * @param array $array - the multidimensional array.
     * @param array|string $keys - array of the element's elementary keys or compound key (i.e. keys, separated by dot).
     * @param mixed $default - the default value of an element if it don't exist.
     * @param string $delimiter - the key delimiter in composite keys.
     * @return mixed
     * @access public
     * @static
     */
    public static function get(array $array, $keys, $default = null, $delimiter = null)
    {
        $keys = is_array($keys) ? $keys : explode($delimiter === null ? static::DEFAULT_KEY_DELIMITER : $delimiter, $keys);
        $arr = $array;
        foreach ($keys as $key)
        {
            if (!is_array($arr) || !array_key_exists($key, $arr))
            {
                return $default;
            }
            $arr = $arr[$key];
        }
        return $arr;
    }
    
    /**
     * Sets new value of an element of a multidimensional array, defined by its compound key.
     *
     * @param array $array - the multidimensional array.
     * @param array|string $keys - array of the element's elementary keys or compound key (i.e. keys, separated by dot).
     * @param mixed $value - the new value of an array element.
     * @param boolean $merge - determines whether the old element value should be merged with new one.
     * @param string $delimiter - the key delimiter in composite keys.
     * @access public
     * @static
     */
    public static function set(array &$array, $keys, $value, $merge = false, $delimiter = null)
    {
        $arr = &$array;
        $keys = is_array($keys) ? $keys : explode($delimiter === null ? static::DEFAULT_KEY_DELIMITER : $delimiter, $keys);
        foreach ($keys as $key)
        {
            $arr = &$arr[$key];
        }
        if ($merge && is_array($arr) && is_array($value))
        {
            $arr = static::merge($arr, $value);
        }
        else
        {
            $arr = $value;
        }
    }
    
    /**
     * Checks whether an element of a multidimensional array exists or not.
     *
     * @param array $array - the multidimensional array.
     * @param array|string $keys - array of the element's elementary keys or compound key (i.e. keys, separated by dot).
     * @param string $delimiter - the key delimiter in composite keys.
     * @return boolean
     * @access public
     * @static
     */
    public static function has(array $array, $keys, $delimiter = null)
    {
        $arr = $array;
        $keys = is_array($keys) ? $keys : explode($delimiter === null ? static::DEFAULT_KEY_DELIMITER : $delimiter, $keys);
        foreach ($keys as $key)
        {
            if (!is_array($arr) || !array_key_exists($key, $arr))
            {
                return false;
            }
            $arr = $arr[$key];
        }
        return true;
    }
    
    /**
     * Removes an element of a multidimensional array, defined by its compound key.
     *
     * @param array $array - the array from which an element will be removed.
     * @param array|string $keys - array of the element's elementary keys or compound key (i.e. keys, separated by dot).
     * @param boolean $removeEmptyParent - determines whether the parent element should be removed if it no longer contains elements after removing the given one.
     * @param string $delimiter - the key delimiter in composite keys.
     * @access public
     * @static
     */
    public static function remove(array &$array, $keys, $removeEmptyParent = false, $delimiter = null)
    {
        $keys = is_array($keys) ? $keys : explode($delimiter === null ? static::DEFAULT_KEY_DELIMITER : $delimiter, $keys);
        if ($removeEmptyParent)
        {
            $key = array_shift($keys);
            if (array_key_exists($key, $array))
            {
                if ($keys && is_array($array[$key])) 
                {
                    self::remove($array[$key], $keys, true);
                    if (!$array[$key])
                    {
                        unset($array[$key]);
                    }
                }
                else
                {
                    unset($array[$key]);
                }
            }
        }
        else
        {
            $last = array_pop($keys);
            $arr = &$array;
            foreach ($keys as $key)
            {
                if (!is_array($arr))
                {
                    return;
                }
                $arr = &$arr[$key];
            }
            unset($arr[$last]);
        }
    }

    /**
     * Swaps two elements of the array.
     *
     * @param array $array - the array in which the two elements will be swapped.
     * @param integer $n1 - the first element's key or index.
     * @param integer $n2 - the second element's key or index.
     * @param boolean $index - determines whether $key1 and $key2 treated as element indexes. 
     * @param boolean $swapKeys - determines whether keys of the elements should also be swapped.
     * @access public
     * @static
     */
    public static function swap(array &$array, $key1, $key2, $index = false, $swapKeys = false)
    {
        if ($swapKeys)
        {
            $tmp = []; 
            if ($index)
            {
                $n1 = (int)$key1;
                $n2 = (int)$key2;
                if ($n2 === $n1)
                {
                    return $array;
                }
                $key1 = key(array_slice($array, $n1, 1, true));
                $key2 = key(array_slice($array, $n2, 1, true));
            }
            else
            {
                $key1 == is_numeric($key1) ? (int)$key1 : (string)$key1;
                $key2 == is_numeric($key2) ? (int)$key2 : (string)$key2;
            }
            foreach ($array as $key => $value)
            {
                if ($key === $key1)
                {
                    $tmp[$key2] = $array[$key2];
                }
                else if ($key === $key2)
                {
                    $tmp[$key1] = $array[$key1];
                }
                else
                {
                    $tmp[$key] = $value;
                }
            }
            $array = $tmp;
        }
        else
        {
            if ($index)
            {
                $n1 = (int)$key1;
                $n2 = (int)$key2;
                if ($n2 === $n1)
                {
                    return $array;
                }
                if ($n1 > $n2)
                {
                    $tmp = $n1;
                    $n1 = $n2;
                    $n2 = $tmp;
                }
                $n = 0;
                foreach ($array as $key => $value)
                {
                    if ($n === $n1)
                    {
                        $key1 = $key;
                    }
                    else if ($n === $n2)
                    {
                        $key2 = $key;
                        break;
                    }
                    $n++;
                }
            }
            $tmp = $array[$key1];
            $array[$key1] = $array[$key2];
            $array[$key2] = $tmp;
        }
    }
  
    /**
     * Inserts a value or an array to the input array at the specified position.
     *
     * @param array $array - the input array in which a value will be inserted.
     * @param mixed $value - the inserting value.
     * @param integer $offset - the position in the first array.
     * @access public
     * @static
     */
    public static function insert(array &$array, $value, $offset = 0)
    {
        $array = array_merge(array_slice($array, 0, $offset, true), is_array($value) ? $value : [$value], array_slice($array, $offset, null, true));
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
                $a1[$k] = self::merge($a1[$k], $v);
            }
            else
            {
                $a1[$k] = $v;
            }
        }
        return $a1;
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
     *     ['node_1'] => [
     *         ['node'] => 'node 1',
     *         ['children'] => [
     *             ['node_2'] => [
     *                 ['parent'] => 'node_1',
     *                 ['node'] => 'node 2',
     *                 ['children'] => [
     *                     ['node_3'] => [     
     *                         ['parent'] => 'node_2',
     *                         ['node'] => 'node 3',
     *                         ['children'] => [
     *                             ...
     *                             ['node_N'] => [
     *                                 ['parent'] => 'node_3',
     *                                 [node] => 'node_N'
     *                             ]
     *                             ...
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
            if (isset($node[$parent]))
            {
                $nodes[$node[$parent]][$children][$ID] = &$node;
            }
            else
            {
                $tree[$ID] = &$node; 
            }
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
     * @return array
     * @access public
     * @static
     */
    public static function makeFlat(array $nodes, $parent = 'parent', $children = 'children')
    {
        $tree = [];
        $reduce = function(array $nodes, $parentID = null) use(&$reduce, &$tree, $parent, $children)
        {
            foreach ($nodes as $ID => $node)
            {
                if ($parentID !== null)
                {
                    $node[$parent] = $parentID;
                }
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
    
    /**
     * Recursively iterates an array.
     *
     * @param array $array - the array to iterate.
     * @param boolean $iterateObjects - determines whether to iterate an objects (all objects will be converted to an array).
     * @return Generator
     * @access public
     * @static
     */
    public static function iterate(array $array, $iterateObjects = false)
    {
        foreach ($array as $key => $value)
        {
            if ($iterateObjects && is_object($value))
            {
                $value = get_object_vars($value);
            }
            yield $key => $value;
            if (is_array($value))
            {
                foreach (self::iterate($value) as $key => $value)
                {
                    yield $key => $value;
                }
            }
        }
    }
}