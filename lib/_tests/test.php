<?php

set_time_limit(0);

$classes = ['Aleph\Net\URL',
            'Aleph\Utils\PHP\Tokenizer'];

foreach ($classes as $class)
{
  $res = require_once(__DIR__ . '/' . str_replace('\\', '.', strtolower($class)) . '.php');
  echo '<div style="' . ($res !== true ? 'color:#B22222;' : '') . '"><b>' . $class . ':</b> ' . ($res === true ? '+' : ($res ? $res : '-')) . '</div>';
}