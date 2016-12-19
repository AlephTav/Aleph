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

namespace Aleph\Data\Structures\Interfaces;

/**
 * The common interface for any collection of data.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.data.structures
 */
interface IContainer extends \IteratorAggregate, \Countable
{
    /**
     * Returns TRUE if this set contains no elements.
     *
     * @return bool
     */
    public function isEmpty() : bool;

    /**
     * Removes all elements from this container.
     *
     * @return \Aleph\Data\Structures\Interfaces\IContainer
     */
    public function clean();

    /**
     * Returns a copy of this container.
     *
     * @return \Aleph\Data\Structures\Interfaces\IContainer
     */
    public function copy();

    /**
     * Converts this container to an array.
     *
     * @return array
     */
    public function toArray() : array;

    /**
     * Converts this container to a JSON-encoded string.
     *
     * @return string
     */
    public function toJson() : string;
}