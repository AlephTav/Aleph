<?php

return array('debugging' => 1,
             'logging' => 1,
             'cache' => array('gcProbability' => 33.333,
                              'type' => 'file',
                              'directory' => 'cache'),
             'dirs' => array('logs' => 'app/logs',
                             'cache' => 'app/cache',
                             'temp' => 'app/temp',
                             'ar' => 'app/core/engine/ar',
                             'orm' => 'app/core/engine/orm'),
             'orm' => array('arDirectory' => 'ar',
                            'arTemplate' => 'lib/db/orm/templates/ar.tpl',
                            'ormDirectory' => 'orm',
                            'ormTemplate' => 'lib/db/orm/templates/orm.tpl'),
             'db' => array('dsn' => 'mysql:dbname=vw;host=127.0.0.1',
                           'username' => 'root',
                           'password' => 1));