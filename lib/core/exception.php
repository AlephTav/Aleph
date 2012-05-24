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
 * Exception allows to generate exceptions with parameterized error messages which possible to get by their tokens.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
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
   * Constructor. Constructor takes as arguments a class name with the error constant,
   * the error token (constant name) and values of parameters ​​of the error message.
   *
   * @param string $class
   * @param string $token
   * @params parameters of the error message.
   * @access public
   */
  public function __construct(/* $class, $token, $var1, $var2, ... */)
  {
    $this->class = func_get_arg(0);
    $this->token = func_get_arg(1);
    parent::__construct(call_user_func_array(array('Aleph', 'error'), func_get_args()));
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
}