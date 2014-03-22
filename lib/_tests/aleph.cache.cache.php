<?php

use Aleph\Cache;

require_once(__DIR__ . '/../cache/cache.php');
require_once(__DIR__ . '/../cache/file.php');

/**
 * Test for Aleph\Cache\Cache (basic functionality)
 */
function test_cache()
{
  // Prepairing environment.
  $dir = __DIR__ . '/_cache/' . uniqid('dir', true);
  $rmdir = function($dir) use(&$rmdir)
  {
    if (!is_dir($dir)) return false;
    foreach (scandir($dir) as $item)
    {
      if ($item == '..' || $item == '.') continue;
      $file = $dir . '/' . $item;
      if (is_dir($file)) $rmdir($file);
      else unlink($file);   
    }
    rmdir($dir);
  };
  $error = function($error) use ($rmdir, $dir)
  {
    $rmdir($dir);
    return $error;
  };
  // Gets File cache object.
  $cache = Cache\Cache::getInstance('file', ['directory' => $dir]);
  // Checks CRUD logic (for default group).
  $key = uniqid('key', true);
  $cache->set($key, 'test', 1);
  if ($cache->get($key) !== 'test') return $error('Storing data in cache doesn\'t work.');
  sleep(1);
  if ($cache->isExpired($key) === false) return $error('Expiration cached data doesn\'t work.');
  $cache->set($key, 'test', 1);
  $cache->remove($key);
  if ($cache->get($key) !== null) return $error('Removing expired data doesn\'t work.');
  // Checks group logic.
  $grp = uniqid('grp', true);
  $cache->set($key . '1', 0, 10, $grp);
  $cache->set($key . '2', 1, 10, $grp);
  $cache->set($key . '3', 2, 10, $grp);
  if ($cache->getByGroup($grp) !== [$key . '1' => 0, $key . '2' => 1, $key . '3' => 2] || $cache->getByGroup('') !== []) return $error('Group data storing doesn\'t work.');
  if (count($cache) !== 3) return $error('Count method is wrong.');
  $cache->cleanByGroup($grp);
  if ($cache->getByGroup($grp) !== []) return $error('Group data removing doesn\'t work.');
  $rmdir($dir);
  return true;
}

return test_cache();