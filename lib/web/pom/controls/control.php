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

abstract class Control implements \ArrayAccess
{
  const ID_REG_EXP = '/^[0-9a-zA-Z_]+$/';
  
  const ERR_CTRL_1 = 'ID of [{var}] should match /^[0-9a-zA-Z_]+$/ pattern, "[{var}]" was given.';
  const ERR_CTRL_2 = 'Web control [{var}] (full ID: [{var}]) does not have property [{var}].';
  const ERR_CTRL_3 = 'You cannot change readonly attribute ID of [{var}] (full ID: [{var}]).';
  const ERR_CTRL_4 = 'Web control with such logical ID exists already within the panel [{var}] (full ID: [{var}]).';
  const ERR_CTRL_5 = 'Web control [{var}] (logic ID: [{var}]) is not attached to the view.';
  const ERR_CTRL_6 = 'You cannot remove control [{var}] because it is not attached to the view.';
  
  //const ERR_CTRL_4 = 'You cannot change parentUniqueID of [{var}] with fullID = "[{var}]". To change parentUniqueID of some web control you should use setParent or setParentByUniqueID methods of web control object.';
  //const ERR_CTRL_7 = 'Web control with uniqueID = "[{var}]" does not exist.';
  
  protected $doRefresh = false;
  protected $doRemove = false;
  
  protected $parent = null;
  
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
    return MVC\Page::$current->view->has($this);
  }
  
  public function getVS()
  {
    return ['class' => get_class($this), 
            'parent' => $this->parent instanceof Control ? $this->parent->id : $this->parent,
            'attributes' => $this->attributes, 
            'properties' => $this->properties, 
            'events' => $this->events,
            'methods' => ['init' => method_exists($this, 'init'),
                          'load' => method_exists($this, 'load'),
                          'unload' => method_exists($this, 'unload')]];
  }

  public function setVS(array $vs)
  {
    $this->parent = $vs['parent'];
    $this->attributes = $vs['attributes'];
    $this->properties = $vs['properties'];
    $this->events = $vs['events'];
    return $this;
  }
  
  public function __set($attribute, $value)
  {
    $attribute = strtolower($attribute);
    if ($attribute == 'id') throw new Core\Exception($this, 'ERR_CTRL_3', get_class($this), $this->getFullID());
    $this->attributes[$attribute] = $value;
  }
  
  public function __get($attribute)
  {
    $attribute = strtolower($attribute);
    return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null;
  }
  
  public function __isset($attribute)
  {
    return isset($this->attributes[strtolower($attribute)]);
  }
  
  public function __unset($attribute)
  {
    unset($this->attributes[strtolower($attribute)]);
  }
  
  public function offsetSet($property, $value)
  {
    $property = strtolower($property);
    if (!array_key_exists($property, $this->properties)) throw new Core\Exception($this, 'ERR_CTRL_2', get_class($this), $this->getFullID(), $property);
    if ($property == 'id')
    {
      if ($value == $this->properties[$property]) return;
      if (!preg_match(self::ID_REG_EXP, $value)) throw new Core\Exception($this, 'ERR_CTRL_1', get_class($this), $value);
      if (false !== $parent = $this->getParent() && $parent->get($value, false)) throw new Core\Exception($this, 'ERR_CTRL_4', get_class($parent), $parent->getFullID());
    }
    $this->properties[$property] = $value;
  }
  
  public function offsetGet($property)
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
  
  public function event($event, $delegate)
  {
    
  }
  
  public function hasClass($class)
  {
    $class = trim($class);
    if (strlen($class) == 0 || !isset($this->attributes['class'])) return false;
    return strpos(' ' . trim($this->attributes['class']) . ' ', ' ' . $class . ' ') !== false;
  }

  public function addClass($class)
  {
    $class = trim($class);
    if (strlen($class) == 0 || $this->hasClass($class)) return $this;
    if (!isset($this->attributes['class'])) $this->attributes['class'] = $class;
    else $this->attributes['class'] = trim(trim($this->attributes['class']) . ' ' . $class);
    return $this;
  }

  public function removeClass($class)
  {
    $class = trim($class);
    if (strlen($class) == 0 || !isset($this->attributes['class'])) return $this;
    $this->attributes['class'] = trim(str_replace(' ' . $class . ' ', '', trim($this->attributes['class'])));
    return $this;
  }

  public function replaceClass($class1, $class2)
  {
    return $this->removeClass($class1)->addClass($class2);
  }

  public function toggleClass($class1, $class2 = null)
  {
    if (!$this->hasClass($class1)) $this->replaceClass($class2, $class1);
    else $this->replaceClass($class1, $class2);
    return $this;
  }
  
  public function hasStyle($style)
  {
    $style = trim($style);
    if (strlen($style) == 0 || !isset($this->attributes['style'])) return false;
    return strpos($this->attributes['style'], $style) !== false;
  }

  public function addStyle($style, $value)
  {
    if ($this->hasStyle($style)) $this->setStyle($style, $value);
    {
      $style = trim($style);
      if (strlen($style) == 0) return $this;
      if (!isset($this->attributes['style'])) $this->attributes['style'] = $style . ':' . $value . ';';
      else $this->attributes['style'] = trim($this->attributes['style'] . (substr($this->attributes['style'], -1, 1) != ';' ? ';' : '') . $style . ':' . $value . ';');
    }
    return $this;
  }

  public function setStyle($style, $value)
  {
    if (!isset($this->attributes['style'])) return $this;
    $this->attributes['style'] = preg_replace('/' . preg_quote($style) . ' *:[^;]*;*/', $style . ':' . $value . ';', $this->attributes['style']);
    return $this;
  }

  public function getStyle($style)
  {
    if (!isset($this->attributes['style'])) return;
    preg_match('/' . preg_quote($style) . ' *:([^;]*);*/', $this->attributes['style'], $matches);
    return isset($matches[1]) ? $matches[1] : null;
  }

  public function removeStyle($style)
  {
    if (!isset($this->attributes['style'])) return $this;
    $this->attributes['style'] = preg_replace('/' . preg_quote($style) . ' *:[^;]*;*/', '', $this->attributes['style']);
    $this->attributes['style'] = trim(str_replace(['  ', '   '], ' ', $this->attributes['style']));
    return $this;
  }

  public function toggleStyle($style, $value)
  {
    if (!$this->hasStyle($style)) $this->addStyle($style, $value);
    else $this->removeStyle($style);
    return $this;
  }
  
  public function refresh($time = true)
  {
    if ($time !== false) $time = ($time === true) ? 0 : (int)$time;
    $this->doRefresh = $time;
    return $this;
  }
  
  public function remove($time = 0)
  {
    if ($this->doRemove !== false) $this->doRemove = (int)$time;
    else
    {
      if (false === $parent = $this->getParent()) throw new Core\Exception($this, 'ERR_CTRL_6', $this->properties['id']);
      $this->doRemove = (int)$time;
      $parent->detach($this);
    }
    return $this;
  }
  
  public function compare(array $vs, array $exclusions = null)
  {
    if (!$this->properties['parentUniqueID']) return false;
    $new = array($this->attributes, $this->properties, $this->events);
    if ($this->doRefresh !== false) return $new;
    $diff = array(array(), array(), array());
    for ($i = 0; $i < 3; $i++)
    {
      foreach ($vs['parameters'][$i] as $k => $v) 
      {
        if ($v != $new[$i][$k]) $diff[$i][$k] = $new[$i][$k];
      }
    }
    return ($diff[0] || $diff[1] || $diff[2]) ? $diff : false;
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
  
  public function getParent()
  {
    if (!$this->parent) return false;
    if ($this->parent instanceof Control) return $this->parent;
    return $this->parent = MVC\Page::$current->view->get($this->parent);
  }

  public function setParent(Control $parent)
  {
    //if (!$parent->isAttached()) throw new Core\Exception($this, 'ERR_CTRL_5', get_class($parent), $parent->getFullID());
    if (!$this->parent) $this->parent = $parent;
    else
    {
    
    }
    $this->doRemove = false;
    return $this;
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
  
  /*protected function refresh(array $diff = null, $selector = null)
  {
    $selector = $selector ?: '#' . $this->attributes['id'];
    if (!$diff || $diff[1] || $diff[2]) $this->ajax->replace($selector, $this->render(), $this->doRefresh);
    else
    {
      foreach ($diff[0] as $k => &$v) $v = $k . ': \'' . addslashes($v) . '\'';
      $this->ajax->script('aleph.dom.attr(\'' . $selector . '\', {' . implode(',', $diff[0]) . '})');
    }
    return $this;
  }*/
  
  /*public function setParent(Panel $parent, $id = null, $mode = 'top')
  {
    return $this->setParentByUniqueID($parent->uniqueID, $id, $mode);
  }

  public function setParentByUniqueID($uniqueID, $id = null, $mode = 'top')
  {
    $parent = self::getByUniqueID($uniqueID);
    if (!$parent) throw new \Exception('ERR_CTRL_7', $uniqueID);
    //$this->remove();
    //$parent->inject($this, $id, $mode);
    return $this;
  }*/
  
  protected function renderAttributes()
  {
    $tmp = ['data-ctrl="' . $this->ctrl . '"'];
    foreach ($this->attributes as $attr => $value) 
    {
      if (strlen($value)) $tmp[] = (isset($this->dataAttributes[$attr]) ? 'data-' : '') . $attr . '="' . htmlspecialchars($value) . '"';
    }
    return ' ' . implode(' ', $tmp);
  }
  
  protected function invisible()
  {
    return '<span id="' . htmlspecialchars($this->attributes['id']) . '" style="display:none;"></span>';
  }
}