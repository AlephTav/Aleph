<?php

require_once(__DIR__ . '/src/configurator.php');

(new \Aleph\Configuration\Configurator(require_once(__DIR__ . '/config.php')))->process();