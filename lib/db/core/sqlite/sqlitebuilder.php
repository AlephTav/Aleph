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

namespace Aleph\DB;

use Aleph\Core;

class SQLiteBuilder extends SQLBuilder
{
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
      case 'integer':
        return 'integer';
      case 'real':
        return 'float';
    }
    return 'string';
  }
  
  /**
   * Quotes a table or column name for use in SQL queries.
   *
   * @param string $name - a column or table name.
   * @param boolean $isTableName - determines whether table name is used.
   * @return string
   * @access public
   */
  public function wrap($name, $isTableName = false)
  {
    if (strlen($name) == 0) throw new Core\Exception('Aleph\DB\SQLBuilder::ERR_SQL_2');
    $name = explode('.', $name);
    foreach ($name as &$part)
    {
      if ($part == '*') continue;
      if (substr($part, 0, 1) == '"' && substr($part, -1, 1) == '"')
      {
        $part = str_replace('""', '"', substr($part, 1, -1));
      }
      if (trim($part) == '') throw new Core\Exception('Aleph\DB\SQLBuilder::ERR_SQL_2');
      $part = '"' . str_replace('"', '""', $part) . '"';
    }
    return implode('.', $name);
  }
  
  /**
   * Quotes a string value for use in a query.
   *
   * @param string $value
   * @param boolean $isLike - determines whether the value is used in LIKE clause.
   * @return string
   * @access public
   */
  public function quote($value, $isLike = false)
  {
    $value = str_replace("'", "''", $value);
    return "'" . ($isLike ? addcslashes($value, '_%') : $value) . "'";
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
    return 'SELECT sql FROM sqlite_master WHERE type = \'table\' AND tbl_name = ' . $this->quote($table);
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
  
  public function createTable($table, array $columns, $options = null)
  {
  
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
  
  public function addColumn($table, $column, $type)
  {
  }
  
  public function renameColumn($table, $oldName, $newName)
  {
  }
  
  public function changeColumn($table, $oldName, $newName, $type)
  {
  }
  
  public function dropColumn($table, $column)
  {
  }
  
  public function addForeignKey($name, $table, array $columns, $refTable, array $refColumns, $delete = null, $update = null)
  {
  }
  
  public function dropForeignKey($name, $table)
  {
  }
  
  public function createIndex($name, $table, array $columns, $option = null)
  {
  }
  
  public function dropIndex($name, $table)
  {
  }
  
  public function normalizeColumnInfo(array $info)
  {
  }
  
  public function normalizeTableInfo(array $info)
  {
  }
}