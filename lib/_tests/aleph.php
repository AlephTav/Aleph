<?php

require_once(__DIR__ . '/../aleph.php');

/**
 * Test for \Aleph;
 */
function test_aleph()
{
  $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
  $a = \Aleph::init();
  if (($res = test_registry()) !== true) return $res;
  if (($res = test_config($a)) !== true) return $res;
  return true;
}

/**
 * Test for registry functionality.
 */
function test_registry()
{
  $res = \Aleph::get('foo') === null;
  $res &= \Aleph::has('foo') === false;
  \Aleph::set('foo', 'test');
  $res &= \Aleph::get('foo') === 'test';
  $res &= \Aleph::has('foo') === true;
  \Aleph::remove('foo');
  $res &= \Aleph::get('foo') === null;
  return !$res ? 'Registry functionality does not work.' : true; 
}

/**
 * Test for config functionality.
 */
function test_config(\Aleph $a)
{
  // Checks ini config loading.
  $a->config(__DIR__ . '/_resources/config.ini');
  if ($a->config() !== ['var1' => '1', 'var2' => 'a', 'var3' => '[1,2,3]', 'section1' => ['var1' => 'test'], 'section2' => ['var1' => [1, 2, 3]]]) return 'Loading of ini config is failed.';
  // Checks php config loading.
  $arr = ['var1' => 1, 'var2' => '2', 'var4' => [1, 2, 3 => ['var1' => 'test']], 'section1' => ['var1' => '[1,2,3]']];
  $a->config($arr, true);
  if ($a->config() !== $arr) return 'Loading of php config is failed.';
  // Checks merging config data.
  $a->config(__DIR__ . '/_resources/config.ini');
  if ($a->config() !== ['var1' => '1', 'var2' => 'a', 'var4' => [1, 2, 3 => ['var1' => 'test']], 'section1' => ['var1' => 'test'], 'var3' => '[1,2,3]', 'section2' => ['var1' => [1, 2, 3]]]) return 'Merging config data is failed.';
  // Checks array access to the config data.
  $res = $a['foo'] === null;
  $res &= $a['var1'] === '1';
  $res &= $a['var4'][3]['var1'] === 'test';
  $res &= $a['section2']['var1'][2] === 3;
  $a['foo'] = [];
  $res &= $a['foo'] === [];
  unset($a['var4'][3]);
  $res &= empty($a['var4'][3]);
  $res &= $a['a']['b'] === null;
  return !$res ? 'Implementation of array access to config data is incorrect.' : true;
}

return test_aleph();