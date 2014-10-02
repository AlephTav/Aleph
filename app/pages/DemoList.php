<?php

namespace Aleph\MVC;

/**
 * The main page class.
 */
class DemoList extends Page
{
  /**
   * Sets page template.
   */
  public function __construct()
  {
    parent::__construct(__DIR__ . '/DemoList.html');
  }
}