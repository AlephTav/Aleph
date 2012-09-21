<?php

namespace Aleph\Web\UI\POM;

class Iterator implements \Iterator
{
  private $controls = array();

  public function __construct(array $controls)
  {
    $this->controls = $controls;
  }

  public function rewind()
  {
    $obj = reset($this->controls);
    return $obj instanceof Control ? $obj : Control::getByUniqueID($obj);
  }

  public function current()
  {
    $obj = current($this->controls);
    return $obj instanceof Control ? $obj : Control::getByUniqueID($obj);
  }

  public function key()
  {
    return key($this->controls);
  }

  public function next()
  {
    $obj = next($this->controls);
    return $obj instanceof Control ? $obj : Control::getByUniqueID($obj);
  }

  public function valid()
  {
    return key($this->controls) !== null;
  }
}