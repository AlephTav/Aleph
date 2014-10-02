<?php

// This line is necessary for correct work of the framework when you run the script from the console.
$_SERVER['DOCUMENT_ROOT'] = __DIR__;

// Includes the main class of the framework.
require_once(__DIR__ . '/lib/Aleph.php');

// Initializes the framework.
$a = Aleph::init();

// Loading of the main application config.
$a->setConfig(__DIR__ . '/app/config.php');

// Launching of the garbage collector with the given probability if it set.
if (!empty($a['cache']['gcProbability'])) $a->getCache()->gc($a['cache']['gcProbability']);