<?php

use Aleph\Core,
    Aleph\MVC,
    Aleph\DB,
    Aleph\Cache,
    Aleph\Net,
    Aleph\Utils,
    Aleph\Utils\PHP,
    Aleph\Data\Validators,
    Aleph\Data\Converters;

require_once(__DIR__ . '/connect.php');

$map = ['.*' => ['GET' => ['callback' => 'Aleph\MVC\DemoList[]']]];

(new MVC\Controller($map))->execute();