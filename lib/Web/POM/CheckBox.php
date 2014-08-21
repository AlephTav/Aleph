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

namespace Aleph\Web\POM;

/**
 * Represents the <input type="checkbox"> HTML element.
 *
 * The control has the following properties:
 * id - the logic identifier of the control.
 * visible - determines whether or not the control is visible on the client side.
 * value - the value of the checkbox control.
 * caption - the checkbox title.
 * align - the caption alignment. Valid values are "right" and "left".
 * tag - determines HTML tag of the external HTML element of the checkbox control.
 *
 * @version 1.0.0
 * @package aleph.web.pom
 */
class CheckBox extends Control
{
  /**
   * The control type.
   *
   * @var string $ctrl
   * @access protected
   */
  protected $ctrl = 'checkbox';
  
  /**
   * Constructor. Initializes the control properties and attributes.
   *
   * @param string $id - the logic identifier of the control.
   * @param string $value - the value of the <input> element.
   * @access public
   */
  public function __construct($id, $value = null)
  {
    parent::__construct($id);
    $this->properties['value'] = $value;
    $this->properties['caption'] = null;
    $this->properties['align'] = 'right';
    $this->properties['tag'] = 'div';
  }
  
  /**
   * Always returns TRUE as a sign of a control having complex structure (more than one HTML element).
   *
   * @return boolean
   * @access public
   */
  public function hasContainer()
  {
    return true;
  }
  
  /**
   * Turns the checkbox to checked/unchecked state.
   *
   * @param boolean $flag - if it is TRUE, the checkbox will be checked. Otherwise, it will be unchecked.
   * @return self
   * @access public
   */
  public function check($flag = true)
  {
    if ($flag) $this->attributes['checked'] = 'checked';
    else unset($this->attributes['checked']);
    return $this;
  }
  
  /**
   * Turns the checkbox to unchecked state.
   *
   * @return self
   * @access public
   */
  public function clean()
  {
    unset($this->attributes['checked']);
    return $this;
  }

  /**
   * Returns TRUE if the checkbox is checked and FALSE otherwise.
   * This method is only affected by the required validator.
   *
   * @param Aleph\Web\POM\Validator $validator - the instance of the validator control.
   * @return boolean
   * @access public
   */
  public function validate(Validator $validator)
  {
    if ($validator instanceof VRequired) return $validator->check(!empty($this->attributes['checked']));
    return true;
  }
  
  /**
   * Returns HTML of the control.
   *
   * @return string
   * @access public
   */
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    $html = '<' . $this->properties['tag'] . ' id="' . self::CONTAINER_PREFIX . $this->attributes['id'] . '"' . $this->renderAttributes(false) . '>';
    if (strlen($this->properties['caption'])) $label = '<label for="' . $this->attributes['id'] . '">' . $this->properties['caption'] . '</label>';
    if ($this->properties['align'] == 'left' && isset($label)) $html .= $label;
    $html .= '<input type="checkbox" value="' . htmlspecialchars($this->properties['value']) . '"' . $this->renderAttributes() . ' />';
    if ($this->properties['align'] != 'left' && isset($label)) $html .= $label;
    $html .= '</' . $this->properties['tag'] . '>';
    return $html;
  }
}