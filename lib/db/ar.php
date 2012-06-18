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

class AR
{
  /**
   * Error message templates.
   */
  const ERR_AR_1 = 'None of Aleph\DB\DB instance is created. You should set a global variable with name "db" or pass the instance into constructor of Aleph\DB\AR class.';
  const ERR_AR_2 = 'Column "[{var}]" doesn\'t exist in table "[{var}]".';
  const ERR_AR_3 = 'Column "[{var}]" of table "[{var}]" cannot be NULL.';
  const ERR_AR_4 = 'Column "[{var}]" cannot be an array or object (except for Aleph\DB\SQLExpression instance). It can only be a scalar value.';
  const ERR_AR_5 = 'Maximum length of column "[{var}]" in table "[{var}]" cannot be more than [{var}].';
  const ERR_AR_8 = 'Primary key of a row of table "[{var}]" is not filled yet. You can\'t [{var}] the row.';
  const ERR_AR_9 = 'The row in table "[{var}]" was deleted, and now, you can use this Aleph\DB\AR object only as a read-only object.';
  const ERR_AR_10 = 'Relation "[{var}]" doesn\'t exist in table "[{var}]".';
  
  public static $metaInfoExpire = 0;
  
  protected static $info = array();
  
  protected $db = null;
  protected $table = null;
  protected $columns = array();
  protected $pk = array();
  protected $ai = null;
  protected $assigned = false;
  protected $changed = false;
  protected $deleted = false;
  
  public static function getInstance($table, DB $db = null, $metaInfoExpire = null)
  {
    return foo(new self($table))->init($db, $metaInfoExpire);
  }

  private function __construct($table)
  {
    $this->table = $table;
  }
  
  public function init(DB $db = null, $metaInfoExpire = null)
  {
    if (!$db) 
    {
      $db = \Aleph::get('db');
      if (!$db) throw new Core\Exception($this, 'ERR_AR_1');
    }
    $this->db = $db;
    if (!isset(self::$info[$this->db->getDBName()][$this->table]))
    {
      if ($metaInfoExpire === null) $metaInfoExpire = self::$metaInfoExpire;
      if ($metaInfoExpire !== false)
      {
        $cache = $db->getCache();
        $key = $this->db->getDBName() . $this->table;
        if ($cache->isExpired($key))
        {
          $info = array('table' => $this->db->getTableInfo($this->table), 'columns' => $this->db->getColumnsInfo($this->table));
          $cache->set($key, $info, $metaInfoExpire ?: $cache->getVaultLifeTime(), 'db_ar_meta');
        }
        else $info = $cache->get($key);
      }
      else $info = array('table' => $this->db->getTableInfo($this->table), 'columns' => $this->db->getColumnsInfo($this->table));
      self::$info[$this->db->getDBName()][$this->table] = $info;
    }
    return $this->reset();
  }
  
  public function reset()
  {
    $this->assigned = false;
    $this->changed = false;
    $this->deleted = false;
    $this->pk = $this->columns = array();
    foreach ($this->getInfo('columns') as $column => $data) 
    {
      $this->columns[$column] = $data['default'];
      if ($data['isPrimaryKey']) $this->pk[] = $column;
      if ($data['isAutoIncrement']) $this->ai = $column;
    }
    return $this;
  }
  
  public function getTableName()
  {
    return $this->table;
  }
  
  public function getInfo($entity = null)
  {
    $info = self::$info[$this->db->getDBName()][$this->table];
    return isset($info[$entity]) ? $info[$entity] : $info;
  }
  
  protected function getColumnInfo($column, $entity = null)
  {
    $info = $this->getInfo('columns');
    if (!isset($info[$column])) return false;
    return isset($info[$column][$entity]) ? $info[$column][$entity] : $info[$column];
  }
  
  public function isPrimaryKey($column)
  {
    return $this->getColumnInfo($column, 'isPrimaryKey');
  }
  
  public function isAutoIncrement($column)
  {
    return $this->getColumnInfo($column, 'isAutoIncrement');
  }
  
  public function isNullable($column)
  {
    return $this->getColumnInfo($column, 'isNullable');
  }
  
  public function isUnsigned($column)
  {
    return $this->getColumnInfo($column, 'isUnsigned');
  }
  
  public function getColumnType($column)
  {
    return $this->getColumnInfo($column, 'type');
  }
  
  public function getDefaultValue($column)
  {
    return $this->getColumnInfo($column, 'default');
  }
  
  public function getMaxLength($column)
  {
    return $this->getColumnInfo($column, 'maxLength');
  }
  
  public function getPrecision($column)
  {
    return $this->getColumnInfo($column, 'precision');
  }
  
  public function getSetValues($column)
  {
    return $this->getInfo($column, 'set');
  }
  
  public function getPHPType($column)
  {
    switch ($this->getColumnType($column))
    {
      case 'varchar':
        return 'string';
      case 'int':
      case 'double':
        return 'numeric';
      case 'bit':
        return 'boolean';
    }
    return false;
  }
  
  public function getValues()
  {
    return $this->columns;
  }
  
  public function setValues(array $values, $ignoreNonExistingColumns = true)
  {
    if ($ignoreNonExistingColumns)
    {
      foreach ($values as $column => $value) 
      {
        if (isset($this->columns[$column])) $this->__set($column, $value);
      }
    }
    else
    {
      foreach ($values as $column => $value) $this->__set($column, $value);
    }
    return $this;
  }
  
  public function exp($sql)
  {
    return $this->db->exp($sql);
  }
  
  public function isAssigned()
  {
    return $this->assigned;
  }
  
  public function isChanged()
  {
    return $changed;
  }
  
  public function isDeleted()
  {
    return $deleted;
  }
  
  public function isPrimaryKeyFilled($insert = false)
  {
    foreach ($this->pk as $column)
    {
      if ($insert && $column == $this->ai) continue;
      if ($this->getPHPType($column) == 'numeric' && strlen($this->columns[$column]) == 0) return false;
    }
    return true;
  }
  
  public function __isset($column)
  {
    return array_key_exists($column, $this->columns);
  }
   
  public function __get($column)
  {
    if (!array_key_exists($column, $this->columns)) throw new Core\Exception($this, 'ERR_AR_2', $column, $this->table);
    return $this->columns[$column];
  }
  
  public function __set($column, $value)
  {
    if ($this->deleted) throw new Core\Exception($this, 'ERR_AR_9', $this->table);
    if (!array_key_exists($column, $this->columns)) throw new Core\Exception($this, 'ERR_AR_2', $column, $this->table);
    if ($value == $this->columns[$column]) return;
    if ($value === null && !$this->isNullable($column)) throw new Core\Exception($this, 'ERR_AR_3', $column, $this->table);
    if (!($value instanceof SQLExpression))
    {
      if (is_array($value) || is_object($value)) throw new Core\Exception($this, 'ERR_AR_4', $column);
      if (($ml = $this->getMaxLength($column)) > 0)
      {
        $l = strlen($value);
        if ($l > 0)
        {
          if ($this->getPHPType($column) == 'numeric' && strcmp((string)$value, abs($value)) != 0) $l--;
          if ($l > $ml) throw new Core\Exception($this, 'ERR_AR_5', $column, $this->table, $ml);
        }
      }
    }
    $this->columns[$column] = $value;
    $this->changed = true;
  }
  
  public function assign($where, $order = null)
  {
    if (!is_array($where)) 
    {
      $tmp = array();
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
  
  public function count($where = null)
  {
    return $this->db->column($this->db->sql->select($this->table, $this->db->exp('COUNT(*)'))->where($where)->build($data), $data);
  }
  
  public function select($columns = '*', $where = null, $order = null, $limit = null, $offset = null)
  {
    $sql = $this->db->sql;
    $sql = $sql->select($this->table, $columns)->where($where)->order($order)->limit($limit, $offset)->build($data);
    if ((int)$limit == 1) return $this->db->row($sql, $data);
    return $this->db->rows($sql, $data);
  }
  
  public function __call($method, array $params)
  {
    $info = $this->getInfo('table');
    $info = $info['constraints'];
    if (!isset($info[$method])) throw new Core\Exception($this, 'ERR_AR_10', $method, $this->table);
    $info = $info[$method];
    $sql = $this->db->sql;
    $tmp1 = $tmp2 = array();
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
    $sql->select($this->table, isset($params[0]) ? $params[0] : null);
    $sql->join($info['reference']['table'], $tmp1);
    $sql->where($tmp2);
    $sql->order(isset($params[1]) ? $params[1] : null);
    $limit = isset($params[2]) ? $params[2] : null;
    $sql->limit($limit, isset($params[3]) ? $params[3] : null);
    $sql = $sql->build($data);
    if ((int)$limit == 1) return $this->db->row($sql, $data);
    return $this->db->rows($sql, $data);
  }
  
  public function save()
  {
    if ($this->assigned) return $this->update();
    return $this->insert();
  }
  
  public function insert($sequenceName = null)
  {
    if ($this->deleted) throw new Core\Exception($this, 'ERR_AR_9', $this->table);
    if (!$this->isPrimaryKeyFilled(true)) throw new Core\Exception($this, 'ERR_AR_8', $this->table, 'insert');
    $tmp = array();
    foreach ($this->columns as $column => $value) 
    {
      if ($column == $this->ai && !is_object($value) && strlen($value) == 0) continue;
      if ($this->isNullable($column) && $value === null) continue;
      $tmp[$column] = $value;
    }
    $res = $this->db->insert($this->table, $tmp, $sequenceName);
    $this->changed = false;
    $this->assigned = true;
    if ($this->ai) $this->columns[$this->ai] = $res;
    return $this->db->getAffectedRows();
  }
  
  public function update($where = null)
  {
    if ($this->deleted) throw new Core\Exception($this, 'ERR_AR_9', $this->table);
    if ($where !== null) return $this->db->update($this->table, $this->columns, $where);
    if (!$this->changed) return;
    if (!$this->isPrimaryKeyFilled()) throw new Core\Exception($this, 'ERR_AR_8', $this->table, 'update');
    $this->changed = false;
    return $this->db->update($this->table, $this->columns, $this->getWhereData());
  }
  
  public function delete($where = null)
  {
    if ($this->deleted) throw new Core\Exception($this, 'ERR_AR_9', $this->table);
    if ($where !== null) return $this->db->delete($this->table, $where);
    if (!$this->isPrimaryKeyFilled()) throw new Core\Exception($this, 'ERR_AR_8', $this->table, 'delete');
    $this->deleted = true;
    return $this->db->delete($this->table, $this->getWhereData());
  }
  
  private function getWhereData()
  {
    $where = array();
    foreach ($this->pk as $column) $where[$column] = $this->columns[$column];
    return $where;
  }
}