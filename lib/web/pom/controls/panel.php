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

use Aleph\Core,
    Aleph\MVC,
    Aleph\Web,
    Aleph\Utils;

/**
 * The base class of all container controls that have the main functionality such as:
 * adding a control to the panel, removing a control from the panel, search controls within a panel and other methods.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.web.pom
 */
class Panel extends Control implements \IteratorAggregate, \Countable
{
  // Error message templates.
  const ERR_PANEL_1 = 'Web control [{var}] does not exist in panel [{var}] (full ID: [{var}]).';
  
  /**
   * The panel template object.
   *
   * @var Aleph\Core\Template $tpl
   * @access public
   */
  public $tpl = null;
  
  /**
   * The control type.
   *
   * @var string $ctrl
   * @access protected   
   */
  protected $ctrl = 'panel';
  
  /**
   * The panel controls.
   *
   * @var array $controls
   * @access protected
   */
  protected $controls = [];
  
  /**
   * It equals TRUE if some control detaches from the panel. Otherwise it is FALSE.
   *
   * @var boolean $inDetach
   * @access protected
   */
  protected $inDetach = false;
  
  /**
   * Constructor. Initializes the panel template object.
   *
   * @param string $id - the logic identifier of the panel.
   * @param string $template - the panel template or the full path to the template file.
   * @param integer $expire - the cache lifetime of the panel template.
   * @access public
   */
  public function __construct($id, $template = null, $expire = 0)
  {
    parent::__construct($id);
    $this->properties['tag'] = 'div';
    $this->properties['expire'] = $expire;
    $this->tpl = new Core\Template($template);
    $this->tpl->cacheID = $this->attributes['id'];
    $this->tpl->cacheExpire = $expire;
  }
  
  /**
   * Returns view state of the panel.
   *
   * @return array
   * @access public
   */
  public function getVS()
  {
    return parent::getVS() + ['controls' => array_keys($this->controls),
                              'tpl' => $this->tpl->getTemplate(), 
                              'tplExpire' => $this->tpl->cacheExpire,
                              'tplGroup' => $this->tpl->cacheGroup,
                              'tplID' => $this->tpl->cacheID];
  }
  
  /**
   * Sets view state of the panel.
   *
   * @param array $vs - the panel view state.
   * @return self
   * @access public
   */
  public function setVS(array $vs)
  {
    $this->controls = array_combine($vs['controls'], $vs['controls']);
    $this->tpl->setTemplate($vs['tpl']);
    $this->tpl->cacheID = $vs['tplID'];
    $this->tpl->cacheExpire = $vs['tplExpire'];
    $this->tpl->cacheGroup = $vs['tplGroup'];
    parent::setVS($vs);
  }
  
  /**
   * Sets value of the panel property.
   *
   * @param string $property - the property name.
   * @param mixed $value - the property value.
   * @access public
   */
  public function offsetSet($property, $value)
  {
    if (strtolower($property) == 'expire') $this->tpl->cacheExpire = $value;
    parent::offsetSet($property, $value);
  }
  
  /**
   * Returns controls of the panel.
   *
   * @return array
   * @access public   
   */
  public function getControls()
  {
    return $this->controls;
  }
  
  /**
   * Sets controls of the panel.
   *
   * @param array $controls
   * @return self
   * @access public
   */
  public function setControls(array $controls)
  {
    $this->controls = $controls;
    return $this;
  }
  
  /**
   * Returns number of the panel controls.
   *
   * @return integer
   * @access public
   */
  public function count()
  {
    return count($this->controls);
  }
  
  /**
   * Returns the control iterator object.
   *
   * @return Aleph\Web\POM\Iterator
   * @access public
   */
  public function getIterator()
  {
    return new Iterator($this->controls);
  }
  
  /**
   * Parses the given template of the panel.
   * If $template is not defined, the current panel template will be parsed.
   *
   * @param string $template - the template string or full path to the template file.
   * @param array $vars - the template variables for the template preprocessing.
   * @return self
   * @access public
   */
  public function parse($template = null, array $vars = null)
  {
    $res = View::analyze($template ?: $this->tpl->getTemplate(), $vars);
    $this->tpl->setTemplate($res['html']);
    foreach ($this->controls as $ctrl) $this->detach($ctrl);
    foreach ($res['controls'] as $ctrl) $this->add($ctrl);
    return $this->refresh();
  }
  
  /**
   * Adds some control to the panel.
   *
   * @param Aleph\Web\POM\Control $ctrl - any control to be added to the panel.
   * @param string $mode - determines the placement of the adding control in the panel template.
   * @param string $id - the unique or logic identifier of some panel control or value of the attribute "id" of some element in the panel template.
   * @return self
   * @access public
   */
  public function add(Control $ctrl, $mode = null, $id = null)
  {
    return $ctrl->setParent($this, $mode, $id);
  }
  
  /**
   * Removes the given control from the panel.
   *
   * @param string $id - the unique or logic identifier of the control.
   * @return self
   * @access public
   */
  public function detach($id)
  {
    if ($this->inDetach) return $this;
    $this->inDetach = true;
    $ctrl = $this->get($id, false);
    if (!$ctrl) throw new Core\Exception($this, 'ERR_PANEL_1', $id, get_class($this), $this->getFullID());
    $ctrl->remove();
    unset($this->controls[$ctrl->id]);
    $this->tpl->setTemplate(str_replace(View::getControlPlaceholder($ctrl->id), '', $this->tpl->getTemplate()));
    $this->inDetach = false;
    return $this;
  }
  
  /**
   * Replaces some panel control with the given control.
   *
   * @param string $id - the unique or logic identifier of the panel control to be replaced.
   * @param Aleph\Web\POM\Control $new - the control to replace.
   * @return self
   * @access public   
   */
  public function replace($id, Control $new)
  {
    $ctrl = $this->get($id, false);
    if (!$ctrl) throw new Core\Exception($this, 'ERR_PANEL_1', $id, get_class($this), $this->getFullID());
    $oph = View::getControlPlaceholder($ctrl->id);
    $nph = View::getControlPlaceholder($new->id);
    if (false !== $this->get($new->id, false)) 
    {
      $this->tpl->setTemplate(str_replace($nph, '', $this->tpl->getTemplate()));
      unset($this->controls[$new->id]);
    }
    $this->tpl->setTemplate(str_replace($oph, $nph, $this->tpl->getTemplate()));
    $ctrl->remove();
    return $this->add($new, 'replace', $ctrl->id);
  }
  
  /**
   * Creates full copy of the control.
   * If $id is not defined the logic identifier of the original control is used.
   *
   * @param string $id - the logic identifier of the control copy.
   * @return Aleph\Web\POM\Control
   * @access public
   */
  public function copy($id = null)
  {
    $class = get_class($this);
    $ctrl = new $class($id ?: $this->properties['id']);
    $vs = $this->getVS();
    $vs['parent'] = null;
    $vs['attributes']['id'] = $ctrl->id;
    $vs['properties']['id'] = $ctrl['id'];
    $vs['controls'] = [];
    $ctrl->setVS($vs);
    foreach ($this as $child) 
    {
      $copy = $child->copy();
      $ctrl->tpl->setTemplate(str_replace(View::getControlPlaceholder($child->id), View::getControlPlaceholder($copy->id), $ctrl->tpl->getTemplate()));
      $ctrl->add($copy);
    }
    return $ctrl;
  }
  
  /**
   * Checks or unchecks checkboxes of the panel.
   *
   * @param boolean $flag - determines whether a checkbox will be checked or not.
   * @param boolean $searchRecursively - determines whether the method should be recursively applied to all child panels of the given panel.
   * @return self
   * @access public
   */
  public function check($flag = true, $searchRecursively = true)
  {
    MVC\Page::$current->view->check($this->attributes['id'], $flag, $searchRecursively);
    return $this;
  }
  
  /**
   * For every panel control restores value of the property "value" to default.
   *
   * @param boolean $searchRecursively - determines whether the method should be recursively applied to all child panels of the given panel.
   * @return self
   * @access public
   */
  public function clean($searchRecursively = true)
  {
    MVC\Page::$current->view->clean($this->attributes['id'], $searchRecursively);
    return $this;
  }
  
  /**
   * Searches the required control in the panel and returns its instance.
   * The method returns FALSE if the required control is not found.
   *
   * @param string $id - unique or logic identifier of a control.
   * @param boolean $searchRecursively - determines whether to recursively search the control inside all child panels of the given panel.
   * @return boolean|Aleph\Web\POM\Control
   * @access public
   */
  public function get($id, $searchRecursively = true)
  {
    return MVC\Page::$current->view->get($id, $searchRecursively, $this);
  }
  
  /**
   * Returns associative array of values of form (panel) controls.
   *
   * @param boolean $searchRecursively - determines whether values of controls of nested panels will also be gathered.
   * @return array
   * @access public
   */
  public function getFormValues($searchRecursively = true)
  {
    return MVC\Page::$current->view->getFormValues($this, $searchRecursively);
  }
  
  /**
   * Assigns values to the panel controls.
   *
   * @param array $values - values to be assigned to.
   * @param boolean $searchRecursively - determines whether values will be also assigned to the controls of nested panels.
   * @return self
   * @access public
   */
  public function setFormValues(array $values, $searchRecursively = true)
  {
    MVC\Page::$current->view->setFormValues($this, $values, $searchRecursively);
    return $this;
  }
  
  /**
   * Returns HTML of the panel.
   *
   * @return string
   * @access public
   */
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    return '<' . $this->properties['tag'] . $this->renderAttributes() . '>' . $this->renderInnerHTML() . '</' . $this->properties['tag'] . '>';
  }
  
  /**
   * Returns the inner HTML of the panel.
   *
   * @return string
   * @access public
   */
  public function renderInnerHTML()
  {
    if ($this->tpl->isExpired())
    {
      foreach ($this as $uniqueID => $ctrl) $this->tpl->{$uniqueID} = $ctrl->render();
    }
    return $this->tpl->render();
  }
  
  /**
   * Returns XHTML of the control.
   *
   * @return string
   * @access public
   */
  public function renderXHTML()
  {
    $tpl = $this->tpl->getTemplate();
    $tag = Utils\PHP\Tools::getClassName($this);
    $html = '<' . $tag . $this->renderXHTMLAttributes() . '>';
    foreach ($this as $uniqueID => $ctrl) $tpl = str_replace(View::getControlPlaceholder($uniqueID), $ctrl->renderXHTML(), $tpl);
    return $html . $tpl . '</' . $tag . '>';
  }
}