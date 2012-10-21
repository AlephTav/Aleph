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

class ValidatorRequired extends Validator
{
  public function __construct($id, $message = null)
  {
    parent::__construct('validatorrequired', $id, $message);
  }

  public function validate()
  {
    switch ($this->properties['mode'])
    {
      case 'AND':
        $flag = true;
        foreach ($this->properties['controls'] as $id) $flag &= $this->validateControl($id);
        break;
      case 'OR':
        $flag = false;
        foreach ($this->properties['controls'] as $id) $flag |= $this->validateControl($id);
        break;
      case 'XOR':
        $n = 0;
        foreach ($this->properties['controls'] as $id) if ($this->validateControl($id)) $n++;
        $flag = ($n == 1);
        break;
    }
    $this->properties['isValid'] = $flag;
    $this->doAction();
    return $this;
  }

  public function check($value)
  {
    return (strlen($value) > 0);
  }
}