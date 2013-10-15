<?php

$_SERVER['DOCUMENT_ROOT'] = __DIR__;
require_once(__DIR__ . '/lib/aleph.php');

$a = Aleph::init();
$a->setConfig(__DIR__ . '/app/core/config.php');
$a->getCache()->gc($a['cache']['gcProbability']);