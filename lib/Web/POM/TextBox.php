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

use Aleph\Core;

/**
 * Represents the <input type="text">, <input type="password"> and <textarea> HTML elements.
 *
 * The control has the following properties:
 * id - the logic identifier of the control.
 * visible - determines whether or not the control is visible on the client side.
 * type - type of the control. Valid values are "text", "password" and "memo".
 * value - the control value.
 *
 * The special control attributes:
 * default - the default control value.
 *
 * @version 1.0.0
 * @package aleph.web.pom
 */
class TextBox extends Control
{
  /**
   * Error message templates.
   */
  const ERR_TEXTBOX_1 = 'Incorrect type value "[{var}]". Property "type" can take only one of the following values: "text", "password" and "memo".';
  
  /**
   * The control types.
   */
  const TYPE_TEXT = 'text';
  const TYPE_PASSWORD = 'password';
  const TYPE_MEMO = 'memo';

  /**
   * The control type.
   *
   * @var string $ctrl
   * @access protected
   */
  protected $ctrl = 'textbox';
  
  /**
   * Non-standard control attributes, that should be rendered as "data-" attributes on the web page.
   *
   * @var array $dataAttributes
   * @access protected
   */
  protected $dataAttributes = ['default' => 1];

  /**
   * Constructor. Initializes the control properties and attributes.
   *
   * @param string $id - the logic identifier of the control.
   * @param mixed $value - the control value.
   * @access public
   */
  public function __construct($id, $value = null)
  {
    parent::__construct($id);
    $this->properties['type'] = self::TYPE_TEXT;
    $this->properties['value'] = $value;
  }
  
  /**
   * Sets or returns property value.
   * If $value is not defined, the method returns the current property value. Otherwise, it will set new property value.
   *
   * @param string $property - the property name.
   * @param mixed $value - the property value.
   * @return mixed
   * @access public
   */
  public function &prop($property, $value = null)
  {
    if ($value === null)
    {
      $val = parent::prop($property);
      if (strtolower($property) == 'value' && strlen($val) == 0 && isset($this->attributes['default'])) $val = $this->attributes['default'];
      return $val;
    }
    return parent::prop($property, $value);
  }
  
  /**
   * Sets the control value to empty string.
   *
   * @return self
   * @access public
   */
  public function clean()
  {
    $this->properties['value'] = isset($this->attributes['default']) ? $this->attributes['default'] : '';
    return $this;
  }

  /**
   * Validates the control value.
   *
   * @param ClickBlocks\Web\POM\Validator $validator - the instance of the validator control.
   * @return boolean
   * @access public
   */
  public function validate(Validator $validator)
  {
    return $validator->check($this->prop('value'));
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
    switch ($this->properties['type'])
    {
      case self::TYPE_TEXT:
      case self::TYPE_PASSWORD:
        return '<input type="' . htmlspecialchars($this->properties['type']) . '"' . $this->renderAttributes() . ' value="' . htmlspecialchars($this->properties['value']) . '" />';
      case self::TYPE_MEMO:
        return '<textarea' . $this->renderAttributes() . '>' . $this->properties['value'] . '</textarea>';
    }
    throw new Core\Exception($this, 'ERR_TEXTBOX_1', $this->properties['type']);
  }
}