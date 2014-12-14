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

namespace Aleph\DB\Drivers\SQLite;

use Aleph\Core;

/**
 * Class for building SQLite queries.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.db.drivers.sqlite
 */
class SQLBuilder extends \Aleph\DB\SQLBuilder
{
  /**
   * Error message templates.
   */
  const ERR_SQLITE_1 = 'Renaming a DB column is not supported by SQLite.';
  const ERR_SQLITE_2 = 'Adding a foreign key constraint to an existing table is not supported by SQLite.';
  const ERR_SQLITE_3 = 'Dropping a foreign key constraint is not supported by SQLite.';
  
  /**
   * Returns SQLite data type that mapped to PHP type.
   *
   * @param string $type - SQL type.
   * @return string
   * @access public
   */
  public function getPHPType($type)
  {
    switch ($type)
    {
      case 'int':
      case 'int2':
      case 'int8':
      case 'integer':
      case 'tinyint':
      case 'smallint':
      case 'mediumint':
      case 'bigint':
        return 'int';
      case 'numeric':
      case 'float':
      case 'real':
      case 'decimal':
      case 'double':
      case 'double precision':
        return 'float';
      case 'bool':
        return 'bool';
    }
    return 'string';
  }
  
  /**
   * Quotes a value (or an array of values) to produce a result that can be used as a properly escaped data value in an SQL statement.
   *
   * @param string | array $value - if this value is an array then all its elements will be quoted.
   * @param string $format - determines the format of the quoted value. This value must be one of the SQLBuilder::ESCAPE_* constants.
   * @return string | array
   * @access public
   */
  public function quote($value, $format = self::ESCAPE_QUOTED_VALUE)
  {
    if (is_array($value))
    {
      foreach ($value as &$v) $v = $this->quote($v, $format);
      return $value;
    }
    switch ($format)
    {
      case self::ESCAPE_QUOTED_VALUE:
        return "'" . str_replace("'", "''", $value) . "'";
      case self::ESCAPE_VALUE:
        return str_replace("'", "''", $value);
      case self::ESCAPE_LIKE:
        return addcslashes(str_replace("'", "''", $value), '_%');
      case self::ESCAPE_QUOTED_LIKE:
        return "'%" . addcslashes(str_replace("'", "''", $value), '_%') . "%'";
      case self::ESCAPE_LEFT_LIKE:
        return "'%" . addcslashes(str_replace("'", "''", $value), '_%') . "'";
      case self::ESCAPE_RIGHT_LIKE:
        return "'" . addcslashes(str_replace("'", "''", $value), '_%') . "%'";
    }
    throw new Core\Exception($this, 'ERR_SQL_4', $format);
  }
  
  /**
   * Returns SQL for getting the table list of the current database.
   *
   * @param string $scheme - a table scheme (database name).
   * @return string
   * @access public
   */
  public function tableList($scheme = null)
  {
    return 'SELECT tbl_name FROM sqlite_master WHERE type = \'table\' AND tbl_name <> \'sqlite_sequence\' ORDER BY name';
  }
  
  /**
   * Returns SQL for getting metadata of the specified table.
   *
   * @param string $table
   * @return string
   * @access public
   */
  public function tableInfo($table)
  {
    $tb = $this->quote($table);
    $cnst = 'PRAGMA foreign_key_list(' . $tb . ')';
    $keys = 'PRAGMA index_list(' . $tb . ')';
    return ['meta' => '', 'columns' => $this->columnsInfo($table), 'constraints' => $cnst, 'keys' => $keys];
  }
  
  /**
   * Returns SQL for getting metadata of the table columns.
   *
   * @param string $table
   * @return string
   * @access public
   */
  public function columnsInfo($table)
  {
    return 'PRAGMA table_info(' . $this->quote($table) . ')';
  }
  
  /**
   * Returns SQL for creating a new DB table.
   *
   * @param string $table - the name of the table to be created.
   * @param array $columns - the columns of the new table.
   * @param string $options - additional SQL fragment that will be appended to the generated SQL.
   * @return string
   * @access public
   */
  public function createTable($table, array $columns, $options = null)
  {
    $tmp = [];
    foreach ($columns as $column => $type)
    {
      if (is_numeric($column)) $tmp[] = $type;
      else $tmp[] = $this->wrap($column) . ' ' . $type;
    }
    return 'CREATE TABLE ' . $this->wrap($table, true) . (count($tmp) ? ' (' . implode(', ', $tmp) . ')' : '') . ($options ? ' ' . $options : '');
  }
  
  /**
   * Returns SQL for renaming a table.
   *
   * @param string $oldName - old table name.
   * @param string $newName - new table name.
   * @return string
   * @access public
   */
  public function renameTable($oldName, $newName)
  {
    return 'ALTER TABLE ' . $this->wrap($oldName, true) . ' RENAME TO ' . $this->quote($newName);
  }

  /**
   * Returns SQL that can be used for removing the particular table.
   *
   * @param string $table
   * @return string
   * @access public
   */
  public function dropTable($table)
  {
    return 'DROP TABLE ' . $this->wrap($table, true);
  }

  /**
   * Returns SQL that can be used to remove all data from a table.
   *
   * @param string $table
   * @return string
   * @access public
   */
  public function truncateTable($table)
  {
    return 'DELETE FROM ' . $this->wrap($table, true);
  }
  
  /**
   * Returns SQL for adding a new column to a table.
   *
   * @param string $table - the table that the new column will be added to.
   * @param string $column - the name of the new column.
   * @param string $type - the column type.
   * @return string
   * @access public
   */
  public function addColumn($table, $column, $type)
  {
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' ADD ' . $this->quote($column) . ' ' . $type;
  }
  
  /**
   * Returns SQL for renaming a column.
   *
   * @param string $table - the table whose column is to be renamed.
   * @param string $oldName - the previous name of the column.
   * @param string $newName - the new name of the column.
   * @return string
   * @access public   
   */
  public function renameColumn($table, $oldName, $newName)
  {
    throw new Core\Exception($this, 'ERR_SQLITE_1');
  }
  
  /**
   * Returns SQL for changing the definition of a column.
   *
   * @param string $table - the table whose column is to be changed.
   * @param string $oldName - the old name of the column.
   * @param string $newName - the new name of the column.
   * @param string $type - the type of the new column.
   * @return string
   * @access public
   */
  public function changeColumn($table, $oldName, $newName, $type)
  {
    return 'ALTER TABLE '. $this->wrap($table, true) . ' CHANGE ' . $this->wrap($oldName) . ' ' . $this->quote($newName) . ' ' . $type;
  }
  
  /**
   * Returns SQL for dropping a DB column.
   *
   * @param string $table - the table whose column is to be dropped.
   * @param string $column - the name of the column to be dropped.
   * @return string
   * @access public
   */
  public function dropColumn($table, $column)
  {
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' DROP ' . $this->wrap($column);
  }
  
  /**
   * Returns SQL for adding a foreign key constraint to an existing table.
   *
   * @param string $name - the name of the foreign key constraint.
   * @param string $table - the table that the foreign key constraint will be added to.
   * @param array $columns - the column(s) to that the constraint will be added on.
   * @param string $refTable - the table that the foreign key references to.
   * @param array $refColumns - the column(s) that the foreign key references to.
   * @param string $delete - the ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
   * @param string $update - the ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
   * @return string
   * @access public
   */
  public function addForeignKey($name, $table, array $columns, $refTable, array $refColumns, $delete = null, $update = null)
  {
    throw new Core\Exception($this, 'ERR_SQLITE_2');
  }
  
  /**
   * Returns SQL for dropping a foreign key constraint.
   *
   * @param string $name - the name of the foreign key constraint to be dropped.
   * @param string $table - the table whose foreign key is to be dropped.
   * @return string
   * @access public
   */
  public function dropForeignKey($name, $table)
  {
    throw new Core\Exception($this, 'ERR_SQLITE_3');
  }
  
  /**
   * Returns SQL for creating a new index.
   *
   * @param string $name - the index name.
   * @param string $table - the table that the new index will be created for.
   * @param array $columns - the columns that should be included in the index.
   * @param string $class - the index class. For example, it can be UNIQUE, FULLTEXT and etc.
   * @param string $type - the index type.
   * @return string
   * @access public
   */
  public function createIndex($name, $table, array $columns, $class = null, $type = null)
  {
    $tmp = [];
    foreach ($columns as $column => $length)
    {
      if (is_string($column)) $tmp[] = $this->wrap($column) . '(' . (int)$length . ')';
      else $tmp[] = $this->wrap($length);
    }
    return 'CREATE ' . ($class ? $class . ' ' : '') . 'INDEX ' . $this->quote($name) . ' ON ' . $this->wrap($table, true) . ' (' . implode(', ' , $tmp) . ')';
  }
  
  /**
   * Returns SQL for dropping an index.
   *
   * @param string $name - the name of the index to be dropped.
   * @param string $table - the table whose index is to be dropped.
   * @return string
   * @access public
   */
  public function dropIndex($name, $table)
  {
    return 'DROP INDEX ' . $this->wrap($name, true);
  }
  
  /**
   * Normalizes the metadata of the DB columns.
   *
   * @param array $info - the column metadata.
   * @return array
   * @access public
   */
  public function normalizeColumnsInfo(array $info)
  {
    $tmp = []; $ai = null;
    foreach ($info as $row)
    {
      $row['type'] = strtolower($row['type']);
      preg_match('/(.*)\((.*)\)|[^()]*/', str_replace('unsigned', '', $row['type']), $arr);
      $column = $row['name'];
      if ($ai !== false && $row['pk']) 
      {
        if ($ai !== null) $ai = false;
        else $ai = $column;
      }
      $tmp[$column]['column'] = $column;
      $tmp[$column]['type'] = $type = isset($arr[1]) ? $arr[1] : $arr[0];
      $tmp[$column]['phpType'] = $this->getPHPType($tmp[$column]['type']);
      $tmp[$column]['isPrimaryKey'] = (bool)$row['pk'];
      $tmp[$column]['isNullable'] = ($row['notnull'] == 0);
      $tmp[$column]['isAutoincrement'] = false;
      $tmp[$column]['isUnsigned'] = strpos($row['type'], 'unsigned') !== false;
      $tmp[$column]['default'] = $row['dflt_value'];
      $tmp[$column]['maxLength'] = 0;
      $tmp[$column]['precision'] = 0;
      $tmp[$column]['set'] = false;
      if (empty($arr[2])) continue;
      $arr = explode(',', $arr[2]);
      if (count($arr) == 1) $tmp[$column]['maxLength'] = (int)trim($arr[0]);
      else
      {
        $tmp[$column]['maxLength'] = (int)trim($arr[0]);
        $tmp[$column]['precision'] = (int)trim($arr[1]);
      }
    }
    if (!empty($ai) && !strncasecmp($tmp[$ai]['type'], 'int', 3)) $tmp[$ai]['isAutoincrement'] = true;
    return $tmp;
  }
  
  /**
   * Normalizes the DB table metadata.
   *
   * @param array $info - the table metadata.
   * @return array
   * @access public
   */
  public function normalizeTableInfo(array $info)
  {
    $tmp = [];
    $tmp['meta'] = $info['meta'];
    $tmp['columns'] = $this->normalizeColumnsInfo($info['columns']);
    $tmp['keys'] = $tmp['constraints'] = $tmp['pk'] = [];
    $tmp['ai'] = null;
    $schema = $this->db->getSchema();
    foreach ($info['constraints'] as $cnst)
    {
      $tmp['constraints'][$cnst['id']]['columns'][$cnst['from']] = ['schema' => $schema, 'table' => $cnst['table'], 'column' => $cnst['to']];
      $tmp['constraints'][$cnst['id']]['actions'] = ['update' => $cnst['on_update'], 'delete' => $cnst['on_delete']];
    }
    foreach ($info['keys'] as $key)
    {
      $columns = $this->db->rows('PRAGMA index_info(' . $this->quote($key['name']) . ')');
      foreach ($columns as $column) $tmp['keys'][$key['seq']]['columns'][] = $column['name'];
      $tmp['keys'][$key['seq']]['isUnique'] = $key['unique']; 
    }
    foreach ($info['columns'] as $column) 
    {
      if ($column['pk']) $tmp['pk'][] = $column['name'];
    }
    foreach ($tmp['columns'] as $column => $info) 
    {
      if ($info['isAutoincrement'])
      {
        $tmp['ai'] = $column;
        break;
      }
    }
    return $tmp;
  }
}