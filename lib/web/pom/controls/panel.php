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
    Aleph\MVC,
    Aleph\Web;

class Panel extends Control implements \IteratorAggregate, \Countable
{
  const ERR_PANEL_1 = 'Web control [{var}] (logic ID: [{var}]) already exists in panel [{var}] (full ID: [{var}]).';
  const ERR_PANEL_2 = 'Web control [{var}] does not exist in panel [{var}] (full ID: [{var}]).';
  
  public $tpl = null;
  
  protected $ctrl = 'panel';
  
  protected $controls = [];
  
  protected $inDetach = false;
  
  public function __construct($id, $template = null)
  {
    parent::__construct($id);
    $this->properties['tag'] = 'div';
    $this->tpl = new Core\Template($template);
  }
  
  public function getVS()
  {
    return parent::getVS() + ['controls' => array_keys($this->controls), 'tpl' => $this->tpl];
  }
  
  public function setVS(array $vs)
  {
    $this->controls = array_combine($vs['controls'], $vs['controls']);
    $this->tpl = $vs['tpl'];
    parent::setVS($vs);
  }
  
  public function getControls()
  {
    return $this->controls;
  }
  
  public function count()
  {
    return count($this->controls);
  }
  
  public function getIterator()
  {
    return new Iterator($this->controls);
  }
  
  public function parse($template = null, array $vars = null)
  {
    $res = View::analyze($template ?: $this->tpl->getTemplate(), $vars);
    $this->tpl->setTemplate($res['html']);
    foreach ($this->controls as $ctrl) $this->detach($ctrl);
    foreach ($res['controls'] as $ctrl) $this->add($ctrl);
    $this->refresh();
  }
  
  public function add(Control $ctrl)
  {
    if (isset($this->controls[$ctrl->id]) || $this->get($ctrl['id'], false)) throw new Core\Exception($this, 'ERR_PANEL_1', get_class($ctrl), $ctrl['id'], get_class($this), $this->getFullID());
    $ctrl->setParent($this);
    $this->controls[$ctrl->id] = $ctrl;
  }
  
  public function detach($id, $time = 0)
  {
    if ($this->inDetach) return $this;
    $this->inDetach = true;
    if ($id instanceof Control) $ctrl = $id->remove($time);
    else 
    {
      $ctrl = $this->get($id, false);
      if (!$ctrl) throw new Core\Exception($this, 'ERR_PANEL_2', $id, get_class($this), $this->getFullID());
      $ctrl->remove($time);
    }
    unset($this->controls[$ctrl->id]);
    $this->inDetach = false;
    return $this;
  }
  
  public function get($id, $isRecursion = true)
  {
    return MVC\Page::$current->view->get($id, $isRecursion, $this);
  }
  
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    return '<' . $this->properties['tag'] . $this->renderAttributes() . '>' . $this->getInnerHTML() . '</' . $this->properties['tag'] . '>';
  }
  
  public function getInnerHTML()
  {
    if ($this->tpl->isExpired())
    {
      foreach ($this as $uniqueID => $ctrl) $this->tpl->{$uniqueID} = $ctrl->render();
    }
    return $this->tpl->render();
  }
}