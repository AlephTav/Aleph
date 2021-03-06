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

namespace Aleph\Web\POM;

use Aleph\MVC;

/**
 * This validator checks whether the values of the validating controls matches the user defined conditions.
 *
 * The control has the following properties:
 * id - the logic identifier of the control.
 * visible - determines whether or not the control is visible on the client side.
 * tag - determines the tag of the validator's container element.
 *
 * The special control attributes:
 * controls - comma separated logical or unique identifiers of the validating controls.
 * groups - comma separated names of validation groups.
 * mode - the validation mode. Valid values are "AND", "OR" and "XOR".
 * index - the order of the validator launch.
 * hiding - determines whether the validator element will be invisible (CSS property "display" equals "none") on the client when the validator is valid.
 * text - the validator's message.
 * state - the validator's status.
 * locked - determines whether the validator is locked. The locked validator does not participate in the validation process.
 * clientFunction - the validation function on the client side.
 * serverFunction - the validation function (delegate) on the server side.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.web.pom
 */
class VCustom extends Validator
{
  /**
   * The validator type.
   *
   * @var string $ctrl
   * @access protected   
   */
  protected $ctrl = 'vcustom';

  /**
   * Constructor. Initializes the validator properties.
   *
   * @param string $id - the logic identifier of the validator.
   * @access public
   */
  public function __construct($id)
  {
    parent::__construct($id);
    $this->dataAttributes['clientfunction'] = 1;
    $this->dataAttributes['serverfunction'] = 1;
  }
  
  /**
   * Validates controls of the validator.
   * The method returns TRUE if all controls values matches the user defined conditions and FALSE otherwise.
   *
   * @return boolean
   * @access public
   */
  public function validate()
  {
    if (!empty($this->attributes['serverfunction'])) return $this->attributes['state'] = \Aleph::delegate($this->attributes['serverfunction'], $this);
    $flag = isset($this->attributes['state']) ? (bool)$this->attributes['state'] : true;
    foreach ($this->getControls() as $id) $this->result[$id] = $flag;
    $this->attributes['state'] = $flag;
    return empty($this->attributes['clientfunction']) ? $flag : true;
  }
  
  /**
   * Returns value that passed to the method. 
   *
   * @param mixed $value - the value to validate.
   * @return string
   * @access public
   */
  public function check($value)
  {
    return $value; 
  }
}