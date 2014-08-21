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
 * Represents the <select> HTML element. 
 *
 * The control has the following properties:
 * id - the logic identifier of the control.
 * visible - determines whether or not the control is visible on the client side.
 * value - the value of the control.
 * caption - determines the first option with empty value.
 * options - the option array of the <select> element.
 *
 * @version 1.0.0
 * @package aleph.web.pom
 */
class DropDownBox extends Control
{
  /**
   * The control type.
   *
   * @var string $ctrl
   * @access protected
   */
  protected $ctrl = 'dropdownbox';
  
  /**
   * Constructor. Initializes the control properties and attributes.
   *
   * @param string $id - the logic identifier of the control.
   * @param string $value - the control value.
   * @access public
   */
  public function __construct($id, $value = null)
  {
    parent::__construct($id);
    $this->properties['value'] = $value;
    $this->properties['caption'] = null;
    $this->properties['options'] = [];
  }
  
  /**
   * Sets the control value to empty string.
   *
   * @return self
   * @access public
   */
  public function clean()
  {
    $this->properties['value'] = '';
    return $this;
  }

  /**
   * Validates the control value.
   *
   * @param Aleph\Web\POM\Validator $validator - the instance of the validator control.
   * @return boolean
   * @access public
   */
  public function validate(Validator $validator)
  {
    return $validator->check(is_array($this->properties['value']) ? implode('', $this->properties['value']) : $this->properties['value']);
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
    $html = '<select' . $this->renderAttributes() . '>';
    if (strlen($this->properties['caption'])) $html .= $this->getOptionHTML('', $this->properties['caption']);
    foreach ((array)$this->properties['options'] as $key => $value)
    {
      if (is_array($value))
      {
        $html .= '<optgroup label="' . htmlspecialchars($key) . '">';
        foreach ($value as $k => $v) $html .= $this->getOptionHTML($k, $v);
        $html .= '</optgroup>';
      }
      else
      {
        $html .= $this->getOptionHTML($key, $value);
      }
    }
    $html .= '</select>';
    return $html;
  }
  
  /**
   * Returns inner HTML of the <select> element.
   *
   * @param mixed $value - the control value.
   * @param string $text - the option that corresponds to the given value.
   * @return string
   * @access protected
   */
  protected function getOptionHTML($value, $text)
  {
    if (is_array($this->properties['value'])) 
    {
      $s = in_array($value, $this->properties['value']) ? ' selected="selected"' : '';
    }
    else
    {
      $s = (string)$this->properties['value'] === (string)$value ? ' selected="selected"' : '';
    }
    return '<option value="' . htmlspecialchars($value) . '"' . $s . '>' . htmlspecialchars($text) . '</option>';
  }
}