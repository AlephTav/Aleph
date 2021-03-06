<?php
/**
 * Copyright (c) 2013 - 2015 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2015 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\DB\Sync;

/**
 * Base class for all classes reading database structure.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.db.sync
 * @abstract
 */
abstract class DBReader implements IReader
{
  /**
   * Read database structure. 
   *
   * @var array $info - you can find format of this array in file /lib/db/synchronizer/structure_db.txt
   * @access protected
   */
  protected $info = null;
  
  /**
   * Instance of Aleph\DB\Sync\DBCore class.
   *
   * @var Aleph\DB\Sync\DBCore $db
   * @access protected
   */
  protected $db =  null;
  
  /**
   * Regular expression for detecting the tables, containing synchronizing data.
   *
   * @var string $infoTablesPattern
   * @access protected
   */
  protected $infoTablesPattern = null;
  
  /**
   * Constructor.
   *
   * @param Aleph\DB\Sync\DBCore $db
   * @access public
   */
  public function __construct(DBCore $db)
  {
    $this->db = $db;
  }
  
  /**
   * Sets the regular expression for the names of the tables, containing synchronizing data.
   *
   * @param string $pattern - the regular expression.
   * @access public
   */
  public function setInfoTables($pattern)
  {
    $this->infoTablesPattern = $pattern;
  }
  
  /**
   * Returns the regular expression for detecting the tables, containing synchronizing data.
   * 
   * @return string
   * @access public
   */
  public function getInfoTables()
  {
    return $this->infoTablesPattern;
  }
  
  /**
   * Resets the received data of the database structure. 
   * Repetitive call of the "read" method will allow to receive up-to-date information concerning database structure.
   *
   * @return self
   * @access public
   */
  public function reset()
  {
    $this->info = null;
    return $this;
  }
  
  /**
   * Executes specified SQL query and returns result of its execution. 
   *
   * @param PDO $pdo
   * @param string $type - type of the query.
   * @param array $params - parameters of the query.
   * @return mixed
   * @access protected
   */
  protected function getData(\PDO $pdo, $type, array $params = null)
  {
    $st = $pdo->prepare($this->db->getSQL('info', $type, $params));
    $st->execute();
    return $st;
  }
}