<?php

namespace Aleph\MVC;

require_once(__DIR__ . '/../../connect.php');

$a['autoload']['namespaces']['Aleph\MVC'][] = __DIR__;

$map = ['.*' => ['GET' => ['callback' => 'Aleph\MVC\HelloWorld[]']]];

(new Controller($map))->execute();