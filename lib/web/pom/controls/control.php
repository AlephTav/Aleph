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
    Aleph\Utils;

abstract class Control implements \ArrayAccess
{
  const ID_REG_EXP = '/^[0-9a-zA-Z_]+$/';
  
  const ERR_CTRL_1 = 'ID of [{var}] should match /^[0-9a-zA-Z_]+$/ pattern, "[{var}]" was given.';
  const ERR_CTRL_2 = 'Web control [{var}] (full ID: [{var}]) does not have property [{var}].';
  const ERR_CTRL_3 = 'You cannot change or delete readonly attribute ID of [{var}] (full ID: [{var}]).';
  const ERR_CTRL_4 = 'Web control with such logical ID exists already within the panel [{var}] (full ID: [{var}]).';
  const ERR_CTRL_5 = 'You cannot inject control [{var}] (full ID: [{var}]) AFTER or BEFORE panel [{var}] (full ID: [{var}]) within this panel. Use a parent of the panel for injecting.';
  const ERR_CTRL_6 = 'You cannot inject control [{var}] (full ID: [{var}]) at TOP or BOTTOM of another control [{var}] (full ID: [{var}]). Try to use this control as a parent.';
  const ERR_CTRL_7 = 'Injecting mode [{var}] is invalid. The valid values are "top", "bottom", "after" and "before".';
  
  protected $isRefreshed = false;
  protected $isRemoved = false;
  protected $isCreated = false;
  
  protected $creationInfo = null;
  
  protected $parent = null;
  
  /**
   * The control type.
   *
   * @var string $ctrl
   * @access protected
   */
  protected $ctrl = null;
  
  /**
   * HTML Global Attributes
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
  
  protected $dataAttributes = [];
  
  protected $baseAttributes = [];

  protected $properties = [];
  
  protected $events = [];

  public function __construct($id)
  {
    if (!preg_match(self::ID_REG_EXP, $id)) throw new Core\Exception($this, 'ERR_CTRL_1', get_class($this), $id);
    $this->attributes['id'] = str_replace('.', '', uniqid($id, true));
    $this->properties['id'] = $id;
    $this->properties['visible'] = true;
  }
  
  public function isAttached()
  {
    return MVC\Page::$current->view->isAttached($this);
  }
  
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

  public function setVS(array $vs)
  {
    $this->parent = $vs['parent'];
    $this->attributes = $vs['attributes'];
    $this->properties = $vs['properties'];
    return $this;
  }
  
  public function __set($attribute, $value)
  {
    $attribute = strtolower($attribute);
    if ($attribute == 'id') throw new Core\Exception($this, 'ERR_CTRL_3', get_class($this), $this->getFullID());
    $this->attributes[$attribute] = $value;
  }
  
  public function &__get($attribute)
  {
    $attribute = strtolower($attribute);
    if (isset($this->attributes[$attribute])) return $this->attributes[$attribute];
    $value = null;
    return $value;
  }
  
  public function __isset($attribute)
  {
    return isset($this->attributes[strtolower($attribute)]);
  }
  
  public function __unset($attribute)
  {
    $attribute = strtolower($attribute);
    if ($attribute == 'id') throw new Core\Exception($this, 'ERR_CTRL_3', get_class($this), $this->getFullID());
    unset($this->attributes[$attribute]);
  }
  
  public function offsetSet($property, $value)
  {
    $property = strtolower($property);
    if (!array_key_exists($property, $this->properties)) throw new Core\Exception($this, 'ERR_CTRL_2', get_class($this), $this->getFullID(), $property);
    if ($property == 'id')
    {
      if ($value == $this->properties[$property]) return;
      if (!preg_match(self::ID_REG_EXP, $value)) throw new Core\Exception($this, 'ERR_CTRL_1', get_class($this), $value);
      if ((false !== $parent = $this->getParent()) && $parent->get($value, false)) throw new Core\Exception($this, 'ERR_CTRL_4', get_class($parent), $parent->getFullID());
    }
    $this->properties[$property] = $value;
  }
  
  public function &offsetGet($property)
  {
    $property = strtolower($property);
    if (!array_key_exists($property, $this->properties)) throw new Core\Exception($this, 'ERR_CTRL_2', get_class($this), $this->getFullID(), $property);
    return $this->properties[$property];
  }
  
  public function offsetExists($property)
  {
    return array_key_exists(strtolower($property), $this->properties);
  }
  
  public function offsetUnset($property)
  {
    $this[$property] = null;
  }
  
  public function callback($callback, $isStatic = false)
  {
    return addcslashes(get_class($this) . ($isStatic ? '::' . $callback : '@' . $this->attributes['id'] . '->' . $callback), "'\\");
  }
  
  public function method($callback, array $params = null, $isStatic = false)
  {
    $params = implode(', ', Utils\PHP\Tools::php2js($params !== null ? $params : [], false, View::JS_MARK));
    return '$a.ajax.doit(\'' . $this->callback($callback, $isStatic) . '\'' . (strlen($params) ? ', ' . $params : '') . ')';
  }
  
  public function getEvents()
  {
    return $this->events;
  }
  
  public function addEvent($id, $type, $callback, array $options = null)
  {
    $callback = (string)$callback;
    if (substr($callback, 0, strlen(View::JS_MARK)) == View::JS_MARK) $callback = substr($callback, strlen(View::JS_MARK));
    else 
    {
      $params = implode(', ', Utils\PHP\Tools::php2js(isset($options['params']) ? $options['params'] : [], false, View::JS_MARK));
      $callback = 'function(event){$a.ajax.doit(\'' . addcslashes($callback, "'\\") . '\'' . (strlen($params) ? ', ' . $params : '') . ')}';
    }
    $this->events[$id] = ['type' => strtolower($type), 'callback' => $callback, 'options' => $options];
    return $this;
  }
  
  public function removeEvent($id)
  {
    $this->events[$id] = false;
    return $this;
  }
  
  public function hasContainer()
  {
    return false;
  }
  
  public function hasClass($class, $isContainer = false)
  {
    $attr = $isContainer ? 'container-class' : 'class';
    $class = trim($class);
    if (strlen($class) == 0 || !isset($this->attributes[$attr])) return false;
    return strpos(' ' . $this->attributes[$attr] . ' ', ' ' . $class . ' ') !== false;
  }

  public function addClass($class, $isContainer = false)
  {
    $attr = $isContainer ? 'container-class' : 'class';
    $class = trim($class);
    if (strlen($class) == 0 || $this->hasClass($class, $isContainer)) return $this;
    if (!isset($this->attributes[$attr])) $this->attributes[$attr] = $class;
    else $this->attributes[$attr] = trim(rtrim($this->attributes[$attr]) . ' ' . $class);
    return $this;
  }

  public function removeClass($class, $isContainer = false)
  {
    $attr = $isContainer ? 'container-class' : 'class';
    $class = trim($class);
    if (strlen($class) == 0 || !isset($this->attributes[$attr])) return $this;
    $this->attributes[$attr] = trim(str_replace(' ' . $class . ' ', '', ' ' . $this->attributes[$attr] . ' '));
    return $this;
  }

  public function replaceClass($class1, $class2, $isContainer = false)
  {
    return $this->removeClass($class1, $isContainer)->addClass($class2, $isContainer);
  }

  public function toggleClass($class1, $class2 = null, $isContainer = false)
  {
    if (!$this->hasClass($class1, $isContainer)) $this->replaceClass($class2, $class1, $isContainer);
    else $this->replaceClass($class1, $class2, $isContainer);
    return $this;
  }
  
  public function hasStyle($style, $isContainer = false)
  {
    $attr = $isContainer ? 'container-style' : 'style';
    $style = trim($style);
    if (strlen($style) == 0 || !isset($this->attributes[$attr])) return false;
    return strpos($this->attributes[$attr], $style) !== false;
  }

  public function addStyle($style, $value, $isContainer = false)
  {
    $attr = $isContainer ? 'container-style' : 'style';
    if ($this->hasStyle($style, $isContainer)) $this->setStyle($style, $value, $isContainer);
    else
    {
      $style = trim($style);
      if (strlen($style) == 0) return $this;
      if (!isset($this->attributes[$attr])) $this->attributes[$attr] = $style . ':' . $value . ';';
      else $this->attributes[$attr] = trim($this->attributes[$attr] . (substr($this->attributes[$attr], -1, 1) != ';' ? ';' : '') . $style . ':' . $value . ';');
    }
    return $this;
  }

  public function setStyle($style, $value, $isContainer = false)
  {
    $attr = $isContainer ? 'container-style' : 'style';
    if (!isset($this->attributes[$attr])) return $this;
    $this->attributes[$attr] = preg_replace('/' . preg_quote($style) . ' *:[^;]*;*/', $style . ':' . $value . ';', $this->attributes[$attr]);
    return $this;
  }

  public function getStyle($style, $isContainer = false)
  {
    $attr = $isContainer ? 'container-style' : 'style';
    if (!isset($this->attributes[$attr])) return;
    preg_match('/' . preg_quote($style) . ' *:([^;]*);*/', $this->attributes[$attr], $matches);
    return isset($matches[1]) ? $matches[1] : null;
  }

  public function removeStyle($style, $isContainer = false)
  {
    $attr = $isContainer ? 'container-style' : 'style';
    if (!isset($this->attributes[$attr])) return $this;
    $this->attributes[$attr] = preg_replace('/' . preg_quote($style) . ' *:[^;]*;*/', '', $this->attributes[$attr]);
    $this->attributes[$attr] = trim(str_replace(['  ', '   '], ' ', $this->attributes[$attr]));
    return $this;
  }

  public function toggleStyle($style, $value, $isContainer = false)
  {
    if (!$this->hasStyle($style, $isContainer)) $this->addStyle($style, $value, $isContainer);
    else $this->removeStyle($style, $isContainer);
    return $this;
  }
  
  public function isCreated()
  {
    return $this->isCreated;
  }
  
  public function getCreationInfo()
  {
    return $this->creationInfo;
  }
  
  public function refresh($flag = true)
  {
    $this->isRefreshed = (bool)$flag;
    return $this;
  }
  
  public function isRefreshed()
  {
    return $this->isRefreshed;
  }
  
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
  
  public function isRemoved()
  {
    return $this->isRemoved;
  }
  
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
      if (!isset($this->attributes[$attr])) 
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
  
  public function getParent()
  {
    if (!$this->parent) return false;
    if ($this->parent instanceof Control) return $this->parent;
    return $this->parent = MVC\Page::$current->view->get($this->parent);
  }

  public function setParent(Control $parent, $mode = null, $id = null)
  {
    if (!$this->parent) $this->parent = $parent;
    else
    {
      $prnt = $this->getParent();
      if ($prnt && $prnt->id != $parent->id) 
      {
        if (!$prnt->isRemoved()) $prnt->detach($this->attributes['id']);
      }
      $this->parent = $parent;
    }
    $controls = $parent->getControls();
    $controls[$this->attributes['id']] = $this;
    $parent->setControls($controls);
    $this->creationInfo = ['mode' => $mode, 'id' => $id];
    if ($id === null || $id == $parent->id)
    {
      if ($mode !== null) 
      {
        $ph = View::getControlPlaceHolder($this->attributes['id']);
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
      $ph = View::getControlPlaceHolder($this->attributes['id']);
      $ch = View::getControlPlaceHolder($ctrl->id);
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
      $ph = View::getControlPlaceHolder($this->attributes['id']);
      $tpl = str_replace($ph, '', $parent->tpl->getTemplate());
      $root = md5(microtime(true));
      $dom = new Utils\DOMDocumentEx();
      $dom->setHTML('<div id="' . $root . '">' . $tpl . '</div>');
      $dom->inject($id, View::encodePHPTags($ph, $marks), $mode);
      $parent->tpl->setTemplate(View::decodePHPTags($dom->getInnerHTML($dom->getElementById($root)), $marks));
    }
    if ($parent->isAttached() && !$this->isAttached()) MVC\Page::$current->view->attach($this);
    $this->isCreated = true;
    $this->isRemoved = false;
    return $this;
  }
  
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
  
  public function getXHTML()
  {
  
  }
  
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
  
  protected function invisible()
  {
    return '<span id="' . htmlspecialchars($this->attributes['id']) . '" style="display:none;"></span>';
  }
}