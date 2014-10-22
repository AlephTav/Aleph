<?php

namespace Aleph\Configurator;

use Aleph\DB\DB,
    Aleph\DB\ORM\Generator;

class ORM extends Module
{
  public function process($command, array $args = null)
  {
    switch ($command)
    {
      case 'refresh':
        if (Configurator::isCLI())
        {
          echo $this->getCommandHelp();
          break;
        }
        echo $this->refresh($args['alias']);
        break;
      case 'show':
        if (Configurator::isCLI())
        {
          if (isset($args['alias']))
          {
            foreach ($this->getTables($args['alias']) as $n => $table)
            {
              echo PHP_EOL . ($n + 1) . '. ' . $table;
            }
            echo PHP_EOL;
          }
          else
          {
            foreach ($this->getAliases() as $alias)
            {
              echo PHP_EOL . 'Database: ' . $alias . PHP_EOL;
              foreach ($this->getTables($alias) as $n => $table)
              {
                echo PHP_EOL . ($n + 1) . '. ' . $table;
              }
              echo PHP_EOL;
            }
          }
          break;
        }
        echo $this->renderTables($args['alias']);
        break;
      case 'ar':
        if (empty($args['alias'])) self::error('The database alias is not defined.');
        else
        {
          $gen = new Generator($args['alias'], isset($args['dir']) ? $args['dir'] : null, isset($args['mode']) ? $args['mode'] : Generator::MODE_REPLACE_IMPORTANT);
          $gen->setExcludedTables($this->extractTables($args));
          $gen->ar(isset($args['ns']) ? $args['ns'] : 'Aleph\DB\AR');
          if (Configurator::isCLI()) echo PHP_EOL . 'Active Record\'s classes have been successfully generated.' . PHP_EOL;
        }
        break;
      case 'xml':
        if (empty($args['alias'])) self::error('The database alias is not defined.');
        else
        {
          $gen = new Generator($args['alias'], isset($args['dir']) ? $args['dir'] : null, isset($args['mode']) ? $args['mode'] : Generator::MODE_REPLACE_IMPORTANT);
          $gen->setExcludedTables($this->extractTables($args));
          $gen->useInheritance = isset($args['useInheritance']) ? (bool)$args['useInheritance'] : false;
          $gen->useTransformation = isset($args['useTransformation']) ? (bool)$args['useTransformation'] : false;
          $gen->usePrettyClassName = isset($args['usePrettyClassName']) ? (bool)$args['usePrettyClassName'] : false;
          $gen->usePrettyPropertyName = isset($args['usePrettyPropertyName']) ? (bool)$args['usePrettyPropertyName'] : false;
          $gen->xml(isset($args['ns']) ? $args['ns'] : 'Aleph\DB\ORM');
          if (Configurator::isCLI()) echo PHP_EOL . 'XML file have been successfully generated.' . PHP_EOL;
        }
      case 'model':
        if (empty($args['alias'])) self::error('The database alias is not defined.');
        else
        {
          $gen = new Generator($args['alias'], isset($args['dir']) ? $args['dir'] : null, isset($args['mode']) ? $args['mode'] : Generator::MODE_REPLACE_IMPORTANT);
          $gen->setExcludedTables($this->extractTables($args));
          $gen->useInheritance = isset($args['useInheritance']) ? (bool)$args['useInheritance'] : false;
          $gen->useTransformation = isset($args['useTransformation']) ? (bool)$args['useTransformation'] : false;
          $gen->usePrettyClassName = isset($args['usePrettyClassName']) ? (bool)$args['usePrettyClassName'] : false;
          $gen->usePrettyPropertyName = isset($args['usePrettyPropertyName']) ? (bool)$args['usePrettyPropertyName'] : false;
          $gen->orm(isset($args['ns']) ? $args['ns'] : 'Aleph\DB\ORM');
          if (Configurator::isCLI()) echo PHP_EOL . 'Model\'s classes have been successfully generated.' . PHP_EOL;
        }
        break;
      default:
        if (Configurator::isCLI()) echo $this->getCommandHelp();
        break;
    }
  }
  
  public function getData()
  {
    $tmp['aliases'] = $this->getAliases();
    $tmp['tables'] = $this->getTables(reset($tmp['aliases']));
    return ['js' => 'orm/js/orm.js', 'html' => 'orm/html/orm.html', 'data' => $tmp];
  }
  
  public function getCommandHelp()
  {
    return <<<'HELP'

Allows to create AR and ORM classes. Also, you can review information about database aliases and database tables.

The use cases:

    1. cfg orm show [--alias DATABASE_ALIAS]
    
       Outputs the database alias list and table list of each database. If DATABASE_ALIAS is defined it outputs table list of the given database.

    2. cfg orm ar [--alias DATABASE_ALIAS] [--dir BASE_DIRECTORY] [--mode CREATION_MODE] [--ns NAMESPACE] [--tables EXCLUDED_TABLES]
    
       Creates Active Record classes in directory BASE_DIRECTORY for database that determined by its alias - DATABASE_ALIAS.
       NAMESPACE - namespace of all Active Record classes.
       CREATION_MODE - determines what need to do with already existing classes. The valid values are the same as in item 2.
       EXCLUDED_TABLES - comma-separated list of tables that will be excluded from processing.
    
    3. cfg orm xml [--alias DATABASE_ALIAS] [--dir BASE_DIRECTORY] [--mode CREATION_MODE] [--ns NAMESPACE] [--tables EXCLUDED_TABLES] [--useInheritance 1|0] [--useTransformation 1|0] [--usePrettyClassName 1|0] [--usePrettyPropertyName 1|0]

       Creates XML file in directory BASE_DIRECTORY that describes model of the given database.
       DATABASE_ALIAS - alias of the needed database.
       NAMESPACE - namespace of all model classes.
       CREATION_MODE - determines what need to do with already existing XML file. The valid values are: 1 - replace existing XML file, 2 - ignore if XML already exists, 3 - replace only important data and don't touch user changes, 4 - add only new data.
       EXCLUDED_TABLES - comma-separated list of tables that will be excluded from processing.
    
    4. cfg orm model [--alias DATABASE_ALIAS] [--dir BASE_DIRECTORY] [--mode CREATION_MODE] [--ns NAMESPACE] [--tables EXCLUDED_TABLES] [--useInheritance 1|0] [--useTransformation 1|0] [--usePrettyClassName 1|0] [--usePrettyPropertyName 1|0]
    
       Creates model classes in directory BASE_DIRECTORY for database that determined by its alias - DATABASE_ALIAS.
       NAMESPACE - namespace of all model classes.
       CREATION_MODE - determines what need to do with already existing classes. The valid values are the same as in item 2. 
       EXCLUDED_TABLES - comma-separated list of tables that will be excluded from processing.

HELP;
  }
  
  private function refresh($alias)
  {
    $aliases = $this->getAliases();
    if (empty($aliases[$alias])) $alias = reset($aliases);
    $tmp['aliases'] = self::render(__DIR__ . '/html/aliases.html', ['aliases' => $aliases, 'alias' => $alias]);
    $tmp['tables'] = $this->renderTables($alias);
    return json_encode($tmp);
  }
  
  private function renderTables($alias)
  {
    return self::render(__DIR__ . '/html/tables.html', ['tables' => $alias ? $this->getTables($alias) : []]);
  }
  
  private function getAliases()
  {
    $tmp = [];
    $config = Configurator::getAleph()->getConfig();
    foreach ($config as $alias => $info) if (isset($info['dsn'])) $tmp[$alias] = $alias;
    return $tmp;
  }
  
  private function getTables($alias)
  {
    if (!$alias) return [];
    try
    {
      $db = DB::getConnection($alias);
      $db->connect();
    }
    catch (\Exception $e)
    {
      return $e->getMessage();
    }
    return $db->getTableList();
  }
  
  private function extractTables(array $args)
  {
    if (empty($args['tables'])) return [];
    if (is_array($args['tables'])) return $args['tables'];
    return explode(',', $args['tables']);
  }
}