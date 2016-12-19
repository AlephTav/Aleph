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

use Aleph\Utils\Str;
use Aleph\Data\Structures\Interfaces\{IContainer, ISet, IBag};

/**
 * Implementation of an unsorted multiset (bag).
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.data.structures
 */
class HashBag implements IBag
{
    /**
     * Contains numbers of identical elements.
     *
     * @var array
     */
    private $counts = [];

    /**
     * A multiset (bag) of items.
     *
     * @var array
     */
    private $bag = [];

    /**
     * Constructor.
     *
     * @param mixed ...$items Items that will be converted to a set.
     */
    public function __construct(...$items)
    {
        $this->add(...$items);
    }

    /**
     * Returns an iterator over the elements in this set.
     *
     * @return \ArrayIterator
     */
    public function getIterator() : \ArrayIterator
    {
        return new \ArrayIterator($this->toArray());
    }

    /**
     * Returns TRUE if this set contains no elements.
     *
     * @return bool
     */
    public function isEmpty() : bool
    {
        return count($this->bag) == 0;
    }

    /**
     * Returns the number of elements in this set (its cardinality).
     *
     * @return int
     */
    public function count() : int
    {
        return count($this->bag);
    }

    /**
     * Return the number of instances (multiplicity) of
     * the given item currently in the bag.
     *
     * @param mixed $item
     * @return int
     */
    public function multiplicity($item) : int
    {
        return $this->counts[Str::hash($item)] ?? 0;
    }

    /**
     * Returns TRUE if this bag contains the specified elements.
     *
     * @param mixed ...$items
     * @return bool
     */
    public function contains(...$items) : bool
    {
        foreach ($items as $item) {
            if (!array_key_exists(Str::hash($item), $this->counts)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Adds the specified elements to this bag.
     *
     * @param mixed ...$items
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function add(...$items) : IBag
    {
        foreach ($items as $item) {
            $hash = Str::hash($item);
            if (!isset($this->counts[$hash])) {
                $this->counts[$hash] = 1;
            } else {
                ++$this->counts[$hash];
            }
            $this->bag[$hash . $this->counts[$hash]] = $item;
        }
        return $this;
    }

    /**
     * Adds the specified number of instances of the given element to this bag.
     *
     * @param mixed $item
     * @param int $count
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function addInstances($item, int $count = 1) : IBag
    {
        if ($count < 1) {
            return $this;
        }
        $hash = Str::hash($item);
        if (!isset($this->counts[$hash])) {
            $min = 0;
            $this->counts[$hash] = $count;
        } else {
            $min = $this->counts[$hash];
            $this->counts[$hash] += $count;
        }
        for ($i = $this->counts[$hash]; $i > $min; --$i) {
            $this->bag[$hash . $i] = $item;
        }
        return $this;
    }

    /**
     * Removes all occurrences of the specified elements from this bag
     * if they are present.
     *
     * @param mixed ...$items
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function remove(...$items) : IBag
    {
        foreach ($items as $item) {
            $hash = Str::hash($item);
            if (isset($this->counts[$hash])) {
                for ($i = $this->counts[$hash]; $i > 0; --$i) {
                    unset($this->bag[$hash . $i]);
                }
                unset($this->counts[$hash]);
            }
        }
        return $this;
    }

    /**
     * Remove the given number of instances from the bag.
     *
     * @param mixed $item
     * @param int $count
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function removeInstances($item, int $count = 1) : IBag
    {
        if ($count < 1) {
            return $this;
        }
        $hash = Str::hash($item);
        $max = $this->counts[$hash] ?: 0;
        if ($max > 0) {
            $n = min($count, $max);
            $min = $max - $n + 1;
            for ($i = $min; $i <= $max; ++$i) {
                unset($this->bag[$hash . $i]);
            }
            if ($min == 1) {
                unset($this->counts[$hash]);
            }
        }
        return $this;
    }

    /**
     * Creates a new bag that contains the elements of this bag as well as the
     * elements of another bag and the multiplicity of each element is equal to
     * the maximum multiplicity of corresponded elements in the given bags.
     *
     * @param \Aleph\Data\Structures\Interfaces\IBag $bag
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function union(IBag $bag) : IBag
    {
        $new = new static();
        foreach ($this->counts as $hash => $n) {
            $item = $this->bag[$hash . '1'];
            $new->addInstances($item, max($n, $bag->multiplicity($item)));
        }
        foreach (array_unique($bag->toArray()) as $item) {
            if ($this->multiplicity($item) == 0) {
                $new->addInstances($item, $bag->multiplicity($item));
            }
        }
        return $new;
    }

    /**
     * Creates a bag that contains all elements that are present simultaneously
     * in each of bags, and the multiplicity of each element is equal to the minimum
     * multiplicity of the corresponding elements in the given bags.
     *
     * @param \Aleph\Data\Structures\Interfaces\IBag $bag
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function intersect(IBag $bag) : IBag
    {
        $new = new static();
        foreach ($this->counts as $hash => $n) {
            $item = $this->bag[$hash . '1'];
            $m = $bag->multiplicity($item);
            if ($m > 0) {
                $new->addInstances($item, min($n, $m));
            }
        }
        return $new;
    }

    /**
     * Creates a bag that contains all elements that are present in at least one of
     * bags, and the multiplicity of each element is the sum of the multiplicities of
     * the corresponding elements in the given bags).
     *
     * @param \Aleph\Data\Structures\Interfaces\IBag $bag
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function sum(IBag $bag) : IBag
    {
        return $this->copy()->add(...$bag->toArray());
    }

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
    public function diff(IBag $bag) : IBag
    {
        $new = new static();
        foreach ($this->counts as $hash => $n) {
            $item = $this->bag[$hash . '1'];
            $n = max(0, $bag->multiplicity($item));
            if ($n > 0) {
                $new->addInstances($item, $n);
            }
        }
        return $new;
    }

    /**
     * Creates a new bag that contains elements of the both bags multiplicities
     * of which are different, and the multiplicity of each element is equal to
     * the absolute value of the difference between the multiplicities of
     * the corresponding elements in the given bags.
     *
     * @param \Aleph\Data\Structures\Interfaces\IBag $bag
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function symdiff(IBag $bag) : IBag
    {
        $new = new static();
        foreach ($this->counts as $hash => $n) {
            $item = $this->bag[$hash . '1'];
            $m = $bag->multiplicity($item);
            if ($m != $n) {
                $new->addInstances($item, abs($n - $m));
            }
        }
        foreach (array_unique($bag->toArray()) as $item) {
            $n = $this->multiplicity($item);
            $m = $bag->multiplicity($item);
            if ($n != $m) {
                $new->addInstances($item, abs($n - $m));
            }
        }
        return $new;
    }

    /**
     * Removes all of the elements from this bag.
     *
     * @return \Aleph\Data\Structures\Interfaces\IContainer
     */
    public function clean() : IContainer
    {
        $this->bag = $this->counts = [];
        return $this;
    }

    /**
     * Returns a randomly chosen element of this bag.
     *
     * @return mixed
     */
    public function grab()
    {
        return $this->bag[array_rand($this->bag)];
    }

    /**
     * Returns an array containing all of the elements in this set.
     *
     * @return array
     */
    public function toArray() : array
    {
        return array_values($this->bag);
    }

    /**
     * Converts this container to a JSON-encoded string.
     *
     * @return string
     */
    public function toJson() : string
    {
        return json_encode($this->toArray());
    }

    /**
     * Converts this bag to a set.
     *
     * @return \Aleph\Data\Structures\Interfaces\ISet
     */
    public function toSet() : ISet
    {
        return new HashSet(...$this->bag);
    }

    /**
     * Returns a copy of this set.
     *
     * @return \Aleph\Data\Structures\Interfaces\IBag
     */
    public function copy() : IBag
    {
        return clone $this;
    }
}