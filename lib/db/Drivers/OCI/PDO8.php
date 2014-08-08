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

namespace Aleph\DB\Drivers\OCI;

use Aleph\Core,
    Aleph\Cache,
    Aleph\Net;

/**
 * The class defines the interface for accessing Oracle databases in PHP via OCI8 extension. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.db
 */
class PDO8
{
  /**
   * The connection identifier needed for most other OCI8 operations.
   *
   * @var resource $conn
   * @access protected
   */
  protected $conn = null;
  
  /**
   * Determines whether the autocommit mode is turned off.
   *
   * @var boolean $inTransaction
   * @access protected
   */
  protected $inTransaction = false;
  
  /**
   * Database connection attributes.
   *
   * @var array $attributes
   * @access protected
   */
  protected $attributes = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION];

  /**
   * Constructor. Creates an OCI8 instance to represent a connection to the requested database.
   *
   * @param string $dsn - the Data Source Name (DSN), contains the information required to connect to the database.
   * @param string $username - the user name for the DSN string.
   * @param string $password - the password for the DSN string.
   * @param array $options - the value array of driver-specific connection options.
   * @access public
   */
  public function __construct($dsn, $username = null, $password = null, array $options = null)
  {
    $method = !empty($options['isNewConnection']) ? 'oci_new_connect' : (!empty($options[\PDO::ATTR_PERSISTENT]) ? 'oci_pconnect' : 'oci_connect');
    $this->conn = $method($username, $password, $dsn, isset($options['charset']) ? $options['charset'] : null, isset($options['sessionMode']) ? $options['sessionMode'] : null);
  }
  
  /**
   * Returns all currently available OCI drivers.
   *
   * @return array
   * @access public
   */
  public function getAvailableDrivers()
  {
    return ['OCI8'];
  }
  
  /**
   * Returns the value of a database connection attribute.
   * An unsuccessful call returns null.
   *
   * @param integer $attribute - the attribute identifier.
   * @return mixed
   * @access public
   */
  public function getAttribute($attribute)
  {
    return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null;
  }
  
  /**
   * Sets an attribute on the database handle.
   * The method returns TRUE on success or FALSE on failure.
   *
   * @param integer $attribute - the attribute identifier.
   * @param mixed $value - the attribute value.
   */
  public function setAttribute($attribute, $value)
  {
    if (!isset($this->attributes[$attribute])) return false;
    $this->attributes[$attribute] = $value;
    return true;
  }
  
  /**
   * Places quotes around the input string (if required) and escapes special characters within the input string.
   *
   * @param mixed $value - the string to be quoted.
   * @param integer $type - provides a data type hint.
   */
  public function quote($value, $type = \PDO::PARAM_STR)
  {
    if ($type == \PDO::PARAM_INT || $type == \PDO::PARAM_BOOL) return (int)$value;
    return "'" . str_replace("'", "''", $value) . "'";
  }

  /**
   * Returns an array of error information about the last operation performed by this database handle.
   *
   * @return array
   * @access public
   */
  public function errorInfo()
  {
    $error = oci_error($this->conn);
    return [$error['sqltext'], $error['code'], $error['message'], $error['offset']];
  }
  
  /**
   * Returns the Oracle error number.
   *
   * @return integer.
   * @access public
   */
  public function errorCode()
  {
    return oci_error($this->conn)['code'];
  }
  
  /**
   * Prepares SQL using connection and returns the statement identifier.
   *
   * @param string $statement - this must be a valid SQL statement for the target database server.
   * @param array $options - this array holds one or more (key, value) pairs to set attribute values for the statement handle.
   * @return resource
   * @access public
   */
  public function prepare($statement, array $options = [])
  {
    return new OCI8Statement($this, oci_parse($this->conn, $statement));
  }
  
  /**
   * Returns the last value from a sequence object.
   *
   * @param string $sequenceName - name of the sequence object from which the ID should be returned.
   * @return string
   * @access public
   */
  public function lastInsertId($seqname = null)
  {
    if (!$seqname) return;
    $st = $this->prepare('SELECT "' . $seqname . '".currval FROM dual');
    $st->execute();
    return $st->fetchColumn();
  }
  
  /**
   * Turns off autocommit mode. Returns always TRUE.
   *
   * @return boolean
   * @access public
   */
  public function beginTransaction()
  {
    return $this->inTransaction = true;
  }
  
  /**
   * Checks if a transaction is currently active within the driver.
   *
   * @return boolean
   * @access public
   */
  public function inTransaction()
  {
    return $this->inTransaction;
  }
  
  /**
   * Commits a transaction.
   * Returns TRUE on success or FALSE on failure.
   *
   * @return boolean
   * @access public
   */
  public function commit()
  {
    if (oci_commit($this->conn) === false) return false;
    $this->inTransaction = false; 
    return true;
  }
  
  /**
   * Rolls back a transaction.
   * Returns TRUE on success or FALSE on failure.
   *
   * @return boolean
   * @access public
   */
  public function rollBack()
  {
    if (oci_rollback($this->conn) === false) return false;
    $this->inTransaction = false;
    return true;
  }
}

/**
 * Represents a prepared statement and, after the statement is executed, an associated result set.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.db
 */
class OCI8Statement
{
  /**
   * Error message templates.
   */
  const ERR_OCI8STATEMENT_1 = 'Invalid column index.';
  const ERR_OCI8STATEMENT_2 = 'PDO::FETCH_KEY_PAIR fetch mode requires the result set to contain exactly 2 columns.';

  /**
   * The database connection object.
   *
   * @var ClickBlocks\DB\OCI8 $db
   * @access protected
   */
  protected $db = null;

  /**
   * The identifier of the prepared statement object. 
   *
   * @var resource $st
   * @access protected
   */
  protected $st = null;
  
  /**
   * The default fetch mode.
   *
   * @var array $defaultFetchMode
   * @access protected
   */
  protected $defaultFetchMode = [\PDO::FETCH_BOTH, null, []];

  /**
   * Constructor.
   *
   * @param ClickBlocks\DB\OCI8 $db - the database connection object.
   * @param resource $stdid - the prepared statement identifier.
   * @access public   
   */
  public function __construct(OCI8 $db, $stid)
  {
    $this->st = $stid;
    $this->db = $db;
  }
  
  /**
   * Changes the default fetch mode for a OCI8Statement object.
   *
   * @param integer $style - determines how OCI8 returns the rows.
   * @param mixed $arg - this argument have a different meaning depending on the value of the $style parameter.
   * @param array $args - arguments of custom class constructor when the $style parameter is PDO::FETCH_CLASS.
   * @access public
   */
  public function setFetchMode($style = \PDO::FETCH_BOTH, $arg = null, array $args = [])
  {
    $this->defaultFetchMode = [$style, $arg, $args];
  }
  
  /**
   * Binds a value to a corresponding named or question mark placeholder in the SQL statement that was used to prepare the statement.
   * Returns TRUE on success or FALSE on failure.
   *
   * @param mixed $parameter - the parameter identifier.
   * @param mixed $value - the value to bind to the parameter.
   * @param integer $type - the explicit data type for the parameter using PDO::PARAM_* constants.
   * @return boolean
   * @access public
   */
  public function bindValue($parameter, $value, $type = \PDO::PARAM_STR)
  {
    return oci_bind_by_name($this->st, $parameter, $value, -1, $this->getOCI8Type($type));
  }
  
  /**
   * Binds a PHP variable to a corresponding named placeholder in the SQL statement that was used to prepare the statement.
   * Returns TRUE on success or FALSE on failure.
   *
   * @param mixed $parameter - the parameter identifier.
   * @param mixed $variable - name of the PHP variable to bind to the SQL statement parameter.
   * @param integer $type - the explicit data type for the parameter using PDO::PARAM_* constants.
   * @param integer $length - the length of the data type. To indicate that a parameter is an OUT parameter from a stored procedure, you must explicitly set the length.
   * @param mixed $options - the driver specific options.
   * @return boolean
   * @access public
   */
  public function bindParam($parameter, &$variable, $type = \PDO::PARAM_STR, $length = -1, $options = null)
  {
    return oci_bind_by_name($this->st, $parameter, $variable, $length, $this->getOCI8Type($type));
  }
  
  /**
   * Execute the prepared statement.
   * Returns TRUE on success or FALSE on failure.
   *
   * @param array $parameters - an array of values with as many elements as there are bound parameters in the SQL statement being executed.
   * @return boolean
   * @access public
   */
  public function execute(array $parameters = null)
  {
    if ($parameters) foreach ($parameters as $k => $v) $this->bindValue($k, $v);
    $errMode = $this->db->getAttribute(\PDO::ATTR_ERRMODE);
    if ($errMode != \PDO::ERRMODE_WARNING)
    {
      $enabled = \Aleph::isErrorHandlingEnabled();
      $level = \Aleph::errorHandling(false, E_ALL & ~E_WARNING);
    }
    $res = oci_execute($this->st, $this->db->inTransaction() ? OCI_NO_AUTO_COMMIT : OCI_COMMIT_ON_SUCCESS);
    if ($errMode != \PDO::ERRMODE_WARNING) \Aleph::errorHandling($enabled, $level);
    if ($res === false && $errMode == \PDO::ERRMODE_EXCEPTION) throw new \Exception(oci_error($this->st)['message']);
    return $res;
  }
  
  /**
   * Returns the number of rows affected by the last DELETE, INSERT, or UPDATE statement executed by the corresponding OCI8Statement object.
   *
   * @return integer
   * @access public
   */
  public function rowCount()
  {
    return oci_num_rows($this->st);
  }
  
  /**
   * Fetches multiple rows from a query into a two-dimensional array.
   *
   * @param integer $style - determines how OCI8 returns the rows.
   * @param mixed $arg - this argument have a different meaning depending on the value of the $style parameter.
   * @param array $args - arguments of custom class constructor when the $style parameter is PDO::FETCH_CLASS.
   * @access public
   */
  public function fetchAll($style = null, $arg = null, array $args = [])
  {
    if ($style === null) list($style, $arg, $args) = $this->defaultFetchMode;
    if (($style & \PDO::FETCH_COLUMN) == \PDO::FETCH_COLUMN || $style == \PDO::FETCH_FUNC || $style == \PDO::FETCH_KEY_PAIR) $s = OCI_NUM;
    else if (($style & \PDO::FETCH_CLASS) == \PDO::FETCH_CLASS) $s = OCI_ASSOC;
    else if (($style & 7) == \PDO::FETCH_BOTH) $s = OCI_BOTH;
    else if (($style & 7) == \PDO::FETCH_ASSOC) $s = OCI_ASSOC;
    else if (($style & 7) == \PDO::FETCH_NUM) $s = OCI_NUM;
    else $s = OCI_BOTH;
    if (oci_fetch_all($this->st, $rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + $s) === false) return false;
    if ($style == \PDO::FETCH_FUNC)
    {
      $tmp = [];
      foreach ($rows as $row) $tmp[] = call_user_func_array($arg, $row);
      $rows = $tmp;
    }
    else if ($style == \PDO::FETCH_KEY_PAIR)
    {
      $tmp = [];
      if (count($rows) && count($rows[0]) != 2) throw new Core\Exception($this, 'ERR_OCI8STATEMENT_2');
      foreach ($rows as $row) $tmp[$row[0]] = $row[1];
      $rows = $tmp;
    }
    else if (($style & \PDO::FETCH_COLUMN) == \PDO::FETCH_COLUMN)
    {
      $arg = (int)$arg; $tmp = [];
      if (($style & \PDO::FETCH_UNIQUE) == \PDO::FETCH_UNIQUE)
      {
        foreach ($rows as $row) $tmp[$row[$arg]] = $row[$arg];
      }
      else if (($style & \PDO::FETCH_GROUP) == \PDO::FETCH_GROUP)
      {
        if (count($rows))
        {
          $row = $rows[0]; $argn = count($row);
          if ($argn < 2) throw new Core\Exception($this, 'ERR_OCI8STATEMENT_1');
          $argn = $arg == $argn - 1 ? 0 : $arg + 1;
          foreach ($rows as $row) $tmp[$row[$arg]][] = $row[$argn];
        }
      }
      else
      {
        foreach ($rows as $row) $tmp[] = $row[$arg];
      }
      $rows = $tmp;
    }
    else if (($style & \PDO::FETCH_CLASS) == \PDO::FETCH_CLASS)
    {
      $tmp = [];
      $ref = new \ReflectionClass($arg);
      foreach ($rows as $row) 
      {
        $class = $ref->newInstanceArgs($args);
        foreach ($row as $k => $v)
        {
          if ($ref->hasProperty($k))
          {
            $prop = $ref->getProperty($k);
            $prop->setAccessible(true);
            $prop->setValue($class, $v);
          }
          else
          {
            $class->{$k} = $v;
          }
        }
        $tmp[] = $class;
      }
      $rows = $tmp;
    }
    return $rows;
  }
  
  /**
   * Fetches a row from a result set associated with a OCI8Statement object.
   *
   * @param integer $style - determines how OCI8 returns the row.
   * @return mixed
   * @access public
   */
  public function fetch($style = null)
  {
    if ($style === null) $style = $this->defaultFetchMode[0];
    if (($style & \PDO::FETCH_OBJ) == \PDO::FETCH_OBJ) return oci_fetch_object($this->st);
    if (($style & 7) == \PDO::FETCH_BOTH) $style = OCI_BOTH;
    else if (($style & 7) == \PDO::FETCH_ASSOC) $style = OCI_ASSOC;
    else if (($style & 7) == \PDO::FETCH_NUM) $style = OCI_NUM;
    else $style = OCI_BOTH;
    return oci_fetch_array($this->st, $style + OCI_RETURN_NULLS);
  }
  
  /**
   * Returns a single column from the next row of a result set or FALSE if there are no more rows.
   *
   * @param integer $column - 0-indexed number of the column you wish to retrieve from the row.
   * @return string
   * @access public
   */
  public function fetchColumn($column = 0)
  {
    $row = oci_fetch_row($this->st);
    if ($row === false) return false;
    return array_key_exists($column, $row) ? $row[$column] : array_shift($row);
  }
  
  /**
   * Converts the given PDO data type to OCI data type.
   *
   * @param integer $pdoType - the PDO data type identifier.
   * @return integer
   * @access protected
   */
  protected function getOCI8Type($pdoType)
  {
    switch ($pdoType)
    {
      case \PDO::PARAM_INT:
      case \PDO::PARAM_BOOL:
        return OCI_B_INT;
      default:
        return SQLT_CHR;
    }
  }
}