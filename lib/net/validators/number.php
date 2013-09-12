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
 * ValidatorNumber validates that the given numeric value is in the specified limits.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.net
 */
class ValidatorNumber extends Validator
{
  /**
   * The maximum value of the validating number.
   * Null value means no maximum value.
   * @var float $max
   * @access public
   */
  public $max = null;

  /**
   * The minimum value of the validating number.
   * Null value means no minimum value.
   * @var float $min
   * @access public
   */
  public $min = null;
  
  /**
   * Determines whether the given value can only be an integer.
   *
   * @var boolean $integerOnly
   * @access public
   */
  public $integerOnly = false;
  
  /**
   * Determines whether the value can be null or empty.
   * If $allowEmpty is TRUE then validating empty value will be considered valid.
   *
   * @var boolean $allowEmpty
   * @access public
   */
  public $allowEmpty = true;
  
  /**
   * Validates a number value. If the given value is not number, it will be converted to number.
   * The method returns TRUE if value of the given number is in the required limits. Otherwise, the method returns FALSE.
   *
   * @param float $entity - the value for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->allowEmpty && $this->isEmpty($entity)) return true;
    if ($this->integerOnly && (string)$entity !== (string)(int)$entity) return false;
    $entity = (float)$entity;
    return ($this->min === null || $entity >= (float)$this->min) && ($this->max === null || $entity <= (float)$this->max); 
  }
}