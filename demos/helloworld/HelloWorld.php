<?php

namespace Aleph\MVC;

/**
 * The main page class.
 */
class HelloWorld extends Page
{
  public function __construct()
  {
    // Sets page XHTML template.
    parent::__construct(__DIR__  . '/HelloWorld.html');
  }
}