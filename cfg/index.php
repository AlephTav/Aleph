<?php

require_once(__DIR__ . '/src/configurator.php');

$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../';

$config = ['path' => ['aleph' => 'lib/aleph.php', 
                      'config' => ['app/core/config.php' => true,
                                   'app/core/config.ini' => true]]];

$cfg = new \Aleph\Configurator($config);
$cfg->init();
$cfg->process();