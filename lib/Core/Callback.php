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

namespace Aleph\Core;

use Aleph\Core\Interfaces\ICallback;

/**
 * This class is generalization of callable type.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.0
 * @package aleph.core
 */
class Callback implements ICallback
{
    /**
     * Error message templates.
     */
    const ERR_CALLBACK_1 = 'Callback %s is not callable.';

    /**
     * The callable object or null.
     *
     * @var object|callable|null
     */
    private $callable = null;

    /**
     * A string in the Aleph callback format.
     *
     * @var string
     */
    private $callback = '';

    /**
     * Full name (class name with namespace) of a callback class.
     *
     * @var string
     */
    private $class = '';

    /**
     * Method name of a callback class.
     *
     * @var string
     */
    private $method = '';

    /**
     * Equals TRUE if a callback method is static and FALSE if it isn't.
     *
     * @var bool
     */
    private $static = false;

    /**
     * Shows the number of callback arguments that should be passed into callback constructor.
     *
     * @var int
     */
    private $numargs = 0;

    /**
     * Can be equal 'function', 'closure' or 'class' according to callback format.
     *
     * @var string
     */
    private $type = '';

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
    public function __construct($callback)
    {
        $this->parse($callback);
    }

    /**
     * Invokes a callback's method or creating a class object.
     * For callbacks in format 'class[n]' and 'class[n]->method' first n arguments of the method
     * are arguments of the constructor of a class 'class'.
     *
     * @param array $args An array of callback's arguments.
     * @param object $newThis The object to which the given closure function should be bound.
     * @return mixed
     */
    public function call(array $args = [], $newThis = null)
    {
        $args = (array)$args;
        if ($this->type == 'closure') {
            $closure = $this->callable;
            if (is_object($newThis)) {
                return $closure->call($newThis, ...$args);
            }
            return $closure(...$args);
        }
        if ($this->type == 'function') {
            $callable = $this->method;
            return $callable(...$args);
        }
        if ($this->static) {
            return call_user_func_array([$this->class, $this->method], $args);
        }
        $class = $this->getObject($args);
        if ($this->method == '__construct') {
            return $class;
        }
        return call_user_func_array([$class, $this->method], array_splice($args, $this->numargs));
    }

    /**
     * The magic method allowing to invoke an object of this class as a method.
     * The method can take different number of arguments.
     *
     * @param array $params The callback's arguments.
     * @return mixed
     */
    public function __invoke(...$params)
    {
        return $this->call($params);
    }

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
    public function isPermitted(array $permissions) : bool
    {
        foreach (['permitted' => true, 'forbidden' => false] as $type => $res) {
            if (isset($permissions[$type])) {
                $flag = !$res;
                foreach ((array)$permissions[$type] as $regexp) {
                    if (preg_match($regexp, $this->callback)) {
                        $flag = $res;
                        break;
                    }
                }
                if (!$flag) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Verifies that the callback exists and can be invoked.
     *
     * @param bool $autoload Whether or not to call __autoload by default.
     * @return bool
     */
    public function isCallable(bool $autoload = true) : bool
    {
        if ($this->type == 'closure') {
            return true;
        }
        if ($this->type == 'function') {
            return function_exists($this->method);
        }
        if ($this->callable || class_exists($this->class, $autoload)) {
            if (!method_exists($this->class, $this->method)) {
                return false;
            }
            $class = new \ReflectionClass($this->class);
            if (!$class->hasMethod($this->method)) {
                return false;
            }
            $method = $class->getMethod($this->method);
            return $method->isPublic() && $this->static == $method->isStatic();
        }
        return false;
    }

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
    public function getInfo() : array
    {
        return [
            'type' => $this->type,
            'class' => $this->class,
            'method' => $this->method,
            'static' => $this->static,
            'numargs' => $this->numargs
        ];
    }

    /**
     * Returns full class name (with namespace) of the callback.
     *
     * @return string
     */
    public function getClass() : string
    {
        return $this->class;
    }

    /**
     * Returns method name of the callback.
     *
     * @return string
     */
    public function getMethod() : string
    {
        return $this->method;
    }

    /**
     * Returns callback type.
     * Possible values can be "closure", "function" or "class".
     *
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Returns TRUE if the given callback is a static class method and FALSE otherwise.
     *
     * @return bool
     */
    public function isStatic() : bool
    {
        return $this->static;
    }

    /**
     * Returns parameters of a callback class method, function or closure.
     * Method returns FALSE if the class method doesn't exist.
     *
     * @return \ReflectionParameter[]|bool
     */
    public function getParameters()
    {
        if ($this->type != 'class') {
            return (new \ReflectionFunction($this->method))->getParameters();
        }
        $class = new \ReflectionClass($this->class);
        if (!$class->hasMethod($this->method)) {
            return false;
        }
        return $class->getMethod($this->method)->getParameters();
    }

    /**
     * Creates and returns object of callback's class.
     *
     * @param bool $createNew Determines whether the callable object should be a new instance.
     * @param array $args Arguments of the class constructor.
     * @return object|null
     */
    public function getObject(bool $createNew = false, array $args = [])
    {
        if ($this->type == 'closure') {
            return $createNew ? clone $this->callable : $this->callable;
        }
        if ($this->type == 'class') {
            if (!$createNew && $this->callable) {
                return $this->callable;
            }
            $class = new \ReflectionClass($this->class);
            if ($this->numargs == 0) {
                $callable = $class->newInstance();
            } else {
                $callable = $class->newInstanceArgs(array_splice($args, 0, $this->numargs));
            }
            if (!$createNew) {
                $this->callable = $callable;
            }
            return $callable;
        }
        return null;
    }

    /**
     * The magic method allowing to convert an object of this class to a callback string.
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->callback;
    }

    /**
     * Parses the callable object.
     *
     * @param mixed $callback Any valid callable object.
     * @return void
     * @throws \InvalidArgumentException If $callback is in wrong format.
     */
    private function parse($callback)
    {
        if ($callback instanceof ICallback) {
            foreach ($callback->getInfo() as $var => $value) {
                $this->{$var} = $value;
            }
            $this->callback = (string)$callback;
            $this->callable = $callback;
        } else if ($callback instanceof \Closure) {
            $this->type = 'closure';
            $this->class = 'Closure';
            $this->callback = 'Closure';
            $this->callable = $callback;
        } else if (is_object($callback)) {
            $constructor = (new \ReflectionClass($callback))->getConstructor();
            $this->type = 'class';
            $this->class = get_class($callback);
            $this->method = '__construct';
            $this->numargs = $constructor ? $constructor->getNumberOfParameters() : 0;
            $this->callback = $this->class . '[' . ($this->numargs ?: '') . ']';
            $this->callable = $callback;
        } else if (is_array($callback)) {
            if (!is_callable($callback)) {
                if (isset($callback[0]) && is_object($callback[0])) {
                    $callback[0] = '{' . get_class($callback[0]) . '}';
                }
                throw new \InvalidArgumentException(sprintf(static::ERR_CALLBACK_1, json_encode($callback)));
            }
            $constructor = (new \ReflectionClass($callback))->getConstructor();
            $this->type = 'class';
            $this->static = !is_object($this->class);
            $this->class = $this->static ? get_class($callback[0]) : $callback[0];
            $this->method = $callback[1];
            $this->numargs = $constructor ? $constructor->getNumberOfParameters() : 0;
            $this->callback = $this->class .
                ($this->static ? '::' : '[' . ($this->numargs ?: '') . ']->') . $this->method;
            $this->callable = $this->static ? null : $callback[0];
        } else {
            if ($callback == '' || is_numeric($callback)) {
                throw new \InvalidArgumentException(sprintf(static::ERR_CALLBACK_1, $callback));
            }
            preg_match('/^([^\[:-]*)(\[([^\]]*)\])?(::|->)?([^:-]*(?:parent::|self::|)[^:-]*)$/', $callback, $matches);
            if (count($matches) == 0 || $matches[1] == '') {
                throw new \InvalidArgumentException(sprintf(static::ERR_CALLBACK_1, $callback));
            }
            if ($matches[4] == '' && $matches[2] == '') {
                $this->type = 'function';
                $this->method = $matches[1];
                $this->callback = $this->method;
            } else {
                $this->type = 'class';
                $this->class = $matches[1];
                if ($this->class[0] == '\\') {
                    $this->class = ltrim($this->class, '\\');
                }
                $this->numargs = (int)$matches[3];
                $this->static = ($matches[4] == '::');
                $this->method = $matches[5] ?: '__construct';
                $this->callback = $this->class .
                    ($this->static ? '::' : '[' . ($this->numargs ?: '') . ']->') . $this->method;
            }
        }
    }
}