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

use Aleph\Core,
    Aleph\Cache,
    Aleph\Net;

/**
 * The class defines the interface for accessing Oracle databases in PHP via OCI8 extension. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.db
 */
class OCI8
{
  /**
   * The connection identifier needed for most other OCI8 operations.
   *
   * @var resource $conn
   * @access protected
   */
  protected $conn = null;

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
   *
   * @param integer $attribute - the attribute identifier.
   * @return mixed
   * @access public
   */
  public function getAttribute($attribute)
  {
  
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
    return new OCI8Statement(oci_parse($this->conn, $statement));
  }
}

/**
 * Represents a prepared statement and, after the statement is executed, an associated result set.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.db
 */
class OCI8Statement
{
  protected $st = null;

  public function __construct($stid)
  {
    $this->st = $stid;
  }
  
  public function bindValue($parameter, $value, $type = \PDO::PARAM_STR)
  {
    return oci_bind_by_name($this->conn, $parameter, $value, -1, $this->getOCI8Type($type));
  }
  
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