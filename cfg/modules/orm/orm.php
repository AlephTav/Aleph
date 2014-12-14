<?php
/**
 * Copyright (c) 2013 - 2015 Aleph Tav
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated 
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, 
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO 
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, 
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @link http://www.4leph.com
 * @copyright Copyright &copy; 2013 - 2015 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Configuration;

use Aleph\DB\DB,
    Aleph\DB\ORM\Generator;

/**
 * Module for generating Active Record and ORM classes.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.configuration
 */
class ORM extends Module
{
  /**
   * Performs the given command.
   *
   * @param string $command - the command name.
   * @param array $args - the command arguments.
   * @access public
   * @abstract
   */
  public function process($command, array $args = null)
  {
    switch ($command)
    {
      case 'refresh':
        if (Configurator::isCLI()) 
        {
          $this->showCommandHelp();
          break;
        }
        echo $this->refresh($args['alias']);
        break;
      case 'show':
        if (Configurator::isCLI())
        {
          $output = '';
          if (isset($args['alias']))
          {
            $tables = $this->getTables($args['alias']);
            if (!is_array($tables)) 
            {
              $this->error($tables);
              break;
            }
            foreach ($tables as $n => $table)
            {
              $output .= PHP_EOL . ($n + 1) . '. ' . $table;
            }
            $output .= PHP_EOL;
          }
          else
          {
            foreach ($this->getAliases() as $alias)
            {
              $output .= PHP_EOL . 'Database: ' . $alias . PHP_EOL;
              foreach ($this->getTables($alias) as $n => $table)
              {
                $output .= PHP_EOL . ($n + 1) . '. ' . $table;
              }
              $output .= PHP_EOL;
            }
            $this->write($output);
          }
          break;
        }
        echo $this->renderTables($args['alias']);
        break;
      case 'ar':
        if (empty($args['alias'])) $this->error('The database alias is not defined.');
        else
        {
          $gen = new Generator($args['alias'], isset($args['dir']) ? $args['dir'] : null, isset($args['mode']) ? $args['mode'] : Generator::MODE_REPLACE_IMPORTANT);
          $gen->setExcludedTables($this->extractTables($args));
          $gen->ar(isset($args['ns']) ? $args['ns'] : 'Aleph\DB\AR');
          $this->write(PHP_EOL . 'Active Record\'s classes have been successfully generated.' . PHP_EOL);
        }
        break;
      case 'xml':
        if (empty($args['alias'])) 
        {
          $this->error('The database alias is not defined.');
          break;
        }
        else
        {
          $gen = new Generator($args['alias'], isset($args['dir']) ? $args['dir'] : null, isset($args['mode']) ? $args['mode'] : Generator::MODE_REPLACE_IMPORTANT);
          $gen->setExcludedTables($this->extractTables($args));
          $gen->useInheritance = isset($args['useInheritance']) ? (bool)$args['useInheritance'] : false;
          $gen->useTransformation = isset($args['useTransformation']) ? (bool)$args['useTransformation'] : false;
          $gen->usePrettyClassName = isset($args['usePrettyClassName']) ? (bool)$args['usePrettyClassName'] : false;
          $gen->usePrettyPropertyName = isset($args['usePrettyPropertyName']) ? (bool)$args['usePrettyPropertyName'] : false;
          $gen->xml(isset($args['ns']) ? $args['ns'] : 'Aleph\DB\ORM');
          $this->write(PHP_EOL . 'XML file have been successfully generated.' . PHP_EOL);
        }
      case 'model':
        if (empty($args['alias'])) $this->error('The database alias is not defined.');
        else
        {
          $xml = (isset($args['dir']) ? \Aleph::dir($args['dir']) : \Aleph::getRoot()) . '/' . $args['alias'] . '.xml';
          if (!is_file($xml)) $xml = null;
          $gen = new Generator($args['alias'], isset($args['dir']) ? $args['dir'] : null, isset($args['mode']) ? $args['mode'] : Generator::MODE_REPLACE_IMPORTANT);
          $gen->setExcludedTables($this->extractTables($args));
          $gen->useInheritance = isset($args['useInheritance']) ? (bool)$args['useInheritance'] : false;
          $gen->useTransformation = isset($args['useTransformation']) ? (bool)$args['useTransformation'] : false;
          $gen->usePrettyClassName = isset($args['usePrettyClassName']) ? (bool)$args['usePrettyClassName'] : false;
          $gen->usePrettyPropertyName = isset($args['usePrettyPropertyName']) ? (bool)$args['usePrettyPropertyName'] : false;
          $gen->orm(isset($args['ns']) ? $args['ns'] : 'Aleph\DB\ORM', null, $xml);
          $this->write(PHP_EOL . 'Model\'s classes have been successfully generated.' . PHP_EOL);
        }
        break;
      default:
        $this->showCommandHelp();
        break;
    }
  }
  
  /**
   * Returns HTML/CSS/JS data for the module GUI.
   *
   * @access public
   * @return array
   */
  public function getData()
  {
    $tmp['aliases'] = $this->getAliases();
    $tmp['tables'] = $this->getTables(reset($tmp['aliases']));
    return ['js' => 'orm/js/orm.js', 'html' => 'orm/html/orm.html', 'data' => $tmp];
  }
  
  /**
   * Returns command help of the module.
   *
   * @return string
   * @access public
   */
  public function getCommandHelp()
  {
    return <<<HELP

\e[33mAllows to create AR and ORM classes. Also, you can review information about database aliases and database tables.\e[0m

\e[36mThe use cases:\e[0m

    1. \e[32mcfg orm show [--alias DATABASE_ALIAS]\e[0m
    
       Outputs the database alias list and table list of each database. If DATABASE_ALIAS is defined it outputs table list of the given database.

    2. \e[32mcfg orm ar [--alias DATABASE_ALIAS] [--dir BASE_DIRECTORY] [--mode CREATION_MODE] [--ns NAMESPACE] [--tables EXCLUDED_TABLES]\e[0m
    
       Creates Active Record classes in directory \e[30;1mBASE_DIRECTORY\e[0m for database that determined by its alias - \e[30;1mDATABASE_ALIAS\e[0m.
       \e[30;1mNAMESPACE\e[0m - namespace of all Active Record classes.
       \e[30;1mCREATION_MODE\e[0m - determines what need to do with already existing classes. The valid values are the same as in item 2.
       \e[30;1mEXCLUDED_TABLES\e[0m - comma-separated list of tables that will be excluded from processing.
    
    3. \e[32mcfg orm xml [--alias DATABASE_ALIAS] [--dir BASE_DIRECTORY] [--mode CREATION_MODE] [--ns NAMESPACE] [--tables EXCLUDED_TABLES] 
                   [--useInheritance 1|0] [--useTransformation 1|0] [--usePrettyClassName 1|0] [--usePrettyPropertyName 1|0]\e[0m

       Creates XML file in directory \e[30;1mBASE_DIRECTORY\e[0m that describes model of the given database.
       \e[30;1mDATABASE_ALIAS\e[0m - alias of the needed database.
       \e[30;1mNAMESPACE\e[0m - namespace of all model classes.
       \e[30;1mCREATION_MODE\e[0m - determines what need to do with already existing XML file. The valid values are: 
           1 - replace existing XML file,
           2 - ignore if XML already exists,
           3 - replace only important data and don't touch user changes,
           4 - add only new data.
       \e[30;1mEXCLUDED_TABLES\e[0m - comma-separated list of tables that will be excluded from processing.
    
    4. \e[32mcfg orm model [--alias DATABASE_ALIAS] [--dir BASE_DIRECTORY] [--mode CREATION_MODE] [--ns NAMESPACE] [--tables EXCLUDED_TABLES] 
                     [--useInheritance 1|0] [--useTransformation 1|0] [--usePrettyClassName 1|0] [--usePrettyPropertyName 1|0]\e[0m
    
       Creates model classes in directory \e[30;1mBASE_DIRECTORY\e[0m for database that determined by its alias - \e[30;1mDATABASE_ALIAS\e[0m.
       \e[30;1mNAMESPACE\e[0m - namespace of all model classes.
       \e[30;1mCREATION_MODE\e[0m - determines what need to do with already existing classes. The valid values are the same as in item 2. 
       \e[30;1mEXCLUDED_TABLES\e[0m - comma-separated list of tables that will be excluded from processing.

HELP;
  }
  
  /**
   * Refreshes the table list of the given database.
   *
   * @param string $alias - the alias of the database.
   * @return string
   * @access private
   */
  private function refresh($alias)
  {
    $aliases = $this->getAliases();
    if (empty($aliases[$alias])) $alias = reset($aliases);
    $tmp['aliases'] = $this->render(__DIR__ . '/html/aliases.html', ['aliases' => $aliases, 'alias' => $alias]);
    $tmp['tables'] = $this->renderTables($alias);
    return json_encode($tmp);
  }
  
  /**
   * Refreshes the module GUI.
   *
   * @param string $alias - the alias of the database.
   * @return string - the rendered HTML.
   * @access private
   */
  private function renderTables($alias)
  {
    return $this->render(__DIR__ . '/html/tables.html', ['tables' => $alias ? $this->getTables($alias) : []]);
  }
  
  /**
   * Returns aliases of the available databases.
   *
   * @return array
   * @access private
   */
  private function getAliases()
  {
    $tmp = [];
    $config = \Aleph::getInstance()->getConfig();
    foreach ($config as $alias => $info) if (isset($info['dsn'])) $tmp[$alias] = $alias;
    return $tmp;
  }
  
  /**
   * Returns tables for the given database.
   *
   * @param string $alias - the alias of the database.
   * @return array
   * @access private
   */
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
  
  /**
   * Extracts table names from string.
   *
   * @param array $args - the command parameters.
   * @return array
   * @access private
   */
  private function extractTables(array $args)
  {
    if (empty($args['tables'])) return [];
    if (is_array($args['tables'])) return $args['tables'];
    return explode(',', $args['tables']);
  }
}