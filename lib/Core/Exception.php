<?php
/**
 * Copyright (c) 2013 - 2015 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2015 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Core;
 
/**
 * Exception allows to generate exceptions with parameterized error messages which possible to get by their tokens.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.1.0
 * @package aleph.core
 */
class Exception extends \Exception
{
    /**
     * Name of a error message constant.
     * 
     * @var string $token
     * @access protected
     */
    protected $token = null;
  
    /**
     * Class name in which there is a constant of exception message.
     *
     * @var string $class
     * @access protected
     */
    protected $class = null;

    /**
     * Constructor.
     *
     * @param mixed $const - the name of a constant that contains error message template.
     * @param mixed $vars - the error template variable or variables.
     * @param integer $code - the exception code.
     * @param Exception $previous - the previous exception used for the exception chaining. 
     * @access public
     */
    public function __construct($const, $vars = [], $code = 0, \Exception $previous = null)
    {
        if (is_array($const))
        {
            if (count($const))
            {
                $class = array_shift($const);
                $this->class = is_object($class) ? get_class($class) : $class;
                $token = array_shift($const);
                $this->token = $token;
            }
        }
        else
        {
            $const = explode('::', $const);
            if (isset($const[1]))
            {
                $this->class = ltrim($const[0], '\\');
                $this->token = $const[1];
            }
            else
            {
                $this->token = $const[0];
            }
        }
        $vars = is_array($vars) ? $vars : [$vars];
        if ($this->class)
        {
            $error = $this->token ? constant($this->class . '::' . $this->token) : '';			
            $error = vsprintf($error, $vars);
        }
        else
        {
            $error = vsprintf($this->token, $vars);
        }
        parent::__construct($error, $code, $previous);
    }
  
    /**
     * Returns class name in which there is a error message constant.
     *
     * @return string
     * @access public
     */
    public function getClass()
    {
        return $this->class;
    }
  
    /**
     * Returns token of the error message.
     *
     * @return string
     * @access public
     */
    public function getToken()
    {
        return $this->token;
    }
  
    /**
     * Returns full information about the current exception.
     *
     * @return array
     * @access public
     */
    public function getInfo()
    {
        return \Aleph::analyzeException($this);
    }
}