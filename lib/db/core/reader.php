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

/**
 * This class implements an iteration of rows from a query result set.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.db
 */
class Reader implements \Countable, \Iterator
{
  /**
   * An instance of \PDOStatement.
   *
   * @var \PDOStatement $st
   * @access protected
   */
  protected $st = null;
  
  /**
   * The current row of the rowset.
   *
   * @var array $row
   * @access protected
   */
  protected $row = null;
  
  /**
   * The index of the current row.
   *
   * @var integer $index
   * @access protected
   */
  protected $index = null;

  /**
   * Class constructor.
   *
   * @param PDOStatement $statement
   * @param integer $mode - fetch mode for this SQL statement.
   * @param mixed $arg - this argument have a different meaning depending on the value of the $mode parameter.
   * @param array $ctorargs - arguments of custom class constructor when the $mode parameter is PDO::FETCH_CLASS.
   * @access public
   */
  public function __construct(\PDOStatement $statement, $mode = \PDO::FETCH_ASSOC, $arg = null, array $ctorargs = null)
  {
    $this->st = $statement;
    $this->setFetchMode($mode, $arg, $ctorargs);
  }
  
  /**
   * Returns SQL statement for more low-level operating.
   *
   * @return \PDOStatement
   * @access public
   */
  public function getStatement()
  {
    return $this->st;
  }
  
  /**
   * Set the default fetch mode for this statement.
   *
   * @param integer $mode - the fetch mode should be one of the PDO::FETCH_* constants.
   * @param mixed $arg - this argument have a different meaning depending on the value of the $mode parameter.
   * @param array $ctorargs - arguments of custom class constructor when the $mode parameter is PDO::FETCH_CLASS.
   * @access public
   */
  public function setFetchMode($mode = \PDO::FETCH_ASSOC, $arg = null, array $ctorargs = null)
  {
    if ($arg === null) $this->st->setFetchMode($mode);
    else
    {
      if ($ctorargs === null) $this->st->setFetchMode($mode, $arg);
      else $this->st->setFetchMode($mode, $arg, $ctorargs);
    }
  }
  
  /**
   * Returns the number of rows in the result set.
   *
   * @return integer
   * @access public
   */
  public function count()
  {
    return $this->st->rowCount();
  }

  /**
   * Resets the iterator to the initial state.
   *
   * @access public
   */
  public function rewind() 
  {
    if ($this->index !== null) $this->st->execute();
    $this->row = $this->st->fetch();
    $this->index = 0;
  }
  
  /**
   * Returns the index of the current row.
   *
   * @return integer
   * @access public
   */
  public function key()
  {
    return $this->index;
  }
  
  /**
   * Moves the internal pointer to the next row.
   *
   * @access public
   */
  public function next()
  {
    $this->row = $this->st->fetch();
    $this->index++;
  }
  
  /**
   * Returns the current row.
   *
   * @return mixed
   * @access public
   */
  public function current()
  {
    return $this->row;
  }
  
  /**
   * Returns whether there is a row of data at the current position.
   *
   * @return boolean
   * @access public
   */
  public function valid()
  {
    return $this->row !== false;
  }
}