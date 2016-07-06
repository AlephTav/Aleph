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

use Aleph\Web;

/**
 * With this class you can transform a string in certain format into a method or function invoking.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.1.2
 * @package aleph.core
 */
class Callback
{
    /**
     * Error message templates.
     */
    const ERR_CALLBACK_1 = 'Callback %s is not callable.';
    const ERR_CALLBACK_2 = 'Control with UID = "%s" is not found.';

    /**
     * A string in the Aleph callback format.
     *
     * @var string
     */
    protected $callback = '';
  
    /**
     * Full name (class name with namespace) of a callback class.
     *
     * @var string
     */
    protected $class = '';
  
    /**
     * Method name of a callback class.
     *
     * @var string
     */
    protected $method = '';
  
    /**
     * Equals TRUE if a callback method is static and FALSE if it isn't.
     *
     * @var bool
     */
    protected $static = false;
  
    /**
     * Shows number of callback arguments that should be transmited into callback constructor.
     *
     * @var int
     */
    protected $numargs = 0;
  
    /**
     * Contains logic identifier or unique identifier of a web-control.
     *
     * @var string
     */
    protected $cid = '';
  
    /**
     * Can be equal 'function', 'closure' or 'class' according to callback format.
     *
     * @var string
     */
    protected $type = '';

    /**
     * Constructor. The only argument of it is string in Aleph framework format.
     * The following formats are possible:
     * 'function' - invokes a global function with name 'function'.
     * '->method' - invokes a method 'method' of Aleph\Web\Page::current() object (if defined) or Aleph object. 
     * '::method' - invokes a static method 'method' of Aleph\Web\Page::current() object (if defined) or Aleph object.
     * 'class::method' - invokes a static method 'method' of a class 'class'.
     * 'class->method' - invokes a method 'method' of a class 'class' with its constructor without arguments.
     * 'class[]' - creates an object of a class 'class' without sending any arguments in its constructor.
     * 'class[]::method' - the same as 'class::method'.
     * 'class[]->method' - the same as 'class->method'.
     * 'class[n]' - creates an object of a class 'class' with its constructor taking n arguments.
     * 'class[n]::method' - the same as 'class::method'.
     * 'class[n]->method' - invokes a method 'method' of a class 'class' with its constructor taking n arguments.
     * 'class@cid->method' - invokes a method 'method' of web control type of 'class' with unique (or logic) ID equals 'cid'.
     *
     * @param mixed $callback An Aleph framework callback string or a callable callback.
     * @return void
     */
    public function __construct($callback)
    {
        if ($callback instanceof Callback)
        {
            foreach ($callback->getInfo() as $var => $value)
            {
                $this->{$var} = $value;
            }
            $this->callback = (string)$callback;
        }
        else if ($callback instanceof \Closure)
        {
            $this->type = 'closure';
            $this->method = $callback;
            $this->callback = 'Closure';
        }
        else if (is_object($callback))
        {
            $class = new \ReflectionClass($callback);
            $this->type = 'class';
            $this->class = $callback;
            $this->method = '__construct';
            $this->numargs = $class->hasMethod($this->method) ? $class->getConstructor()->getNumberOfParameters() : 0;
            $this->callback = get_class($callback) . '[' . ($this->numargs ?: '') . ']';
        }
        else if (is_array($callback))
        {
            if (!is_callable($callback))
            {
                if (isset($callback[0]) && is_object($callback[0]))
                {
                    $callback[0] = '{' . get_class($callback[0]) . '}';
                }
                throw new \InvalidArgumentException(sprintf(static::ERR_CALLBACK_1, json_encode($callback)));
            }
            $this->type = 'class';
            $this->class = $callback[0];
            $this->method = $callback[1];
            $this->static = !is_object($this->class);
            $this->numargs = $this->static ? 0 : (new \ReflectionClass($this->class))->getConstructor()->getNumberOfParameters();
            $this->callback = (is_object($this->class) ? get_class($this->class) : $this->class) . ($this->static ? '::' : '[' . ($this->numargs ?: ''). ']->') . $this->method;
        }
        else
        {
            if ($callback == '' || is_numeric($callback))
            {
                throw new \InvalidArgumentException(sprintf(static::ERR_CALLBACK_1, $callback));
            }
            $callback = htmlspecialchars_decode($callback);
            preg_match('/^([^\[:-]*)(\[([^\]]*)\])?(::|->)?([^:-]*|[^:-]*parent::[^:-]*)$/', $callback, $matches);
            if (count($matches) == 0)
            {
                throw new \InvalidArgumentException(sprintf(static::ERR_CALLBACK_1, $callback));
            }
            if ($matches[4] == '' && $matches[2] == '')
            {
                $this->type = 'function';
                $this->method = $matches[1];
            }
            else
            {
                $this->type = 'class';
                $this->class = $matches[1] ?: (Web\Page::current() instanceof Web\Page ? get_class(Web\Page::current()) : false);
                if ($this->class === false)
                {
                    throw new \InvalidArgumentException(sprintf(static::ERR_CALLBACK_1, $callback));
                }
                if ($this->class[0] == '\\')
                {
                    $this->class = ltrim($this->class, '\\');
                }
                $this->numargs = (int)$matches[3];
                $this->static = ($matches[4] == '::');
                $this->method = $matches[5] ?: '__construct';
                $class = explode('@', $this->class);
                if (count($class) > 1)
                {
                    $this->class = $class[0];
                    $this->cid = $class[1];
                    $this->type = 'control';
                }
            }
            $this->callback = $this->class . ($this->static ? '::' : '[' . ($this->numargs ?: ''). ']->') . $this->method;
        }
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
     * The magic method allowing to invoke an object of this class as a method.
     * The method can take different number of arguments.
     *
     * @return mixed
     */
    public function __invoke()
    {
        return $this->call(func_get_args());
    }
  
    /**
     * Invokes a callback's method or creating a class object.
     * For callbacks in format 'class[n]' and 'class[n]->method' first n arguments of the method
     * are arguments of constructor of a class 'class'.
     *
     * @param array $args An array of method arguments.
     * @param object $newthis The object to which the given closure function should be bound.
     * @return mixed
    */
    public function call(array $args = [], $newthis = null)
    {
        $args = (array)$args;
        if ($this->type == 'closure')
        {
            if (is_object($newthis))
            {
                return $this->method->call($newthis, ...$args);
            }
            return $this->method(...$args);
        }
        if ($this->type == 'function')
        {
            return $this->method(...$args);
        }
        if ($this->type == 'control')
        {
            if (($class = Web\Page::current()->get($this->cid)) === false)
            {
                throw new \LogicException(sprintf(static::ERR_CALLBACK_2, $this->cid));
            }
            if ($this->method == '__construct')
            {
                return $class;
            }
            return call_user_func_array([$class, $this->method], $args);
        }
        else
        {
            if ($this->static)
            {
                return call_user_func_array([$this->class, $this->method], $args);
            }
            if (is_object($this->class))
            {
                $class = $this->class;
            }
            else if (Web\Page::current() instanceof Web\Page && $this->class == get_class(Web\Page::current()))
            {
                $class = Web\Page::current();
            }
            else
            {
                $class = $this->getClassObject($args);
            }
            if ($this->method == '__construct')
            {
                return $class;
            }
            return call_user_func_array([$class, $this->method], $args);
        }
    }

    /**
     * Checks whether or not the callback can be invoked according to permissions.
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
        foreach (['permitted' => true, 'forbidden' => false] as $type => $res)
        {
            if (isset($permissions[$type]))
            {
                $flag = !$res;
                foreach ((array)$permissions[$type] as $regexp)
                {
                    if (preg_match($regexp, $this->callback))
                    {
                        $flag = $res;
                        break;
                    }
                }
                if (!$flag)
                {
                    return false;
                }
            }
        }
        return true;
    }
  
    /**
     * Verifies that the callack exists and can be invoked.
     *
     * @param boolean $autoload Whether or not to call __autoload by default.
     * @return bool
     */
    public function isCallable(bool $autoload = true) : bool
    {
        if ($this->type == 'closure')
        {
            return true;
        }
        if ($this->type == 'function')
        {
            return function_exists($this->method);
        }
        if ($this->type == 'control')
        {
            return Web\Page::current()->get($this->cid) !== false;
        }
        $static = $this->static;
        $methodExists = function($class, $method) use ($static)
        {
            if (!method_exists($class, $method))
            {
                return false;
            }
            $class = new \ReflectionClass($class);
            if (!$class->hasMethod($method))
            {
                return false;
            }
            $method = $class->getMethod($method);
            return $method->isPublic() && $static === $method->isStatic();
        };
        if (is_object($this->class) || class_exists($this->class, false))
        {
            return $methodExists($this->class, $this->method);
        }
        if (!$autoload)
        {
            return false;
        }
        if (!\Aleph::loadClass($this->class))
        {
            return false;
        }
        return $methodExists($this->class, $this->method);
    }
  
    /**
     * Returns array of detail information of the callback.
     * Output array has the format
     * [
     *     'class' => ... [string] ..., 
     *     'method' => ... [string] ..., 
     *     'static' => ... [boolean] ..., 
     *     'numargs' => ... [integer] ..., 
     *     'cid' => ... [string] ...,
     *     'type' => ... [string] ...
     * ]
     *
     * @return array
     */
    public function getInfo() : array
    {
        return [
            'class' => $this->class, 
            'method' => $this->method,
            'static' => $this->static,
            'numargs' => $this->numargs,
            'cid' => $this->cid,
            'type' => $this->type
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
     * Returns callback type. Possible values can be "closure", "function", "class" or "control".
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
     * Method returns FALSE if class method doesn't exist.
     * Parameters are returned as an array of \ReflectionParameter class instance.
     *
     * @return array|bool
     */
    public function getParameters()
    {
        if ($this->type != 'class')
        {
            return (new \ReflectionFunction($this->method))->getParameters();
        }
        $class = new \ReflectionClass($this->class);
        if (!$class->hasMethod($this->method))
        {
            return false;
        }
        return $class->getMethod($this->method)->getParameters();
    }
  
    /**
     * Creates and returns object of callback's class.
     *
     * @param array $args Arguments of the class constructor.
     * @return object|null
     */
    public function getClassObject(array $args = [])
    {
        if (empty($this->class))
        {
            return;
        }
        if ($this->type == 'control')
        {
            return Web\Page::current()->get($this->cid);
        }
        $class = new \ReflectionClass($this->class);
        if ($this->numargs == 0)
        {
            return $class->newInstance();
        }
        return $class->newInstanceArgs(array_splice($args, 0, $this->numargs));
    }
}