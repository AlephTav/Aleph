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
  const ERR_SQL_1 = 'Database engine "[{var}]" doesn\'t exist or is not supported.';
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
      case 'sqlite':
        return new SQLiteBuilder();
    }
    throw new Core\Exception('Aleph\DB\SQLBuilder::ERR_SQL_1', $engine);
  }
  
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
  
  /**
   * Returns SQL data type of the particular DBMS that mapped to PHP type.
   *
   * @param string $type - SQL type.
   * @return string
   * @access public
   */
  abstract public function getPHPType($type);
  
  /**
   * Quotes a table or column name for use in SQL queries.
   *
   * @param string $name - a column or table name.
   * @param boolean $isTableName - determines whether table name is used.
   * @return string
   * @access public
   */
  abstract public function wrap($name, $isTableName = false);
  
  /**
   * Quotes a string value for use in a query.
   *
   * @param string $value
   * @param boolean $isLike - determines whether the value is used in LIKE clause.
   * @return string
   * @access public
   */
  abstract public function quote($value, $isLike = false);
  
  /**
   * Returns SQL for getting the table list of the current database.
   *
   * @param string $scheme - a table scheme (database name).
   * @return string
   * @access public
   */
  abstract public function tableList($scheme = null);
  
  /**
   * Returns SQL for getting metadata of the specified table.
   *
   * @param string $table
   * @return string
   * @access public
   */
  abstract public function tableInfo($table);
  
  /**
   * Returns SQL for getting metadata of the table columns.
   *
   * @param string $table
   * @return string
   * @access public
   */
  abstract public function columnsInfo($table);
  
  /**
   * Returns SQL for creating a new DB table.
   *
   * @param string $table - the name of the table to be created.
   * @param array $columns - the columns of the new table.
   * @param string $options - additional SQL fragment that will be appended to the generated SQL.
   * @return string
   * @access public
   */
  abstract public function createTable($table, array $columns, $options = null);
  
  /**
   * Returns SQL for renaming a table.
   *
   * @param string $oldName - old table name.
   * @param string $newName - new table name.
   * @return string
   * @access public
   */
  abstract public function renameTable($oldName, $newName);

  /**
   * Returns SQL that can be used for removing the particular table.
   *
   * @param string $table
   * @return string
   * @access public
   */
  abstract public function dropTable($table);

  /**
   * Returns SQL that can be used to remove all data from a table.
   *
   * @param string $table
   * @return string
   * @access public
   */
  abstract public function truncateTable($table);
  
  /**
   * Returns SQL for adding a new column to a table.
   *
   * @param string $table - the table that the new column will be added to.
   * @param string $column - the name of the new column.
   * @param string $type - the column type.
   * @return string
   * @access public
   */
  abstract public function addColumn($table, $column, $type);
  
  /**
   * Returns SQL for renaming a column.
   *
   * @param string $table - the table whose column is to be renamed.
   * @param string $oldName - the previous name of the column.
   * @param string $newName - the new name of the column.
   * @return string
   * @access public   
   */
  abstract public function renameColumn($table, $oldName, $newName);
  
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
  abstract public function changeColumn($table, $oldName, $newName, $type);
  
  /**
   * Returns SQL for dropping a DB column.
   *
   * @param string $table - the table whose column is to be dropped.
   * @param string $column - the name of the column to be dropped.
   * @return string
   * @access public
   */
  abstract public function dropColumn($table, $column);
  
  /**
   * Returns SQL for adding a foreign key constraint to an existing table.
   *
   * @param string $name - the name of the foreign key constraint.
   * @param string $table - the table that the foreign key constraint will be added to.
   * @param string $columns - the name of the column to that the constraint will be added on. If there are multiple columns, separate them with commas.
   * @param string $refTable - the table that the foreign key references to.
   * @param string $refColumns - the name of the column that the foreign key references to. If there are multiple columns, separate them with commas.
   * @param string $delete - the ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
   * @param string $update - the ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
   * @return string
   * @access public
   */
  abstract public function addForeignKey($name, $table, array $columns, $refTable, array $refColumns, $delete = null, $update = null);
  
  /**
   * Returns SQL for dropping a foreign key constraint.
   *
   * @param string $name - the name of the foreign key constraint to be dropped.
   * @param string $table - the table whose foreign key is to be dropped.
   * @return string
   * @access public
   */
  abstract public function dropForeignKey($name, $table);
  
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
  abstract public function createIndex($name, $table, array $columns, $class = null, $type = null);
  
  /**
   * Returns SQL for dropping an index.
   *
   * @param string $name - the name of the index to be dropped.
   * @param string $table - the table whose index is to be dropped.
   * @return string
   * @access public
   */
  abstract public function dropIndex($name, $table);
  
  /**
   * Normalizes the metadata of the DB columns.
   *
   * @param array $info - the column metadata.
   * @return array
   * @access public
   */
  abstract public function normalizeColumnsInfo(array $info);
  
  /**
   * Normalizes the DB table metadata.
   *
   * @param array $info - the table metadata.
   * @return array
   * @access public
   */
  abstract public function normalizeTableInfo(array $info);
  
  /**
   * Starts to form the SQL-query of INSERT-type.
   * Value of $columns can be one of the following possible variants:
   * <ul>
   * <li>A string or SQLExpression instance: <code>$sql->insert('MyTable', '(2, CURTIME())');</code></li>
   * <li>One-dimensional associative array: <code>$sql->insert('MyTable', ['firstName' => 'John', 'lastName' => 'Smith', 'email' => 'johnsmith@gmail.com']);</code></li>
   * <li>Multi-dimensional associative array: <code>$sql->insert('MyTable', ['column1' => [1, 2, new SQLExpression('VERSION()')], 'column2' => ['a', 'b'], 'column3' => 'foo']);</code></li>
   * </ul>
   * @param string $table - the table name.
   * @param mixed $columns - the column metadata.
   * @param array $options - additional information for some DBMS.
   * @return self
   * @access public
   */
  public function insert($table, $columns, array $options = null)
  {
    $res = $this->insertExpression($columns, $data);
    $tmp = [];
    $tmp['type'] = 'insert';
    $tmp['data'] = $data;
    $tmp['table'] = $this->wrap($table, true);
    $tmp['columns'] = $res['columns'];
    $tmp['values'] = $res['values'];
    $tmp['options'] = $options;
    $this->sql[] = $tmp;
    return $this;
  }
  
  public function update($table, $columns, array $options = null)
  {
    $tmp = array();
    $tmp['type'] = 'update';
    $tmp['data'] = array();
    $tmp['table'] = $this->selectExpression($table, true);
    $tmp['columns'] = $this->updateExpression($columns, $tmp['data']);
    $tmp['options'] = $options;
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
  
  /**
   * Returns completely formed SQL-query of the given type.
   *
   * @param mixed $data - a variable in which the data array for the SQL-query will be written.
   * @return string
   * @access public
   */
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
  
  /**
   * Returns completed SQL-query of INSERT-type.
   *
   * @param array $insert - the query data.
   * @return string
   * @access protected
   */
  protected function buildInsert(array $insert)
  {
    $sql = 'INSERT INTO ' . $insert['table'] . ' ';
    if (!is_array($insert['values'])) $sql .= $insert['values'];
    else 
    {
      foreach ($insert['values'] as &$values) $values = '(' . implode(',', $values) . ')';
      $sql .= '(' . implode(',', $insert['columns']) . ') VALUES ' . implode(',', $insert['values']);
    }
    return $sql;
  }
  
  /**
   * Returns completed SQL-query of UPDATE-type.
   *
   * @param array $update - the query data.
   * @return string
   * @access protected
   */
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
    if (!empty($select['group'])) $sql .= ' GROUP BY ' . implode(', ', $select['group']);
    if (!empty($select['having'])) $sql .= ' HAVING ' . $select['having'];
    if (!empty($select['order'])) $sql .= ' ORDER BY ' . implode(', ', $select['order']);
    if (!empty($select['limit'])) $sql .= ' LIMIT ' . implode(', ', $select['limit']);
    return $sql;
  }
  
  /**
   * Normalizes the column metadata for the INSERT-type SQL-query.
   *
   * @param mixed $expression - the column metadata.
   * @param mixed $data - a variable in which the data array for the SQL-query will be written.
   * @return array - normalized column data for query building.
   * @access protected
   */
  protected function insertExpression($expression, &$data)
  {
    $data = [];
    if (!is_array($expression)) return ['columns' => null, 'values' => (string)$expression];
    $tmp = ['columns' => [], 'values' => []]; $max = $i = 0;
    foreach ($expression as $column => &$values) 
    {
      $tmp['columns'][$column] = $this->wrap($column);
      if (!is_array($values)) $values = [$values];
      if (count($values) > $max) $max = count($values);
    }
    unset($values);
    $records = array_values($expression);
    for ($i = 0; $i < $max; $i++)
    {
      foreach ($records as $j => $values)
      {
        $value = empty($values[$i]) ? end($values) : $values[$i];
        if ($value instanceof self || $value instanceof SQLExpression) $tmp['values'][$i][$j] = (string)$value;
        else 
        {
          $tmp['values'][$i][$j] = '?';
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