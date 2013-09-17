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

namespace Aleph\Net;

/**
 * ValidatorString validates that the given string value is of certain length.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.net
 */
class ValidatorString extends Validator
{
  /**
   * The maximum length of the validating string.
   * Null value means no maximum limit.
   * @var integer $max
   * @access public
   */
  public $max = null;

  /**
   * The minimum length of the validating string.
   * Null value means no minimum limit.
   * @var integer $min
   * @access public
   */
  public $min = null;
  
  /**
   * The encoding of the string value to be validated.
   * This property is used only when mbstring PHP extension is enabled.
   * The value of this property will be used as the 2nd parameter of the mb_strlen() function.
   * If this property is not set, the internal character encoding value will be used (see function mb_internal_encoding()).
   * If this property is FALSE, then strlen() will be used even if mbstring is enabled.
   *
   * @var string $charset
   * @access public
   */
  public $charset = false;
  
  /**
   * Validates a string value. If the given value is not string, it will be converted to string.
   * The method returns TRUE if length of the given string is in the required limits. Otherwise, the method returns FALSE.
   *
   * @param string $entity - the value for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->empty && $this->isEmpty($entity)) return $this->reason = true;
    $len = $this->charset !== false && function_exists('mb_strlen') ? mb_strlen($entity, $this->charset) : strlen($entity);
    if ($this->min !== null)
    {
      if ($len < $this->min)
      {
        $this->reason = ['code' => 1, 'reason' => 'too short'];
        return false;
      }
    }
    if ($this->max !== null)
    {
      if ($len > $this->max)
      {
        $this->reason = ['code' => 2, 'reason' => 'too '];
        return false;
      }
    }
    return $this->reason = true;
  }
}