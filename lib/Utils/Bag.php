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
 * Simple container for key/value pairs.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.utils
 */
class Bag implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * An array of key/value pairs.
     *
     * @var array $arr
     * @access protected
     */
    protected $arr = [];
    
    /**
     * The default key delimiter in composite array keys.
     *
     * @var string $delimiter
     * @access protected
     */
    protected $delimiter = null;
    
    /**
     * Constructor.
     *
     * @param array $arr - an array of key/value pairs.
     * @param string $delimiter - the default key delimiter in composite keys.
     * @access public
     */
    public function __construct(array $arr = [], $delimiter = Arr::DEFAULT_KEY_DELIMITER)
    {
        $this->arr = $arr;
        $this->delimiter = $delimiter;
    }
    
    /**
     * Returns the number of stored key/value pairs.
     *
     * @return integer
     * @access public
     */
    public function count()
    {
        return count($this->arr);
    }
    
    /**
     * Returns an iterator for key/value pairs.
     *
     * @return ArrayIterator
     * @access public
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->arr);
    }
    
    /**
     * Returns the array.
     *
     * @return array
     * @access public
     */
    public function all()
    {
        return $this->arr;
    }
    
    /**
     * Returns the array keys.
     *
     * @return array
     * @access public
     */
    public function keys()
    {
        return array_keys($this->arr);
    }
    
    /**
     * Returns the array values.
     *
     * @return array
     * @access public
     */
    public function values()
    {
        return array_values($this->arr);
    }
    
    /**
     * Replaces the current array by a new one.
     *
     * @param array $arr
     * @access public
     */
    public function replace(array $arr = [])
    {
        $this->arr = $arr;
    }
    
    /**
     * Adds new key/value pairs to the current set.
     *
     * @param array $arr
     * @access public
     */
    public function add(array $arr = [])
    {
        $this->arr = array_replace($this->arr, $arr);
    }
    
    /**
     * Merge existing key/value pairs with new set.
     *
     * @param array $arr
     * @access public
     */
    public function merge(array $arr = [])
    {
        $this->arr = Arr::merge($this->arr, $arr);
    }
    
    /**
     * Removes all key/value pairs.
     *
     * @access public
     */
    public function clean()
    {
        $this->arr = [];
    }
    
    /**
     * Returns value of an array element, defined by its compound key.
     *
     * @param array|string $key - array of the element's elementary keys or compound key (i.e. keys, separated by dot).
     * @param mixed $default - the default value of an element if it don't exist.
     * @param boolean $compositeKey - determines whether the key is compound key.
     * @param string $delimiter - the key delimiter in composite keys.
     * @return mixed
     * @access public
     */
    public function get($key, $default = null, $compositeKey = false, $delimiter = null)
    {
        if (!$compositeKey)
        {
            return array_key_exists($key, $this->arr) ? $this->arr[$key] : $default;
        }
        return Arr::get($this->arr, $key, $default, $delimiter === null ? $this->delimiter : $delimiter);
    }
    
    /**
     * Sets new value of an array element, defined by its compound key.
     *
     * @param array|string $key - array of the element's elementary keys or compound key (i.e. keys, separated by dot).
     * @param mixed $value - the new value of an array element.
     * @param boolean $merge - determines whether the old element value should be merged with new one.
     * @param boolean $compositeKey - determines whether the key is compound key.
     * @param string $delimiter - the key delimiter in composite keys.
     * @access public
     */
    public function set($key, $value, $merge = false, $compositeKey = false, $delimiter = null)
    {
        if (!$compositeKey)
        {
            if ($merge && is_array($value) && isset($this->arr[$key]) && is_array($this->arr[$key]))
            {
                $this->arr[$key] = Arr::merge($this->arr[$key], $value);
            }
            else
            {
                $this->arr[$key] = $value;
            }
        }
        else
        {
            Arr::set($this->arr, $key, $value, $merge, $delimiter === null ? $this->delimiter : $delimiter);
        }
    }
    
    /**
     * Checks whether an element of the array exists or not.
     *
     * @param array|string $key - array of the element's elementary keys or compound key (i.e. keys, separated by dot).
     * @param boolean $compositeKey - determines whether the key is compound key.
     * @param string $delimiter - the key delimiter in composite keys.
     * @return boolean
     * @access public
     */
    public function has($key, $compositeKey = false, $delimiter = null)
    {
        if (!$compositeKey)
        {
            return array_key_exists($key, $this->arr);
        }
        return Arr::has($this->arr, $key, $delimiter === null ? $this->delimiter : $delimiter);
    }
    
    /**
     * Removes an element of the array, defined by its compound key.
     *
     * @param array|string $key - array of the element's elementary keys or compound key (i.e. keys, separated by dot).
     * @param boolean $compositeKey - determines whether the key is compound key.
     * @param string $delimiter - the key delimiter in composite keys.
     * @param boolean $removeEmptyParent - determines whether the parent element should be removed if it no longer contains elements after removing the given one.
     * @access public
     */
    public function remove($key, $compositeKey = false, $delimiter = null, $removeEmptyParent = false)
    {
        if (!$compositeKey)
        {
            unset($this->arr[$key]);
        }
        else
        {
            Arr::remove($this->arr, $key, $removeEmptyParent, $delimiter === null ? $this->delimiter : $delimiter);
        }
    }
    
    /**
     * Returns value of the array element by its simple (not a composite) key.
     *
     * @param string $key - the element key.
     * @return mixed
     * @access public
     */
    public function __get($key)
    {
        return $this->get($key, null, false, null);
    }
    
    /**
     * Sets value of an array element.
     *
     * @param string $key - the simple (not a composite) element key.
     * @param mixed $value - the new element value.
     * @access public
     */
    public function __set($key, $value)
    {
        $this->set($key, $value, false, false, null);
    }
    
    /**
     * Returns TRUE if an array element with the given key exists.
     *
     * @param string $key - the simple (not a composite) element key.
     * @return boolean
     * @access public
     */
    public function __isset($key)
    {
        return $this->has($key, false, null);
    }
    
    /** 
     * Remove an array element by its key.
     *
     * @param string $key - the simple (not a composite) element key.
     * @access public
     */
    public function __unset($key)
    {
        $this->remove($key, false, false, null);
    }
    
    /**
     * Returns value of the array element by its simple (not a composite) key.
     *
     * @param string $key - the element key.
     * @return mixed
     * @access public
     */
    public function offsetGet($key)
    {
        return $this->get($key, null, false, null);
    }
    
    /**
     * Sets value of an array element.
     *
     * @param string $key - the simple (not a composite) element key.
     * @param mixed $value - the new element value.
     * @access public
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value, false, false, null);
    }
    
    /**
     * Returns TRUE if an array element with the given key exists.
     *
     * @param string $key - the simple (not a composite) element key.
     * @return boolean
     * @access public
     */
    public function offsetExists($key)
    {
        return $this->has($key, false, null);
    }
    
    /** 
     * Remove an array element by its key.
     *
     * @param string $key - the simple (not a composite) element key.
     * @access public
     */
    public function offsetUnset($key)
    {
        $this->remove($key, false, false, null);
    }
}