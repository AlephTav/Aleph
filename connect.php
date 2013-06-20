<?php

use Aleph\Cache;

$_SERVER['DOCUMENT_ROOT'] = __DIR__;
require_once(__DIR__ . '/lib/aleph.php');

$a = Aleph::init();

$a->config(__DIR__ . '/app/core/config.php')
  ->cache(Cache\Cache::getInstance())
  ->gc($a['cache']['gcProbability']);