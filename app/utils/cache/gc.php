<?php

require_once(__DIR__ . '/../../../connect.php');

$a->cache()->gc();

echo 'ok.' . PHP_EOL;