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
   * This SQL query should not have WHERE, GROUP BY, HAVING, ORDER BY and LIMIT clauses.
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
  
  /**
   * Sets the internal pointer to the first row of the dataset.
   *
   * @access public
   */
  public function rewind() 
  {
    $this->ds = $this->db->query($this->getSQL($tmp), $tmp);
    $this->ds->rewind();
  }
  
  /**
   * Returns TRUE if the dataset is empty or current position of the internal pointer is valid.
   * Otherwise, it returns FALSE.
   *
   * @return boolean
   * @access public
   */
  public function valid()
  {
    $flag = $this->ds->valid();
    if (!$flag && $this->ds) $this->reset();
    return $flag;
  }
  
  /**
   * Returns current row or batch of rows of the dataset.
   *
   * @return array|Aleph\DB\Model
   * @access public
   */
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
  
  /**
   * Returns the key of the current row.
   *
   * @return integer
   * @access public
   */
  public function key()
  {
    return $this->ds->key();
  }
  
  /**
   * Moves the current position to the next row of the dataset.
   *
   * @access public
   */
  public function next()
  {
    $this->ds->next();
  }
  
  /**
   * Equivalent of call of two methods asArray() and limit().
   *
   * @param integer $limit - the limit part of the LIMIT clause.
   * @param integer $offset - the offset part of the LIMIT clause.
   * @param boolean $asArray - determines whether the each row should be presented as array or as model instance.
   * @return self
   * @access public
   */
  public function __invoke($limit = null, $offset = null, $asArray = false)
  {
    $this->asArray = $asArray;
    return $this->limit($limit, $offset);
  }
  
  /**
   * Returns dataset rows or part of dataset.
   *
   * @param integer $limit - the limit part of the LIMIT clause.
   * @param integer $offset - the offset part of the LIMIT clause.
   * @param boolean $asArray - determines whether each row should be presented as array or as model instance.
   * @return array
   * @access public
   */
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
  
  /**
   * Returns the first row of the dataset.
   * If $asArray is TRUE and the dataset is empty, the method returns NULL. 
   *
   * @param boolean $asArray - determines whether the row should be presented as array or as model instance.
   * @return array|Aleph\DB\Model
   */
  public function one($asArray = false)
  {
    $res = $this->slice(1, 0, $asArray);
    return isset($res[0]) ? $res[0] : ($asArray ? [] : null);
  }
  
  /**
   * Returns all rows of the dataset.
   *
   * @param boolean $asArray - determines whether each row should be presented as array or as model instance.
   * @return array
   * @access public
   */
  public function all($asArray = false)
  {
    return $this->slice(null, null, $asArray);
  }
  
  /**
   * Sets representation of each row of the dataset to an array ($flag is TRUE) or model instance ($flag is FALSE).
   *
   * @param boolean $flag - determines whether each row of the dataset should be presented as array or as model instance.
   * @return self
   * @access public
   */
  public function asArray($flag = true)
  {
    $this->asArray = (bool)$flag;
    return $this;
  }
  
  /**
   * Sets fetching rows in batches.
   *
   * @param integer|boolean - the number of records to be fetched in each batch. If it is FALSE, the batch operation will not applied.
   * @return self
   * @access public
   */
  public function batch($size)
  {
    $this->batch = (int)$size == 0 ? false : $size;
    return $this;
  }
  
  /**
   * Sets WHERE part of the query.
   *
   * @param mixed $where - the WHERE conditions.
   * @return self
   * @access public
   */
  public function where($where)
  {
    $this->where = $where;
    return $this;
  }
  
  /**
   * Sets GROUP BY part of the query.
   *
   * @param mixed $group - the GROUP BY conditions.
   * @return self
   * @access public
   */
  public function group($group)
  {
    $this->group = $group;
    return $this;
  }
  
  /**
   * Sets HAVING part of the query.
   *
   * @param mixed $having - the HAVING conditions.
   * @return self
   * @access public
   */
  public function having($having)
  {
    $this->having = $having;
    return $this;
  }
  
  /**
   * Sets ORDER BY part of the query.
   *
   * @param mixed $order - the ORDER BY conditions.
   * @return self
   * @access public
   */
  public function order($order)
  {
    $this->order = $order;
    return $this;
  }
  
  /**
   * Sets LIMIT part of the query.
   *
   * @param integer $limit - the limit part of the LIMIT clause.
   * @param integer $offset - the offset part of the LIMIT clause.
   * @return self
   * @access public
   */
  public function limit($limit, $offset = null)
  {
    $this->offset = $offset;
    $this->limit = $limit;
    return $this;
  }
  
  /**
   * Resets all previously set conditions.
   *
   * @return self
   * @access public
   */
  public function reset()
  {
    $this->where = null;
    $this->group = null;
    $this->having = null;
    $this->order = null;
    $this->offset = null;
    $this->limit = null;
    $this->batch = false;
    $this->asArray = false;
    return $this;
  }
  
  /**
   * Builds the SQL query to iterate the dataset.
   *
   * @param mixed $tmp - a variable in which the query parameters will be written.
   * @return string
   * @access protected
   */
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