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

namespace Aleph\Core\Traits;

/**
 * This trait provides the access to properties of some class via magic methods.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.core
 */
trait ObjectAccess
{
    /**
     * Returns value of the array element by its key.
     *
     * @param string $key The element key.
     * @return mixed
     */
    public function &__get(string $key)
    {
        if (!array_key_exists($key, $this->items)) {
            $this->items[$key] = null;
        }
        return $this->items[$key];
    }

    /**
     * Sets value of an array element.
     *
     * @param string $key The element key.
     * @param mixed $value The element value.
     * @return void
     */
    public function __set(string $key, $value)
    {
        $this->items[$key] = $value;
    }

    /**
     * Returns TRUE if an array element with the given key exists.
     *
     * @param string $key The element key.
     * @return bool
     */
    public function __isset(string $key) : bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Remove an array element by its key.
     *
     * @param string $key The element key.
     * @return void
     */
    public function __unset(string $key)
    {
        unset($this->items[$key]);
    }
}