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

class TextBox extends Control
{
  const ERR_TEXTBOX_1 = 'Property "type" can take only one of the following values: "text", "password" and "memo".';

  protected $ctrl = 'textbox';
  
  protected $dataAttributes = ['default' => 1];

  public function __construct($id, $value = null)
  {
    parent::__construct($id);
    $this->properties['type'] = 'text';
    $this->properties['value'] = $value;
  }

  /*public function validate($mode = null)
  {
    return strlen($this->attributes['value']) > 0;
  }*/

  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    switch ($this->properties['type'])
    {
      case 'text':
      case 'password':
        return '<input type="' . htmlspecialchars($this->properties['type']) . '"' . $this->renderAttributes() . ' value="' . htmlspecialchars($this->properties['value']) . '" />';
      case 'memo':
        return '<textarea' . $this->renderAttributes() . '>' . $this->properties['value'] . '</textarea>';
    }
    throw new Core\Exception($this, 'ERR_TEXTBOX_1');
  }
}