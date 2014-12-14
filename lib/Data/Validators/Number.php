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

namespace Aleph\Data\Validators;

/**
 * This validator validates that the given numeric value is in the specified limits.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.data.validators
 */
class Number extends Validator
{
  /**
   * The maximum value of the validating number.
   * Null value means no maximum value.
   *
   * @var float $max
   * @access public
   */
  public $max = null;

  /**
   * The minimum value of the validating number.
   * Null value means no minimum value.
   *
   * @var float $min
   * @access public
   */
  public $min = null;
  
  /**
   * Determines whether the strict comparison is used.
   *
   * @var boolean $strict
   * @access public
   */
  public $strict = false;
  
  /**
   * Determines valid format (PCRE) of the given number.
   * Null value means no format checking.
   *
   * @var string $format
   * @access public
   */
  public $format = null;
  
  /**
   * Validates a number value. If the given value is not number, it will be converted to number.
   * The method returns TRUE if value of the given number is in the required limits. Otherwise, the method returns FALSE.
   *
   * @param mixed $entity - the value for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->empty && $this->isEmpty($entity)) return $this->reason = true;
    if ($this->format && !preg_match($this->format, $entity)) 
    {
      $this->reason = ['code' => 0, 'reason' => 'invalid format'];
      return false;
    }
    $entity = (float)$entity;
    if ($this->min !== null) 
    {
      if (!$this->strict && $entity < (float)$this->min || $this->strict && $entity <= (float)$this->min)
      {
        $this->reason = ['code' => 1, 'reason' => 'too small'];
        return false;
      }
    }
    if ($this->max !== null)
    {
      if (!$this->strict && $entity > (float)$this->max || $this->strict && $entity >= (float)$this->max)
      {
        $this->reason = ['code' => 2, 'reason' => 'too large'];
        return false;
      }
    }
    return $this->reason = true;
  }
}