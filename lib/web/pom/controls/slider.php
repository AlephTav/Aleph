<?php

namespace Aleph\Web\POM;

use Aleph\MVC;

class Slider extends Control
{
  protected $ctrl = 'slider';
  
  protected $dataAttributes = ['settings' => 1];

  public function __construct($id)
  {
    parent::__construct($id);
    $this->attributes['settings'] = [];
  }
  
  public function init()
  {
    MVC\Page::$current->view->addCSS(['href' => \Aleph::url('framework') . '/web/js/jquery/nouislider/jquery.nouislider.css']);
    MVC\Page::$current->view->addJS(['src' => \Aleph::url('framework') . '/web/js/jquery/nouislider/jquery.nouislider.min.js']);
    return $this;
  }
  
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    return '<div' . $this->renderAttributes() . '></div>';
  }
}