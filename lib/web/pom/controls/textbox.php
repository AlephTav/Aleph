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

namespace Aleph\Web\POM;

use Aleph\Core;

class TextBox extends Control
{
  const ERR_TEXTBOX_1 = 'Incorrect type value "[{var}]". Property "type" can take only one of the following values: "text", "password" and "memo".';
  
  const TYPE_TEXT = 'text';
  const TYPE_PASSWORD = 'password';
  const TYPE_MEMO = 'memo';

  protected $ctrl = 'textbox';
  
  protected $dataAttributes = ['default' => 1];

  public function __construct($id, $value = null)
  {
    parent::__construct($id);
    $this->properties['type'] = self::TYPE_TEXT;
    $this->properties['value'] = $value;
  }
  
  public function offsetGet($property)
  {
    $value = parent::offsetGet($property);
    if (strtolower($property) == 'value' && strlen($value) == 0 && isset($this->attributes['default'])) $value = $this->attributes['default'];
    return $value;
  }
  
  public function clean()
  {
    $this->properties['value'] = isset($this->attributes['default']) ? $this->attributes['default'] : '';
    return $this;
  }

  public function validate(Validator $validator)
  {
    return $validator->check($this['value']);
  }

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