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
 * Main class for all database operations.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.db.sync
 * @abstract
 */
abstract class DBCore
{
  /**
   * Error message templates.
   */
  const ERR_DB_1 = 'DSN is empty. You should set DSN to be able to connect to database.';
  const ERR_DB_2 = 'DSN is wrong.';
  const ERR_DB_3 = 'Unknown DBMS driver.';
  
  /**
   * Database connection parameters.
   *
   * @var array $params
   * @access protected
   */
  protected $params = array();
  
  /**
   * Array of SQL query templates for different database operations.
   *
   * @var array $sql
   * @access protected
   */
  protected $sql = array();
  
  protected $engines = ['mysql' => 'MySQL',
                        'mysqli' => 'MySQL',
                        'pgsql' => 'PostgreSQL',
                        'sqlite' => 'SQLite',
                        'sqlite2' => 'SQLite',
                        'mssql' => 'MSSQL',
                        'dblib' => 'MSSQL',
                        'sqlsrv' => 'MSSQL',
                        'oci' => 'OCI',
                        'oci8' => 'OCI'];
  
  /**
   * Returns a class instance for database interaction based on the type of DBMS.
   *
   * @param string $dsn - the database connection DSN.
   * @param string $username - the username of establishing database connection.
   * @param string $password - the password of establishing database connection.
   * @param array $options - the options of establishing database connection.
   * @return Aleph\DB\Sync\DBCore
   * @access public
   * @static
   */
  public static function getInstance($dsn, $username = null, $password = null, array $options = null)
  {
    if ($dsn == '') throw new Core\Exception(__CLASS__, 'ERR_DB_1');
    $tmp = array();
    do
    {
      $dsn = get_cfg_var('pdo.dsn.' . $dsn) ?: $dsn;
      $tmp['dsn'] = $dsn;
      $dsn = explode(':', $dsn);
      $tmp['driver'] = strtolower($dsn[0]);
      if ($tmp['driver'] == 'uri') 
      {
        unset($dsn[0]);
        unset($dsn[1]);
        $dsn[2] = ltrim($dsn[2], '/');
        $dsn = file_get_contents(implode(':', $dsn));
      }
    }
    while ($tmp['driver'] == 'uri');
    if (empty($dsn[1])) throw new Core\Exception(__CLASS__, 'ERR_DB_2');
    $dsn = explode(';', $dsn[1]);
    foreach ($dsn as $v)
    {
      $v = explode('=', $v);
      $tmp[strtolower(trim($v[0]))] = trim($v[1]);
    }
    $tmp['username'] = $username;
    $tmp['password'] = $password;
    $tmp['options'] = $options;
    switch ($tmp['driver'])
    {
      case 'mysql': return new MySQLCore($tmp);
    }
    throw new Core\Exception(__CLASS__, 'ERR_DB_3');
  }
  
  /**
   * Constructor.
   *
   * @param array $params - database connection parameters.
   * @access private
   */
  private function __construct(array $params)
  {
    $this->params = $params;
  }
  
  /**
   * Returns the database connection parameters.
   *
   * @return array
   * @access public
   */
  public function getParameters()
  {
    return $this->params;
  }
  
  /**
   * Returns PDO object of relevant current database session.
   *
   * @return PDO
   * @access public
   */
  public function getPDO()
  {
    $pdo = new \PDO($this->params['dsn'], $this->params['username'], $this->params['password'], $this->params['options']);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    return $pdo;
  }
  
  /**
   * Returns SQL query.
   *
   * @param string $class - SQL query class.
   * @param string $type - SQL query type.
   * @param array $params - parameters of SQL query.
   * @return string
   * @access public
   */
  public function getSQL($class, $type, array $params = null)
  {
    if (!isset($this->sql[$class][$type])) return false;
    $sql = $this->sql[$class][$type];
    if ($params) $sql = strtr($sql, $params);
    return $sql;
  }
  
  /**
   * Returns DBReader class for the current DBMS.
   *
   * @return DBReader
   * @access public
   */
  public function getReader()
  {
    $class = __namespace__ . '\\' . $this->engines[$this->params['driver']] . '\Reader';
    return new $class($this);
  }
  
  /**
   * Returns DBWriter class for the current DBMS.
   *
   * @return DBWriter
   * @access public
   */
  public function getWriter()
  {
    $class = __namespace__ . '\\' . $this->engines[$this->params['driver']] . '\Writer';
    return new $class($this);
  }
  
  /**
   * Quotes a column name or table name for use in queries.
   *
   * @param string $name - column or table name.
   * @param boolean $isColumnName
   * @return string
   * @access public
   * @abstract
   */
  abstract public function wrap($name, $isColumnName = true);
  
  /**
   * Quotes a string value for use in queries.
   *
   * @param string $value
   * @param boolean $isLike - determines whether the quoting value is used in LIKE clause.
   * @return string
   * @access public
   * @abstract
   */
  abstract public function quote($value, $isLike = false);
}