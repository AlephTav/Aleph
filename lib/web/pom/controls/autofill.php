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

class Autofill extends TextBox
{
  protected $ctrl = 'autofill';
  
  protected $dataAttributes = ['default' => 1, 'callback' => 1, 'timeout' => 1, 'activeitemclass' => 1];

  public function __construct($id, $value = null)
  {
    parent::__construct($id, $value);
    $this->properties['tag'] = 'div';
  }
  
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    $html = '<' . $this->properties['tag'] . ' id="container_' . $this->attributes['id'] . '"' . $this->renderAttributes(false) . '>' . parent::render();
    $html .= '<ul id="list_' . $this->attributes['id'] . '" style="display:none;"></ul></' . $this->properties['tag'] . '>';
    return $html;
  }
}