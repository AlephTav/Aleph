<?php

// Includes the main class of the framework.
require_once(__DIR__ . '/lib/Aleph.php');

// Initializes the framework.
Aleph::init(__DIR__, 'UTC');

// Loading of the main application config.
Aleph::setConfig(__DIR__ . '/app/config.php');

// Launching of the garbage collector with the given probability if specified.
Aleph::getCache()->gc();