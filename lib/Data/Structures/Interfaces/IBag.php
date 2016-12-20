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
 * The common interface for unsorted multisets (bags).
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.data.structures
 */
interface IBag extends IContainer
{
    /**
     * Return the number of instances (multiplicity) of
     * the given item currently in the bag.
     *
     * @param mixed $item
     * @return int
     */
    public function multiplicity($item) : int;

    /**
     * Returns TRUE if this bag contains the specified elements.
     *
     * @param mixed ...$items
     * @return bool
     */
    public function contains(...$items) : bool;

    /**
     * Adds the specified elements to this bag.
     *
     * @param mixed ...$items
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function add(...$items);

    /**
     * Adds the specified number of instances of the given element to this bag.
     *
     * @param mixed $item
     * @param int $count
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function addInstances($item, int $count = 1);

    /**
     * Removes all occurrences of the specified elements from this bag
     * if they are present.
     *
     * @param mixed ...$items
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function remove(...$items);

    /**
     * Remove the given number of instances from the bag.
     *
     * @param mixed $item
     * @param int $count
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function removeInstances($item, int $count = 1);

    /**
     * Creates a new bag that contains the elements of this bag as well as the
     * elements of another bag and the multiplicity of each element is equal to
     * the maximum multiplicity of corresponded elements in the given bags.
     *
     * @param \Aleph\Data\Structures\Interfaces\IBag $bag
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function union(IBag $bag);

    /**
     * Creates a bag that contains all elements that are present simultaneously
     * in each of bags, and the multiplicity of each element is equal to the minimum
     * multiplicity of the corresponding elements in the given bags.
     *
     * @param \Aleph\Data\Structures\Interfaces\IBag $bag
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function intersect(IBag $bag);

    /**
     * Creates a bag that contains all elements that are present in at least one of
     * bags, and the multiplicity of each element is the sum of the multiplicities of
     * the corresponding elements in the given bags).
     *
     * @param \Aleph\Data\Structures\Interfaces\IBag $bag
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function sum(IBag $bag);

    /**
     * Creates a bag that contains all elements of the first bag,
     * multiplicities of which are greater than multiplicities of the corresponding
     * elements in the second bag, and the multiplicity of each element is equal
     * to the difference between the multiplicities of the corresponding elements
     * in the given bags.
     *
     * @param \Aleph\Data\Structures\Interfaces\IBag $bag
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function diff(IBag $bag);

    /**
     * Creates a new bag that contains elements of the both bags multiplicities
     * of which are different, and the multiplicity of each element is equal to
     * the absolute value of the difference between the multiplicities of
     * the corresponding elements in the given bags.
     *
     * @param \Aleph\Data\Structures\Interfaces\IBag $bag
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function symdiff(IBag $bag);

    /**
     * Returns a randomly chosen element of this bag.
     *
     * @param bool $remove Determines whether to delete the returning element from this bag.
     * @return mixed
     */
    public function grab(bool $remove = false);

    /**
     * Converts this bag to a set.
     *
     * @return \Aleph\Data\Structures\Interfaces\ISet
     */
    public function toSet() : ISet;
}