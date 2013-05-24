<?php

use Aleph\Cache;

require_once(__DIR__ . '/../cache/cache.php');

/**
 * Test for Aleph\Cache\Cache (basic functionality)
 */
function test_cache()
{
  // Gets File cache object.
  $cache = Cache\Cache::getInstance('file', ['directory' => __DIR__ . '/_cache']);
  // Checks CRUD logic (for default group).
  $key = uniqid('key', true);
  $cache->set($key, 'test', 1);
  if ($cache->get($key) !== 'test') return 'Storing data in cache doesn\'t work.';
  sleep(1);
  if ($cache->isExpired($key) !== true) return 'Expiration cached data doesn\'t work.';
  $cache->{$key} = 'test';
  if ($cache->{$key} !== 'test') return 'Fluent interface doesn\'t work.';
  unset($cache->{$key});
  if ($cache->{$key} !== null) return 'Removing expired data doesn\'t work.';
  // Checks group logic.
  $grp = uniqid('grp', true);
  $cache->set($key . '1', 0, 10, $grp);
  $cache->set($key . '2', 1, 10, $grp);
  $cache->set($key . '3', 2, 10, $grp);
  $cache[$key . '1'] = $cache[$key . '1'] + 1;
  $cache[$key . '2'] = $cache[$key . '2'] + 1;
  $cache[$key . '3'] = $cache[$key . '3'] + 1;
  if ($cache->getByGroup($grp) !== [$key . '1' => 1, $key . '2' => 2, $key . '3' => 3] || $cache->getByGroup('') !== []) return 'Group data storing doesn\'t work.';
  $cache->cleanByGroup($grp);
  if ($cache->getByGroup($grp) !== []) return 'Group data removing doesn\'t work.';
  return true;
}

return test_cache();