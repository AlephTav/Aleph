<?php
/**
 * Copyright (c) 2014 Aleph Tav
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
 * @copyright Copyright &copy; 2014 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Data\Validators;

use Aleph\Core;

/**
 * This class is the base class for all validators.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.data.validators
 */
abstract class Validator
{
  /**
   * Error message templates.
   */
  const ERR_VALIDATOR_1 = 'Invalid validator type "[{var}]". The only following types are valid: "type", "required", "compare", "regexp", "string", "number", "collection", "xml" and "custom".';
  const ERR_VALIDATOR_2 = 'The third parameter of "verify" method is invalid. You can only use the following mode values: "and", "or", "xor".';
  const ERR_VALIDATOR_3 = 'The second parameter should only contain objects of Aleph\Validators\Validator type.';
  
  /**
   * Determines whether the value can be null or empty.
   * If $empty is TRUE then validating empty value will be considered valid.
   *
   * @var boolean $empty
   * @access public
   */
  public $empty = true;
  
  /**
   * The reason of validator failure. The reason equals TRUE if the validator returns TRUE.
   * The structure of the reason array as follows:
   * [
      'code'    => ... // the reson code.
   *  'reason'  => ... // the short reason message.
   *  'details' => ... // the reason details. It can be omitted for some validators.
   * ]
   *
   * @var array $reason
   * @access protected
   */
  protected $reason = null;

  /**
   * Creates and returns a validator object of the required type.
   * Validator type can be one of the following values: "type", "required", "compare", "regexp", "text", "number", "collection", "xml", "json" and "custom".
   *
	  * @param string $type - the type of the validator object.
   * @param array $params - initial values to be applied to the validator properties.
   * @return Aleph\Data\Validators\Validator
   * @access public
   */
  final public static function getInstance($type, array $params = [])
  {
    $class = 'Aleph\Data\Validators\\' . $type;
    if (!\Aleph::getInstance()->loadClass($class)) throw new Core\Exception('Aleph\Data\Validators\Validator::ERR_VALIDATOR_1', $type);
    $validator = new $class;
    foreach ($params as $k => $v) $validator->{$k} = $v;
    return $validator;
  }
  
  /**
   * Verifies the value for matching with one or more validators.
   *
   * @param mixed $value - the value to be verified.
   * @param array $validators - the validators to be matched with the given value.
   * @param string $mode - determines the way of the calculation of the validation result for multiple validators.
   * @param array $result - array of the results of the validators work. Each array element contains TRUE if the appropriate validator is valid, otherwise the element contains the reason of validator failure.
   * @return boolean
   * @access public
   */
  public static function verify($value, array $validators, $mode = 'and', &$result = null)
  {
    $n = 0; $result = [];
    foreach ($validators as $key => $validator) 
    {
      if (!($validator instanceof Validator)) throw new Core\Exception('Aleph\Data\Validators\Validator::ERR_VALIDATOR_3');   
      if ($validator->validate($value)) $n++;
      $result[$key] = $validator->getReason();
    }
    switch (strtolower($mode))
    {
      case 'and': return $n == count($validators);
      case 'or': return $n > 0;
      case 'xor': return $n == 1;
    }
    throw new Core\Exception('Aleph\Data\Validators\Validator::ERR_VALIDATOR_2', $mode);
  }
  
  /**
   * Returns the reason of validator failure.
   * If the validator returns TRUE the reason will be TRUE as well.
   *
   * @return array
   * @access public
   */
  public function getReason()
  {
    return $this->reason;
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