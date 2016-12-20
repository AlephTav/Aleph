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
 * The common interface for unsorted sets.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.data.structures
 */
interface ISet extends IContainer
{
    /**
     * Returns TRUE if this set contains the specified elements.
     *
     * @param mixed ...$items
     * @return bool
     */
    public function contains(...$items) : bool;

    /**
     * Adds the specified elements to this set if they are not already present.
     *
     * @param mixed ...$items
     * @return \Aleph\Data\Structures\Interfaces\ISet
     */
    public function add(...$items);

    /**
     * Removes the specified elements from this set if they are present.
     *
     * @param mixed ...$items
     * @return \Aleph\Data\Structures\Interfaces\ISet
     */
    public function remove(...$items);

    /**
     * Creates a new set that contains the values of this set as well as the
     * values of another set.
     *
     * A ∪ B = {x: x ∈ A ∨ x ∈ B}
     *
     * @param \Aleph\Data\Structures\Interfaces\ISet $set
     * @return \Aleph\Data\Structures\Interfaces\ISet
     */
    public function union(Iset $set);

    /**
     * Creates a new set using values common to both this set and another set.
     *
     * A ∩ B = {x : x ∈ A ∧ x ∈ B}
     *
     * @param \Aleph\Data\Structures\Interfaces\ISet $set
     * @return \Aleph\Data\Structures\Interfaces\ISet
     */
    public function intersect(Iset $set);

    /**
     * Creates a new set using values from this set that are not in another set.
     *
     * A \ B = {x ∈ A | x ∉ B}
     *
     * @param \Aleph\Data\Structures\Interfaces\ISet $set
     * @return \Aleph\Data\Structures\Interfaces\ISet
     */
    public function diff(ISet $set);

    /**
     * Creates a new set using values in either this set or in another set,
     * but not in both.
     *
     * A ⊖ B = {x : x ∈ (A \ B) ∪ (B \ A)}
     *
     * @param \Aleph\Data\Structures\Interfaces\ISet $set
     * @return \Aleph\Data\Structures\Interfaces\ISet
     */
    public function symdiff(ISet $set);

    /**
     * Returns a randomly chosen element of this set.
     *
     * @param bool $remove Determines whether to delete the returning element from this set.
     * @return mixed
     */
    public function grab(bool $remove = false);
}