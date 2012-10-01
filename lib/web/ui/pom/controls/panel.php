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

interface IPanel extends \IteratorAggregate//, \ArrayAccess, \Countable
{
}

class Panel extends Control implements IPanel
{
  const ERR_PANEL_1 = 'Property Aleph\Web\UI\POM\Panel->tpl is read-only.';
  const ERR_PANEL_2 = '[{var}] with ID = "[{var}]" exists already in the Panel with fullID = "[{var}]"';
  
  protected $controls = array();
  
  protected $tpl = null;
  
  public function __construct($id)
  {
    parent::__construct('panel', $id);
    $this->properties['expire'] = 0;
    $this->properties['tag'] = 'div';
    $this->tpl = new Core\Template();
  }
  
  public function getVS()
  {
    return parent::getVS() + array('controls' => array_keys($this->controls)) + array('tpl' => $this->tpl);
  }
  
  public function setVS(array $vs)
  {
    if ($vs['controls']) $this->controls = array_combine($vs['controls'], $vs['controls']);
    $this->tpl = $vs['tpl'];
    parent::setVS($vs);
  }
  
  public function getControls()
  {
    return $this->controls;
  }
  
  public function getIterator()
  {
    return new Iterator($this->controls);
  }

  public function __set($param, $value)
  {
    if ($param == 'tpl') throw new Core\Exception($this, 'ERR_PANEL_1');
    else parent::__set($param, $value);
  }

  public function __get($param)
  {
    if ($param == 'tpl') return $this->tpl;
    return parent::__get($param);
  }
  
  public function get($id, $isRecursion = true)
  {
    $ctrl = Control::getByUniqueID($id);
    if ($ctrl) return $ctrl;
    $searchControl = function($cid, $controls, $deep = -1) use(&$searchControl)
    {
      foreach ($controls as $obj)
      {
        $vs = $obj instanceof IControl ? $obj->getVS() : Control::vs($obj);
        if ($vs['parameters'][1]['id'] == $cid[0])
        {
          $m = 1; $n = count($cid);
          for ($k = 1; $k < $n; $k++)
          {
            if (!isset($vs['controls'])) break;
            $controls = $vs['controls']; $flag = false;
            foreach ($controls as $obj)
            {
              $vs = $obj instanceof IControl ? $obj->getVS() : Control::vs($obj);
              if ($vs['parameters'][1]['id'] == $cid[$k])
              {
                $m++;
                $flag = true;
                break;
              }
            }
            if (!$flag) break;
          }
          if ($m == $n) break;
          return false;
        }
        else if (isset($vs['controls']) && ($deep > 0 || $deep < 0)) 
        {
          $ctrl = $searchControl($cid, $vs['controls'], $deep > 0 ? $deep - 1 : -1);
          if ($ctrl !== false) return $ctrl;
        }
      }
      return Control::getByUniqueID($vs['parameters'][1]['uniqueID']);
    };
    $cid = explode('.', $id);
    if ($isRecursion) return $searchControl($cid, $this->controls);
    return $searchControl($cid, $this->controls, 0);
  }
  
  public function invokeMethod($method, $uniqueID = null)
  {
    $vs = Control::vs($uniqueID ?: $this->uniqueID);
    if (isset($vs['controls']))
    {
      foreach ($vs['controls'] as $uID)
      {
        $this->invokeMethod($method, $uID);
      }
    }
    if (!empty($vs['methods'][$method])) 
    {
      if ($this->uniqueID == $uniqueID) $this->{$method}();
      else Control::vsGet($uniqueID)->{$method}();
    }
  }
  
  public function add(IControl $ctrl)
  {
    foreach ($this->controls as $uniqueID => $obj)
    {
      $vs = $obj instanceof IControl ? $obj->getVS() : Control::vs($uniqueID);
      if ($vs['parameters'][1]['id'] == $ctrl->id) throw new Core\Exception('ERR_PANEL_2', get_class($ctrl), $ctrl->id, $this->getFullID());
    }
    if (Control::vs($this->properties['uniqueID']))
    {
      $this->controls[$ctrl->uniqueID] = $ctrl->uniqueID;
      $ctrl->setParent($this);
      Control::vsSet($ctrl, false, true);
    }
    else
    {
      $properties = $ctrl->getProperties();
      $properties['parentUniqueID'] = $this->properties['uniqueID'];
      $ctrl->setProperties($properties);
      $this->controls[$ctrl->uniqueID] = $ctrl;
    }
  }
  
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    return '<' . $this->properties['tag'] . $this->renderAttributes() . $this->renderEvents() . '>' . $this->getInnerHTML() . '</' . $this->properties['tag'] . '>';
  }
  
  public function getInnerHTML()
  {
    $this->tpl->expire = $this->properties['expire'];
    if ($this->tpl->isExpired()) foreach ($this as $uniqueID => $ctrl) $this->tpl->{$uniqueID} = $ctrl->render();
    return $this->tpl->render();
  }
}