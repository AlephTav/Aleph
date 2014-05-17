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

class Radio extends Control
{
  protected $ctrl = 'radio';
  
  public function __construct($id, $value = null)
  {
    parent::__construct($id);
    $this->properties['value'] = $value;
    $this->properties['caption'] = null;
    $this->properties['align'] = 'right';
    $this->properties['tag'] = 'div';
  }
  
  public function check($flag = true)
  {
    if ($flag) $this->attributes['checked'] = 'checked';
    else unset($this->attributes['checked']);
  }
  
  public function clean()
  {
    unset($this->attributes['checked']);
  }

  public function validate(Validator $validator)
  {
    if ($validator instanceof ValidatorRequired) return $validator->check(!empty($this->attributes['checked']));
    return true;
  }
  
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    $html = '<' . $this->properties['tag'] . ' id="container_' . $this->attributes['id'] . '"' . $this->renderAttributes(false) . '>';
    if (strlen($this->properties['caption'])) $label = '<label for="' . $this->attributes['id'] . '">' . $this->properties['caption'] . '</label>';
    if ($this->properties['align'] == 'left' && isset($label)) $html .= $label;
    $html .= '<input type="radio" value="' . htmlspecialchars($this->properties['value']) . '"' . $this->renderAttributes() . ' />';
    if ($this->properties['align'] != 'left' && isset($label)) $html .= $label;
    $html .= '</' . $this->properties['tag'] . '>';
    return $html;
  }
}