<?php

namespace Aleph\Web\UI\Tags;

use Aleph\Core;

interface ITag
{
  public function getAttributes();
  public function setAttributes(array $attributes);
  public function getProperties();
  public function setProperties(array $properties);
  public function render();
}

abstract class Tag implements ITag
{
  protected $attributes = array('id' => null,
                                'style' => null,
                                'class' => null,
                                'title' => null,
                                'lang' => null,
                                'dir' => null,
                                'spellcheck' => null,
                                'contenteditable' => null,
                                'contextmenu' => null,
                                'draggable' => null,
                                'dropzone' => null,
                                'hidden' => null,
                                'tabindex' => null,
                                'accesskey' => null);
  
  protected $properties = array();
  
  protected $events = array();
   
  public function __construct($id = null)
  {
    $this->attributes['id'] = $id;
  }
  
  public function getAttributes()
  {
    return $this->attributes;
  }

  public function setAttributes(array $attributes)
  {
    $this->attributes = $attributes;
    return $this;
  }
  
  public function getProperties()
  {
    return $this->properties;
  }

  public function setProperties(array $properties)
  {
    $this->properties = $properties;
    return $this;
  }

  public function __set($param, $value)
  {
    if (array_key_exists($param, $this->attributes)) $this->attributes[$param] = $value;
    else if (array_key_exists($param, $this->properties)) $this->properties[$param] = $value;
    else throw new Core\Exception('Aleph', 'ERR_GENERAL_3', $param, get_class($this));
  }
  
  public function __get($param)
  {
    if (array_key_exists($param, $this->attributes)) return $this->attributes[$param];
    else if (array_key_exists($param, $this->properties)) return $this->properties[$param];
    else throw new Core\Exception('Aleph', 'ERR_GENERAL_3', $param, get_class($this));
  }

  public function __isset($param)
  {
    return (array_key_exists($param, $this->attributes) || array_key_exists($param, $this->properties) || array_key_exists($param, $this->events));
  }
   
  public function __unset($param)
  {
    unset($this->attributes[$param]);
    unset($this->properties[$param]);
    unset($this->events[$param]);
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
  
  public function hasClass($class)
  {
    return (strpos(' ' . trim($this->class) . ' ', ' ' . trim($class) . ' ') !== false);
  }

  public function addClass($class)
  {
    $class = trim($class);
    if (!$this->hasClass($class)) $this->class = trim(trim($this->class) . ' ' . $class);
    return $this;
  }

  public function removeClass($class)
  {
    $this->class = trim(str_replace(' ' . trim($class) . ' ', '', trim($this->class)));
    return $this;
  }

  public function replaceClass($class1, $class2)
  {
    $this->removeClass($class1)->addClass($class2);
  }

  public function toggleClass($class1, $class2 = null)
  {
    if (!$this->hasClass($class1)) $this->replaceClass($class2, $class1);
    else $this->replaceClass($class1, $class2);
    return $this;
  }
  
  public function hasStyle($style)
  {
    return (strpos($this->style, $style) !== false);
  }

  public function addStyle($style, $value)
  {
    if (!$this->hasStyle($style)) $this->style = trim($this->style . $style . ':' . $value . ';');
    else $this->setStyle($style, $value);
    return $this;
  }

  public function setStyle($style, $value)
  {
    $this->style = preg_replace('/' . $style . ' *:[^;]*;*/', $style . ':' . $value . ';', $this->style);
    return $this;
  }

  public function getStyle($style)
  {
    preg_match('/' . $style . ' *:([^;]*);*/', $this->style, $arr);
    return $arr[1];
  }

  public function removeStyle($style)
  {
    $this->style = preg_replace('/' . $style . ' *:[^;]*;*/', '', $this->style);
    $this->style = trim(str_replace(array('  ', '   '), ' ', $this->style));
    return $this;
  }

  public function toggleStyle($style, $value)
  {
    if (!$this->hasStyle($style)) $this->addStyle($style, $value);
    else $this->removeStyle($style);
    return $this;
  }
  
  protected function renderEvents()
  {
  }

  protected function renderAttributes(array $attributes = null, array $properties = null)
  {
    $tmp = array(); $attributes = $attributes === null ? $this->attributes : $attributes;
    if ($properties) foreach ($properties as $property => $attribute) $attributes[$attribute] = $this->properties[$property];
    foreach ($attributes as $k => $v) 
    {
      if (strlen($v)) $tmp[] = $k . '="' . htmlspecialchars($v) . '"';
    }
    return count($tmp) ? ' ' . implode(' ', $tmp) : '';
  }
}