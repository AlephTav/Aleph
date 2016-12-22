<?php
/**
 * Copyright (c) 2013 - 2016 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2016 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Utils;

/**
 * Contains the set of static methods for facilitating the work with arrays.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.1.3
 * @package aleph.utils
 */
class Arr
{
    /**
     * Default key delimiter in composite array keys.
     */
    const DEFAULT_KEY_DELIMITER = '.';

    /**
     * Returns TRUE if the given array has sequential integer keys and FALSE otherwise.
     *
     * @param array $arr
     * @return bool
     */
    public static function isSequential(array $arr) : bool
    {
        if ($arr) {
            $n = 0;
            foreach ($arr as $k => $v) {
                if ($n !== $k) {
                    return false;
                }
                ++$n;
            }
            return true;
        }
        return false;
    }

    /**
     * Returns TRUE if the given array has only integer keys and FALSE otherwise.
     *
     * @param array $arr
     * @return bool
     */
    public static function isNumeric(array $arr) : bool
    {
        if ($arr) {
            foreach ($arr as $k => $v) {
                if (!is_int($k)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Returns TRUE if the given array has only string keys and FALSE otherwise.
     *
     * @param array $arr
     * @return bool
     */
    public static function isAssoc(array $arr) : bool
    {
        if ($arr) {
            foreach ($arr as $k => $v) {
                if (!is_string($k)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Returns TRUE if the given array has mixed (integer and string) keys and FALSE otherwise.
     *
     * @param array $arr
     * @return bool
     */
    public static function isMixed(array $arr) : bool
    {
        $count = count($arr);
        if ($count == 0) {
            return false;
        }
        if ($count == 1) {
            return true;
        }
        $int = $str = 1;
        foreach ($arr as $k => $v) {
            if (is_int($k)) {
                if ($int) {
                    --$int;
                    if ($str == 0) {
                        return true;
                    }
                }
            } else if ($str) {
                --$str;
                if ($int == 0) {
                    return true;
                }
            }
        }
        return $int == $str;
    }

    /**
     * Returns element value of a multidimensional array, defined by its compound key.
     *
     * @param array $arr The multidimensional array.
     * @param array|string $keys An array of the element's elementary keys or compound key (i.e. keys, separated by dot).
     * @param mixed $default The default value of an element if it don't exist.
     * @param string $delimiter The key delimiter in composite keys.
     * @return mixed
     */
    public static function get(array $arr, $keys, $default = null, string $delimiter = '')
    {
        $a = $arr;
        $keys = is_array($keys) ? $keys :
            explode($delimiter === '' ? static::DEFAULT_KEY_DELIMITER : $delimiter, $keys);
        foreach ($keys as $key) {
            if (!is_array($a) || !array_key_exists($key, $a)) {
                return $default;
            }
            $a = $a[$key];
        }
        return $a;
    }

    /**
     * Sets new value of an element of a multidimensional array, defined by its compound key.
     *
     * @param array $arr The multidimensional array.
     * @param array|string $keys An array of the element's elementary keys or compound key (i.e. keys, separated by dot).
     * @param mixed $value The new value of an array element.
     * @param bool $merge Determines whether the old element value should be merged with new one.
     * @param string $delimiter The key delimiter in composite keys.
     * @return void
     */
    public static function set(array &$arr, $keys, $value, bool $merge = false, string $delimiter = '')
    {
        $a = &$arr;
        $keys = is_array($keys) ? $keys :
            explode($delimiter === '' ? static::DEFAULT_KEY_DELIMITER : $delimiter, $keys);
        foreach ($keys as $key) {
            if (!is_array($a)) {
                $a = [];
            }
            $a = &$a[$key];
        }
        if ($merge && is_array($a) && is_array($value)) {
            $a = static::merge($a, $value);
        } else {
            $a = $value;
        }
    }

    /**
     * Checks whether an element of a multidimensional array exists or not.
     *
     * @param array $arr The multidimensional array.
     * @param array|string $keys An array of the element's elementary keys or compound key (i.e. keys, separated by dot).
     * @param string $delimiter The key delimiter in composite keys.
     * @return bool
     */
    public static function has(array $arr, $keys, string $delimiter = '') : bool
    {
        $a = $arr;
        $keys = is_array($keys) ? $keys :
            explode($delimiter === '' ? static::DEFAULT_KEY_DELIMITER : $delimiter, $keys);
        foreach ($keys as $key) {
            if (!is_array($a) || !array_key_exists($key, $a)) {
                return false;
            }
            $a = $a[$key];
        }
        return true;
    }

    /**
     * Removes an element of a multidimensional array, defined by its compound key.
     *
     * @param array $arr The array from which an element will be removed.
     * @param array|string $keys An array of the element's elementary keys or compound key (i.e. keys, separated by dot).
     * @param bool $removeEmptyParent Determines whether the parent element should be removed if it no longer contains
     * elements after removing the given one.
     * @param string $delimiter The key delimiter in composite keys.
     * @return void
     */
    public static function remove(array &$arr, $keys, bool $removeEmptyParent = false, string $delimiter = '')
    {
        $keys = is_array($keys) ? $keys :
            explode($delimiter === '' ? static::DEFAULT_KEY_DELIMITER : $delimiter, $keys);
        if ($removeEmptyParent) {
            $key = array_shift($keys);
            if (array_key_exists($key, $arr)) {
                if ($keys && is_array($arr[$key])) {
                    self::remove($arr[$key], $keys, true);
                    if (!$arr[$key]) {
                        unset($arr[$key]);
                    }
                } else {
                    unset($arr[$key]);
                }
            }
        } else {
            $a = &$arr;
            $last = array_pop($keys);
            foreach ($keys as $key) {
                if (!is_array($a) || !array_key_exists($key, $a)) {
                    return;
                }
                $a = &$a[$key];
            }
            unset($a[$last]);
        }
    }

    /**
     * Swaps two elements of the array.
     *
     * @param array $arr The array in which the two elements will be swapped.
     * @param int|string $key1 The first element's key or index.
     * @param int|string $key2 The second element's key or index.
     * @param bool $index Determines whether $key1 and $key2 treated as element indexes.
     * @param bool $swapKeys Determines whether keys of the elements should also be swapped.
     * @return void
     */
    public static function swap(array &$arr, $key1, $key2, bool $index = false, bool $swapKeys = false)
    {
        if ($index) {
            $n1 = (int)$key1;
            $n2 = (int)$key2;
            if ($n2 === $n1) {
                return;
            }
        } else {
            $key1 = is_numeric($key1) ? (int)$key1 : (string)$key1;
            $key2 = is_numeric($key2) ? (int)$key2 : (string)$key2;
            if ($key1 === $key2) {
                return;
            }
        }
        if ($swapKeys) {
            $tmp = [];
            if ($index) {
                $key1 = key(array_slice($arr, $n1, 1, true)) ?? $n1;
                $key2 = key(array_slice($arr, $n2, 1, true)) ?? $n2;
            }
            foreach ($arr as $key => $value) {
                if ($key === $key1) {
                    $tmp[$key2] = $arr[$key2] ?? null;
                } else if ($key === $key2) {
                    $tmp[$key1] = $arr[$key1] ?? null;
                } else {
                    $tmp[$key] = $value;
                }
            }
            $arr = $tmp;
        } else {
            if ($index) {
                if ($n1 > $n2) {
                    $tmp = $n1;
                    $n1 = $n2;
                    $n2 = $tmp;
                }
                $n = 0;
                foreach ($arr as $key => $value) {
                    if ($n === $n1) {
                        $key1 = $key;
                    } else if ($n === $n2) {
                        $key2 = $key;
                        break;
                    }
                    $n++;
                }
            }
            $tmp = $arr[$key1] ?? null;
            $arr[$key1] = $arr[$key2] ?? null;
            $arr[$key2] = $tmp;
        }
    }

    /**
     * Inserts a value or an array to the input array at the specified position.
     *
     * @param array $arr The input array in which a value will be inserted.
     * @param mixed $value The inserting value.
     * @param int $offset The position in the first array.
     * @return void
     */
    public static function insert(array &$arr, $value, int $offset = 0)
    {
        $offset = $offset < 0 ? 0 : $offset;
        $arr = array_merge(array_slice($arr, 0, $offset, true), is_array($value) ? $value : [$value], array_slice($arr, $offset, null, true));
    }

    /**
     * Merges two arrays recursively.
     * This method merges values with equal integer keys and replaces values with the same string keys.
     *
     * @param array $a1 The first array to merge.
     * @param array $a2 The second array to merge.
     * @return array
     */
    public static function merge(array $a1, array $a2) : array
    {
        if ($a1) {
            foreach ($a2 as $k => $v) {
                if (is_int($k)) {
                    $a1[] = $v;
                } else if (is_array($v) && isset($a1[$k]) && is_array($a1[$k])) {
                    $a1[$k] = self::merge($a1[$k], $v);
                } else {
                    $a1[$k] = $v;
                }
            }
            return $a1;
        }
        return $a2;
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
     * @param array $nodes The original flat tree-like structure.
     * @param int|string $parent The element key of a node which is parent identifier of the parent node.
     * @param int|string $children The element key of a node which will contain all node children.
     * @return array
     */
    public static function makeNested(array $nodes, $parent = 'parent', $children = 'children') : array
    {
        $tree = [];
        foreach ($nodes as $ID => &$node) {
            if (isset($node[$parent])) {
                $nodes[$node[$parent]][$children][$ID] = &$node;
            } else {
                $tree[$ID] = &$node;
            }
        }
        return $tree;
    }

    /**
     * Converts the nested tree-like structure into the flat tree-like structure.
     * The result of performing this method is opposite of the result of performing makeNested() method.
     *
     * @param array $nodes The original nested tree-like structure.
     * @param int|string $parent The element key of a node which will be parent identifier of the parent node.
     * @param int|string $children The element key of a node which contains all node children.
     * @return array
     */
    public static function makeFlat(array $nodes, $parent = 'parent', $children = 'children') : array
    {
        $tree = [];
        $reduce = function(array $nodes, $parentID = null) use(&$reduce, &$tree, $parent, $children) {
            foreach ($nodes as $ID => $node) {
                if ($parentID !== null) {
                    $node[$parent] = $parentID;
                }
                $tree[$ID] = $node;
                if (isset($node[$children])) {
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
     * @param array $arr The array to iterate.
     * @param bool $iterateObjects Determines whether to iterate an objects (all objects will be converted to an array).
     * @return \Generator
     */
    public static function iterate(array $arr, $iterateObjects = false) : \Generator
    {
        foreach ($arr as $key => $value) {
            if ($iterateObjects && is_object($value)) {
                $value = get_object_vars($value);
            }
            yield $key => $value;
            if (is_array($value)) {
                yield from self::iterate($value);
            }
        }
    }
}