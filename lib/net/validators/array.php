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

use Aleph\Core;

/**
 * ValidatorArray compares the given array with another array.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.net
 */
class ValidatorArray extends Validator
{
  /**
   * Error message templates.
   */
  const ERR_VALIDATOR_ARRAY_1 = 'The first argument of "validate" method should be an array.';
  const ERR_VALIDATOR_ARRAY_2 = 'The property "array" should be an array.';
  const ERR_VALIDATOR_ARRAY_3 = 'Invalid "type" property. It should be one of the following values: "numeric", "associative" or "mixed".';

  /**
   * The array to be compared with.
   *
   * @var array $array
   * @access public
   */
  public $array = null;
  
  /**
   * Determines whether the strict comparison is used.
   *
   * @var boolean $strict
   * @access public
   */
  public $strict = false;
  
  /**
   * Determines the required type of the validating array.
   * Possible values are "numeric", "associative" and "mixed".
   * The null value means no type checking.
   *
   * @var string $type
   * @access public
   */
  public $type = null;
  
  /**
   * Validates a value.
   * The method returns TRUE if the given array equal the another array. Otherwise, the method returns FALSE.
   *
   * @param mixed $entity - the array for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->empty && $this->isEmpty($entity)) return $this->reason = true;
    if (!is_array($entity)) throw new Core\Exception($this, 'ERR_VALIDATOR_ARRAY_1');
    if ($this->type !== null)
    {
      switch (strtolower($this->type))
      {
        case 'numeric':
          foreach ($entity as $k => $v) if (!is_numeric($k))
          {
            $this->reason = ['code' => 1, 'reason' => 'not numeric array'];
            return false;
          }
          break;
        case 'associative':
          foreach ($entity as $k => $v) if (!is_string($k))
          {
            $this->reason = ['code' => 2, 'reason' => 'not associative array'];
            return false;
          }
          break;
        case 'mixed':
          $n = 0;
          foreach ($entity as $k => $v) 
          {
            if (is_string($k)) $n |= 1;
            else if (is_numeric($k)) $n |= 2;
            if ($n == 3) break;
          }
          if ($n != 3)
          {
            $this->reason = ['code' => 3, 'reason' => 'not mixed array'];
            return false;
          }
          break;
        default:
          throw new Core\Exception($this, 'ERR_VALIDATOR_ARRAY_3', $this->type);
      }
    }
    if ($this->array !== null)
    {
      if (!is_array($this->array)) throw new Core\Exception($this, 'ERR_VALIDATOR_ARRAY_2');
      if ($this->strict)
      {
        if ($this->array === $entity) return $this->reason = true;
      }
      else
      {
        if ($this->array == $entity) return $this->reason = true;
      }
      $this->reason = ['code' => 0, 'arrays are not equal'];
      return false;
    }
    return $this->reason = true;
  }
}