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

class MySQLBuilder extends SQLBuilder
{
  const ERR_MYSQL_1 = 'Renaming DB column is not supported by MySQL.';

  public function wrap($name, $isTableName = false)
  {
    if ($name == '') throw new Core\Exception('Aleph\DB\SQLBuilder', 'ERR_SQL_2');
    $name = explode('.', $name);
    foreach ($name as &$part)
    {
      if ($part == '*') continue;
      if ($part[0] == '`' && $part[strlen($part) - 1] == '`') 
      {
        $part = str_replace('``', '`', substr($part, 1, -1));
      }
      $part = '`' . str_replace('`', '``', $part) . '`';
    }
    return implode('.', $name);
  }
  
  public function tableList($schema = null)
  {
    if ($schema == '') return 'SHOW TABLES';
    return 'SHOW TABLES FROM ' . $this->wrap($schema, true);
  }
  
  public function tableInfo($table)
  {
    return 'SHOW CREATE TABLE ' . $this->wrap($table, true);
  }
  
  public function columnsInfo($table)
  {
    return 'SHOW COLUMNS FROM ' . $this->wrap($table, true);
  }
  
  public function createTable($table, array $columns, $options = null)
  {
    $tmp = array();
    foreach ($columns as $column => $type)
    {
      if (is_numeric($column)) $tmp[] = $type;
      else $tmp[] = $this->wrap($column) . ' ' . $type;
    }
    return 'CREATE TABLE ' . $this->wrap($table, true) . ' (' . implode(', ', $tmp) . ')' . ($options ? ' ' . $options : '');
  }
  
  public function renameTable($oldName, $newName)
  {
    return 'RENAME TABLE ' . $this->wrap($oldName, true) . ' TO ' . $this->wrap($newName, true);
  }
  
  public function dropTable($table)
  {
    return 'DROP TABLE ' . $this->wrap($table, true);
  }

  public function truncateTable($table)
  {
    return 'TRUNCATE TABLE ' . $this->wrap($table, true);
  }
  
  public function addColumn($table, $column, $type)
  {
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' ADD ' . $this->wrap($column) . ' ' . $type;
  }
  
  public function renameColumn($table, $oldName, $newName)
  {
    throw new Core\Exception($this, 'ERR_MYSQL_1');
  }
  
  public function changeColumn($table, $oldName, $newName, $type)
  {
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' CHANGE ' . $this->wrap($oldName) . ' ' . $this->wrap($newName) . ' ' . $type;
  }
  
  public function dropColumn($table, $column)
  {
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' DROP COLUMN ' . $this->wrap($column);
  }
  
  public function addForeignKey($name, $table, array $columns, $refTable, array $refColumns, $delete = null, $update = null)
  {
    foreach ($columns as &$column) $column = $this->wrap($column);
    foreach ($refColumns as &$column) $column = $this->wrap($column);
    $sql = 'ALTER TABLE ' . $this->wrap($table, true) . ' ADD CONSTRAINT ' . $this->wrap($name) . ' FOREIGN KEY (' . implode(', ', $columns) . ') REFERENCES ' . $this->wrap($refTable, true) . ' (' . implode(', ', $refColumns) . ')';
    if ($update != '') $sql .= ' ON UPDATE ' . $update;
    if ($delete != '') $sql .= ' ON DELETE ' . $delete;
    return $sql;
  }
  
  public function dropForeignKey($name, $table)
  {
    return 'ALTER TABLE ' . $this->wrap($table, true) . ' DROP FOREIGN KEY ' . $this->wrap($name);
  }
  
  public function createIndex($name, $table, array $columns, $option = null)
  {
    $tmp = array();
    foreach ($columns as $column => $length)
    {
      if (is_string($column)) $tmp[] = $this->wrap($column) . '(' . (int)$length . ')';
      else $tmp[] = $this->wrap($length);
    }
    return 'CREATE ' . ($option ? $option . ' ' : '') . 'INDEX ' . $this->wrap($name, true) . ' ON ' . $this->wrap($table, true) . ' (' . implode(', ' , $tmp) . ')';
  }
  
  public function dropIndex($name, $table)
  {
    return 'DROP INDEX ' . $this->wrap($name, true) . ' ON ' . $this->wrap($table, true);
  }
  
  public function normalizeColumnInfo(array $info)
  {
    $tmp = array();
    foreach ($info as $row)
    {
      preg_match('/.+\(([\d\w,\']+)\)/U', $row['Type'], $arr);
      $column = $row['Field'];
      $tmp[$column]['column'] = $column;
      $tmp[$column]['type'] = $type = preg_replace('/\([\d\w,\']+\)/U', '', $row['Type']);
      $tmp[$column]['isPrimaryKey'] = ($row['Key'] == 'PRI');
      $tmp[$column]['isNullable'] = ($row['Null'] != 'NO');
      $tmp[$column]['isAutoIncrement'] = ($row['Extra'] == 'auto_increment');
      if (substr($type, -8) == 'unsigned')
      {
        $tmp[$column]['type'] = trim(substr($type, 0, -8));
        $tmp[$column]['isUnsigned'] = true;
      }
      else $tmp[$column]['isUnsigned'] = false;
      $tmp[$column]['default'] = ($type == 'bit') ? substr($row['Default'], 2, 1) : $row['Default'];
      if ($tmp[$column]['default'] === null && !$tmp[$column]['isNullable']) $tmp[$column]['default'] = '';
      $tmp[$column]['maxLength'] = 0;
      $tmp[$column]['precision'] = 0;
      $tmp[$column]['set'] = false;
      if (!isset($arr[1])) continue;
      $arr = explode(',', $arr[1]);
      if ($type == 'enum' || $type == 'set') $tmp[$column]['set'] = $arr;
      else
      {
        if (count($arr) == 1) $tmp[$column]['maxLength'] = $arr[0];
        else
        {
          $tmp[$column]['maxLength'] = $arr[0];
          $tmp[$column]['precision'] = $arr[1];
        }
      }
    }
    return $tmp;
  }
  
  public function normalizeTableInfo(array $info)
  {
    $sql = $info['Create Table'];
    $info = array('constraints' => array(), 'keys' => array());
    $clean = function($column, $smart = false)
    {
      $column = explode('.', $column);
      foreach ($column as &$col) $col = substr(trim($col), 1, -1);
      return $smart && count($column) == 1 ? $column[0] : $column;
    };
    preg_match_all('/CONSTRAINT\s+(.+)\s+FOREIGN KEY\s+\((.+)\)\s+REFERENCES\s+(.+)\s*\((.+)\)\s*(ON\s+[^,\r\n\)]+)?/mi', $sql, $matches, PREG_SET_ORDER);
    foreach ($matches as $k => $match)
    {
      $actions = array();
      if (isset($match[5]))
      {
        foreach (explode('ON', trim($match[5])) as $act)
        {
          if ($act == '') continue;
          $act = explode(' ', trim($act));
          $actions[strtolower($act[0])] = $act[1];
        }
      }
      $info['constraints'][$clean($match[1], true)] = array('columns' => $clean($match[2]), 
                                                            'reference' => array('table' => $clean($match[3], true), 'columns' => $clean($match[4])),
                                                            'actions' => $actions);
    }
    preg_match_all('/[^YN]+\s+KEY\s+(.+)\s*\(([^\)]+)\)/mi', $sql, $matches, PREG_SET_ORDER);
    foreach ($matches as $match)
    {
      $info['keys'][$clean($match[1], true)] = array_map($clean, explode(',', $match[2]));
    }
    if (preg_match('/\s+ENGINE\s*=\s*([^\s]+)/mi', $sql, $match)) $info['engine'] = $match[1];
    if (preg_match('/\s+DEFAULT CHARSET\s*=\s*([^\s]+)/mi', $sql, $match)) $info['charset'] = $match[1];
    if (preg_match('/\s+AUTO_INCREMENT\s*=\s*([^\s]+)/mi', $sql, $match)) $info['autoIncrementStartValue'] = $match[1];
    return $info;
  }
}