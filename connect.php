<?php

use Aleph\Cache;

$_SERVER['DOCUMENT_ROOT'] = __DIR__;
require_once('lib/aleph.php');

$a = Aleph::init();

$a->config('app/engine/config.ini')
  ->cache(Cache\Cache::getInstance())
  ->gc($a['cache']['gcProbability']);