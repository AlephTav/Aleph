<?php

require_once(__DIR__ . '/src/configurator.php');

$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/..';

$config = array('path' => array('aleph' => __DIR__ . '/../lib/aleph.php', 
                                'config' => __DIR__ . '/../app/core/config.php'));

$cfg = new \Aleph\Configurator($config);
$cfg->init();
$cfg->process();