<?php

namespace Aleph\Web\UI\Tags;

class IFrame extends Control
{
  public function __construct($id = null)
  {
    parent::__construct($id);
    $this->attributes['name'] = $id;
    $this->attributes['src'] = null;
    $this->attributes['srcdoc'] = null;
    $this->attributes['width'] = null;
    $this->attributes['height'] = null;
    $this->attributes['sandbox'] = null;
    $this->attributes['seamless'] = null;
  }
   
  public function render()
  {
    return '<iframe' . $this->renderAttributes() . $this->renderEvents() . '></iframe>';
  }
}