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

namespace Aleph\Web\UI\POM;

use Aleph\Core,
    Aleph\MVC,
    Aleph\Web,
    Aleph\Web\UI\Tags;

class TextBox extends Control
{
  public function __construct($id, $value = null)
  {
    parent::__construct('textbox', $id);
    $this->attributes['name'] = $id;
    $this->attributes['type'] = 'text';
    $this->attributes['value'] = $value;
    $this->attributes['title'] = null;
    $this->attributes['size'] = null;
    $this->attributes['maxlength'] = null;
    $this->attributes['autocorrect'] = null;
    $this->attributes['autocapitalize'] = null;
    $this->attributes['autocomplete'] = null;
    $this->attributes['placeholder'] = null;
    $this->attributes['disabled'] = null;
    $this->attributes['required'] = null;
    $this->attributes['readonly'] = null;
    $this->attributes['data-filter'] = null;
    $this->attributes['data-mask'] = null;
  }

  public function clean()
  {
    $this->attributes['value'] = null;
    return $this;
  }

  public function assign($value)
  {
    $this->attributes['value'] = $value;
    return $this;
  }

  public function validate($mode = null)
  {
    return strlen($this->attributes['value']) > 0;
  }

  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    return '<input' . $this->renderAttributes() . $this->renderEvents() . ' />';
  }
}