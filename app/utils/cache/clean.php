<?php

use Aleph\Utils;

require_once(__DIR__ . '/../../../connect.php');

if (PHP_SAPI === 'cli') 
{
  $args = Utils\CLI::getArguments(array(array('--mode', '-m')));
  $mode = empty($args['--mode']) ? 'all' : $args['--mode'];
}
else
{
  $mode = empty($_REQUEST['mode']) ? 'all' : $_REQUEST['mode'];
}

switch ($mode)
{
  case 'all':
    $a->cache->clean();
    break;
  case 'autoload':
    $a->loader->cleanCache();
    break;
  case 'pages':
    $a->cache()->cleanByGroup('--pages');
    break;
  case 'localization':
    $a->cache()->cleanByGroup('--localization');
    break;
  default:
    exit('Error: parameter "mode" with such value doesn\'t exist. Only the following mode values are possible: all (cleans all cache data), autoload (cleans autoload info), pages (cleans page templates\' cache), localization (cleans localization cache).' . PHP_EOL);
}

echo 'ok.' . PHP_EOL;