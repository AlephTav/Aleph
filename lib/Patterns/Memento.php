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

namespace Aleph\Patterns;

use Aleph\Patterns\Interfaces\IMemento;

/**
 * Implementation of memento object for Memento design pattern.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.patterns
 */
class Memento implements IMemento
{
    /**
     * The originator object.
     *
     * @var object
     */
    private $originator = null;
    
    /**
     * The originator saved state.
     *
     * @var mixed
     */
    private $state = null;
    
    /**
     * Constructor.
     *
     * @param object $originator
     * @return void
     */
    public function __construct($originator)
    {
        $this->originator = $originator;
        $this->state = $this->call($originator, 'getState');
    }
    
    /**
     * Restores the originator state.
     *
     * @return void     
     */
    public function restore()
    {
        $this->call($this->originator, 'setState', $this->state);
    }

    /**
     * Invokes the given method.
     *
     * @param object $object An object that uses Mementable trait.
     * @param string $method The method to be invoked.
     * @param array $params The method parameters.
     * @throws \ReflectionException If the given method does not exist.
     * @return mixed
     */
    private function call($object, $method, ...$params)
    {
        $m = new \ReflectionMethod($object, $method);
        $m->setAccessible(true);
        return $m->invokeArgs($object, $params);
    }
    
    /**
     * Protects against serialization through "serialize".
     *
     * @return void
     */
    private function __sleep() {}
    
    /**
     * Protects against creation through "unserialize".
     *
     * @return void
     */
    private function __wakeup() {}
}