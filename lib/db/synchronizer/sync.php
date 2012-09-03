<?php
/**
 * Copyright (c) 2012 Aleph Tav
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
 * @copyright Copyright &copy; 2012 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\DB\Sync;

use Aleph\Core;

/**
 * Interface for all classes that read the information on the database structure.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.db.sync
 */
interface IReader
{
  /**
   * Sets the regular expression for table names that are information tables.
   * Data of information tables will also be synchronized.
   *
   * @param string $pattern - the regular expression.
   * @access public
   */
  public function setInfoTables($pattern);
  
  /**
   * Returns the regular expression for detecting of names of information tables.
   * 
   * @return string
   * @access public
   */
  public function getInfoTables();

  /**
   * Resets read data of database structure. 
   * Repeated calling method "read" will allow us to get up to date information about database structure.
   *
   * @return self
   * @access public
   */
  public function reset();
  
  /**
   * Reads information about database structure.
   *
   * @return array - you can see format of returned array in file /lib/db/synchronizer/structure_db.txt
   * @access public
   */
  public function read();
}

/**
 * Interface for all classes that implement changes in structure of databases.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.db.sync
 */
interface IWriter
{
  /**
   * Makes changes in database structure and data of information tables.
   * If changes of db structure move to a database then the method returns the array of executed SQL queries.
   *
   * @param array $info - the array returned by method Synchronizer::compare.
   * @return array | NULL
   * @access public
   */
  public function write(array $info);
}

/**
 * Main class for database structure synchronization.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.db.sync
 */
class Synchronizer
{
  /**
   * Error message templates.
   */
  const ERR_SYNC_1 = 'Source data provider is not specified.';
  const ERR_SYNC_2 = 'Destination data provider is not specified.';
  const ERR_SYNC_3 = 'It is impossible to synchronize databases with different DBMS';
  
  /**
   * The source database structure.
   *
   * @var array $out - you can see format of this array in file /lib/db/synchronizer/structure_db.txt
   * @access protected
   */
  protected $out = null;
  
  /**
   * The recipient database structure.
   *
   * @var array $in - you can see format of this array in file /lib/db/synchronizer/structure_db.txt
   * @access protected
   */
  protected $in = null;
  
  /**
   * Regular expression for detecting of names of information tables.
   * Data of such tables will be synchronized.
   *
   * @var string $infoTablesPattern
   * @access protected
   */
  protected $infoTablesPattern = null;
  
  protected $params = array();
  
  /**
   * Sets the regular expression for table names that are information tables.
   * Data of information tables will also be synchronized.
   *
   * @param string $pattern - the regular expression.
   * @access public
   */
  public function setInfoTables($pattern)
  {
    $this->infoTablesPattern = $pattern;
  }
  
  /**
   * Returns the regular expression for detecting of names of information tables.
   * 
   * @return string
   * @access public
   */
  public function getInfoTables()
  {
    return $this->infoTablesPattern;
  }
  
  /**
   * Reads database structure from the source of changes. It can be any database or the vault.
   * If the first argument of this method is file path to the vault then the method reads db structure from the vault.
   * Otherwise the methods reads structure from a database.
   *
   * @param string $vaultordsn - the file path or database connection DSN.
   * @param string $username - the username of establishing database connection.
   * @param string $password - the password of establishing database connection.
   * @param array $options - the options of establishing database connection.
   * @return self
   * @access public
   */
  public function out($vaultordsn, $username = null, $password = null, array $options = null)
  {
    if ($username === null) $reader = new VaultReader($vaultordsn);
    else $reader = DBCore::getInstance($vaultordsn, $username, $password, $options)->getReader();
    $reader->setInfoTables($this->infoTablesPattern);
    $this->out = $reader->read();
    return $this;
  }
  
  /**
   * Reads database structure from the recipient of changes. It can be any database or the vault.
   * If the first argument of this method is file path to the vault then the method reads db structure from the vault.
   * Otherwise the methods reads structure from a database.
   *
   * @param string $vaultordsn - the file path or database connection DSN.
   * @param string $username - the username of establishing database connection.
   * @param string $password - the password of establishing database connection.
   * @param array $options - the options of establishing database connection.
   * @return self
   * @access public
   */
  public function in($vaultordsn, $username = null, $password = null, array $options = null)
  {
    if ($username === null) $reader = new VaultReader($vaultordsn);
    else $reader = DBCore::getInstance($vaultordsn, $username, $password, $options)->getReader();
    $this->params = array('vaultordsn' => $vaultordsn, 'username' => $username, 'password' => $password, 'options' => $options);
    $this->in = $reader->read();
    return $this;
  }
  
  /**
   * Performs synchronization.
   * If changes of db structure move to a database then the method returns the array of executed SQL queries.  
   *
   * @param boolean $merge - determines whether or not merge of db structures is needed.
   * @return array | NULL
   * @access public
   */
  public function sync($merge = false)
  {
    if ($this->out === null) throw new Core\Exception($this, 'ERR_SYNC_1');
    if ($this->in === null) throw new Core\Exception($this, 'ERR_SYNC_2');
    if ($this->params['username'] === null) $writer = new VaultWriter($this->params['vaultordsn']);
    else $writer = DBCore::getInstance($this->params['vaultordsn'], $this->params['username'], $this->params['password'], $this->params['options'])->getWriter();
    return $writer->write($this->compare($this->out, $this->in, $merge));
  }
  
  /**
   * Compares two database structures.
   *
   * @param array $d1 - the source db structure.
   * @param array $d2 - the recipient db structure.
   * @param boolean $merge - determines whether or not merge of db structures is needed.
   * @return array - you can see format of returned array in file /lib/db/synchronizer/structure_compare.txt
   * @access protected
   */
  protected function compare(array $d1, array $d2, $merge)
  {
    if (count($d2) == 0) return array('insert' => array(), 'update' => $d1, 'delete' => array());
    if ($d1['meta']['driver'] != $d2['meta']['driver']) throw new Core\Exception($this, 'ERR_SYNC_3');
    $update = array();
    $insert = array('tables' => array(), 'columns' => array(), 'indexes' => array(), 'constraints' => array(), 'triggers' => array());
    $delete = array('triggers' => array(), 'constraints' => array(), 'indexes' => array(), 'columns' => array(), 'tables' => array());
    if ($d1['meta']['charset'] != $d2['meta']['charset'] || $d1['meta']['collation'] != $d2['meta']['collation']) $update['meta'] = $d1['meta'];
    if ($tmp = array_diff_key($d1['tables'], $d2['tables'])) $insert['tables'] = $tmp;
    if (!$merge && $tmp = array_diff_key($d2['tables'], $d1['tables'])) $delete['tables'] = $tmp;
    if ($tables = array_intersect_key($d1['tables'], $d2['tables']))
    {
      foreach ($tables as $table => $tb1)
      {
        $tb2 = $d2['tables'][$table];
        if ($tb1['meta'] != $tb2['meta']) $update['tables'][$table]['meta'] = $tb1['meta'];
        if ($tmp = array_diff_key($tb1['columns'], $tb2['columns'])) $insert['columns'][$table] = $tmp;
        if (!$merge && $tmp = array_diff_key($tb2['columns'], $tb1['columns'])) $delete['columns'][$table] = $tmp;
        if ($columns = array_intersect_key($tb1['columns'], $tb2['columns']))
        {
          foreach ($columns as $column => $cl1)
          {
            $cl2 = $tb2['columns'][$column];
            if ($cl1 != $cl2) $update['tables'][$table]['columns'][$column] = $cl1;
          }
        }
        foreach (array('indexes', 'constraints', 'triggers') as $entity)
        {
          if ($tmp = array_diff_key($tb1[$entity], $tb2[$entity])) $insert[$entity][$table] = $tmp;
          if (!$merge && $tmp = array_diff_key($tb2[$entity], $tb1[$entity])) $delete[$entity][$table] = $tmp;
          if ($tmp = array_intersect_key($tb1[$entity], $tb2[$entity]))
          {
            foreach ($tmp as $name => $value)
            {
              if ($value != $tb2[$entity][$name]) $update['tables'][$table][$entity][$name] = $value;
            }
          }
        }
      }
    }
    foreach (array('procedures', 'events', 'views') as $entity)
    {
      if ($tmp = array_diff_key($d1[$entity], $d2[$entity])) $insert[$entity] = $tmp;
      if (!$merge && $tmp = array_diff_key($d2[$entity], $d1[$entity])) $delete[$entity] = $tmp;
      if ($tmp = array_intersect_key($d1[$entity], $d2[$entity]))
      {
        foreach ($tmp as $name => $value)
        {
          if ($value != $d2[$entity][$name]) $update[$entity][$name] = $value;
        }
      }
    }
    return array('insert' => $insert, 'update' => $update, 'delete' => $delete, 'data' => $d1['data']);
  }
}