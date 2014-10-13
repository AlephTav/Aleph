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
    'type' => 'PSR-4',
    'namespaces' => ['Aleph\MVC' => ['app/pages']]
    //'type' => 'classmap',
    //'search' => true, 
    //'unique' => true, 
    //'classmap' => 'classmap.php', 
    //'mask' => '/.+\\.php\\z/i', 
    //'timeout' => 300
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
  ]
];