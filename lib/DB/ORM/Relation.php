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

namespace Aleph\DB\ORM;

use Aleph\Core,
    Aleph\DB,
    Aleph\Utils,
    Aleph\Utils\PHP;

/**
 * Utility class that intended for navigation on related data of a model.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.db.orm
 */
class Relation implements \Iterator
{
  /**
   * Error message templates.
   */
  const ERR_RELATION_1 = 'The first argument should be an instance of Aleph\DB\ORM\Model or Aleph\DB\DB';
  
  /**
   * Instance of the database connection object.
   *
   * @var Aleph\DB\DB $db
   * @access protected
   */
  protected $db = null;
  
  /**
   * SQL statement that represents the related data of a model.
   *
   * @var string $sql
   * @access protected
   */
  protected $sql = null;
  
  /**
   * Instance of a model that bound with related data.
   *
   * @var Aleph\DB\ORM\Model $bind
   * @access protected
   */
  protected $bind = null;

  /**
   * Class name of related model.
   *
   * @var string $model
   * @access protected
   */
  protected $model = null;
  
  /**
   * Array of links between the given model $bind and columns of table(s) in SQL statement $sql.
   *
   * @var array $properties
   * @access protected
   */
  protected $properties = null;
  
  /**
   * If it is TRUE, every row of data will be returned as array on each iteration.
   *
   * @var boolean $asArray
   * @access private
   */
  private $asArray = false;
  
  /**
   * WHERE clause conditions.
   *
   * @var mixed $where
   * @access private
   */
  private $where = null;
  
  /**
   * GROUP BY clause conditions.
   *
   * @var mixed $group
   * @access private
   */
  private $group = null;
  
  /**
   * HAVING clause conditions.
   *
   * @var mixed $having
   * @access private
   */
  private $having = null;
  
  /**
   * ORDER BY clause conditions.
   *
   * @var mixed $order
   * @access private
   */
  private $order = null;
  
  /**
   * Offset part of LIMIT clause.
   *
   * @var mixed $offset
   * @access private
   */
  private $offset = null;
  
  /**
   * Limit part of LIMIT clause.
   *
   * @var mixed $limit
   * @access private
   */
  private $limit = null;
  
  /**
   * If it is TRUE, on each iteration batch of rows will be returned instead of one row per iteration.
   *
   * @var boolean $batch
   * @access private
   */
  private $batch = false;
  
  /**
   * Instance of data reader.
   *
   * @var Aleph\DB\Reader $ds
   * @access private
   */
  private $ds = null;
  
  /**
   * Constructor. Initializes the class properties.
   *
   * @param Aleph\DB\DB|Aleph\DB\ORM\Model $bind - determines a model or database connection object.
   * @param string $model - class name of the related model.
   * @param string $sql - SQL statement that represents the related data of a model.
   * @param array $properties - array of links between the given model $bind and columns of table(s) in SQL statement $sql.
   * @access public
   */
  public function __construct($bind, $model, $sql, array $properties = [])
  {
    if ($bind instanceof Model)
    {
      $this->db = $bind->getConnection();
      $this->bind = $bind;
      $this->properties = $properties;
    }
    else
    {
      if (!($bind instanceof DB\DB)) throw new Core\Exception($this, 'ERR_RELATION_1');
      $this->db = $bind;
    }
    $this->sql = $sql;
    $this->model = $model;
  }
  
  public function rewind() 
  {
    $this->ds = $this->db->query($this->getSQL($tmp), $tmp);
    $this->ds->rewind();
  }
  
  public function valid()
  {
    $flag = $this->ds->valid();
    if (!$flag && $this->ds) $this->reset();
    return $flag;
  }
  
  public function current()
  {
    if ($this->asArray) 
    {
      if (!$this->batch) return $this->ds->current();
      $tmp = []; $n = $this->batch;
      do
      {
        $tmp[] = $this->ds->current();
        $n--;
        if ($n) $this->ds->next();
      }
      while ($this->valid() && $n);
      return $tmp; 
    }
    if (!$this->batch) return (new $this->model)->setValues($this->ds->current());
    $tmp = []; $n = $this->batch;
    do
    {
      $tmp[] = (new $this->model)->setValues($this->ds->current());
      $n--;
      if ($n) $this->ds->next();
    }
    while ($this->valid() && $n);
    return $tmp;
  }
  
  public function key()
  {
    return $this->ds->key();
  }
  
  public function next()
  {
    $this->ds->next();
  }
  
  public function __invoke($limit = null, $offset = null, $asArray = false)
  {
    $this->asArray = $asArray;
    return $this->limit($limit, $offset);
  }
  
  public function slice($limit = null, $offset = null, $asArray = false)
  {
    $this->limit($limit, $offset);
    $rows = $this->db->rows($this->getSQL($tmp), $tmp);
    $this->reset();
    if ($asArray) return $rows;
    $tmp = [];
    foreach ($rows as $row) $tmp[] = (new $this->model)->setValues($row);
    return $tmp;
  }
  
  public function one($asArray = false)
  {
    $res = $this->slice(1, 0, $asArray);
    return isset($res[0]) ? $res[0] : [];
  }
  
  public function all($asArray = false)
  {
    return $this->slice(null, null, $asArray);
  }
  
  public function asArray()
  {
    $this->asArray = true;
    return $this;
  }
  
  public function batch($size)
  {
    if ((int)$size) $this->batch = $size;
    return $this;
  }
  
  public function where($where)
  {
    $this->where = $where;
    return $this;
  }
  
  public function group($group)
  {
    $this->group = $group;
    return $this;
  }
  
  public function having($having)
  {
    $this->having = $having;
    return $this;
  }
  
  public function order($order)
  {
    $this->order = $order;
    return $this;
  }
  
  public function limit($limit, $offset = null)
  {
    $this->offset = $offset;
    $this->limit = $limit;
    return $this;
  }
  
  public function reset()
  {
    $this->where = null;
    $this->group = null;
    $this->having = null;
    $this->order = null;
    $this->offset = null;
    $this->limit = null;
    $this->batch = null;
    $this->asArray = false;
    return $this;
  }
  
  protected function getSQL(&$tmp)
  {
    $sql = $this->db->sql->start($this->sql);
    if ($this->bind)
    {
      $where = [];
      foreach ($this->properties as $property => $column) $where[$column] = $this->bind->__get($property);
      if (isset($this->where)) $where = array_merge($where, $this->where);
    }
    else
    {
      $where = $this->where;
    }
    return $sql->where($where)->group($this->group)->having($this->having)->order($this->order)->limit($this->limit, $this->offset)->build($tmp);
  }
}