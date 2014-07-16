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
    Aleph\Utils;

/**
 * The base class of all web controls.
 * It contains methods for access to attributes and properties of controls, methods for managing view state of controls and
 * methods for attaching control to a view (panel).
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.web.pom
 */
abstract class Control implements \ArrayAccess
{
  // Regular expression for checking the format of logic control identifiers.
  const ID_REG_EXP = '/^[0-9a-zA-Z_]+$/';
  
  // Error message templates.
  const ERR_CTRL_1 = 'ID of [{var}] should match /^[0-9a-zA-Z_]+$/ pattern, "[{var}]" was given.';
  const ERR_CTRL_2 = 'Web control [{var}] (full ID: [{var}]) does not have property [{var}].';
  const ERR_CTRL_3 = 'You cannot change or delete readonly attribute ID of [{var}] (full ID: [{var}]).';
  const ERR_CTRL_4 = 'Web control with logical ID "[{var}]" exists already within the panel [{var}] (full ID: [{var}]).';
  const ERR_CTRL_5 = 'You cannot inject control [{var}] (full ID: [{var}]) AFTER or BEFORE panel [{var}] (full ID: [{var}]) within this panel. Use a parent of the panel for injecting.';
  const ERR_CTRL_6 = 'You cannot inject control [{var}] (full ID: [{var}]) at TOP or BOTTOM of another control [{var}] (full ID: [{var}]). Try to use this control as a parent.';
  const ERR_CTRL_7 = 'Injecting mode [{var}] is invalid. The valid values are "top", "bottom", "after" and "before".';
  
  /**
   * Determines whether the control properties or attributes were changed 
   * and the control should be refreshed on the client side.
   *
   * @var boolean $isRefreshed
   * @access protected
   */
  protected $isRefreshed = false;
  
  /**
   * Determines whether the control was removed and it should be removed on the client side.
   *
   * @var boolean $isRemoved
   * @access protected
   */
  protected $isRemoved = false;
  
  /**
   * Determines whether the control has just created and it should be added on the page on the client side.
   *
   * @var boolean $isCreated
   * @access protected
   */
  protected $isCreated = false;
  
  /**
   * Information about the placement on the web page of the newly created control.
   *
   * @var array $creationInfo
   * @access protected
   */
  protected $creationInfo = null;
  
  /**
   * Unique identifier of the parent control or its object.
   *
   * @var string|ClickBlocks\Web\POM\Control $parent
   * @access protected
   */
  protected $parent = null;
  
  /**
   * The control type.
   * This type is shown as attribute "data-ctrl" in the control tag on the web page.
   *
   * @var string $ctrl
   * @access protected
   */
  protected $ctrl = null;
  
  /**
   * HTML global attributes of the control.
   *
   * @var array $attributes
   * @access protected
   */
  protected $attributes = [/* 
                            'title' => '',           // Specifies extra information about an element.
                            'class' => '',           // Specifies one or more classnames for an element (refers to a class in a style sheet).
                            'style' => '',           // Specifies an inline CSS style for an element.
                            'lang' => '',            // Specifies the language of the element's content.
                            'dir' => '',             // Specifies the text direction for the content in an element.
                            'tabindex' => '',        // Specifies the tabbing order of an element.
                            'hidden' => '',          // Specifies that an element is not yet, or is no longer, relevant.
                            'spellcheck' => '',      // Specifies whether the element is to have its spelling and grammar checked or not.
                            'translate' => '',       // Specifies whether the content of an element should be translated or not.
                            'dropzone' => '',        // Specifies whether the dragged data is copied, moved, or linked, when dropped.
                            'draggable' => '',       // Specifies whether an element is draggable or not.
                            'contextmenu' => '',     // Specifies a context menu for an element. The context menu appears when a user right-clicks on the element.
                            'contenteditable' => '', // Specifies whether the content of an element is editable or not.
                            'accesskey' => ''        // Specifies a shortcut key to activate/focus an element.
                           */];
  
  /**
   * Non-standard control attributes, that should be rendered as "data-" attributes on the web page.
   *
   * @var array $dataAttributes
   * @access protected
   */
  protected $dataAttributes = [];

  /**
   * The control properties.
   *
   * @var array $properties
   * @access protected
   */
  protected $properties = [];
  
  /**
   * The control events.
   *
   * @var array $events
   */
  protected $events = [];

  /**
   * Constructor. Creates the unique control identifier that based on its logic identifier.
   *
   * @param string $id - the logic control identifier. It should contains only numeric, alphabetic symbols and symbol "_".
   * @access public
   */
  public function __construct($id)
  {
    if (!preg_match(self::ID_REG_EXP, $id)) throw new Core\Exception($this, 'ERR_CTRL_1', get_class($this), $id);
    $this->attributes['id'] = str_replace('.', '', uniqid($id, true));
    $this->properties['id'] = $id;
    $this->properties['visible'] = true;
  }
  
  /**
   * Returns TRUE if the control is attached to the current view and FALSE otherwise.
   *
   * @return boolean
   * @access public
   */
  public function isAttached()
  {
    return MVC\Page::$current->view->isAttached($this);
  }
  
  /**
   * Returns full logic identifier of the control.
   * The full logic identifier is a dot-separated string containing logic identifiers of 
   * all parents of the given control along with its logic identifier.
   *
   * @return string
   * @access public
   */
  public function getFullID()
  {
    $id = $this->properties['id'];
    $ctrl = $this;
    while ($parent = $ctrl->getParent())
    {
      $id = $parent['id'] . '.' . $id;
      $ctrl = $parent;
    }
    return $id;
  }
  
  /**
   * Returns view state of the control.
   *
   * @return array
   * @access public
   */
  public function getVS()
  {
    return ['class' => get_class($this), 
            'parent' => $this->parent instanceof Control ? $this->parent->id : $this->parent,
            'attributes' => $this->attributes, 
            'properties' => $this->properties,
            'methods' => ['init' => method_exists($this, 'init'),
                          'load' => method_exists($this, 'load'),
                          'unload' => method_exists($this, 'unload')]];
  }

  /**
   * Sets view state of the control.
   *
   * @param array $vs - view state of the control.
   * @return self
   * @access public
   */
  public function setVS(array $vs)
  {
    $this->parent = $vs['parent'];
    $this->attributes = $vs['attributes'];
    $this->properties = $vs['properties'];
    return $this;
  }
  
  /**
   * Sets value of the control attribute.
   *
   * @param string $attribute - the attribute name.
   * @param mixed $value - the attribute value.
   * @access public
   */
  public function __set($attribute, $value)
  {
    $attribute = strtolower($attribute);
    if ($attribute == 'id') throw new Core\Exception($this, 'ERR_CTRL_3', get_class($this), $this->getFullID());
    $this->attributes[$attribute] = $value;
  }
  
  /**
   * Returns value of the control attribute.
   *
   * @param string $attribute - the attribute name.
   * @return mixed
   * @access public
   */
  public function &__get($attribute)
  {
    $attribute = strtolower($attribute);
    if (isset($this->attributes[$attribute])) return $this->attributes[$attribute];
    $value = null;
    return $value;
  }
  
  /**
   * Returns TRUE if value of the given attribute is defined and FALSE otherwise.
   *
   * @param string $attribute - the attribute name.
   * @return boolean
   * @access public
   */
  public function __isset($attribute)
  {
    return isset($this->attributes[strtolower($attribute)]);
  }
  
  /**
   * Removes the control attributes.
   *
   * @param string $attribute - the attribute name.
   * @access public
   */
  public function __unset($attribute)
  {
    $attribute = strtolower($attribute);
    if ($attribute == 'id') throw new Core\Exception($this, 'ERR_CTRL_3', get_class($this), $this->getFullID());
    unset($this->attributes[$attribute]);
  }
  
  /**
   * Sets value of the control property.
   *
   * @param string $property - the property name.
   * @param mixed $value - the property value.
   * @access public
   */
  public function offsetSet($property, $value)
  {
    $property = strtolower($property);
    if (!array_key_exists($property, $this->properties)) throw new Core\Exception($this, 'ERR_CTRL_2', get_class($this), $this->getFullID(), $property);
    if ($property == 'id')
    {
      if ($value == $this->properties[$property]) return;
      if (!preg_match(self::ID_REG_EXP, $value)) throw new Core\Exception($this, 'ERR_CTRL_1', get_class($this), $value);
      if ((false !== $parent = $this->getParent()) && $parent->get($value, false)) throw new Core\Exception($this, 'ERR_CTRL_4', $value, get_class($parent), $parent->getFullID());
    }
    $this->properties[$property] = $value;
  }
  
  /**
   * Returns value of the control property.
   *
   * @param string $property - the property name.
   * @return mixed
   * @access public   
   */
  public function &offsetGet($property)
  {
    $property = strtolower($property);
    if (!array_key_exists($property, $this->properties)) throw new Core\Exception($this, 'ERR_CTRL_2', get_class($this), $this->getFullID(), $property);
    return $this->properties[$property];
  }
  
  /**
   * Returns TRUE if the control has the given property and FALSE otherwise.
   *
   * @param string $property - the property name.
   * @return boolean
   * @access public
   */
  public function offsetExists($property)
  {
    return array_key_exists(strtolower($property), $this->properties);
  }
  
  /**
   * Removes value (sets it to NULL) of the control property.
   *
   * @param string $property - the property name.
   * @access public
   */
  public function offsetUnset($property)
  {
    $this[$property] = null;
  }
  
  /**
   * Returns delegate string for the given control method.
   *
   * @param string $callback - the control method.
   * @param boolean $isStatic - determines whether the control method is static.
   * @return string
   * @access public
   */
  public function callback($callback, $isStatic = false)
  {
    return addcslashes(get_class($this) . ($isStatic ? '::' . $callback : '@' . $this->attributes['id'] . '->' . $callback), '\\');
  }
  
  /**
   * Returns JS code for invoking of the given control method through Ajax request.
   *
   * @param string $callback - the control method.
   * @param array $params - the method parameters.
   * @param boolean $isStatic - determines whether the control method is static.
   * @return string
   * @access public
   */
  public function method($callback, array $params = null, $isStatic = false)
  {
    $params = implode(', ', Utils\PHP\Tools::php2js($params !== null ? $params : [], false, View::JS_MARK));
    return '$ajax.doit(\'' . $this->callback($callback, $isStatic) . '\'' . (strlen($params) ? ', ' . $params : '') . ')';
  }
  
  /**
   * Returns the control events.
   *
   * @return array
   * @access public
   */
  public function getEvents()
  {
    return $this->events;
  }
  
  /**
   * Adds new JS event to the control.
   * Parameter $options can have the following elements:
   * [
   *  'params'      => [string], // parameters of the control method if $callback represents the control method.
   *  'check'       => [string], // JS function that will determine whether or not to trigger the given event handler.
   *  'toContainer' => [boolean] // determines whether or not the given event should be applied to the container tag of the control.
   * ]
   *
   * @param string $id - the unique event identifier.
   * @param string $type - the DOM event type, such as "click" or "change", or custom event type.
   * @param string $callback - the control method or marked JS code string of the event handler.
   * @param array $options - the additional event parameters.
   * @return self
   * @access public
   */
  public function addEvent($id, $type, $callback, array $options = null)
  {
    $callback = (string)$callback;
    if (substr($callback, 0, strlen(View::JS_MARK)) == View::JS_MARK) $callback = substr($callback, strlen(View::JS_MARK));
    else 
    {
      $params = implode(', ', Utils\PHP\Tools::php2js(isset($options['params']) ? $options['params'] : [], false, View::JS_MARK));
      $callback = 'function(event){$ajax.doit(\'' . addcslashes($callback, '\\') . '\'' . (strlen($params) ? ', ' . $params : '') . ')}';
    }
    $this->events[$id] = ['type' => strtolower($type), 'callback' => $callback, 'options' => $options];
    return $this;
  }
  
  /**
   * Removes the control event.
   *
   * @param string $id - the event identifier.
   * @return self
   * @access public
   */
  public function removeEvent($id)
  {
    $this->events[$id] = false;
    return $this;
  }
  
  /**
   * Returns TRUE if the control has the container tag and FALSE otherwise.
   * This method need to be overridden in child classes if your control has the container tag.
   *
   * @return boolean
   * @access public
   */
  public function hasContainer()
  {
    return false;
  }
  
  /**
   * Returns TRUE if the control has the given CSS class.
   *
   * @param string $class - the CSS class.
   * @param boolean $applyToContainer - determines whether the container tag of the control is considered.
   * @return boolean
   * @access public
   */
  public function hasClass($class, $applyToContainer = false)
  {
    $attr = $applyToContainer ? 'container-class' : 'class';
    $class = trim($class);
    if (strlen($class) == 0 || !isset($this->attributes[$attr])) return false;
    return strpos(' ' . $this->attributes[$attr] . ' ', ' ' . $class . ' ') !== false;
  }

  /**
   * Adds CSS class to the control.
   *
   * @param string $class - CSS class to add.
   * @param boolean $applyToContainer - determines whether the container tag of the control is considered.
   * @return self
   * @access public
   */
  public function addClass($class, $applyToContainer = false)
  {
    $attr = $applyToContainer ? 'container-class' : 'class';
    $class = trim($class);
    if (strlen($class) == 0 || $this->hasClass($class, $applyToContainer)) return $this;
    if (!isset($this->attributes[$attr])) $this->attributes[$attr] = $class;
    else $this->attributes[$attr] = trim(rtrim($this->attributes[$attr]) . ' ' . $class);
    return $this;
  }

  /**
   * Removes CSS class from the control.
   *
   * @param string $class - CSS class to remove.
   * @param boolean $applyToContainer - determines whether the container tag of the control is considered.
   * @return self
   * @access public
   */
  public function removeClass($class, $applyToContainer = false)
  {
    $attr = $applyToContainer ? 'container-class' : 'class';
    $class = trim($class);
    if (strlen($class) == 0 || !isset($this->attributes[$attr])) return $this;
    $this->attributes[$attr] = trim(str_replace(' ' . $class . ' ', '', ' ' . $this->attributes[$attr] . ' '));
    return $this;
  }

  /**
   * Replaces CSS class with the other CSS class.
   *
   * @param string $class1 - CSS class to be replaced.
   * @param string $class2 - CSS class to replace.
   * @param boolean $applyToContainer - determines whether the container tag of the control is considered.
   * @return self
   * @access public
   */
  public function replaceClass($class1, $class2, $applyToContainer = false)
  {
    return $this->removeClass($class1, $applyToContainer)->addClass($class2, $applyToContainer);
  }

  /**
   * Toggles CSS class.
   *
   * @param string $class1 - CSS class to toggle.
   * @param string $class2 - CSS class to replace.
   * @param boolean $applyToContainer - determines whether the container tag of the control is considered.
   * @return self
   * @access public
   */
  public function toggleClass($class1, $class2 = null, $applyToContainer = false)
  {
    if (!$this->hasClass($class1, $applyToContainer)) $this->replaceClass($class2, $class1, $applyToContainer);
    else $this->replaceClass($class1, $class2, $applyToContainer);
    return $this;
  }
  
  /**
   * Returns TRUE if the control has the given CSS property.
   *
   * @param string $style - the CSS property name.
   * @param boolean $applyToContainer - determines whether the container tag of the control is considered.
   * @return string
   * @access public
   */
  public function hasStyle($style, $applyToContainer = false)
  {
    $attr = $applyToContainer ? 'container-style' : 'style';
    $style = trim($style);
    if (strlen($style) == 0 || !isset($this->attributes[$attr])) return false;
    return strpos($this->attributes[$attr], $style) !== false;
  }

  /**
   * Adds new CSS property value to the control attribute "style".
   * If the control hasn't the given property it will be added. 
   *
   * @param string $style - the CSS property name.
   * @param mixed $value - the CSS property value.
   * @param boolean $applyToContainer - determines whether the container tag of the control is considered.
   * @return self
   * @access public
   */
  public function addStyle($style, $value, $applyToContainer = false)
  {
    $attr = $applyToContainer ? 'container-style' : 'style';
    if ($this->hasStyle($style, $applyToContainer)) $this->setStyle($style, $value, $applyToContainer);
    else
    {
      $style = trim($style);
      if (strlen($style) == 0) return $this;
      if (!isset($this->attributes[$attr])) $this->attributes[$attr] = $style . ':' . $value . ';';
      else $this->attributes[$attr] = trim($this->attributes[$attr] . (substr($this->attributes[$attr], -1, 1) != ';' ? ';' : '') . $style . ':' . $value . ';');
    }
    return $this;
  }

  /**
   * Sets value of the CSS property of the control attribute "style".
   * If CSS property with the given name does not exists it won't be added to the control.
   *
   * @param string $style - the CSS property name.
   * @param mixed $value - the CSS property value.
   * @param boolean $applyToContainer - determines whether the container tag of the control is considered.
   * @return self
   * @access public
   */
  public function setStyle($style, $value, $applyToContainer = false)
  {
    $attr = $applyToContainer ? 'container-style' : 'style';
    if (!isset($this->attributes[$attr])) return $this;
    $this->attributes[$attr] = preg_replace('/' . preg_quote($style) . ' *:[^;]*;*/', $style . ':' . $value . ';', $this->attributes[$attr]);
    return $this;
  }

  /**
   * Returns value of the CSS property of the control attribute "style".
   *
   * @param string $style - the CSS property name.
   * @param boolean $applyToContainer - determines whether the container tag of the control is considered.
   * @return mixed
   * @access public
   */
  public function getStyle($style, $applyToContainer = false)
  {
    $attr = $applyToContainer ? 'container-style' : 'style';
    if (!isset($this->attributes[$attr])) return;
    preg_match('/' . preg_quote($style) . ' *:([^;]*);*/', $this->attributes[$attr], $matches);
    return isset($matches[1]) ? $matches[1] : null;
  }

  /**
   * Removes CSS property from the control attribute "style".
   *
   * @param string $style - the CSS property name.
   * @param boolean $applyToContainer - determines whether the container tag of the control is considered.
   * @return self
   * @access public
   */
  public function removeStyle($style, $applyToContainer = false)
  {
    $attr = $applyToContainer ? 'container-style' : 'style';
    if (!isset($this->attributes[$attr])) return $this;
    $this->attributes[$attr] = preg_replace('/' . preg_quote($style) . ' *:[^;]*;*/', '', $this->attributes[$attr]);
    $this->attributes[$attr] = trim(str_replace(['  ', '   '], ' ', $this->attributes[$attr]));
    return $this;
  }

  /**
   * Toggles CSS property value from the control attribute "style".
   *
   * @param string $style - the CSS property name.
   * @param mixed $value - the CSS property value.
   * @param boolean $applyToContainer - determines whether the container tag of the control is considered.
   * @return self
   * @access public
   */
  public function toggleStyle($style, $value, $applyToContainer = false)
  {
    if (!$this->hasStyle($style, $applyToContainer)) $this->addStyle($style, $value, $applyToContainer);
    else $this->removeStyle($style, $applyToContainer);
    return $this;
  }
  
  /**
   * Returns TRUE if the control has just created and not attached to the current view.
   * Otherwise it returns FALSE.
   *
   * @return boolean
   * @access public
   */
  public function isCreated()
  {
    return $this->isCreated;
  }
  
  /**
   * Returns TRUE if the control has an attribute or a property changed, or if method refresh() was invoked with parameter TRUE.
   * Otherwise it returns FALSE.
   *
   * @return boolean
   * @access public
   */
  public function isRefreshed()
  {
    return $this->isRefreshed;
  }
  
  /**
   * Returns TRUE if the control was removed.
   *
   * @return boolean
   * @access public
   */
  public function isRemoved()
  {
    return $this->isRemoved;
  }
  
  /**
   * Returns information about placement of the newly created control.
   *
   * @return array
   * @access public
   */
  public function getCreationInfo()
  {
    return $this->creationInfo;
  }
  
  /**
   * This method can be used to force the control to refresh on the client side even though no its attributes or properties were changed.
   *
   * @param boolean $flag - if it is TRUE, the control will be refreshed on the client side.
   * @return self
   * @access public
   */
  public function refresh($flag = true)
  {
    $this->isRefreshed = (bool)$flag;
    return $this;
  }
  
  /**
   * Removes the control from the current view.
   *
   * @return self
   * @access public
   */
  public function remove()
  {
    if (!$this->isRemoved)
    {
      if ((false !== $parent = $this->getParent()) && !$parent->isRemoved()) $parent->detach($this->attributes['id']);
      $this->isRemoved = true;
      $this->isCreated = false;
    }
    return $this;
  }
  
  /**
   * Returns array of the changed or removed control attributes, or the rendered control HTML.
   * This method is automatically invoked by the current view to refresh DOM of the web page.  
   *
   *
   * @param array $vs - the old view state of the control.
   * @return array|string
   * @access public
   */
  public function compare(array $vs)
  {
    if ($this->isRefreshed || $vs['properties'] != $this->properties) return $this->render();
    $tmp = ['attrs' => [], 'removed' => []];
    foreach ($this->attributes as $attr => $value)
    {
      if (!isset($vs['attributes'][$attr]) && $value !== null || $value != $vs['attributes'][$attr])
      {
        $container = '';
        if (substr($attr, 0, 10) == 'container-') 
        {
          $container = 'container-';
          $attr = substr($attr, 10);
        }
        $tmp['attrs'][$container . (isset($this->dataAttributes[$attr]) ? 'data-' . $attr : $attr)] = is_array($value) ? Utils\PHP\Tools::php2js($value, true, View::JS_MARK) : (string)$value;
      }
    }
    foreach ($vs['attributes'] as $attr => $value)
    {
      if (!isset($this->attributes[$attr]) && $value !== null) 
      {
        $container = '';
        if (substr($attr, 0, 10) == 'container-') 
        {
          $container = 'container-';
          $attr = substr($attr, 10);
        }
        $tmp['removed'][] = $container . (isset($this->dataAttributes[$attr]) ? 'data-' . $attr : $attr);
      }
    }
    return $tmp;
  }
  
  /**
   * Returns the parent control.
   * If the control does not have the parent, it returns FALSE.
   *
   * @return Aleph\Web\POM\Panel
   * @access public
   */
  public function getParent()
  {
    if (!$this->parent) return false;
    if ($this->parent instanceof Control) return $this->parent;
    return $this->parent = MVC\Page::$current->view->get($this->parent);
  }

  /**
   * Sets parent of the control.
   * The control will be attached to the view if it isn't and its parent is attached to the view.
   *
   * @param Aleph\Web\POM\Panel $parent - the control parent.
   * @param string $mode - determines the placement of the control in the parent template.
   * @param string $id - the unique or logic identifier of one of the parent control or value of the attribute "id" of some element in the parent template.
   * @return self
   * @access public
   */
  public function setParent(Control $parent, $mode = null, $id = null)
  {
    if (!$this->parent) 
    {
      if ($parent->get($this->properties['id'], false)) throw new Core\Exception($this, 'ERR_CTRL_4', $this->properties['id'], get_class($parent), $parent->getFullID());
      $this->parent = $parent;
    }
    else
    {
      $prnt = $this->getParent();
      if ($prnt && $prnt->id != $parent->id) 
      {
        if (!$prnt->isRemoved()) $prnt->detach($this->attributes['id']);
        if ($parent->get($this->properties['id'], false)) throw new Core\Exception($this, 'ERR_CTRL_4', $this->properties['id'], get_class($parent), $parent->getFullID());
      }
      $this->parent = $parent;
    }
    $this->creationInfo = ['mode' => $mode, 'id' => $id];
    if ($id === null || $id == $parent->id)
    {
      if ($mode !== null) 
      {
        $ph = View::getControlPlaceholder($this->attributes['id']);
        $tpl = str_replace($ph, '', $parent->tpl->getTemplate());
        switch ($mode)
        {
          case Utils\DOMDocumentEx::DOM_INJECT_TOP:
            $parent->tpl->setTemplate($ph . $tpl);
            break;
          case Utils\DOMDocumentEx::DOM_INJECT_BOTTOM:
            $parent->tpl->setTemplate($tpl . $ph);
            break;
          case Utils\DOMDocumentEx::DOM_INJECT_AFTER:
          case Utils\DOMDocumentEx::DOM_INJECT_BEFORE:
            throw new Core\Exception($this, 'ERR_CTRL_5', get_class($this), $this->getFullID(), get_class($parent), $parent->getFullID());
          default:
            throw new Core\Exception($this, 'ERR_CTRL_7', $mode);
        }
        $this->creationInfo = ['mode' => $mode, 'id' => $parent->id];
      }
    }
    else if (false !== $ctrl = $parent->get($id, false))
    {
      $ph = View::getControlPlaceholder($this->attributes['id']);
      $ch = View::getControlPlaceholder($ctrl->id);
      if (!empty($prnt) && $prnt->id == $parent->id) $tpl = str_replace($ph, '', $parent->tpl->getTemplate());
      else $tpl = $parent->tpl->getTemplate();
      switch ($mode)
      {
         case Utils\DOMDocumentEx::DOM_INJECT_TOP:
         case Utils\DOMDocumentEx::DOM_INJECT_BOTTOM:
           throw new Core\Exception($this, 'ERR_CTRL_6', get_class($this), $this->getFullID(), get_class($ctrl), $ctrl->getFullID());
         case Utils\DOMDocumentEx::DOM_INJECT_AFTER:
           $parent->tpl->setTemplate(str_replace($ch, $ch . $ph, $tpl));
           break;
         case Utils\DOMDocumentEx::DOM_INJECT_BEFORE:
           $parent->tpl->setTemplate(str_replace($ch, $ph . $ch, $tpl));
           break;
         default:
           throw new Core\Exception($this, 'ERR_CTRL_7', $mode);
      }
      $this->creationInfo = ['mode' => $mode, 'id' => $ctrl->id];
    }
    else if ($mode !== 'replace')
    {
      $ph = View::getControlPlaceholder($this->attributes['id']);
      $tpl = str_replace($ph, '', $parent->tpl->getTemplate());
      $root = md5(microtime(true));
      $dom = new Utils\DOMDocumentEx();
      $dom->setHTML('<div id="' . $root . '">' . $tpl . '</div>');
      $dom->inject($id, View::encodePHPTags($ph, $marks), $mode);
      $parent->tpl->setTemplate(View::decodePHPTags($dom->getInnerHTML($dom->getElementById($root)), $marks));
    }
    $controls = $parent->getControls();
    $controls[$this->attributes['id']] = $this;
    $parent->setControls($controls);
    if ($parent->isAttached())
    {
      if (!$this->isAttached()) MVC\Page::$current->view->attach($this);
      $this->isCreated = true;
      $this->isRemoved = false;
    }
    return $this;
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
    $ctrl->setVS($vs);
    return $ctrl;
  }
  
  /**
   * Returns rendered HTML of the control.
   *
   * @return string
   * @access public
   * @abstract
   */
  abstract public function render();
  
  /**
   * Returns rendered HTML of the control.
   *
   * @return string
   * @access public
   */
  public function __toString()
  {
    try
    {
      return $this->render();
    }
    catch (\Exception $e)
    {
      \Aleph::exception($e);
    }
  }
  
  /**
   * Returns XHTML of the control.
   *
   * @return string
   * @access public
   */
  public function renderXHTML()
  {
    return '<' . Utils\PHP\Tools::getClassName($this) . $this->renderXHTMLAttributes() . ' />';
  }
  
  /**
   * Renders HTML of the control attributes.
   *
   * @param boolean $renderBaseAttributes - determines whether the attributes of the main control tag are rendered.
   * @return string
   * @access protected
   */
  protected function renderAttributes($renderBaseAttributes = true)
  {
    if ($renderBaseAttributes)
    {
      $tmp = ['data-ctrl="' . $this->ctrl . '"'];
      foreach ($this->attributes as $attr => $value) 
      {
        if (substr($attr, 0, 10) == 'container-') continue;
        $value = is_array($value) ? Utils\PHP\Tools::php2js($value, true, View::JS_MARK) : (string)$value;
        if (strlen($value)) $tmp[] = (isset($this->dataAttributes[$attr]) ? 'data-' : '') . $attr . '="' . htmlspecialchars($value) . '"';
      }
    }
    else
    {
      $tmp = [];
      foreach ($this->attributes as $attr => $value) 
      {
        if (substr($attr, 0, 10) != 'container-') continue;
        $attr = substr($attr, 10);
        $value = is_array($value) ? Utils\PHP\Tools::php2js($value, true, View::JS_MARK) : (string)$value;
        if (strlen($value)) $tmp[] = (isset($this->dataAttributes[$attr]) ? 'data-' : '') . $attr . '="' . htmlspecialchars($value) . '"';
      }
    }
    return ' ' . implode(' ', $tmp);
  }
  
  /**
   * Returns the control attributes and properties rendered to XHTML.
   *
   * @return string
   * @access protected
   */
  protected function renderXHTMLAttributes()
  {
    $html = '';
    $attributes = $this->attributes;
    unset($attributes['id']);
    foreach ($attributes as $attr => $value)
    {
      if (!is_scalar($value) || strlen($value)) 
      {
        $html .= ' attr-' . strtolower($attr) . '="';
        if (is_scalar($value)) $html .= htmlspecialchars($value) . '"';
        else $html .= View::PHP_MARK . 'unserialize(\'' . addcslashes(htmlspecialchars(serialize($value)), "'") . '\')"';
      }
    }
    foreach ($this->properties as $prop => $value)
    {
      $html .= ' ' . strtolower($prop) . '="';
      if (is_scalar($value)) $html .= htmlspecialchars($value) . '"';
      else $html .= View::PHP_MARK . 'unserialize(\'' . addcslashes(htmlspecialchars(serialize($value)), "'") . '\')"';
    }
    return $html;
  }
  
  /**
   * Returns the standard HTML for an invisible control.
   *
   * @return string
   * @access protected
   */
  protected function invisible()
  {
    return '<span id="' . htmlspecialchars($this->attributes['id']) . '" style="display:none;"></span>';
  }
}