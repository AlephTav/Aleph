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

use Aleph\Core,
    Aleph\MVC;

/**
 * This validator checks whether the values of the validating controls are equal to each other.
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
 * caseInsensitive - determines whether the case insensitive comparison is used.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.web.pom
 */
class VCompare extends Validator
{
  /**
   *  Error message template.
   */
  const ERR_VCOMPARE_1 = 'The validating value should be scalar.';

  /**
   * The validator type.
   *
   * @var string $ctrl
   * @access protected   
   */
  protected $ctrl = 'vcompare';

  /**
   * Constructor. Initializes the validator properties.
   *
   * @param string $id - the logic identifier of the validator.
   * @access public
   */
  public function __construct($id)
  {
    parent::__construct($id);
    $this->dataAttributes['caseinsensitive'] = 1;
  }
  
  /**
   * Validates controls of the validator.
   * The returning result depends on the validator's mode:
   * AND - it returns TRUE if all controls values are equal to each other and FALSE otherwise.
   * OR - it returns TRUE if value of one control is equal to the value of at least one other control and FALSE otherwise.
   * XOR - it returns TRUE if exactly two controls have equal values and FALSE otherwise. 
   *
   * @return boolean
   * @access public
   */
  public function validate()
  {
    $this->result = [];
    $ids = $this->getControls(); 
    $len = count($ids);
    if ($len == 0) 
    {
      $this->attributes['state'] = true;
      return true;
    }
    $view = MVC\Page::$current->view;
    switch (isset($this->attributes['mode']) ? strtoupper($this->attributes['mode']) : 'AND')
    {
      default:
      case 'AND':
        $flag = true;
        for ($i = 0; $i < $len - 1; $i++) 
        {
          $ctrl1 = $view->get($ids[$i]);
          if ($ctrl1 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]);
          $value1 = $ctrl1->validate($this);
          for ($j = $i + 1; $j < $len; $j++)
          {
            $ctrl2 = $view->get($ids[$j]);
            if ($ctrl2 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$j]);
            $value2 = $ctrl2->validate($this);
            if ($value1 === $value2) $this->result[$ids[$i]] = $this->result[$ids[$j]] = true;
            else $this->result[$ids[$i]] = $this->result[$ids[$j]] = $flag = false;
          }
        }
        break;
      case 'OR':
        $flag = false;
        for ($i = 0; $i < $len - 1; $i++) 
        {
          $ctrl1 = $view->get($ids[$i]);
          if ($ctrl1 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]);
          $value1 = $ctrl1->validate($this);
          for ($j = $i + 1; $j < $len; $j++)
          {
            $ctrl2 = $view->get($ids[$j]);
            if ($ctrl2 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$j]);
            $value2 = $ctrl2->validate($this);
            if ($value1 === $value2) $this->result[$ids[$i]] = $this->result[$ids[$j]] = $flag = true;
            else $this->result[$ids[$i]] = $this->result[$ids[$j]] = false;
          }
        }
        break;
      case 'XOR':
        $n = 0;
        for ($i = 0; $i < $len - 1; $i++) 
        {
          $ctrl1 = $view->get($ids[$i]);
          if ($ctrl1 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]);
          $value1 = $ctrl1->validate($this);
          for ($j = $i + 1; $j < $len; $j++)
          {
            $ctrl2 = $view->get($ids[$j]);
            if ($ctrl2 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$j]);
            $value2 = $ctrl2->validate($this);
            if ($value1 === $value2)
            {
              $this->result[$ids[$i]] = $this->result[$ids[$j]] = $n < 1;
              $n++;
            }
            else $this->result[$ids[$i]] = $this->result[$ids[$j]] = false;
          }
        }
        $flag = $n == 1;
        break;
    }
    return $this->attributes['state'] = $flag;
  }
  
  /**
   * Returns the given value for comparison.
   * If the given value is not scalar, the method throws exception.
   *
   * @param mixed $value - the value to validate.
   * @return string
   * @access public
   */
  public function check($value)
  {
    if (isset($value) && !is_scalar($value)) throw new Core\Exception($this, 'ERR_VCOMPARE_1');
    return empty($this->attributes['caseinsensitive']) ? (string)$value : strtolower($value);
  }
}