<?php
/**
 * Copyright (c) 2012 Aleph Tav
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
 * @copyright Copyright &copy; 2012 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Core;

/**
 * IDelegate interface is intended for building of Aleph framework callbacks.
 * Using such classes we can transform strings in certain format into callback if necessary. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.core
 */
interface IDelegate
{
  /**
   * Constructor. The only argument of it is string in Aleph framework format.
   * The following formats are possible:
   * 'function' - invokes a global function with name 'function'.
   * '->method' - invokes a method 'method' of object Aleph. 
   * '::method' - invokes a static method 'method' of class Aleph.
   * 'class::method' - invokes a static method 'method' of a class 'class'.
   * 'class->method' - invokes a method 'method' of a class 'class' with its constructor without arguments.
   * 'class[]' - creates an object of a class 'class' without sending any arguments in its constructor.
   * 'class[]::method' - the same as 'class::method'.
   * 'class[]->method' - the same as 'class->method'.
   * 'class[n]' - creates an object of a class 'class' with its constructor taking n arguments.
   * 'class[n]::method' - the same as 'class::method'.
   * 'class[n]->method' - invokes a method 'method' of a class 'class' with its constructor taking n arguments.
   *
   * Also $callback can be an instance of \Closure class or Aleph\Core\Delegate class.
   *
   * @param string $callback - an Aleph framework callback string.
   * @access public
   */
  public function __construct($callback);
  
  /**
   * The magic method allowing to invoke an object of this class as a method.
   * The method can take different number of arguments.
   *
   * @return mixed
   * @access public
   */
  public function __invoke();
  
  /**
   * The magic method allowing to convert an object of this class to a callback string.
   *
   * @return string
   * @access public
   */
  public function __toString();
  
  /**
   * Invokes a callback's method or creating a class object.
   * For callbacks in format 'class[n]' and 'class[n]->method' first n arguments of the method
   * are arguments of constructor of a class 'class'.
   *
   * @param array $args - array of method arguments.
   * @return mixed
   * @access public
   */
  public function call(array $args = null);
  
  /**
   * Checks whether it is possible according to permissions to invoke this delegate or not.
   * Permission can be in the following formats:
   * - class name with method name: class::method, class->method
   * - class name without any methods.
   * - function name.
   * - namespace.
   * The method returns TRUE if a callback matches with one or more permissions and FALSE otherwise.
   *
   * @param string | array $permissions - permissions to check.
   * @return boolean
   * @access public
   */
  public function in($permissions);
  
  /**
   * Verifies that the delegate exists and can be invoked.
   *
   * @param boolean $autoload - whether or not to call __autoload by default.
   * @return boolean
   * @access public
   */
  public function isCallable($autoload = true);
  
  /**
   * Returns parameters of a delegate class method, function or closure. 
   * Parameters returns as an array of ReflectionParameter class instance.
   *
   * @return \ReflectionParameter
   * @access public
   */
  public function getParameters();
  
  /**
   * Returns array of detail information of a callback.
   * Output array has the format array('class' => ... [string] ..., 
   *                                   'method' => ... [string] ..., 
   *                                   'static' => ... [boolean] ..., 
   *                                   'numargs' => ... [integer] ..., 
   *                                   'type' => ... [string] ...)
   *
   * @return array
   * @access public
   */
  public function getInfo();
}

/**
 * With this class you can transform a string in certain format into a method or function invoking.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.core
 */
class Delegate implements IDelegate
{
  /**
   * A string in the Aleph callback format.
   *
   * @var string $callback
   * @access protected
   */
  protected $callback = null;
  
  /**
   * Full name (class name with namespace) of a callback class.
   *
   * @var string $class
   * @access protected
   */
  protected $class = null;
  
  /**
   * Method name of a callback class.
   *
   * @var string $method
   * @access protected
   */
  protected $method = null;
  
  /**
   * Equals TRUE if a callback method is static and FALSE if it isn't.
   *
   * @var boolean $static
   * @access protected
   */
  protected $static = null;
  
  /**
   * Shows number of callback arguments that should be transmited into callback constructor.
   *
   * @var integer $numargs
   * @access protected
   */
  protected $numargs = null;
  
  /**
   * Can be equal 'function', 'closure' or 'class' according to callback format.
   *
   * @var string $type
   * @access protected
   */
  protected $type = null;

  /**
   * Constructor. The only argument of it is string in Aleph framework format.
   * The following formats are possible:
   * 'function' - invokes a global function with name 'function'.
   * '->method' - invokes a method 'method' of object Aleph. 
   * '::method' - invokes a static method 'method' of class Aleph.
   * 'class::method' - invokes a static method 'method' of a class 'class'.
   * 'class->method' - invokes a method 'method' of a class 'class' with its constructor without arguments.
   * 'class[]' - creates an object of a class 'class' without sending any arguments in its constructor.
   * 'class[]::method' - the same as 'class::method'.
   * 'class[]->method' - the same as 'class->method'.
   * 'class[n]' - creates an object of a class 'class' with its constructor taking n arguments.
   * 'class[n]::method' - the same as 'class::method'.
   * 'class[n]->method' - invokes a method 'method' of a class 'class' with its constructor taking n arguments.
   *
   * Also $callback can be an instance of \Closure class or Aleph\Core\Delegate class.
   *
   * @param string $callback - an Aleph framework callback string or closure.
   * @access public
   */
  public function __construct($callback)
  {
    if ($callback instanceof Delegate)
    {
      foreach ($callback->getInfo() as $var => $value) $this->{$var} = $value;
      $this->callback = (string)$callback;
    }
    else if ($callback instanceof \Closure)
    {
      $this->type = 'closure';
      $this->method = $callback;
      $callback = 'Closure';
    }
    else
    {
      preg_match('/^([^\[:-]*)(\[([^\]]*)\])?(::|->)?([^:-]*)$/', $callback, $matches);
      if ($matches[4] == '' && $matches[2] == '')
      {
        $this->type = 'function';
        $this->method = $matches[1];
      }
      else
      {
        $this->type = 'class';
        $this->class = $matches[1] ?: 'Aleph';
        $this->numargs = (int)$matches[3];
        $this->static = ($matches[4] == '::');
        $this->method = $matches[5] ?: '__construct';
      }
    }
    $this->callback = $callback;
  } 
  
  /**
   * The magic method allowing to convert an object of this class to a callback string.
   *
   * @return string
   * @access public
   */
  public function __toString()
  {
    return $this->callback;
  }
  
  /**
   * The magic method allowing to invoke an object of this class as a method.
   * The method can take different number of arguments.
   *
   * @return mixed
   * @access public
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
   * @param array $args - array of method arguments.
   * @return mixed
   * @access public
   */
  public function call(array $args = null)
  {
    $args = (array)$args;
    if ($this->type != 'class') return call_user_func_array($this->method, $args);
    else
    {
      if ($this->static) return call_user_func_array(array($this->class, $this->method), $args);
      if ($this->class == 'Aleph') $class = \Aleph::instance();
      else if (\Aleph::get('page')) $class = \Aleph::get('page');
      else
      {
        $class = new \ReflectionClass($this->class);
        if ($this->numargs == 0) $class = $class->newInstance();
        else $class = $class->newInstanceArgs(array_splice($args, 0, $this->numargs));
      }
      if ($this->method == '__construct') return $class;
      return call_user_func_array(array($class, $this->method), $args);
    }
  }

  /**
   * Checks whether it is possible according to permissions to invoke this delegate or not.
   * Permission can be in the following formats:
   * - class name with method name: class::method, class->method
   * - class name without any methods.
   * - function name.
   * - namespace.
   * The method returns TRUE if a callback matches with one or more permissions and FALSE otherwise.
   *
   * @param string | array $permissions - permissions to check.
   * @return boolean
   * @access public
   */
  public function in($permissions)
  {
    if ($this->type == 'closure' || $this->type = 'control') return true;
    if ($this->type == 'function')
    {
      $m = $this->split($this->method);
      foreach ((array)$permissions as $permission)
      {
        $p = $this->split($permission);
        if ($p[0] != '' && $m == $p || $p[0] == '' && substr($m[1], 0, strlen($p[1])) == $p[1]) return true;
      }
    }
    else
    {
      $m = $this->split($this->class);
      foreach ((array)$permissions as $permission)
      {
        $info = explode($this->static ? '::' : '->', $permission);
        if (!empty($info[1]) && $info[1] != $this->method) continue;
        $p = $this->split($info[0]);
        if ($p[0] != '' && $m == $p || $p[0] == '' && substr($m[1], 0, strlen($p[1])) == $p[1]) return true;
      }
    }
    return false;
  }
  
  /**
   * Verifies that the delegate exists and can be invoked.
   *
   * @param boolean $autoload - whether or not to call __autoload by default.
   * @return boolean
   * @access public
   */
  public function isCallable($autoload = true)
  {
    if ($this->type == 'closure') return true;
    if ($this->type == 'function') return function_exists($this->method);
    $static = $this->static;
    $methodExists = function($class, $method) use ($static)
    {
      if (!method_exists($class, $method)) return false;
      $method = foo(new \ReflectionClass($class))->getMethod($method);
      return $method->isPublic() && $static === $method->isStatic();
    };
    if (class_exists($this->class, false)) return $methodExists($this->class, $this->method);
    if (!$autoload) return false;
    if (!\Aleph::getInstance()->load($this->class)) return false;
    return $methodExists($this->class, $this->method);
  }
  
  /**
   * Returns parameters of a delegate class method, function or closure. 
   * Parameters returns as an array of ReflectionParameter class instance.
   *
   * @return \ReflectionParameter
   * @access public
   */
  public function getParameters()
  {
    if ($this->type != 'class') return foo(new \ReflectionFunction($this->method))->getParameters();
    return foo(new \ReflectionClass($this->class))->getMethod($this->method)->getParameters();
  }
  
  /**
   * Returns array of detail information of a callback.
   * Output array has the format array('class' => ... [string] ..., 
   *                                   'method' => ... [string] ..., 
   *                                   'static' => ... [boolean] ..., 
   *                                   'numargs' => ... [integer] ..., 
   *                                   'type' => ... [string] ...)
   *
   * @return array
   * @access public
   */
  public function getInfo()
  {
    return array('class' => $this->class, 
                 'method' => $this->method,
                 'static' => $this->static,
                 'numargs' => $this->numargs,
                 'type' => $this->type);
  } 
  
  /**
   * Splits full class name on two part: namespace and proper class name. Method returns these parts as an array.
   *
   * @param string $identifier - full class name.
   * @return array
   * @access protected
   */
  protected function split($identifier)
  {
    $ex = explode('\\', $identifier);
    return array(array_pop($ex), implode('\\', $ex));
  }
}