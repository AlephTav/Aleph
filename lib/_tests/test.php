<?php

set_time_limit(0);

$classes = ['Aleph',
            'Aleph\Core\Exception',
            'Aleph\Cache\Cache',
            'Aleph\Core\Template',
            'Aleph\Net\URL',
            'Aleph\Net\Router',
            'Aleph\Utils\PHP\Tokenizer'];

foreach ($classes as $class)
{
  $res = require_once(__DIR__ . '/' . str_replace('\\', '.', strtolower($class)) . '.php');
  $msg = $res === true ? '+' : ($res ? $res : '-');
  if (PHP_SAPI === 'cli') echo $class . ': ' . $msg . PHP_EOL;
  else echo '<div style="' . ($res !== true ? 'color:#B22222;' : '') . '"><b>' . $class . ':</b> ' . $msg . '</div>';
  ob_flush();
  flush();
}