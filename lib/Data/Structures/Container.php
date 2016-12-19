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

namespace Aleph\Data\Structures;

use Aleph\Utils\Arr;
use Aleph\Data\Structures\Interfaces\IContainer;

/**
 * Simple container for key/value pairs.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.1
 * @package aleph.data.structures
 */
class Container implements \ArrayAccess, IContainer
{
    /**
     * An array of key/value pairs.
     *
     * @var array
     */
    protected $items = [];

    /**
     * The default key delimiter in composite array keys.
     *
     * @var string
     */
    protected $delimiter = '';

    /**
     * Constructor.
     *
     * @param array $items An array of key/value pairs.
     * @param string $delimiter The default key delimiter in composite keys.
     */
    public function __construct(array $items = [], string $delimiter = Arr::DEFAULT_KEY_DELIMITER)
    {
        $this->items = $items;
        $this->delimiter = $delimiter;
    }

    /**
     * Returns the number of stored key/value pairs.
     *
     * @return int
     */
    public function count() : int
    {
        return count($this->items);
    }

    /**
     * Returns TRUE if this container contains no items.
     *
     * @return bool
     */
    public function isEmpty() : bool
    {
        return count($this->items) == 0;
    }

    /**
     * Iterates an array.
     *
     * @return \ArrayIterator
     */
    public function getIterator() : \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Recursively iterates an array.
     *
     * @return \RecursiveArrayIterator
     */
    public function getRecursiveIterator() : \RecursiveArrayIterator
    {
        return new \RecursiveArrayIterator($this->items);
    }

    /**
     * Returns generator that iterates over all elements of a multidimensional array of items.
     *
     * @param bool $iterateObjects Determines whether to iterate an objects (all objects will be converted to an array).
     * @return \Generator
     */
    public function getGenerator($iterateObjects = false) : \Generator
    {
        return Arr::iterate($this->items, $iterateObjects);
    }

    /**
     * Returns TRUE if the container is a numeric array and FALSE otherwise.
     *
     * @return bool
     */
    public function isNumeric() : bool
    {
        return Arr::isNumeric($this->items);
    }

    /**
     * Converts this container to an associative array.
     *
     * @return array
     */
    public function toArray() : array
    {
        return $this->items;
    }

    /**
     * Converts this container to a JSON-encoded string.
     *
     * @return string
     */
    public function toJson() : string
    {
        return json_encode($this->items);
    }

    /**
     * Returns the array keys.
     *
     * @return array
     */
    public function keys() : array
    {
        return array_keys($this->items);
    }

    /**
     * Returns the array values.
     *
     * @return array
     */
    public function values() : array
    {
        return array_values($this->items);
    }

    /**
     * Replaces the current array by a new one.
     *
     * @param array $items
     * @return \Aleph\Data\Structures\Interfaces\IContainer
     */
    public function replace(array $items = []) : IContainer
    {
        $this->items = $items;
        return $this;
    }

    /**
     * Adds new key/value pairs to the current set.
     *
     * @param array $items
     * @return \Aleph\Data\Structures\Interfaces\IContainer
     */
    public function add(array $items = []) : IContainer
    {
        $this->items = array_replace($this->items, $items);
        return $this;
    }

    /**
     * Merge existing key/value pairs with new set.
     *
     * @param array $items
     * @return \Aleph\Data\Structures\Interfaces\IContainer
     */
    public function merge(array $items = []) : IContainer
    {
        $this->items = Arr::merge($this->items, $items);
        return $this;
    }

    /**
     * Removes all key/value pairs.
     *
     * @return \Aleph\Data\Structures\Interfaces\IContainer
     */
    public function clean() : IContainer
    {
        $this->items = [];
        return $this;
    }

    /**
     * @return \Aleph\Data\Structures\Interfaces\IContainer
     */
    public function copy() : IContainer
    {
        return clone $this;
    }

    /**
     * Applies the given callback to an each item.
     *
     * @param callable $callback
     * @return \Aleph\Data\Structures\Interfaces\IContainer
     */
    public function each(callable $callback) : IContainer
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }
        return $this;
    }

    /**
     * Returns the first item of the container.
     *
     * @return mixed
     */
    public function first()
    {
        return reset($this->items);
    }

    /**
     * Returns the last item of the container.
     *
     * @return mixed
     */
    public function last()
    {
        return end($this->items);
    }

    /**
     * Pushes an item to the end of the container.
     *
     * @param mixed $value
     * @param string|int $key The simple key.
     * @return \Aleph\Data\Structures\Interfaces\IContainer
     */
    public function push($value, $key = null) : IContainer
    {
        if ($key === null) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
        return $this;
    }

    /**
     * Removes and returns the last item from the container.
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Prepends an item to the beginning of the container.
     *
     * @param mixed $value
     * @param string|int $key The simple key.
     * @return \Aleph\Data\Structures\Interfaces\IContainer
     */
    public function prepend($value, $key = null) : IContainer
    {
        if ($key === null) {
            array_unshift($this->items, $value);
        } else {
            $this->items = [$key => $value] + $this->items;
        }
        return $this;
    }

    /**
     * Removes and returns the first item from the container.
     *
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Returns a container item, defined by its key.
     *
     * @param array|string $key An array of the item's elementary keys or compound key (i.e. keys, separated by dot).
     * @param mixed $default The default value of an item if it does not exist.
     * @param bool $compositeKey Determines whether the item key is a compound key.
     * @param string $delimiter The key delimiter in compound keys.
     * @return mixed
     */
    public function get($key, $default = null, bool $compositeKey = false, string $delimiter = '')
    {
        if (!$compositeKey) {
            return array_key_exists($key, $this->items) ? $this->items[$key] : $default;
        }
        return Arr::get($this->items, $key, $default, $delimiter === '' ? $this->delimiter : $delimiter);
    }

    /**
     * Sets new value of a container item, defined by its key.
     *
     * @param array|string $key An array of the item's elementary keys or compound key (i.e. keys, separated by dot).
     * @param mixed $value The new value of a container item.
     * @param bool $merge Determines whether the old item value should be merged with new one.
     * @param bool $compositeKey Determines whether the key is a compound key.
     * @param string $delimiter The key delimiter in compound keys.
     * @return \Aleph\Data\Structures\Interfaces\IContainer
     */
    public function set($key, $value, bool $merge = false,
                        bool $compositeKey = false, string $delimiter = '') : IContainer
    {
        if (!$compositeKey) {
            if ($merge && is_array($value) && isset($this->items[$key]) && is_array($this->items[$key])) {
                $this->items[$key] = Arr::merge($this->items[$key], $value);
            } else {
                $this->items[$key] = $value;
            }
        } else {
            Arr::set($this->items, $key, $value, $merge, $delimiter === '' ? $this->delimiter : $delimiter);
        }
        return $this;
    }

    /**
     * Checks whether an element of the array exists or not.
     *
     * @param array|string $key An array of the element's elementary keys or compound key (i.e. keys, separated by dot).
     * @param bool $compositeKey Determines whether the key is compound key.
     * @param string $delimiter The key delimiter in composite keys.
     * @return bool
     */
    public function has($key, bool $compositeKey = false, string $delimiter = '') : bool
    {
        if (!$compositeKey) {
            return array_key_exists($key, $this->items);
        }
        return Arr::has($this->items, $key, $delimiter === '' ? $this->delimiter : $delimiter);
    }

    /**
     * Removes an element of the array, defined by its compound key.
     *
     * @param array|string $key An array of the element's elementary keys or compound key (i.e. keys, separated by dot).
     * @param bool $compositeKey Determines whether the key is compound key.
     * @param string $delimiter The key delimiter in composite keys.
     * @param bool $removeEmptyParent Determines whether the parent element should be removed if it no longer contains
     * elements after removing the given one.
     * @return \Aleph\Data\Structures\Interfaces\IContainer
     */
    public function remove($key, bool $compositeKey = false,
                           string $delimiter = '', bool $removeEmptyParent = false) : IContainer
    {
        if (!$compositeKey) {
            unset($this->items[$key]);
        } else {
            Arr::remove($this->items, $key, $removeEmptyParent, $delimiter === '' ? $this->delimiter : $delimiter);
        }
        return $this;
    }

    /**
     * Returns value of the array element by its simple (not a composite) key.
     *
     * @param string $key The element key.
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Sets value of an array element.
     *
     * @param string $key The simple (not a composite) element key.
     * @param mixed $value The new element value.
     * @return void
     */
    public function __set(string $key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Returns TRUE if an array element with the given key exists.
     *
     * @param string $key The simple (not a composite) element key.
     * @return bool
     */
    public function __isset(string $key) : bool
    {
        return $this->has($key);
    }

    /**
     * Remove an array element by its key.
     *
     * @param string $key The simple (not a composite) element key.
     * @return void
     */
    public function __unset(string $key)
    {
        $this->remove($key);
    }

    /**
     * Returns value of the array element by its simple (not a composite) key.
     *
     * @param string $key The element key.
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Sets value of an array element.
     *
     * @param string $key The simple (not a composite) element key.
     * @param mixed $value The new element value.
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Returns TRUE if an array element with the given key exists.
     *
     * @param string $key The simple (not a composite) element key.
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Removes an array element by its key.
     *
     * @param string $key The simple (not a composite) element key.
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }
}