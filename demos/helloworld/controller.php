<?php

namespace Aleph\MVC;

require_once(__DIR__ . '/../../connect.php');

$map = ['.*' => ['GET' => ['callback' => 'Aleph\MVC\HelloWorld[]']]];

(new Controller($map))->execute();