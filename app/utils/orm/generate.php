<?php

use Aleph\DB\ORM,
    Aleph\Utils;

require_once(__DIR__ . '/../../../connect.php');

if (PHP_SAPI === 'cli') 
{
  $args = Utils\CLI::getArguments(array(array('--mode', '-m'), array('--db-alias', '-d'), array('--namespace', '-n')));
  if (empty($args['--mode'])) exit('Error: parameter "--mode" (or "-m") can not be empty.' . PHP_EOL);
  if (empty($args['--db-alias'])) exit('Error: parameter "--db-alias" (or "-d") can not be empty.' . PHP_EOL);
  if (strtolower($args['--mode']) == 'ar' && empty($args['--namespace'])) exit('Error: parameter "--namespace" (or "-n") can not be empty.' . PHP_EOL);
}
else
{
  if (empty($_REQUEST['mode'])) exit('Error: parameter "mode" can not be empty.');
  if (empty($_REQUEST['dbalias'])) exit('Error: parameter "dbalias" can not be empty.');
  $args = array('--mode' => $_REQUEST['mode'], '--db-alias' => $_REQUEST['dbalias']);
  if (strtolower($_REQUEST['mode']) == 'ar' && empty($_REQUEST['namespace'])) exit('Error: parameter "namespace" can not be empty.');
  else $args['--namespace'] = $_REQUEST['namespace'];
}

$gen = new ORM\Generator($args['--db-alias']);

switch (strtolower($args['--mode']))
{
  case 'ar': // Generates Active Records files.
    $gen->createARClasses($args['--namespace']);
    break;
  default:
    exit('Error: parameter "mode" with such value doesn\'t exist. Only the following mode values are possible: ar (generates active record files), orm (generates orm classes), xml (generates xml mapping file for orm).' . PHP_EOL);
}

echo 'ok.';