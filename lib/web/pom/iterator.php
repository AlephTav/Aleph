<?php

namespace Aleph\Web\POM;

use Aleph\MVC;

class Iterator implements \Iterator
{
  private $controls = [];
  
  private $view = null;

  public function __construct(array $controls)
  {
    $this->controls = $controls;
    $this->view = MVC\Page::$current->view;
  }

  public function rewind()
  {
    reset($this->controls);
  }

  public function current()
  {
    $obj = current($this->controls);
    return $obj instanceof Control ? $obj : $this->view->get($obj);
  }

  public function key()
  {
    return key($this->controls);
  }

  public function next()
  {
    next($this->controls);
  }

  public function valid()
  {
    return key($this->controls) !== null;
  }
}