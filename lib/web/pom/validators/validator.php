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

use Aleph\Core,
    Aleph\MVC;

abstract class Validator extends Control
{
  const ERR_VAL_1 = 'Control with ID = "[{var}]" is not found.';
  
  protected $result = [];
  
  protected $dataAttributes = ['controls' => 1, 'groups' => 1, 'mode' => 1, 'index' => 1, 'hiding' => 1, 'text' => 1, 'state' => 1];
  
  public function __construct($id)
  {
    parent::__construct($id);
    $this->attributes['index'] = 0;
    $this->attributes['mode'] = 'AND';
    $this->attributes['hiding'] = false;
    $this->attributes['locked'] = false;
    $this->attributes['controls'] = null;
    $this->attributes['groups'] = 'default';
    $this->attributes['text'] = null;
    $this->attributes['state'] = true;
    $this->properties['tag'] = 'div';
  }

  abstract public function check($value);
  
  public function __set($attribute, $value)
  {
    $attribute = strtolower($attribute);
    if ($attribute == 'groups' && strlen($value) == 0) $value = 'default';
    parent::__set($attribute, $value);
  }

  public function getControls()
  {
    if (!isset($this->attributes['controls'])) return [];
    return array_map('trim', explode(',', $this->attributes['controls']));
  }
  
  public function getGroups()
  {
    if (!isset($this->attributes['groups'])) return [];
    return array_map('trim', explode(',', $this->attributes['groups']));
  }
  
  public function getResult()
  {
    return $this->result;
  }
  
  public function setResult(array $result)
  {
    $this->result = $result;
    return $this;
  }
  
  public function lock($flag = true)
  {
    $this->attributes['locked'] = (bool)$flag;
    return $this;
  }
  
  public function isLocked()
  {
    return !empty($this->attributes['locked']);
  }
  
  public function clean()
  {
    $this->attributes['state'] = true;
    return $this;
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
        for ($i = 0; $i < $len; $i++) 
        {
          $ctrl = $view->get($ids[$i]);
          if ($ctrl === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]); 
          if ($ctrl->validate($this)) $this->result[$ctrl->id] = true;
          else $this->result[$ctrl->id] = $flag = false;
        }
        break;
      case 'OR':
        $flag = false;
        for ($i = 0; $i < $len; $i++) 
        {
          $ctrl = $view->get($ids[$i]);
          if ($ctrl === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]); 
          if ($ctrl->validate($this)) $this->result[$ctrl->id] = $flag = true;
          else $this->result[$ctrl->id] = false;
        }
        break;
      case 'XOR':
        $n = 0;
        for ($i = 0; $i < $len; $i++)
        {
          $ctrl = $view->get($ids[$i]);
          if ($ctrl === false) throw new Core\Exception($this, 'ERR_VAL_1', $ids[$i]); 
          if ($ctrl->validate($this))
          {
            $this->result[$ctrl->id] = $n < 1;
            $n++;
          }
          else $this->result[$ctrl->id] = false;
        }
        $flag = $n == 1;
        break;
    }
    return $this->attributes['state'] = $flag;
  }

  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    $ids = $this->getControls();
    foreach ($ids as &$id) 
    {
      $ctrl = MVC\Page::$current->view->get($id);
      if ($ctrl) $id = $ctrl->id;
    }
    $this->attributes['controls'] = implode(',', $ids);
    if (empty($this->attributes['hiding']))
    {
      return '<' . $this->properties['tag'] . $this->renderAttributes() . '>' . ($this->attributes['state'] ? '' : $this->attributes['text']) . '</' . $this->properties['tag'] . '>';
    }
    if (!empty($this->attributes['state'])) $this->addStyle('display', 'none');
    else $this->removeStyle('display');
    if (!isset($this->attributes['text'])) $this->attributes['text'] = '';
    return '<' . $this->properties['tag'] . $this->renderAttributes() . '>' . $this->attributes['text'] . '</' . $this->properties['tag'] . '>';
  }
}