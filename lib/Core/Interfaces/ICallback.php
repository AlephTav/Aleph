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

namespace Aleph\Core\Interfaces;


interface ICallback
{
    /**
     * Constructor.
     *
     * The following formats of $callback are possible:
     * 'function' - invokes a global function with name 'function'.
     * 'class::method' - invokes a static method 'method' of a class 'class'.
     * 'class->method' - invokes a method 'method' of a class 'class' with its constructor without arguments.
     * 'class[]' - creates an object of a class 'class' without sending any arguments in its constructor.
     * 'class[]::method' - the same as 'class::method'.
     * 'class[]->method' - the same as 'class->method'.
     * 'class[n]' - creates an object of a class 'class' with its constructor taking n arguments.
     * 'class[n]::method' - the same as 'class::method'.
     * 'class[n]->method' - invokes a method 'method' of a class 'class' with its constructor taking n arguments.
     *
     * @param mixed $callback Any valid callable object.
     * @throws \InvalidArgumentException If $callback is in wrong format.
     */
    public function __construct($callback);

    /**
     * Invokes a callback's method or creating a class object.
     * For callbacks in format 'class[n]' and 'class[n]->method' first n arguments of the method
     * are arguments of the constructor of a class 'class'.
     *
     * @param array $args An array of callback's arguments.
     * @param object $newThis The object to which the given closure function should be bound.
     * @return mixed
     */
    public function call(array $args = [], $newThis = null);

    /**
     * The magic method allowing to invoke an object of this class as a method.
     * The method can take different number of arguments.
     *
     * @param array $params The callback's arguments.
     * @return mixed
     */
    public function __invoke(...$params);

    /**
     * Checks whether or not the callback can be invoked according to permissions.
     *
     * Permissions array have the following structure:
     * [
     *   'permitted' => ['regexp1', 'regexp2', ... ],
     *   'forbidden' => ['regexp1', 'regexp2', ...]
     * ]
     * If string representation of the callback matches at least one of 'permitted'
     * regular expressions and none of 'forbidden' regular expressions, the method
     * returns TRUE. Otherwise it returns FALSE.
     *
     * @param array $permissions Permissions to check.
     * @return bool
     */
    public function isPermitted(array $permissions) : bool;

    /**
     * Verifies that the callback exists and can be invoked.
     *
     * @param bool $autoload Whether or not to call __autoload by default.
     * @return bool
     */
    public function isCallable(bool $autoload = true) : bool;

    /**
     * Returns an array of the detailed information of the callback.
     *
     * Output array has the format
     * [
     *     'type' => ... [string] ...,
     *     'class' => ... [string] ...,
     *     'method' => ... [string] ...,
     *     'static' => ... [boolean] ...,
     *     'numargs' => ... [integer] ...
     * ]
     *
     * @return array
     */
    public function getInfo() : array;

    /**
     * Returns full class name (with namespace) of the callback.
     *
     * @return string
     */
    public function getClass() : string;

    /**
     * Returns method name of the callback.
     *
     * @return string
     */
    public function getMethod() : string;

    /**
     * Returns callback type.
     * Possible values can be "closure", "function" or "class".
     *
     * @return string
     */
    public function getType() : string;

    /**
     * Returns TRUE if the given callback is a static class method and FALSE otherwise.
     *
     * @return bool
     */
    public function isStatic() : bool;

    /**
     * Returns parameters of a callback class method, function or closure.
     * Method returns FALSE if the class method doesn't exist.
     *
     * @return \ReflectionParameter[]|bool
     */
    public function getParameters();

    /**
     * Creates and returns object of callback's class.
     *
     * @param bool $createNew Determines whether the callable object should be a new instance.
     * @param array $args Arguments of the class constructor.
     * @return object|null
     */
    public function getObject(bool $createNew = false, array $args = []);

    /**
     * The magic method allowing to convert an object of this class to a callback string.
     *
     * @return string
     */
    public function __toString() : string;
}