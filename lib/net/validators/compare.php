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
 * ValidatorCompare compares the specified value with another value(s) according to the given mode and comparison operator.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.net
 */
class ValidatorCompare extends Validator
{
  /**
   * Error message templates.
   */
  const ERR_VALIDATOR_COMPARE_1 = 'The mode "[{var}]" is invalid. You can only use the following mode values: "and", "or", "xor".';
  const ERR_VALIDATOR_COMPARE_2 = 'The operator "[{var}]" is invalid. You can only use the following operators: "==", "===", "!=", "!==", ">", "<", ">=", "<=".';

  /**
   * The value or values to be compared with.
   *
   * @var mixed $value
   * @access public
   */
  public $value = null;
  
  /**
   * Determines whether $value is an array of values or a single value for comparison.
   *
   * @var boolean $hasMultipleValues
   * @access public
   */
  public $hasMultipleValues = true;
  
  /**
   * Determines the way of the calculation of the comparison result with multiple values.
   * If $mode equals "and" then the result of the comparison with all values should be TRUE.
   * If $mode is "or" then at least one comparison result should be TRUE.
   * If $mode equals "xor" then the only one comparison result should get TRUE.
   *
   * @var string $mode
   * @access public
   */   
  public $mode = 'and';
  
  /**
   * The operator for comparison.
   * The following operators are valid: "==", "!=", "===", "!==", "<", ">", "<=", ">=".
   *
   * @var string $operator
   * @access public
   */
  public $operator = '==';
  
  /**
   * Determines whether the value can be null or empty.
   * If $allowEmpty is TRUE then validating empty value will be considered valid.
   *
   * @var boolean $allowEmpty
   * @access public   
   */
  public $allowEmpty = true;
  
  /**
   * Contains the number of the successful matches of the last compare operation.
   *
   * @var integer $matches
   * @access protected
   */
  protected $matches = 0;
  
  /**
   * Returns the number of the successful matches of the last compare operation.
   *
   * @return integer
   * @access public
   */
  public function getMatchingNumber()
  {
    return $this->matches;
  }

  /**
   * Validates a value.
   * The method returns TRUE if the validating value matches the validation conditions and FALSE otherwise.
   *
   * @param mixed $entity - the value for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->allowEmpty && $this->isEmpty($entity))
    {
      $this->matches = 0;
      return true;
    }
    $n = 0;
    $values = $this->hasMultipleValues && is_array($this->value) ? $this->value : array($this->value);
    switch ($this->operator)
    {
      case '===':
        foreach ($values as $value) if ($value === $entity) $n++;
        break;
      case '!==':
        foreach ($values as $value) if ($value !== $entity) $n++;
        break;
      case '==':
        foreach ($values as $value) if ($value == $entity) $n++;
        break;
      case '!=':
        foreach ($values as $value) if ($value != $entity) $n++;
        break;
      case '<':
        foreach ($values as $value) if ($value < $entity) $n++;
        break;
      case '>':
        foreach ($values as $value) if ($value > $entity) $n++;
        break;
      case '<=':
        foreach ($values as $value) if ($value <= $entity) $n++;
        break;
      case '>=':
        foreach ($values as $value) if ($value >= $entity) $n++;
        break;
      default:
        throw new Core\Exception($this, 'ERR_VALIDATOR_COMPARE_2', $this->operator);
    }
    $this->validValues = $n;
    switch (strtolower($this->mode))
    {
      case 'and': return $n == count($values);
      case 'or': return $n > 0;
      case 'xor': return $n == 1;
    }
    throw new Core\Exception($this, 'ERR_VALIDATOR_COMPARE_1', $this->mode);
  }
}