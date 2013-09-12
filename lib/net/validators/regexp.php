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
 * ValidatorRegExp validates that the given value matches to the specified regular expression pattern.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.net
 */
class ValidatorRegExp extends Validator
{
  const ERR_VALIDATOR_REGEXP_1 = 'The pattern is empty.';

  /**
   * The regular expression to be matched with.
   *
   * @var string $pattern
   */
  public $pattern = null;

  /**
   * Determines whether the value can be null or empty.
   * If $allowEmpty is TRUE then validating empty value will be considered valid.
   *
   * @var boolean $allowEmpty
   * @access public
   */
  public $allowEmpty = true;

  /**
   * Determines whether to invert the validation logic.
   * If set to TRUE, the regular expression defined via $pattern should not match the given value.
   *
   * @var boolean $inversion
   * @access public
   */
  public $inversion = false;
  
  /**
   * Validates a value.
   * The method returns TRUE if the given value matches the specified regular expression. Otherwise, the method returns FALSE.
   *
   * @param string $entity - the value for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->allowEmpty && $this->isEmpty($entity)) return true;
    if (!$this->pattern) throw new Core\Exception($this, 'ERR_VALIDATOR_REGEXP_1');
    if ($this->inversion) return !preg_match($this->pattern, $entity);
    return (bool)preg_match($this->pattern, $entity);
  }
}