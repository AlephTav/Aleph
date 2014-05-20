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

class DropDownBox extends Control
{
  protected $ctrl = 'dropdownbox';
  
  public function __construct($id, $value = null)
  {
    parent::__construct($id);
    $this->properties['value'] = $value;
    $this->properties['caption'] = null;
    $this->properties['options'] = [];
  }
  
  public function clean()
  {
    $this->properties['value'] = '';
    return $this;
  }

  public function validate(Validator $validator)
  {
    return $validator->check(is_array($this->properties['value']) ? implode('', $this->properties['value']) : $this->properties['value']);
  }
  
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