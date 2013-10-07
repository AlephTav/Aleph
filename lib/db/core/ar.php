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
 * AR is the base class that implements the active record design pattern.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.db
 */
class AR
{
  /**
   * Error message templates.
   */
  const ERR_AR_1 = 'The first argument (table name) of method Aleph\DB\AR::getInstance is missed.';
  const ERR_AR_2 = 'The second argument of method Aleph\DB\AR::getInstance is wrong. It should be an instance of Aleph\DB\DB.';
  const ERR_AR_3 = 'None of Aleph\DB\DB instance was passed to the constructor of Aleph\DB\AR class. You should set a global variable with name "db" or pass the instance to the constructor.';
  const ERR_AR_4 = 'Column "[{var}]" doesn\'t exist in table "[{var}]".';
  const ERR_AR_5 = 'Column "[{var}]" of table "[{var}]" cannot be NULL.';
  const ERR_AR_6 = 'Column "[{var}]" of table "[{var}]" cannot be an array or object (except for Aleph\DB\SQLExpression instance). It can only be a scalar value.';
  const ERR_AR_7 = 'Maximum length of column "[{var}]" in table "[{var}]" cannot be more than [{var}].';
  const ERR_AR_8 = 'Enumeration type column "[{var}]" of table "[{var}]" has invalid value.';
  const ERR_AR_9 = 'Primary key of a row of table "[{var}]" is not filled yet. You can\'t [{var}] the row.';
  const ERR_AR_10 = 'The row in table "[{var}]" was deleted, and now, you can use this Aleph\DB\AR object only as a read-only object.';
  const ERR_AR_11 = 'Relation "[{var}]" doesn\'t exist in table "[{var}]".';
  
  /**
   * The default database connection object for all active record classes.
   *
   * @var Aleph\DB\DB $connection
   * @access public
   * @static
   */
  public static $connection = null;
  
  /**
   * Lifetime (in seconds) of the table metadata cache.
   * This property contains the default value of the cache lifetime for all instances of AR class.
   * Caching metadata does not occur if $cacheExpire equals FALSE or 0.
   * If $cacheExpire less than 0 the cache lifetime will equal the database cache vault lifetime.
   *
   * @var integer | boolean $cacheExpire
   * @access public
   * @static
   */
  public static $cacheExpire = -1;
  
  /**
   * Cache group of all cached table metadata.
   *
   * @var string $cacheGroup
   * @access public
   */
  public static $cacheGroup = '--ar';
  
  /**
   * Contains table metadata for all tables of all databases.
   *
   * @var array $info
   * @access protected
   * @static
   */
  protected static $info = [];
  
  /**
   * The instance of the database connection class.
   *
   * @var Aleph\DB\DB $db
   * @access protected
   */
  protected $db = null;
  
  /**
   * The databese table name.
   *
   * @var string $table
   * @access protected
   */
  protected $table = null;
  
  /**
   * Contains values of the table columns for the current active record.
   *
   * @var array $columns
   * @access protected
   */
  protected $columns = [];
  
  /**
   * List of the primary key columns.
   *
   * @var array $pk
   * @access protected
   */
  protected $pk = [];
  
  /**
   * Name of the auto-increment column.
   *
   * @var string $ai
   * @acess protected
   */
  protected $ai = null;
  
  /**
   * Determines whether the AR object is initiated from database.
   *
   * @var boolean $assigned
   * @access protected
   */
  protected $assigned = false;
  
  /**
   * Determines whether at least one column value is changed.
   *
   * @var boolean $changed
   * @access protected
   */
  protected $changed = false;
  
  /**
   * Determines whether the current record is deleted.
   *
   * @var boolean $deleted
   * @access protected
   */
  protected $deleted = false;

  /**
   * Constructor. Gets all table metadata and put it into the database cache.
   *
   * @param string $table - the table name
   * @param Aleph\DB\DB $db - the database connection object.
   * @param integer | boolean $cacheExpire - lifetime (in seconds) of the table metadata cache.
   * @access public
   */
  public function __construct(/* $table, DB $db = null, $cacheExpire = null */)
  {
    $args = func_get_args();
    if (empty($args[0])) throw new Core\Exception('Aleph\DB\AR::ERR_AR_1');
    if (isset($args[1]))
    {
      $db = $args[1];
      if (!($db instanceof DB)) throw new Core\Exception('Aleph\DB\AR::ERR_AR_2');
    }
    else
    {
      $db = static::$connection ?: \Aleph::get('db');
      if (!($db instanceof DB)) throw new Core\Exception($this, 'ERR_AR_3');
    }
    if (!$db->isConnected()) $db->connect();
    $table = $args[0]; $dbname = $db->getDBName();
    if (!isset(static::$info[$dbname][$table]))
    {
      $config = \Aleph::getInstance()['ar'];
      if (!empty($args[2])) $cacheExpire = (int)$args[2];
      else 
      {
        if (isset($config['cacheExpire'])) $cacheExpire = (int)$config['cacheExpire'];
        else $cacheExpire = (int)static::$cacheExpire;
      }
      if ($cacheExpire == 0) $info = ['table' => $db->getTableInfo($table), 'columns' => $db->getColumnsInfo($table)];
      else
      {
        $cache = $db->getCache();
        $key = 'ar' . $dbname . $table;
        if (!$cache->isExpired($key)) $info = $cache->get($key);
        else
        {
          $info = ['table' => $db->getTableInfo($table), 'columns' => $db->getColumnsInfo($table)];
          $cache->set($key, $info, $cacheExpire > 0 ? $cacheExpire : $cache->getVaultLifeTime(), isset($config['cacheGroup']) ? $config['cacheGroup'] : static::$cacheGroup);
        }
      }
      static::$info[$dbname][$table] = $info;
    }
    $this->db = $db;
    $this->table = $table;
    $this->reset();
  }
  
  /**
   * Returns the table name.
   *
   * @return string
   * @access public
   */
  public function getTable()
  {
    return $this->table;
  }
  
  /**
   * Returns meta-information about table or its columns.
   * Method returns FALSE if metadata for the given entity doesn't exist.
   *
   * @param string $entity - determines the type of needed metadata ("table" or "columns"). If this parameter is null the method returns all metadata.
   * @return array
   * @access public
   */
  public function getInfo($entity = null)
  {
    $info = static::$info[$this->db->getDBName()][$this->table];
    if ($entity === null) return $info;
    return isset($info[$entity]) ? $info[$entity] : false;
  }
  
  /**
   * Returns meta-information about the table column.
   *
   * @param string $column - the column name.
   * @param string $entity - determines the type of needed metadata. If this parameter is null the method returns all metadata.
   * @return mixed
   * @access public
   */
  public function getColumnInfo($column, $entity = null)
  {
    $info = $this->getInfo('columns');
    if (!isset($info[$column])) return false;
    if ($entity === null) return $info[$column];
    return isset($info[$column][$entity]) ? $info[$column][$entity] : false;
  }
  
  /**
   * Returns TRUE if the given column is a primary key and FALSE otherwise.
   *
   * @param string $column - the column name.
   * @return boolean
   * @access public
   */
  public function isPrimaryKey($column)
  {
    return $this->getColumnInfo($column, 'isPrimaryKey');
  }
  
  /**
   * Returns TRUE if the given column is an autoincrement column and FALSE otherwise.
   *
   * @param string $column - the column name.
   * @return boolean
   * @access public
   */
  public function isAutoincrement($column)
  {
    return $this->getColumnInfo($column, 'isAutoincrement');
  }
  
  /**
   * Returns TRUE if the given column is nullable and FALSE otherwise.
   *
   * @param string $column - the column name.
   * @return boolean
   * @access public
   */
  public function isNullable($column)
  {
    return $this->getColumnInfo($column, 'isNullable');
  }
  
  /**
   * Returns TRUE if the given column is unsigned and FALSE otherwise.
   *
   * @param string $column - the column name.
   * @return boolean
   * @access public
   */
  public function isUnsigned($column)
  {
    return $this->getColumnInfo($column, 'isUnsigned');
  }
  
  /**
   * Returns DBMS data type of the given column.
   *
   * @param string $column - the column name.
   * @return string
   * @access public
   */
  public function getColumnType($column)
  {
    return $this->getColumnInfo($column, 'type');
  }
  
  /**
   * Returns PHP data type of the given column.
   *
   * @param string $column - the column name.
   * @return string
   * @access public
   */
  public function getColumnPHPType($column)
  {
    return $this->getColumnInfo($column, 'phpType');
  }
  
  /**
   * Returns default value of the given column.
   *
   * @param string $column - the column name.
   * @return mixed
   * @access public
   */
  public function getDefaultValue($column)
  {
    return $this->getColumnInfo($column, 'default');
  }
  
  /**
   * Returns maximum length of the given column.
   *
   * @param string $column - the column name.
   * @return integer
   * @access public
   */
  public function getMaxLength($column)
  {
    return $this->getColumnInfo($column, 'maxLength');
  }
  
  /**
   * Returns precision of the given column.
   *
   * @param string $column - the column name.
   * @return integer
   * @access public
   */
  public function getPrecision($column)
  {
    return $this->getColumnInfo($column, 'precision');
  }
  
  /**
   * Returns enumeration values of the given column.
   *
   * @param string $column - the column name.
   * @return array
   * @access public
   */
  public function getEnumeration($column)
  {
    return $this->getColumnInfo($column, 'set');
  }
  
  /**
   * Returns the number of rows affected by the last SQL statement.
   *
   * @return integer
   * @access public
   */
  public function getAffectedRows()
  {
    return $this->affectedRows;
  }
  
  /**
   * Converts the given string to Aleph\DB\SQLExpression object.
   *
   * @param string $sql - the string to convert.
   * @return Aleph\DB\SQLExpression
   * @access public
   */
  public function exp($sql)
  {
    return new SQLExpression($sql);
  }
  
  /**
   * Returns columns' values.
   *
   * @return array
   * @access public
   */
  public function getValues()
  {
    return $this->columns;
  }
  
  /**
   * Sets values of the table columns.
   *
   * @param array $values - new columns' values.
   * @param boolean $ignoreNonExistingColumns - determines whether it is necessary to ignore non-existing columns during setting of the new values.
   * @return self
   * @access public
   */
  public function setValues(array $values, $ignoreNonExistingColumns = true)
  {
    if ($ignoreNonExistingColumns)
    {
      foreach ($values as $column => $value) 
      {
        if ($this->__isset($column)) $this->__set($column, $value);
      }
    }
    else
    {
      foreach ($values as $column => $value) $this->__set($column, $value);
    }
    return $this;
  }
  
  /**
   * Returns TRUE if the active record object was initiated from the database and FALSE otherwise.
   *
   * @return boolean
   * @access public
   */
  public function isAssigned()
  {
    return $this->assigned;
  }
  
  /**
   * Returns TRUE if at least one column value was changed and FALSE otherwise.
   *
   * @return boolean
   * @access public
   */
  public function isChanged()
  {
    return $this->changed;
  }
  
  /**
   * Returns TRUE if the current record was deleted and FALSE otherwise.
   *
   * @return boolean
   * @access public
   */
  public function isDeleted()
  {
    return $this->deleted;
  }
  
  /**
   * Returns TRUE if primary key columns are filled and FALSE otherwise.
   *
   * @param boolean $insert - if TRUE the autoincrement primary key column will be ignored.
   * @return boolean
   * @access public
   */
  public function isPrimaryKeyFilled($insert = false)
  {
    foreach ($this->pk as $column)
    {
      if ($insert && $column == $this->ai) continue;
      $type = $this->getColumnPHPType($column);
      if (($type == 'int' || $type == 'float') && strlen($this->columns[$column]) == 0 && strlen($this->getDefaultValue($column)) == 0) return false;
    }
    return true;
  }
  
  /**
   * Returns TRUE if a column with the given name exists in the table and FALSE if it doesn't.
   *
   * @param string $column - the column name.
   * @return boolean
   * @access public
   */
  public function __isset($column)
  {
    return array_key_exists($column, $this->columns);
  }
  
  /**
   * Returns column value.
   *
   * @param string $column - the column name.
   * @return mixed
   * @access public
   */
  public function __get($column)
  {
    if (!$this->__isset($column)) throw new Core\Exception($this, 'ERR_AR_4', $column, $this->table);
    return $this->columns[$column];
  }
  
  /**
   * Sets column value.
   *
   * @param string $column - the column name.
   * @param mixed $value - the column value.
   * @access public
   */
  public function __set($column, $value)
  {
    if ($this->deleted) throw new Core\Exception($this, 'ERR_AR_10', $this->table);
    if (!$this->__isset($column)) throw new Core\Exception($this, 'ERR_AR_4', $column, $this->table);
    if ($value === null && !$this->isNullable($column)) throw new Core\Exception($this, 'ERR_AR_5', $column, $this->table);
    if (is_array($value) || is_object($value) && !($value instanceof SQLExpression)) throw new Core\Exception($this, 'ERR_AR_6', $column, $this->table);
    if ($value === $this->columns[$column]) return;
    $type = $this->getColumnType($column);
    if ($type == 'enum' && !in_array($value, $this->getEnumeration($column))) throw new Core\Exception($this, 'ERR_AR_8', $column, $this->table);
    if (!($value instanceof SQLExpression))
    {
      if (($ml = $this->getMaxLength($column)) > 0)
      {
        $l = $type == 'bit' ? strlen(decbin($value)) : strlen($value);
        if ($l > 0)
        {
          $type = $this->getColumnPHPType($column);
          if ($type == 'int' || $type == 'float')
          {
            $value = (string)$value;
            if ($value[0] == '+' || $value[0] == '-') $l--;
            if (strpos($value, '.') !== false) $l--;
          }
          if ($l > $ml) throw new Core\Exception($this, 'ERR_AR_7', $column, $this->table, $ml);
        }
      }
    }
    $this->columns[$column] = $value;
    $this->changed = true;
  }
  
  /**
   * Finds record in the table by the given criteria and assign column values to the properties of the active record object.
   *
   * @param mixed $where - the WHERE clause condition.
   * @param mixed $order - the ORDER BY clause condition.
   * @return self
   * @access public
   */
  public function assign($where, $order = null)
  {
    if (!is_array($where)) 
    {
      $tmp = [];
      foreach ($this->pk as $column) $tmp[$column] = $where;
      $where = $tmp;
    }
    $this->columns = $this->db->row($this->db->sql->select($this->table)->where($where)->order($order)->limit(1)->build($tmp), $tmp);
    if ($this->columns)
    {
      $this->assigned = true;
      $this->changed = false;
      $this->deleted = false;
      return $this;
    }
    return $this->reset();
  }
  
  /**
   * Initializes the active record object by array values.
   *
   * @param array $columns - the columns' values.
   * @return self
   * @access public
   */
  public function assignFromArray(array $columns)
  {
    return $this->reset()->setValues($columns, true);
  }
  
  /**
   * Resets the active record object to the initial state.
   *
   * @return self
   * @access public
   */
  public function reset()
  {
    $this->assigned = false;
    $this->changed = false;
    $this->deleted = false;
    $this->pk = $this->columns = [];
    foreach ($this->getInfo('columns') as $column => $data) 
    {
      $this->columns[$column] = $data['default'];
      if ($data['isPrimaryKey']) $this->pk[] = $column;
      if ($data['isAutoincrement']) $this->ai = $column;
    }
    return $this;
  }
  
  /**
   * Updates record in the database table if this record exists or inserts new record otherwise.
   * Method returns numbers of affected rows.
   *
   * @return integer
   * @access public
   */
  public function save()
  {
    if ($this->assigned) return $this->update();
    return $this->insert();
  }
  
  /**
   * Inserts new row to the database table.
   * Method returns the ID of the last inserted row, or the last value from a sequence object, depending on the underlying driver.
   *
   * @param array $options - contains additional parameters (for example, updateOnKeyDuplicate or sequenceName) required by some DBMS.
   * @return integer
   * @access public
   */
  public function insert(array $options = null)
  {
    if ($this->deleted) throw new Core\Exception($this, 'ERR_AR_10', $this->table);
    if (!$this->isPrimaryKeyFilled(true)) throw new Core\Exception($this, 'ERR_AR_9', $this->table, 'insert');
    $tmp = [];
    foreach ($this->columns as $column => $value) 
    {
      if ($column == $this->ai && !is_object($value) && strlen($value) == 0) continue;
      if ($this->isNullable($column) && $value === null) continue;
      $tmp[$column] = $value;
    }
    $res = $this->db->insert($this->table, $tmp, $options);
    $this->changed = false;
    $this->assigned = true;
    if ($this->ai) $this->columns[$this->ai] = $res;
    return $res;
  }
  
  /**
   * Updates existing row (or rows) in the database table.
   * Method returns numbers of affected rows.
   *
   * @param mixed $where - information about conditions of the updating.
   * @return integer
   * @access public
   */
  public function update($where = null)
  {
    if ($this->deleted) throw new Core\Exception($this, 'ERR_AR_10', $this->table);
    if ($where !== null) return $this->db->update($this->table, $this->columns, $where);
    if (!$this->changed) return;
    if (!$this->isPrimaryKeyFilled()) throw new Core\Exception($this, 'ERR_AR_9', $this->table, 'update');
    $this->changed = false;
    return $this->db->update($this->table, $this->columns, $this->getWhereData());
  }
  
  /**
   * Deletes existing row (or rows) from the database table.
   * Method returns numbers of affected rows.
   *
   * @param mixed $where - information about conditions of the deleting.
   * @return integer
   * @access public
   */
  public function delete($where = null)
  {
    if ($this->deleted) throw new Core\Exception($this, 'ERR_AR_10', $this->table);
    if ($where !== null) return $this->db->delete($this->table, $where);
    if (!$this->isPrimaryKeyFilled()) throw new Core\Exception($this, 'ERR_AR_9', $this->table, 'delete');
    $this->deleted = true;
    return $this->db->delete($this->table, $this->getWhereData());
  }
  
  /**
   * Returns total number of rows in the database table.
   *
   * @param mixed $where - the WHERE clause data.
   * @return integer
   * @access public
   */
  public function count($where = null)
  {
    return $this->db->cell($this->db->sql->select($this->table, $this->exp('COUNT(*)'))->where($where)->build($data), $data);
  }
  
  /**
   * Finds rows in the database table according to the given criteria.
   *
   * @param mixed $columns - the column data.
   * @param mixed $where - the WHERE clause data.
   * @param mixed $order - the ORDER BY clause data.
   * @param integer $limit - the maximum number of rows.
   * @param integer $offset - the row offset.
   * @return array
   * @access public
   */
  public function select($columns = '*', $where = null, $order = null, $limit = null, $offset = null)
  {
    $sql = $this->db->sql;
    $sql = $sql->select($this->table, $columns)->where($where)->order($order)->limit($limit, $offset)->build($data);
    if ((int)$limit == 1) return $this->db->row($sql, $data);
    return $this->db->rows($sql, $data);
  }
  
  /**
   * Returns data related with the current database table.
   *
   * @param string | integer $relation - the name of the table constraint (foreign key) or its index number.
   * @param mixed $columns - the column data.
   * @param mixed $order - the ORDER BY clause data.
   * @param integer $limit - the maximum number of rows.
   * @param integer $offset - the row offset.
   * @return array
   * @access public
   */
  public function relation($relation, $columns = '*', $order = null, $limit = null, $offset = null)
  {
    $info = $this->getInfo('table')['constraints'];
    if (isset($info[$relation])) $info = $info[$relation];
    else
    {
      $info = array_values($info);
      if (empty($info[$relation])) throw new Core\Exception($this, 'ERR_AR_11', $relation, $this->table);
      $info = $info[$relation];
    }
    $sql = $this->db->sql;
    $tmp1 = $tmp2 = [];
    foreach ($this->pk as $column)
    {
      $tmp2[$this->table . '.' . $column] = $this->columns[$column];
    }
    foreach ($info['columns'] as $k => $column) 
    {
      $my = $this->table . '.' . $column;
      $ref = $info['reference']['table'] . '.' . $info['reference']['columns'][$k];
      $tmp1[$my] = $sql->exp($sql->wrap($ref));
      $tmp2[$my] = $this->columns[$column];
    }
    $sql = $sql->select($this->table, $columns)
               ->join($info['reference']['table'], $tmp1)
               ->where($tmp2)
               ->order($order)
               ->limit($limit, $offset)
               ->build($data);
    if ((int)$limit == 1) return $this->db->row($sql, $data);
    return $this->db->rows($sql, $data);
  }
  
  /**
   * Returns WHERE condition data for UPDATE or DELETE query.
   *
   * @return array
   * @access private   
   */
  private function getWhereData()
  {
    $where = [];
    foreach ($this->pk as $column) $where[$column] = $this->columns[$column];
    return $where;
  }
}