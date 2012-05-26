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

/**
 * Base abstract class for all sql building classes.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.db
 */
abstract class SQLBuilder
{
  /**
   * Error message templates.
   */
  const ERR_SQL_1 = 'Database engine "[{var}]" doesn\'t exist or support.';
  const ERR_SQL_2 = 'Cannot wrap empty string.';
  const ERR_SQL_3 = 'You can\'t invoke method "[{var}]" twice within the current SQL-construction.';
  
  /**
   * Array of data for SQL building.
   *
   * @var array $sql
   * @access protected
   */
  protected $sql = array();

  /**
   * Returns an instance of the builder class corresponding to the given database type.
   *
   * @param string $engine - database type.
   * @return Aleph\DB\SQLBuilder
   * @access public
   */
  public static function getInstance($engine)
  {
    switch (strtolower($engine))
    {
      case 'mysql':
        return new MySQLBuilder();
    }
    throw new Core\Exception('Aleph\DB\SQLBuilder', 'ERR_SQL_1', $engine);
  }
  
  public function __toString()
  {
    try
    {
      return $this->build();
    }
    catch (\Exception $e)
    {
      \Aleph::exception($e);
    }
  }
  
  abstract public function wrap($name, $isTableName = false);
  
  abstract public function tableList($schema = null);
  
  abstract public function tableInfo($table);
  
  abstract public function columnsInfo($table);
  
  abstract public function createTable($table, array $columns, $options = null);
  
  abstract public function renameTable($oldName, $newName);

  abstract public function dropTable($table);

  abstract public function truncateTable($table);
  
  abstract public function addColumn($table, $column, $type);
  
  abstract public function renameColumn($table, $oldName, $newName);
  
  abstract public function changeColumn($table, $oldName, $newName, $type);
  
  abstract public function dropColumn($table, $column);
  
  abstract public function addForeignKey($name, $table, array $columns, $refTable, array $refColumns, $delete = null, $update = null);
  
  abstract public function dropForeignKey($name, $table);
  
  abstract public function createIndex($name, $table, array $columns, $option = null);
  
  abstract public function dropIndex($name, $table);
  
  abstract public function normalizeColumnInfo(array $info);
  
  abstract public function normalizeTableInfo(array $info);
  
  /**
   * Converts any string to Aleph\DB\SQLExpression object.
   *
   * @param string $sql - string to convert.
   * @return Aleph\DB\SQLExpression
   * @access public
   */
  public function exp($sql)
  {
    return new SQLExpression($sql);
  }
  
  public function insert($table, $columns)
  {
    $res = $this->insertExpression($columns, $data);
    $tmp = array();
    $tmp['type'] = 'insert';
    $tmp['data'] = $data;
    $tmp['table'] = $this->wrap($table, true);
    $tmp['columns'] = $res['columns'];
    $tmp['values'] = $res['values'];
    $this->sql[] = $tmp;
    return $this;
  }
  
  public function update($table, $columns)
  {
    $tmp = array();
    $tmp['type'] = 'update';
    $tmp['data'] = array();
    $tmp['table'] = $this->selectExpression($table, true);
    $tmp['columns'] = $this->updateExpression($columns, $tmp['data']);
    $this->sql[] = $tmp;
    return $this;
  }
  
  public function delete($table)
  {
    $tmp = array();
    $tmp['type'] = 'delete';
    $tmp['data'] = array();
    $tmp['table'] = $this->selectExpression($table, true);
    $this->sql[] = $tmp;
    return $this;
  }
  
  public function select($table, $columns = '*', $distinct = null)
  {
    $tmp = array();
    $tmp['type'] = 'select';
    $tmp['data'] = array();
    $tmp['distinct'] = $distinct;
    $tmp['columns'] = $this->selectExpression($columns);
    $tmp['from'] = $this->selectExpression($table, true);
    $this->sql[] = $tmp;
    return $this;
  }
  
  public function join($table, $conditions, $type = 'INNER')
  {
    if ($conditions == '') return $this;
    $tmp = array_pop($this->sql);
    if (isset($tmp['join'])) $tmp['join'] = array(); 
    $tmp['join'][] = ' ' . $type . ' JOIN ' . implode(', ', $this->selectExpression($table, true)) . ' ON ' . $this->whereExpression($conditions, $tmp['data']);
    $this->sql[] = $tmp;
    return $this;
  }
  
  public function where($conditions)
  {
    if ($conditions == '') return $this;
    $tmp = array_pop($this->sql);
    if (isset($tmp['where'])) throw new Core\Exception($this, 'ERR_SQL_3', 'where');
    $tmp['where'] = $this->whereExpression($conditions, $tmp['data']);
    $this->sql[] = $tmp;
    return $this;
  }
  
  public function group($group)
  {
    if ($group == '') return $this;
    $tmp = array_pop($this->sql);
    if (isset($tmp['group'])) throw new Core\Exception($this, 'ERR_SQL_3', 'group');
    $tmp['group'] = $this->selectExpression($group);
    $this->sql[] = $tmp;
    return $this;
  }
  
  public function having($conditions)
  {
    if ($conditions == '') return $this;
    $tmp = array_pop($this->sql);
    if (isset($tmp['having'])) throw new Core\Exception($this, 'ERR_SQL_3', 'having');
    $tmp['having'] = $this->whereExpression($conditions, $tmp['data']);
    $this->sql[] = $tmp;
    return $this;
  }
  
  public function order($order)
  {
    if ($order == '') return $this;
    $tmp = array_pop($this->sql);
    if (isset($tmp['order'])) throw new Core\Exception($this, 'ERR_SQL_3', 'order');
    $tmp['order'] = $this->selectExpression($order);
    $this->sql[] = $tmp;
    return $this;
  }
  
  public function limit($limit, $offset = null)
  {
    if ((int)$limit == 0) return $this;
    $tmp = array_pop($this->sql);
    if (isset($tmp['limit'])) throw new Core\Exception($this, 'ERR_SQL_3', 'limit');
    $tmp['limit'] = array();
    if ($offset !== null) $tmp['limit'][] = (int)$offset;
    $tmp['limit'][] = (int)$limit;
    $this->sql[] = $tmp;
    return $this;
  }
  
  public function build(&$data = null)
  {
    $tmp = array_pop($this->sql);
    $data = $tmp['data'];
    if ($tmp) switch ($tmp['type'])
    {
      case 'select': return $this->buildSelect($tmp);
      case 'insert': return $this->buildInsert($tmp);
      case 'update': return $this->buildUpdate($tmp);
      case 'delete': return $this->buildDelete($tmp);
    }
  }
  
  protected function buildInsert(array $insert)
  {
    $sql = 'INSERT INTO ' . $insert['table'] . ' ';
    if (!is_array($insert['values'])) $sql .= $insert['values'];
    else 
    {
      foreach ($insert['values'] as &$values) $values = '(' . implode(', ', $values) . ')';
      $sql .= '(' . implode(', ', $insert['columns']) . ') VALUES ' . implode(', ', $insert['values']);
    }
    return $sql;
  }
  
  protected function buildUpdate(array $update)
  {
    $sql = 'UPDATE ' . implode(', ', $update['table']) . ' SET ' . $update['columns'];
    if (!empty($update['where'])) $sql .= ' WHERE ' . $update['where'];
    if (!empty($select['order'])) $sql .= ' ORDER BY ' . implode(', ', $select['order']);
    if (!empty($select['limit'])) $sql .= ' LIMIT ' . implode(', ', $select['limit']);
    return $sql;
  }
  
  protected function buildDelete(array $delete)
  {
    $sql = 'DELETE FROM ' . implode(', ', $delete['table']);
    if (!empty($delete['where'])) $sql .= ' WHERE ' . $delete['where'];
    if (!empty($select['order'])) $sql .= ' ORDER BY ' . implode(', ', $select['order']);
    if (!empty($select['limit'])) $sql .= ' LIMIT ' . implode(', ', $select['limit']);
    return $sql;
  }
  
  protected function buildSelect(array $select)
  {
    $sql = 'SELECT ' . ($select['distinct'] ? $select['distinct'] . ' ' : '');
    $sql .= implode(', ', $select['columns']);
    $sql .= ' FROM ' . implode(', ', $select['from']);
    if (!empty($select['join'])) $sql .= implode(' ', $select['join']);
    if (!empty($select['where'])) $sql .= ' WHERE ' . $select['where'];
    if (!empty($select['group'])) $sql .= ' GROUP BY ' . implode(', ', $sleect['group']);
    if (!empty($select['having'])) $sql .= ' HAVING ' . $select['having'];
    if (!empty($select['order'])) $sql .= ' ORDER BY ' . implode(', ', $select['order']);
    if (!empty($select['limit'])) $sql .= ' LIMIT ' . implode(', ', $select['limit']);
    return $sql;
  }
  
  protected function insertExpression($expression, &$data)
  {
    $data = array();
    if (!is_array($expression)) return array('columns' => null, 'values' => (string)$expression);
    $tmp = array('columns' => array(), 'values' => array());
    if (!is_numeric(key($expression))) $expression = array($expression);
    foreach ($expression as $k => $values)
    {
      $tmp['values'][$k] = array();
      foreach ($values as $column => $value)
      {
        $tmp['columns'][$column] = $this->wrap($column);
        if ($value instanceof self || $value instanceof SQLExpression) $tmp['values'][$k][] = (string)$value;
        else 
        {
          $tmp['values'][$k][] = '?';
          $data[] = $value;
        }
      }
    }
    return $tmp;
  }
  
  protected function updateExpression($expression, &$data)
  {
    if (!is_array($expression)) return (string)$expression;
    $data = $tmp = array();
    foreach ($expression as $column => $value)
    {
      if ($value instanceof self)  $tmp[] =  $this->wrap($column) . ' = (' . (string)$value . ')';
      else if ($value instanceof SQLExpression) $tmp[] =  $this->wrap($column) . ' = ' . (string)$value;
      else 
      {
        $tmp[] = $this->wrap($column) . ' = ?';
        $data[] = $value;
      }
    }
    return implode(', ', $tmp);
  }
  
  protected function selectExpression($expression, $isTableName = false)
  {
    if ($expression == '') return array('*');
    if ($expression instanceof self) return array('(' . (string)$expression . ')');
    if ($expression instanceof SQLExpression) return array((string)$expression);
    if (is_array($expression))
    {
      $tmp = array();
      if (!is_numeric(key($expression))) $expression = array($expression);
      foreach ($expression as $exp)
      {
        if ($exp instanceof self) $tmp[] = '(' . (string)$exp . ')';
        else if ($exp instanceof SQLExpression) $tmp[] = (string)$exp;
        else if (is_array($exp))
        {
          $exp = each($exp);
          $exp[1] = ($exp[1] == 'DESC' || $exp[1] == 'ASC') ? ' ' . $exp[1] : ' AS ' . $this->wrap($exp[1], true); 
          if ($exp[0] instanceof self) $tmp[] = '(' . (string)$exp[0] . ')' . $exp[1];
          else if ($exp[0] instanceof SQLExpression) $tmp[] = (string)$exp[0] . $exp[1];
          else $tmp[] = $this->wrap($exp[0], $isTableName) . $exp[1];
        }
        else
        {
          $tmp[] = $this->wrap($exp, $isTableName);
        }
      }
      return $tmp;
    }
    return array($this->wrap($expression, $isTableName));
  }
  
  protected function whereExpression($expression, &$data, $conjunction = null)
  {
    if ($expression instanceof self) return '(' . (string)$expression . ')';
    if (!is_array($expression)) return (string)$expression;
    $tmp = array();
    $conj = $conjunction ?: ' AND ';
    foreach ($expression as $column => $value)
    {
      if (is_numeric($column))
      {
        if (is_array($value))
        {
          if ($conjunction !== null)
          {
            $tmp[] = '(' . $this->whereExpression($value, $data, ($conjunction == ' AND ') ? ' OR ' : ' AND ') . ')';
          }
          else
          {
            $conj = ' OR ';
            $tmp[] = '(' . $this->whereExpression($value, $data, ' AND ') . ')';
          }
        }
        else
        {
          if ($value instanceof self) $tmp[] = '(' . $value->build() . ')';
          else $tmp[] = (string)$value;
        }
      }
      else
      {
        if ($value instanceof self) $tmp[] = $this->wrap($column) . ' = (' . (string)$value . ')';
        else if ($value instanceof SQLExpression) $tmp[] = $this->wrap($column) . ' = ' . $value;
        else
        {
          $data[] = $value;
          $tmp[] = $this->wrap($column) . ' = ?';
        }
      }
    }
    return implode($conj, $tmp);
  }
}

/**
 * This class is wrapper of any SQL expressions. An instance of this class won't be processed during SQL building.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.db
 */
class SQLExpression
{
  /**
   * SQL expression.
   *
   * @var string $sql
   * @access protected   
   */
  protected $sql = null;

  /** Constructor.
   *
   * @param string $sql - SQL expression.
   * @access public
   */
  public function __construct($sql)
  {
    $this->sql = $sql;
  }
  
  /**
   * Converts an object of this class to string.
   *
   * @access public
   */
  public function __toString()
  {
    return $this->sql;
  }
}