<?php

set_time_limit(0);

$classes = ['Aleph\Utils\PHP\Tokenizer'];

foreach ($classes as $class)
{
  $res = require_once(__DIR__ . '/' . str_replace('\\', '.', strtolower($class)) . '.php');
  echo '<b>' . $class . ':</b> ' . ($res ? '+' : '-') . '<br />';
}