<?php
/**
 * Copyright (c) 2014 Aleph Tav
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
 * @copyright Copyright &copy; 2014 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\DB;

use Aleph\Core,
    Aleph\DB\Drivers\MySQL,
    Aleph\DB\Drivers\SQLite,
    Aleph\DB\Drivers\MSSQL,
    Aleph\DB\Drivers\PostgreSQL,
    Aleph\DB\Drivers\OCI;

/**
 * Base abstract class for all sql building classes.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.db
 */
abstract class SQLBuilder
{
  /**
   * Error message templates.
   */
  const ERR_SQL_1 = 'Database engine "[{var}]" doesn\'t exist or is not supported.';
  const ERR_SQL_2 = 'Cannot wrap or unwrap empty string.';
  const ERR_SQL_3 = 'You can\'t invoke method "[{var}]" twice within the current SQL-construction.';
  const ERR_SQL_4 = 'Escaping format "[{var}]" is invalid.';
  const ERR_SQL_5 = 'Operator "[{var}]" is invalid.';
  
  /**
   * Formats of the quoted values.
   */
  const ESCAPE_VALUE = 'value';
  const ESCAPE_QUOTED_VALUE = 'qvalue';
  const ESCAPE_LIKE = 'like';
  const ESCAPE_QUOTED_LIKE = 'qlike';
  const ESCAPE_LEFT_LIKE = 'llike';
  const ESCAPE_RIGHT_LIKE = 'rlike';
  
  /**
   * The instance of DB class.
   *
   * @var Aleph\DB\DB $db
   * @access protected
   */
  protected $db = null;
  
  /**
   * Array of data for SQL building.
   *
   * @var array $sql
   * @access protected
   */
  protected $sql = [];
  
  /**
   * Determines whether the named ($seq > 0) or question mark placeholder ($seq is 0 or FALSE) are used in the SQL statement.
   *
   * @var integer $seq
   * @access protected
   */
  protected $seq = 0;
  
  /**
   * Constuctor.
   *
   * @param Aleph\DB\DB $db - the database connection object.
   * @access public
   */
  public function __construct(DB $db = null)
  {
    $this->db = $db;
  }

  /**
   * Returns an instance of the builder class corresponding to the given database type.
   *
   * @param string $engine - the type of DBMS.
   * @param Aleph\DB\DB $db - the database connection object.
   * @return Aleph\DB\SQLBuilder
   * @access public
   */
  public static function getInstance($engine, DB $db = null)
  {
    switch ($engine)
    {
      case 'MySQL':
        return new MySQL\SQLBuilder($db);
      case 'SQLite':
        return new SQLite\SQLBuilder($db);
      case 'PostgreSQL':
        return new PostgreSQL\SQLBuilder($db);
      case 'MSSQL':
        return new MSSQL\SQLBuilder($db);
      case 'OCI':
        return new OCI\SQLBuilder($db);
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
   * Quotes a value (or an array of values) to produce a result that can be used as a properly escaped data value in an SQL statement.
   *
   * @param string | array $value - if this value is an array then all its elements will be quoted.
   * @param string $format - determines the format of the quoted value. This value must be one of the SQLBuilder::ESCAPE_* constants.
   * @return string | array
   * @access public
   */
  abstract public function quote($value, $format = self::ESCAPE_QUOTED_VALUE);
  
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
   * @param array $columns - the column(s) to that the constraint will be added on.
   * @param string $refTable - the table that the foreign key references to.
   * @param array $refColumns - the column(s) that the foreign key references to.
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
   * Starts to form the SQL query by using the given query fragment.
   *
   * @param string $sql - any SQL query string beginning with one of the following keywords: SELECT, INSERT, UPDATE, DELETE.
   * @return self
   * @access public
   */
  public function start($sql)
  {
    $this->sql[] = ['type' => strtolower(substr($sql, 0, 6)), 'sql' => $sql];
    return $this;
  }
  
  /**
   * Starts to form the SQL query of INSERT type.
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
    $tmp = [];
    $tmp['type'] = 'insert';
    $tmp['table'] = $table;
    $tmp['columns'] = $columns;
    $tmp['options'] = $options;
    $this->sql[] = $tmp;
    return $this;
  }
  
  /**
   * Starts to form the SQL query of UPDATE type.
   * Value of $table should correspond to one of the following types:
   * <ul>
   * <li>A string or SQLExpression instance: <code>$sql->update('tb1 AS t1, tb2 AS t2', ['column1' => 1, 'column2' => 2]);</code></li>
   * <li>One-dimensional mixed array: <code>$sql->update(['tb1', 'tb2' => 't2', new SQLExpression('(SELECT 5) AS t3')], ['column1' => 1, 'column2' => 2]);</code></li>
   * </ul>
   * Value of $columns can be one of the following possible variants:
   * <ul>
   * <li>A string or SQLExpression instance: <code>$sql->update('MyTable', 'expire > CURDATE()');</code></li>
   * <li>One-dimensional associative array: <code>$sql->update('MyTable', ['column1' => 'v1', 'column2' => 'v2'])</code></li>
   * </ul>
   *
   * @param mixed $table - the table name.
   * @param mixed $columns - the column metadata.
   * @param array $options - additional information for some DBMS.
   * @return self
   * @access public
   */
  public function update($table, $columns, array $options = null)
  {
    $tmp = [];
    $tmp['type'] = 'update';
    $tmp['table'] = $table;
    $tmp['columns'] = $columns;
    $tmp['options'] = $options;
    $this->sql[] = $tmp;
    return $this;
  }
  
  /**
   * Starts to form the SQL query of DELETE type.
   * Value of $table should correspond to one of the following types:
   * <ul>
   * <li>A string or SQLExpression instance: <code>$sql->delete('tb1 AS t1, tb2 AS t2');</code></li>
   * <li>One-dimensional mixed array: <code>$sql->delete(['tb1', 'tb2' => 't2']);</code></li>
   * </ul>
   *
   * @param mixed $table - the table name.
   * @param array $options - additional information for some DBMS.
   * @return self
   * @access public
   */
  public function delete($table, array $options = null)
  {
    $tmp = [];
    $tmp['type'] = 'delete';
    $tmp['table'] = $table;
    $tmp['options'] = $options;
    $this->sql[] = $tmp;
    return $this;
  }
  
  /**
   * Starts to form the SQL query of SELECT type.
   * Value of $table (or $columns) should correspond to one of the following types:
   * <ul>
   * <li>A string or SQLExpression instance: <code>$sql->select('tb1 AS t1, tb2 AS t2', 'column1, column2');</code></li>
   * <li>One-dimensional mixed array: <code>$sql->select(['tb1', 'tb2' => 't2'], ['column1', 'column2' => 'foo', new SQLExpression('CONCAT(column1, column2)')]);</code></li>
   * </ul>
   *
   * @param mixed $table - the table name.
   * @param mixed $columns - the column metadata.
   * @param string $distinct - additional select options for some DBMS.
   * @param array $options - additional query information for some DBMS.
   * @return self
   * @access public
   */
  public function select($table, $columns = '*', $distinct = null, array $options = null)
  {
    if ($table === null) return $this;
    $tmp = [];
    $tmp['type'] = 'select';
    $tmp['table'] = $table;
    $tmp['distinct'] = $distinct;
    $tmp['columns'] = $columns;
    $tmp['options'] = $options;
    $this->sql[] = $tmp;
    return $this;
  }
  
  /**
   * Applies JOIN clause to the current SQL query.
   *
   * @param mixed $table - the table name(s).
   * @param mixed $conditions - the JOIN clause metadata.
   * @param string $type - the JOIN clause type.
   * @return self
   * @access public
   */
  public function join($table, $conditions, $type = 'INNER')
  {
    if ($table === null) return $this;
    $tmp = array_pop($this->sql);
    $tmp['join'][] = ['type' => $type ?: 'INNER', 'table' => $table, 'conditions' => $conditions];
    $this->sql[] = $tmp;
    return $this;
  }
  
  /**
   * Applies WHERE clause to the current SQL query.
   *
   * @param mixed $conditions - the WHERE clause metadata.
   * @return self
   * @access public
   */
  public function where($conditions, $conjunction = 'AND')
  {
    if ($conditions === null) return $this;
    $tmp = array_pop($this->sql);
    $tmp['where'][] = ['conditions' => $conditions, 'conjunction' => $conjunction ?: 'AND'];
    $this->sql[] = $tmp;
    return $this;
  }
  
  /**
   * Applies GROUP clause to the current SQL query.
   *
   * @param mixed $group - the GROUP clause metadata.
   * @return self
   * @access public
   */
  public function group($group)
  {
    if ($group === null) return $this;
    $tmp = array_pop($this->sql);
    $tmp['group'][] = $group;
    $this->sql[] = $tmp;
    return $this;
  }
  
  /**
   * Applies HAVING clause to the current SQL query.
   *
   * @param mixed $conditions - the HAVING clause metadata.
   * @return self
   * @access public
   */
  public function having($conditions, $conjunction = 'AND')
  {
    if ($conditions === null) return $this;
    $tmp = array_pop($this->sql);
    $tmp['having'][] = ['conditions' => $conditions, 'conjunction' => $conjunction ?: 'AND'];
    $this->sql[] = $tmp;
    return $this;
  }
  
  /**
   * Applies ORDER clause to the current SQL query.
   *
   * @param mixed $order - the ORDER clause metadata.
   * @return self
   * @access public
   */
  public function order($order)
  {
    if ($order === null) return $this;
    $tmp = array_pop($this->sql);
    $tmp['order'][] = $order;
    $this->sql[] = $tmp;
    return $this;
  }
  
  /**
   * Applies LIMIT clause to the current SQL query.
   *
   * @param integer $limit - the maximum number of rows.
   * @param integer $offset - the row offset.
   * @return self
   * @access public
   */
  public function limit($limit, $offset = null)
  {
    if ($limit === null && $offset === null) return $this;
    $tmp = array_pop($this->sql);
    if (isset($tmp['limit'])) throw new Core\Exception($this, 'ERR_SQL_3', 'limit');
    $tmp['limit'] = ['limit' => $limit, 'offset' => $offset];
    $this->sql[] = $tmp;
    return $this;
  }
  
  /**
   * Returns completely formed SQL query of the given type.
   *
   * @param mixed $data - a variable in which the data array for the SQL query will be written.
   * @return string
   * @access public
   */
  public function build(&$data = null)
  {
    $data = [];
    $tmp = array_pop($this->sql);
    if ($tmp) switch ($tmp['type'])
    {
      case 'select': return $this->buildSelect($tmp, $data);
      case 'insert': return $this->buildInsert($tmp, $data);
      case 'update': return $this->buildUpdate($tmp, $data);
      case 'delete': return $this->buildDelete($tmp, $data);
    }
  }
  
  /**
   * Returns completed SQL query of INSERT type.
   *
   * @param array $insert - the query data.
   * @param mixed $data - a variable in which the data array for the SQL query will be written.
   * @return string
   * @access protected
   */
  protected function buildInsert(array $insert, &$data)
  {
    if (empty($insert['table'])) $sql = $insert['sql'];
    else $sql = 'INSERT INTO ' . $this->wrap($insert['table'], true) . ' ';
    $res = $this->insertExpression($insert['columns'], $data);
    if (!is_array($res['values'])) $sql .= $res['values'];
    else 
    {
      foreach ($res['values'] as &$values) $values = '(' . implode(', ', $values) . ')';
      $sql .= '(' . implode(', ', $res['columns']) . ') VALUES ' . implode(', ', $res['values']);
    }
    return $sql;
  }
  
  /**
   * Returns completed SQL query of UPDATE type.
   *
   * @param array $update - the query data.
   * @param mixed $data - a variable in which the data array for the SQL query will be written.
   * @return string
   * @access protected
   */
  protected function buildUpdate(array $update, &$data)
  {
    if (empty($update['table'])) $sql = $update['sql'];
    else $sql = 'UPDATE ' . $this->selectExpression($update['table'], true);
    if (!empty($update['join'])) $sql .= $this->buildJoin($update['join'], $data);
    $sql .= ' SET ' . $this->updateExpression($update['columns'], $data);
    if (!empty($update['where'])) $sql .= $this->buildWhere($update['where'], $data);
    if (!empty($update['order'])) $sql .= $this->buildOrder($update['order']);
    if (!empty($update['limit'])) $sql .= $this->buildLimit($update['limit']);
    return $sql;
  }
  
  /**
   * Returns completed SQL query of DELETE type.
   *
   * @param array $delete - the query data.
   * @param mixed $data - a variable in which the data array for the SQL query will be written.
   * @return string
   * @access protected
   */
  protected function buildDelete(array $delete, &$data)
  {
    if (empty($delete['table'])) $sql = $delete['sql'];
    else $sql = 'DELETE FROM ' . $this->selectExpression($delete['table'], true);
    if (!empty($delete['join'])) $sql .= $this->buildJoin($delete['join'], $data);
    if (!empty($delete['where'])) $sql .= $this->buildWhere($delete['where'], $data);
    if (!empty($delete['order'])) $sql .= $this->buildOrder($delete['order']);
    if (!empty($delete['limit'])) $sql .= $this->buildLimit($delete['limit']);
    return $sql;
  }
  
  /**
   * Returns completed SQL query of SELECT type.
   *
   * @param array $select - the query data.
   * @param mixed $data - a variable in which the data array for the SQL query will be written.
   * @return string
   * @access protected
   */
  protected function buildSelect(array $select, &$data)
  {
    if (empty($select['table'])) $sql = $select['sql'];
    else $sql = 'SELECT ' . ltrim($select['distinct'] . ' ') . $this->selectExpression($select['columns']) . ' FROM ' . $this->selectExpression($select['table'], true);
    if (!empty($select['join'])) $sql .= $this->buildJoin($select['join'], $data);
    if (!empty($select['where'])) $sql .= $this->buildWhere($select['where'], $data);
    if (!empty($select['group'])) $sql .= $this->buildGroup($select['group']);
    if (!empty($select['having'])) $sql .= $this->buildHaving($select['having'], $data);
    if (!empty($select['order'])) $sql .= $this->buildOrder($select['order']);
    if (!empty($select['limit'])) $sql .= $this->buildLimit($select['limit']);
    return $sql;
  }
  
  /**
   * Returns JOIN segment of the SQL statement.
   *
   * @param array $info - the array of JOIN data.
   * @param mixed $data - a variable in which the data array for the SQL query will be written.
   * @return string
   * @access protected
   */
  protected function buildJoin(array $info, &$data)
  {
    foreach ($info as &$join) $join = $join['type'] . ' JOIN ' . $this->selectExpression($join['table'], true) . ' ON ' . $this->whereExpression($join['conditions'], $data);
    return ' ' . implode(' ', $info);
  }
  
  /**
   * Returns WHERE segment of the SQL statement.
   *
   * @param array $info - the array of WHERE data.
   * @param mixed $data - a variable in which the data array for the SQL query will be written.
   * @return string
   * @access protected
   */
  protected function buildWhere(array $info, &$data)
  {
    $sql = $this->whereExpression(array_shift($info)['conditions'], $data);
    if (count($info)) 
    {
      $sql = '(' . $sql . ')';
      foreach ($info as $where) $sql .= ' ' . $where['conjunction'] . ' (' . $this->whereExpression($where['conditions'], $data) . ')'; 
    }
    return ' WHERE ' . $sql;
  }
  
  /**
   * Returns GROUP BY segment of the SQL statement.
   *
   * @param array $info - the array of ORDER data.
   * @return string
   * @access protected
   */
  protected function buildGroup(array $info)
  {
    foreach ($info as &$group) $group = $this->selectExpression($group);
    return ' GROUP BY ' . implode(', ', $info);
  }
  
  /**
   * Returns HAVING segment of the SQL statement.
   *
   * @param array $info - the array of HAVING data.
   * @param mixed $data - a variable in which the data array for the SQL query will be written.
   * @return string
   * @access protected
   */
  protected function buildHaving(array $info, &$data)
  {
    return ' HAVING ' . substr($this->buildWhere($info, $data), 7);
  }
  
  /**
   * Returns ORDER BY segment of the SQL statement.
   *
   * @param array $info - the array of ORDER data.
   * @return string
   * @access protected
   */
  protected function buildOrder(array $info)
  {
    foreach ($info as &$order) $order = $this->selectExpression($order, false, true);
    return ' ORDER BY ' . implode(', ', $info);
  }
  
  /**
   * Returns LIMIT clause of the SQL statement.
   *
   * @param array $limit - the LIMIT data.
   * @return string
   * @access protected
   */
  protected function buildLimit(array $limit)
  {
    if ($limit['offset'] === null) return ' LIMIT ' . (int)$limit['limit'];
    if ($limit['limit'] === null) return ' LIMIT ' . (int)$limit['offset'] . ', 18446744073709551610';
    return ' LIMIT ' . (int)$limit['offset'] . ', ' . (int)$limit['limit'];
  }
  
  /**
   * Normalizes the column metadata for the INSERT type SQL query.
   *
   * @param mixed $expression - the column metadata.
   * @param mixed $data - a variable in which the data array for the SQL query will be written.
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
        else $tmp['values'][$i][$j] = $this->addParam($value, $data);
      }
    }
    return $tmp;
  }
  
  /**
   * Normalizes the column metadata for the UPDATE type SQL query.
   *
   * @param mixed $expression - the column metadata.
   * @param mixed $data - a variable in which the data array for the SQL query will be written.
   * @return string - normalized column data for query building.
   * @access protected
   */
  protected function updateExpression($expression, &$data)
  {
    if (!is_array($expression)) return (string)$expression;
    $data = is_array($data) ? $data : [];
    $tmp = [];
    foreach ($expression as $column => $value)
    {
      if (is_numeric($column)) 
      {
        if ($value instanceof self) $tmp[] = '(' . (string)$value . ')';
        else $tmp[] = (string)$value;
      }
      else if ($value instanceof self)  $tmp[] =  $this->wrap($column) . ' = (' . $value . ')';
      else if ($value instanceof SQLExpression) $tmp[] =  $this->wrap($column) . ' = ' . $value;
      else $tmp[] = $this->wrap($column) . ' = ' . $this->addParam($value, $data);
    }
    return implode(', ', $tmp);
  }
  
  /**
   * Normalizes the column metadata for the SELECT type SQL query.
   *
   * @param mixed $expression - the column metadata.
   * @param boolean $isTableName - determines whether $expression is a table name(s).
   * @param boolean $isOrderExpression - determines whether $expression is an order expression or not.
   * @return string - normalized column data for query building.
   * @access protected
   */
  protected function selectExpression($expression, $isTableName = false, $isOrderExpression = false)
  {
    if ($expression == '') return '*';
    if ($expression instanceof self) return '(' . $expression . ')';
    if ($expression instanceof SQLExpression) return (string)$expression;
    if (is_array($expression))
    {
      $tmp = [];
      foreach ($expression as $k => $exp)
      {
        if (is_numeric($k))
        {
          if ($exp instanceof self) $tmp[] = '(' . $exp . ')';
          else if ($exp instanceof SQLExpression) $tmp[] = (string)$exp;
          else $tmp[] = $this->wrap($exp, $isTableName);
        }
        else
        {
          if ($exp instanceof self || $exp instanceof SQLExpression) $exp = ' ' . $exp;
          else $exp = $isOrderExpression ? ' ' . $exp : ' ' . $this->wrap($exp, true);
          $tmp[] = $this->wrap($k, $isTableName) . $exp;
        }
      }
      return implode(', ', $tmp);
    }
    return $this->wrap($expression, $isTableName);
  }
  
  /**
   * Normalizes the column metadata for WHERE clause of the SQL query.
   *
   * @param mixed $expression - the column metadata.
   * @param mixed $data - a variable in which the data array for the SQL query will be written.
   * @param string $conjunction - conjunction SQL keyword of a WHERE clause.
   * @return string - normalized column data for query building.
   * @access protected
   */
  protected function whereExpression($expression, &$data, $conjunction = null)
  {
    if (is_array($expression))
    {
      $tmp = [];
      if ($conjunction) $cnj = strtoupper(trim($conjunction));
      else
      {
        if (empty($expression[0]) || is_array($expression[0])) $cnj = 'AND';
        else
        {
          $cnj = strtoupper(trim($expression[0]));
          if (!in_array($cnj, ['AND', 'OR', 'XOR', '||', '&&'])) $cnj = 'AND';
          else unset($expression[0]);
        }
      }
      foreach ($expression as $key => $operand)
      {
        if (!is_array($operand))
        {
          if ($operand instanceof self) $tmp[] = '(' . $operand . ')';
          else if ($operand instanceof SQLExpression) $tmp[] = $this->wrap($key) . ' = ' . $operand;
          else if (!is_numeric($key)) $tmp[] = $this->wrap($key) . ' = ' . $this->addParam($operand, $data);
          else $tmp[] = (string)$operand;
          continue;
        }
        $operator = isset($operand[0]) ? strtoupper(trim($operand[0])) : null;
        switch ($operator)
        {
          case 'AND':
          case 'OR':
          case 'XOR':
          case '||':
          case '&&':
            unset($operand[0]);
            $value = $this->whereExpression($operand, $data, $operator);
            $tmp[] = ($cnj == 'AND' || $cnj == 'XOR' || $cnj == '&&') && ($operator == 'OR' || $operator == '||') ? '(' . $value . ')' : $value;
            break;
          case '=':
          case '>':
          case '<':
          case '>=':
          case '<=':
          case '<>':
          case '!=':
          case 'LIKE':
          case 'NOT LIKE':
            $column = $operand[1];
            $value = $operand[2];
            if ($value instanceof self) $tmp[] = $this->wrap($column) . ' ' . $operator . ' (' . $value . ')';
            else if ($value instanceof SQLExpression) $tmp[] = $this->wrap($column) . ' ' . $operator . ' ' . $value;
            else $tmp[] = $this->wrap($column) . ' ' . $operator . ' ' . $this->addParam($value, $data);
            break;
          case 'IN':
          case 'NOT IN':
            $column = $operand[1];
            $value = (array)$operand[2];
            if ($this->seq == 0)
            {
              $data = array_merge($data, $value);
              $tmp[] = $this->wrap($column) . ' ' . $operator . ' (' . implode(', ', array_fill(0, count($value), '?')) . ')';
            }
            else
            {
              $t = [];
              foreach ($value as $v) $t[] = $this->addParam($v, $data);
              $tmp[] = $this->wrap($column) . ' ' . $operator . ' (' . implode(', ', $t) . ')';
            }
            break;
          case 'IS':
            $tmp[] = $this->wrap($operand[1]) . ' ' . $operator . ' ' . $operand[2];
            break;
          case 'BETWEEN':
          case 'NOT BETWEEN':
            $tmp[] = $this->wrap($operand[1]) . ' ' . $operator . ' ' . $this->addParam($operand[2], $data) . ' AND ' . $this->addParam($operand[3], $data);
            break;
          default:
            if (count($operand) == 1 && !is_numeric($key)) $tmp[] = $this->wrap($key) . ' = ' . $this->addParam($operand, $data);
            else throw new Core\Exception($this, 'ERR_SQL_5', $operator);
        }
      }
      return implode(' ' . $cnj . ' ', $tmp);
    }
    if ($expression instanceof self) return '(' . $expression . ')';
    return (string)$expression;
  }
  
  /**
   * Adds a parameter value to the SQL statement.
   *
   * @param mixed $value - the value to be added.
   * @param array $data - the SQL statement parameters to which the value will be added.
   * @return string - part of the building SQL string.
   * @access protected
   */
  protected function addParam($value, array &$data)
  {
    if ($this->seq == 0)
    {
      $data[] = $value;
      return '?';
    }
    $param = ':p' . $this->seq++;
    $data[$param] = $value;
    return $param;
  }
}

/**
 * This class is wrapper of any SQL expressions. An instance of this class won't be processed during SQL building.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
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
   * @return string
   * @access public
   */
  public function __toString()
  {
    return $this->sql;
  }
}