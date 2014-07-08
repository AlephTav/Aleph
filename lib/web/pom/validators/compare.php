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

use Aleph\MVC;

class VCompare extends Validator
{
  protected $ctrl = 'vcompare';

  public function __construct($id)
  {
    parent::__construct($id);
    $this->dataAttributes['caseinsensitive'] = 1;
  }
  
  public function validate()
  {
    $this->result = [];
    $ids = $this->getControls(); 
    $len = count($ids);
    if ($len == 0) 
    {
      $this->attributes['state'] = true;
      return true;
    }
    $view = MVC\Page::$current->view;
    switch (isset($this->attributes['mode']) ? strtoupper($this->attributes['mode']) : 'AND')
    {
      default:
      case 'AND':
        $flag = true;
        for ($i = 0; $i < $len - 1; $i++) 
        {
          $ctrl1 = $view->get($ids[$i]);
          if ($ctrl1 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]);
          $value1 = $ctrl1->validate($this);
          for ($j = $i + 1; $j < $len; $j++)
          {
            $ctrl2 = $view->get($ids[$j]);
            if ($ctrl2 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$j]);
            $value2 = $ctrl2->validate($this);
            if ($value1 === $value2) $this->result[$ids[$i]] = $this->result[$ids[$j]] = true;
            else $this->result[$ids[$i]] = $this->result[$ids[$j]] = $flag = false;
          }
        }
        break;
      case 'OR':
        $flag = false;
        for ($i = 0; $i < $len - 1; $i++) 
        {
          $ctrl1 = $view->get($ids[$i]);
          if ($ctrl1 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]);
          $value1 = $ctrl1->validate($this);
          for ($j = $i + 1; $j < $len; $j++)
          {
            $ctrl2 = $view->get($ids[$j]);
            if ($ctrl2 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$j]);
            $value2 = $ctrl2->validate($this);
            if ($value1 === $value2) $this->result[$ids[$i]] = $this->result[$ids[$j]] = $flag = true;
            else $this->result[$ids[$i]] = $this->result[$ids[$j]] = false;
          }
        }
        break;
      case 'XOR':
        $n = 0;
        for ($i = 0; $i < $len - 1; $i++) 
        {
          $ctrl1 = $view->get($ids[$i]);
          if ($ctrl1 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]);
          $value1 = $ctrl1->validate($this);
          for ($j = $i + 1; $j < $len; $j++)
          {
            $ctrl2 = $view->get($ids[$j]);
            if ($ctrl2 === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$j]);
            $value2 = $ctrl2->validate($this);
            if ($value1 === $value2)
            {
              $this->result[$ids[$i]] = $this->result[$ids[$j]] = $n < 1;
              $n++;
            }
            else $this->result[$ids[$i]] = $this->result[$ids[$j]] = false;
          }
        }
        $flag = $n == 1;
        break;
    }
    return $this->attributes['state'] = $flag;
  }
  
  public function check($value)
  {
    return empty($this->attributes['caseinsensitive']) ? (string)$value : strtolower($value);
  }
}