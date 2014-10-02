<?php

return [
  'debugging' => true, 
  'logging' => true, 
  'templateDebug' => 'lib/_templates/debug.tpl', 
  'templateBug' => 'lib/_templates/bug.tpl', 
  'cache' => [
    'type' => 'file', 
    'directory' => 'cache', 
    'gcProbability' => 33.333
  ], 
  'autoload' => [
    'search' => true, 
    'unique' => true, 
    'type' => 'classmap', 
    'classmap' => 'classmap.php', 
    'mask' => '/.+\\.php\\z/i', 
    'timeout' => 300
  ], 
  'mvc' => [
    'locked' => false, 
    'unlockKey' => 'iwanttosee', 
    'unlockKeyExpire' => 108000, 
    'templateLock' => 'lib/_templates/bug.tpl'
  ], 
  'pom' => [
    'cacheEnabled' => false, 
    'cacheGroup' => 'pom', 
    'charset' => 'utf-8', 
    'namespaces' => [
      'c' => 'Aleph\\Web\\POM'
    ], 
    'ppOpenTag' => '<![PP[', 
    'ppCloseTag' => ']PP]!>'
  ], 
  'db' => [
    'logging' => true, 
    'log' => 'tmp/sql.log', 
    'cacheExpire' => 0, 
    'cacheGroup' => 'db'
  ],
  'ar' => [
    'cacheExpire' => -1, 
    'cacheGroup' => 'ar'
  ], 
  'dirs' => [
    'application' => 'app', 
    'framework' => 'lib', 
    'logs' => 'tmp/logs', 
    'cache' => 'tmp/cache', 
    'temp' => 'tmp/null'
  ], 
  'db0' => [
    'dsn' => 'mysql:dbname=addon;host=127.0.0.1;charset=utf8', 
    'username' => 'root', 
    'password' => 1
  ], 
  'db1' => [
    'dsn' => 'sqlite:C:\sites\smartypassword\blog.sqlite', 
    'username' => null, 
    'password' => null
  ],
  'db2' => [
    'dsn' => 'oci8:dbname=127.0.0.1:1522/xe;schema=CUMMINS_STAGING;charset=UTF8;',
    'username' => 'CUMMINS_STAGING',
    'password' => 'Cummins100!',
  ]
];