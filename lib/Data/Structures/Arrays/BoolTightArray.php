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

namespace Aleph\Data\Structures\Arrays;

/**
 * The implementation of tight array of booleans.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.data
 */
class BoolTightArray extends TightArray
{
    /**
     * Constructor.
     *
     * @param int $size The initial capacity of a tight array.
     * @param bool $autoSize Determines whether the array capacity should be automatically increased.
     * @return void
     */
    public function __construct($size = 0, $autoSize = true)
    {
        $this->itemSize = 1;
        $this->format = 'C';
        parent::__construct($size, $autoSize);
    }
    
    /**
     * Returns a regular array reprsentation of the current tight array.
     *
     * @return array
     */
    protected function getArray()
    {
        return array_map('boolval', parent::getArray());
    }
    
    /**
     * Returns an array element with the specified index.
     *
     * @param int $index The element index.
     * @return mixed
     * @throws \OutOfBoundsException If the index is out of array bounds.
     */
    public function offsetGet($index)
    {
        return (bool)parent::offsetGet($index);
    }
    
    /**
     * Assigns new value to an array element with the specified index.
     *
     * @param int $index The element index.
     * @param mixed $value The element value.
     * @return void
     * @throws \OutOfBoundsException If the index is out of array bounds.
     */
    public function offsetSet($index, $value)
    {
        parent::offsetSet($index, $value ? 1 : 0);
    }
}