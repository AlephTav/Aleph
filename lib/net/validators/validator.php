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
 * This class is the base class for all validators.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.net
 */
abstract class Validator
{
  const ERR_VALIDATOR_1 = 'Invalid validator type "[{var}]". The only following types are valid: "type", "required", "compare", "regexp", "string", "number", "array", "xml" and "custom".';
  const ERR_VALIDATOR_2 = 'The third parameter of "verify" method is invalid. You can only use the following mode values: "and", "or", "xor".';
  const ERR_VALIDATOR_3 = 'The second parameter should only contain objects of Aleph\Net\Validator type.';

  /**
   * Creates and returns a validator object of the required type.
   * Validator type can be one of the following values: "type", "required", "compare", "regexp", "string", "number", "array", "xml" and "custom".
   *
	  * @param string $type - the type of the validator object.
   * @param array $params - initial values to be applied to the validator properties.
   * @return Aleph\Net\Validator
   * @access public
   */
  public static function getInstance($type, array $params = [])
  {
    switch (strtolower($type))
    {
      case 'type':
        $validator = new ValidatorType();
        break;
      case 'required':
        $validator = new ValidatorRequired();
        break;
      case 'compare':
        $validator = new ValidatorCompare();
        break;
      case 'regexp':
        $validator = new ValidatorRegExp();
        break;
      case 'string':
        $validator = new ValidatorString();
        break;
      case 'number':
        $validator = new ValidatorNumber();
        break;
      case 'array':
        $validator = new ValidatorArray();
        break;
      case 'xml':
        $validator = new ValidatorXML();
        break;
      case 'json':
        $validator = new ValidatorJSON();
        break;
      case 'custom':
        $validator = new ValidatorCustom();
        break;
      default:
        throw new Core\Exception('Aleph\Net\Validator::ERR_VALIDATOR_1', $type);
    }
    foreach ($params as $k => $v) $validator->{$k} = $v;
    return $validator;
  }
  
  /**
   * Verifies the value for matching with one or more validators.
   *
   * @param array $value - the value to be verified.
   * @param array $validators - the validators to be matched with the given value.
   * @param string $mode - determines the way of the calculation of the validation result for multiple validators.
   * @return boolean
   * @access public
   */
  public static function verify($value, array $validators, $mode = 'and')
  {
    $n = 0;
    foreach ($validators as $validator) 
    {
      if (!($validator instanceof Validator)) throw new Core\Exception('Aleph\Net\Validator::ERR_VALIDATOR_3');   
      if ($validator->validate($value)) $n++;
    }
    switch (strtolower($mode))
    {
      case 'and': return $n == count($validators);
      case 'or': return $n > 0;
      case 'xor': return $n == 1;
    }
    throw new Core\Exception('Aleph\Net\Validator::ERR_VALIDATOR_2', $mode);
  }

  /**
   * Validates a single value.
   * The method returns TRUE if the validating value matches the validation conditions and FALSE otherwise.
   *
   * @param mixed $entity - the value for validation.
   * @return boolean
   * @access public
   */
  abstract public function validate($entity);
  
  /**
   * Checks whether the given value is empty.
   * For scalar values the method returns TRUE if the given value is empty and FALSE otherwise.
   * If the given value is an array the methods return TRUE if this array has not any elements and FALSE otherwise.
   * If the given value is an object the methods return TRUE if this object has not any public property and FALSE otherwise.
   *
   * @param mixed $entity - the value to check.
   * @return boolean
   * @access protected
   */
  protected function isEmpty($entity)
  {
    if (is_array($entity)) return count($entity) == 0;
    if (is_object($entity)) return count(get_object_vars($entity)) == 0;
    return strlen($entity) == 0;
  }
}