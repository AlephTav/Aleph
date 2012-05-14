<?php

$_SERVER['DOCUMENT_ROOT'] = __DIR__;
require_once('lib/aleph.php');

$a = Aleph\Aleph::init()->config('app/engine/config.ini');

if (method_exists($a->cache(), 'gc') && isset($a['cache']['gcProbability']))
{
  if ((int)$a['cache']['gcProbability'] > rand(0, 99)) $a->cache()->gc();
}