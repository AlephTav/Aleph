<?php

namespace Aleph\MVC;

require_once(__DIR__ . '/connect.php');

$map = ['.*' => ['GET' => ['callback' => 'Aleph\MVC\DemoList[]']]];

(new Controller($map))->execute();