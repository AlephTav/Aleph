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

class Relation implements \Iterator
{
  /**
   * Error message templates.
   */
  const ERR_RELATION_1 = 'The first argument should be an instance of Aleph\DB\ORM\Model or Aleph\DB\DB';
  
  protected $db = null;
  
  protected $sql = null;
  
  protected $bind = null;

  protected $model = null;
  
  protected $properties = null;
  
  private $asArray = false;
  
  private $where = null;
  
  private $group = null;
  
  private $having = null;
  
  private $order = null;
  
  private $offset = null;
  
  private $limit = null;
  
  private $batch = null;
  
  private $ds = null;
  
  public function __construct($bind, $model, $sql, array $properties = [])
  {
    if ($bind instanceof Model)
    {
      $this->bind = $bind;
      $this->db = $bind->getConnection();
    }
    else
    {
      if (!($bind instanceof DB\DB)) throw new Core\Exception($this, 'ERR_RELATION_1');
      $this->db = $bind;
    }
    $this->sql = $sql;
    $this->model = $model;
    $this->properties = $properties;
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