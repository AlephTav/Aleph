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
 * Base class for database interaction. Provides low-level operation with relation databases via PDO extension.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.db
 */
class DB
{
  /**
   * Error message templates.
   */
  const ERR_DB_1 = 'DSN is empty. You should set DSN to be able to connect to database.';
  const ERR_DB_2 = 'DSN is wrong.';

  /**
   * These constants affect the format of the output data of method "execute".
   */
  const EXEC = 'exec';
  const CELL = 'cell';
  const COLUMN = 'column';
  const ROW = 'row';
  const ROWS = 'rows';
  const COUPLES = 'couples';
  
  /**
   * An instance of PDO class.
   *
   * @var PDO $pdo
   * @access private
   */
  private $pdo = null;
  
  /**
   * An instance of Aleph\DB\SQLBuilder class.
   *
   * @var Aleph\DB\SQLBuilder $sql
   * @access private
   */
  private $sql = null;
  
  /**
   * DSN information of the current connection.
   *
   * @var array $idsn
   * @access protected
   */
  protected $idsn = [];
  
  /**
   * Contains regular expression patterns for query caching.
   *
   * @var array $patterns
   * @access protected
   */
  protected $patterns = [];
  
  /**
   * An instance of Aleph\Cache\Cache class.
   *
   * @var Aleph\Cache\Cache $cache
   * @access protected
   */
  protected $cache = null;
  
  /**
   * Contains a number of affected rows as the result of the last operation.
   *
   * @var integer $affectedRows
   * @access protected
   */
  protected $affectedRows = null;
  
  /**
   * The mapping between PDO drivers and database engines.
   *
   * @var array $engines
   * @access protected
   */
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
   * if this variable is TRUE then each query execution will be logged, otherwise won't.   
   *
   * @var boolean $logging
   * @access public
   */
  public $logging = false;
  
  /**
   * Default query cache lifetime in seconds.
   * Caching queries does not occur if $cacheExpire equals FALSE or 0.
   * if $cacheExpire less than 0 the cache lifetime will equal the database cache vault lifetime.
   *
   * @var integer $cacheExpire
   * @access public
   */
  public $cacheExpire = 0;
  
  /**
   * Default cache group of all cached query results.
   *
   * @var string $cacheGroup
   * @access public
   */
  public $cacheGroup = '--db';
  
  /**
   * Default charset is used for database connection.
   *
   * @var string $charset
   * @access public
   */
  public $charset = null;
  
  /**
   * DSN for the default connection.
   *
   * @var string $dsn
   * @access public
   */
  public $dsn = null;
  
  /**
   * Username for the default connection.
   *
   * @var string $username
   * @access public
   */
  public $username = null;
  
  /**
   * Password for the default connection.
   *
   * @var string $password
   * @access public
   */
  public $password = null;
  
  /**
   * Options for the default connection.
   *
   * @var array $options
   * @access public
   */
  public $options = [];

  /**
   * Constructor of this class. Allows to set parameters of the default connection.
   *
   * @param string $dsn
   * @param string $username
   * @param string $password
   * @param array $options
   * @access public
   */
  public function __construct($dsn = null, $username = null, $password = null, array $options = null)
  {
    $this->dsn = $dsn;
    $this->username = $username;
    $this->password = $password;
    $this->options = $options;
    $config = \Aleph::getInstance()['db'];
    if (isset($config['logging'])) $this->logging = (bool)$config['logging'];
    if (isset($config['cacheExpire'])) $this->cacheExpire = (int)$config['cacheExpire'];
    if (isset($config['cacheGroup'])) $this->cacheGroup = $config['cacheGroup'];
  }
  
  /**
   * This method is automatically called before serialization process of an object of the class.
   *
   * @return array
   * @access public
   */
  public function __sleep()
  {
    $this->pdo = null;
    return array_keys(get_object_vars($this));
  }
  
  /**
   * This method is automatically called after unserialization process of an object of the class.
   *
   * @access public
   */
  public function __wakeup()
  {
    if (count($this->idsn)) $this->connect($this->idsn['dsn'], $this->idsn['username'], $this->idsn['password'], $this->idsn['options']);
  }
  
  /**
   * Returns values of one of two properties: "pdo" or "sql".
   * Value of property "pdo" is an instance of class \PDO that represents the internal interface between PHP and database layer.
   * Value of property "sql" is an instance of class Aleph\DB\SQLBuilder that provides unified way to construct complex SQL queries.
   *
   * @param string $param - the property name.
   * @return \PDO | Aleph\DB\SQLBuilder
   * @access public
   */
  public function __get($param)
  {
    if ($param == 'pdo')
    {
      if ($this->pdo instanceof \PDO) return $this->pdo;
      $this->connect($this->dsn, $this->username, $this->password, $this->options);
      return $this->pdo;
    }
    if ($param == 'sql')
    {
      if ($this->sql instanceof SQLBuilder) return $this->sql;
      $this->connect($this->dsn, $this->username, $this->password, $this->options);
      return $this->sql;
    }
    throw new Core\Exception('Aleph::ERR_GENERAL_3', $param, get_class($this));
  }
  
  /**
   * Returns an instance of caching class.
   *
   * @return Aleph\Cache\Cache
   * @access public
   */
  public function getCache()
  {
    if ($this->cache === null) $this->cache = \Aleph::getInstance()->cache();
    return $this->cache;
  }
  
  /**
   * Sets an instance of caching class.
   *
   * @param Aleph\Cache\Cache $cache
   * @access public
   */
  public function setCache(Cache\Cache $cache)
  {
    $this->cache = $cache;
  }

  /**
   * Opens database connection. If the connection is already set then it will be closed before creating of new connection.
   *
   * @param string $dsn
   * @param string $username
   * @param string $password
   * @param array $options
   * @access public
   */
  public function connect($dsn = null, $username = null, $password = null, array $options = null)
  {
    if ($dsn === null) $dsn = $this->dsn;
    if ($username === null) $username = $this->username;
    if ($password === null) $password = $this->password;
    if ($options === null) $options = $this->options;
    if (!$dsn) throw new Core\Exception($this, 'ERR_DB_1');
    $this->disconnect();
    // Extracting the database driver.
    do
    {
      $dsn = get_cfg_var('pdo.dsn.' . $dsn) ?: $dsn;
      $this->idsn['dsn'] = $dsn;
      $dsn = explode(':', $dsn, 2);
      $this->idsn['driver'] = strtolower($dsn[0]);
      if ($this->idsn['driver'] == 'uri') 
      {
        unset($dsn[0]);
        unset($dsn[1]);
        $dsn[2] = ltrim($dsn[2], '/');
        $dsn = file_get_contents(implode(':', $dsn));
      }
    }
    while ($this->idsn['driver'] == 'uri');
    if (empty($dsn[1])) throw new Core\Exception($this, 'ERR_DB_2');
    // Extracting the driver specific information.
    if ($this->idsn['driver'] == 'sqlite')
    {
      $this->idsn['host'] = '127.0.0.1';
      $this->idsn['dbname'] = pathinfo($dsn[1], PATHINFO_FILENAME);
    }
    else
    {
      $dsn = explode(';', $dsn[1]);
      foreach ($dsn as $v)
      {
        $v = explode('=', $v);
        if (isset($v[1])) $this->idsn[strtolower(trim($v[0]))] = trim($v[1]);
      }
    }
    if ($this->idsn['driver'] == 'oci' || $this->idsn['driver'] == 'oci8')
    {
      $dsn = $this->idsn['dbname'];
      $v = explode('/', $dsn);
      $this->idsn['dbname'] = array_pop($v);
      if (count($v))
      {
        $v = explode(':', array_pop($v));
        if (count($v) > 1) $this->idsn['port'] = array_pop($v);
        $this->idsn['host'] = array_pop($v);
      }
    }
    // Connecting to the database.
    $this->idsn['username'] = $username;
    $this->idsn['password'] = $password;
    $this->idsn['options'] = $options;
    switch ($this->idsn['driver'])
    {
      case 'oci8':
        $this->pdo = new OCI8($dsn, $username, $password, isset($this->idsn['charset']) ? array_merge((array)$options, ['charset' => $this->idsn['charset']]) : $options);
        break;
      default:
        switch ($this->engines[$this->idsn['driver']])
        {
          case 'MSSQL':
            $this->pdo = new MSSQL($this->idsn['dsn'], $username, $password, $options);
            break;
          case 'OCI':
            $this->pdo = new OCI($this->idsn['dsn'], $username, $password, $options);
            break;
          default:
            $this->pdo = new \PDO($this->idsn['dsn'], $username, $password, $options);
            break;
        }
        break;
    }
    $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    if (!empty($this->charset))
    {
      if (in_array($this->idsn['driver'], ['mysql', 'mysqli', 'pgsql'])) $this->pdo->exec('SET NAMES ' . $this->pdo->quote($this->charset));
    }
    $this->sql = SQLBuilder::getInstance($this->getEngine(), $this);
  }

  /**
   * Terminates the current database connection.
   *
   * @access public
   */
  public function disconnect()
  {
    $this->pdo = $this->sql = null;
    $this->idsn = [];
  }
  
  /**
   * Checks whether the database connection is set.
   *
   * @return boolean
   * @access public
   */
  public function isConnected()
  {
    return $this->pdo !== null;
  }

  /** 
   * Returns the database name for the current connection.
   * The method returns FALSE if the connection is not set.
   *
   * @return string
   * @access public
   */
  public function getDBName()
  {
    return $this->isConnected() ? $this->idsn['dbname'] : false;
  }
  
  /** 
   * Returns the host of the current connection.
   * The method returns FALSE if the connection is not set.
   *
   * @return string
   * @access public
   */
  public function getHost()
  {
    return $this->isConnected() ? $this->idsn['host'] : false;
  }
  
  /**
   * Returns the port of the current connection.
   * The method returns FALSE if the connection is not set.
   *
   * @return integer
   * @access public
   */
  public function getPort()
  {
    return $this->isConnected() ? $this->idsn['port'] : false;
  }
  
  /**
   * Returns the driver name of the current connection.
   * The method returns FALSE if the connection is not set.
   *
   * @return string
   * @access public
   */
  public function getDriver()
  {
    return $this->isConnected() ? $this->idsn['driver'] : false;
  }
  
  /**
   * Returns all DSN information of the current connection.
   * The method returns FALSE if the connection is not set.
   *
   * @return array
   * @access public
   */
  public function getDSN()
  {
    return $this->isConnected() ? $this->idsn : false;
  }
  
  /**
   * Returns the engine of the current connection.
   * The method returns FALSE if the connection is not set.
   *
   * @return string
   * @access public
   */
  public function getEngine()
  {
    return $this->isConnected() ? $this->engines[$this->idsn['driver']] : false;
  }
  
  /**
   * Returns extended error information associated with the last operation on the database handle.
   *
   * @return array
   * @access public
   */
  public function getLastError()
  {
    return $this->__get('pdo')->errorInfo();
  }
  
  /**
   * Return an array of all available PDO drivers.
   *
   * @return array
   * @access public
   * @static
   */
  public static function getAvailableDrivers()
  {
    return \PDO::getAvailableDrivers();
  }
  
  /**
   * Returns PDO type of a PHP-variable.
   *
   * @param mixed $var - the PHP-variable.
   * @return integer
   * @access public
   * @static
   */
  public static function getPDOType($var)
  {
    if (is_int($var)) return \PDO::PARAM_INT;
    if (is_bool($var)) return \PDO::PARAM_BOOL;
    if (is_null($var)) return \PDO::PARAM_NULL;
    return \PDO::PARAM_STR;
  }
  
  /**
   * Returns the case of the column names.
   *
   * @return mixed
   * @acess public
   */
  public function getColumnCase()
  {
    return $this->__get('pdo')->getAttribute(\PDO::ATTR_CASE);
  }

  /**
   * Sets the case of the column names.
   *
   * @param mixed $value
   * @access public
   */
  public function setColumnCase($value)
  {
    $this->__get('pdo')->setAttribute(\PDO::ATTR_CASE, $value);
  }

  /**
   * Returns how the null and empty strings are converted.
   *
   * @return mixed
   */
  public function getNullConversion()
  {
    return $this->__get('pdo')->getAttribute(\PDO::ATTR_ORACLE_NULLS);
  }

  /**
   * Sets how the null and empty strings are converted.
   *
   * @param mixed $value
   * @access public
   */
  public function setNullConversion($value)
  {
    $this->__get('pdo')->setAttribute(\PDO::ATTR_ORACLE_NULLS, $value);
  }
  
  /**
   * Returns whether creating or updating a DB record will be automatically committed.
	  * Some DBMS (such as sqlite) may not support this feature.
   *
   * @return boolean
   * @access public
   */
  public function getAutoCommit()
  {
    return $this->__get('pdo')->getAttribute(\PDO::ATTR_AUTOCOMMIT);
  }
  
  /**
   * Sets whether creating or updating a DB record will be automatically committed.
   *
   * @param boolean $value
   * @access public
   */
  public function setAutoCommit($value)
  {
    $this->__get('pdo')->setAttribute(\PDO::ATTR_AUTOCOMMIT, $value);
  }
  
  /**
   * Returns whether the connection is persistent or not.
   *
   * @return boolean
   * @access public
   */
  public function getPersistent()
  {
    return $this->__get('pdo')->getAttribute(\PDO::ATTR_PERSISTENT);
  }

  /**
   * Sets whether the connection is persistent or not.
   *
   * @param boolean $value
   * @access public
   */
  public function setPersistent($value)
  {
    return $this->__get('pdo')->setAttribute(\PDO::ATTR_PERSISTENT, $value);
  }
  
  /**
   * Returns the version information of the DB driver.
   *
   * @return string
   * @access public
   */
  public function getClientVersion()
  {
    return $this->__get('pdo')->getAttribute(\PDO::ATTR_CLIENT_VERSION);
  }

  /**
   * Returns the status of the connection.
   *
   * @return string
   * @access public
   */
  public function getConnectionStatus()
  {
    return $this->__get('pdo')->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
  }

  /**
   * Returns whether the connection performs data prefetching.
   *
   * @return boolean
   * @access public
   */
  public function getPrefetch()
  {
    return $this->__get('pdo')->getAttribute(\PDO::ATTR_PREFETCH);
  }

  /**
   * Returns the information of DBMS server.
   *
   * @return string
   * @access public
   */
  public function getServerInfo()
  {
    return $this->__get('pdo')->getAttribute(\PDO::ATTR_SERVER_INFO);
  }

  /**
   * Returns the version information of DBMS server.
   *
   * @return string
   * @acess public
   */
  public function getServerVersion()
  {
    return $this->__get('pdo')->getAttribute(\PDO::ATTR_SERVER_VERSION);
  }
  
  /**
   * Returns the timeout settings for the connection.
   *
   * @return integer
   * @access public
   */
  public function getTimeout()
  {
    return $this->__get('pdo')->getAttribute(\PDO::ATTR_TIMEOUT);
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
   * Returns the ID of the last inserted row or sequence value.
   *
   * @param string $name - name of the sequence object from which the ID should be returned.
   * @return string
   * @access public
   */
  public function getLastInsertID($sequenceName = null)
  {
    return $this->__get('pdo')->lastInsertId($sequenceName);
  }

  /**
   * Quotes a table or column name for use in a query.
   *
   * @param string $name - the table or column name.
   * @param boolean $isTableName - determines whether the first parameter is a table name.
   * @return string
   * @access public
   */
  public function wrap($name, $isTableName = false)
  {
    return $this->__get('sql')->wrap($name, $isTableName);
  }

  /**
   * Quotes a string value for use in a query.
   *
   * @param string $value
   * @return string
   * @access public
   */
  public function quote($value)
  {
    if (($value = $this->__get('pdo')->quote($value)) !== false) return $value;
    return $this->__get('sql')->quote($value);
  }
  
  /**
   * Initiates a transaction.
   * Returns TRUE on success or FALSE on failure.
   *
   * @return boolean
   * @access public
   */
  public function beginTransaction()
  {
    return $this->__get('pdo')->beginTransaction();
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
    return $this->__get('pdo')->commit();
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
    return $this->__get('pdo')->rollBack();
  }
  
  /**
   * Checks if a transaction is currently active within the driver.
   * Returns TRUE if a transaction is currently active, and FALSE if not.
   *
   * @return boolean
   * @access public
   */
  public function inTransaction()
  {
    return $this->__get('pdo')->inTransaction();
  }
  
  /**
   * Adds a regular expression that determines SQL queries for caching.
   *
   * @param string $sql - the regular expression.
   * @param integer | boolen $expire - the cache expiration time or false if we don't want to cache some group of queries.
   * @access public   
   */
  public function addPattern($sql, $expire)
  {
    if ($expire !== false) $expire = abs((int)$expire);
    $this->patterns[$sql] = $expire;
  }

  /**
   * Removes a regular expression that determines the group of SQL queries to cache.
   *
   * @param string $sql - the regular expression.
   * @access public
   */
  public function dropPattern($sql)
  {
    unset($this->patterns[$sql]);
  }

  /**
   * Executes the SQL statement.
   *
   * @param string $sql - a SQL to execute.
   * @param array $data - input parameters for the SQL execution.
   * @param string $type - type execution of the query. This parameter affects format of the data returned by the method.
   * @param integer $mode - fetch mode for this SQL statement.
   * @param mixed $arg - this argument have a different meaning depending on the value of the $mode parameter.
   * @param array $ctorargs - arguments of custom class constructor when the $mode parameter is PDO::FETCH_CLASS.
   * @return mixed
   * @access public
   */
  public function execute($sql, array $data = [], $type = self::EXEC, $mode = \PDO::FETCH_ASSOC, $arg = null, array $ctorargs = null)
  {
    if ($type && $type != self::EXEC && ((int)$this->cacheExpire != 0 || count($this->patterns)))
    {
      $key = $this->assemble($sql, $data);
      $flag = true;
      foreach ($this->patterns as $pattern => $expire)
      {
        if (preg_match($pattern, $sql))
        {
          $flag = !empty($expire);
          break;
        }
      }
      if ($flag && !$this->getCache()->isExpired($key)) return $this->getCache()->get($key);
    }
    $st = $this->__get('pdo')->prepare($sql);
    $this->prepare($st, $sql, $data);
    if ($this->logging && isset(\Aleph::getInstance()['db']['log']))
    {
      $id = microtime(true);
      \Aleph::pStart($id);
      $st->execute();
      $duration = \Aleph::pStop($id);
      $this->affectedRows = $st->rowCount();
      $file = \Aleph::dir(\Aleph::getInstance()['db']['log']);
      $dir = pathinfo($file, PATHINFO_DIRNAME);
      if (!is_dir($dir)) mkdir($dir, 0775, true);
      $fp = fopen($file, 'a');
      flock($fp, LOCK_EX);
      if (fstat($fp)['size'] == 0) fputcsv($fp, ['URL', 'DSN', 'SQL', 'Type', 'Mode', 'Duration', 'Timestamp', 'Rows', 'Stack']);
      fputcsv($fp, [Net\URL::current(), $this->idsn['dsn'], isset($key) ? $key : $this->assemble($sql, $data), $type, $mode, $duration, time(), $this->affectedRows, (new \Exception())->getTraceAsString()]);
      fflush($fp);
      flock($fp, LOCK_UN);
      fclose($fp);
    }
    else
    {
      $st->execute();
      $this->affectedRows = $st->rowCount();
    }
    switch ($type)
    {
      case self::EXEC:
        $res = $this->affectedRows;
        break;
      case self::CELL:
        $res = $st->fetchColumn((int)$arg);
        break;
      case self::COLUMN:
        $res = $st->fetchAll($mode | \PDO::FETCH_COLUMN, (int)$arg);
        break;
      case self::ROW:
        $res = $st->fetch($mode);
        if ($res === false) $res = [];
        break;
      case self::ROWS:
      case self::COUPLES:
        if ($arg === null) $res = $st->fetchAll($mode);
        else
        {
          if ($ctorargs === null) $res = $st->fetchAll($mode, $arg);
          else $res = $st->fetchAll($mode, $arg, $ctorargs);
        }
        if ($type == self::COUPLES)
        {
          $tmp = [];
          foreach ($res as $v) $tmp[array_shift($v)] = $v;
          $res = $tmp;
        }
        break;
      default:
        return new Reader($st, $mode, $arg, $ctorargs);
    }
    if ($type && $type != self::EXEC && !empty($flag)) 
    {
      if (empty($expire)) $expire = $this->cacheExpire > 0 ? $this->cacheExpire : $this->getCache()->getVaultLifeTime();
      $this->getCache()->set($key, $res, $expire, $this->cacheGroup);
    }
    return $res;
  }

  /**
   * Executes the SQL query and returns the value of the given column in the first row of data.
   *
   * @param string $sql - SQL query to execute.
   * @param array $data - the input parameters for the SQL execution.
   * @param integer $column - 0-indexed number of the column you wish to retrieve from the row.
   * @return string - returns FALSE when a value is not found.
   * @access public
   */
  public function cell($sql, array $data = [], $column = 0)
  {
    return $this->execute($sql, $data, self::CELL, \PDO::FETCH_COLUMN, $column);
  }

  /**
   * Executes the SQL query and returns the given column of the result.
   *
   * @param string $sql - SQL query to execute.
   * @param array $data - the input parameters for the SQL execution.
   * @param integer $column - 0-indexed number of the column you wish to retrieve from the row.
   * @return array
   * @access public
   */
  public function column($sql, array $data = [], $column = 0)
  {
    return $this->execute($sql, $data, self::COLUMN, \PDO::FETCH_COLUMN, $column);
  }

  /**
   * Executes the SQL query and returns the first row of the result.
   *
   * @param string $sql - SQL query to execute.
   * @param array $data - the input parameters for the SQL execution.
   * @param integer $mode - the fetch mode for this SQL statement.
   * @return array
   * @access public
   */
  public function row($sql, array $data = [], $mode = \PDO::FETCH_ASSOC)
  {
    return $this->execute($sql, $data, self::ROW, $mode);
  }
  
  /**
   * Executes the SQL query and returns all rows.
   *
   * @param string $sql - SQL query to execute.
   * @param array $data - the input parameters for the SQL execution.
   * @param integer $mode - the fetch mode for this SQL statement.
   * @param mixed $arg - this argument have a different meaning depending on the value of the $mode parameter.
   * @param array $ctorargs - arguments of custom class constructor when the $mode parameter is PDO::FETCH_CLASS.
   * @return array
   * @access public
   */
  public function rows($sql, array $data = [], $mode = \PDO::FETCH_ASSOC, $arg = null, array $ctorargs = null)
  {
    return $this->execute($sql, $data, self::ROWS, $mode, $arg, $ctorargs);
  }
  
  /**
   * This method is similar to the method "rows" but returns a two-column result into an array 
   * where the first column is a key and the second column is the value.
   *
   * @param string $sql - SQL query to execute.
   * @param array $data - the input parameters for the SQL execution.
   * @return array
   * @access public
   */
  public function pairs($sql, array $data = [])
  {
    return $this->rows($sql, $data, \PDO::FETCH_KEY_PAIR);
  }
  
  /**
   * Executes the SQL query and returns all rows which grouped by values of the first column.
   *
   * @param string $sql - SQL query to execute.
   * @param array $data - the input parameters for the SQL execution.
   * @param integer $mode - the fetch mode for this SQL statement.
   * @return array
   * @access public
   */
  public function groups($sql, array $data = [], $mode = \PDO::FETCH_ASSOC)
  {
    return $this->rows($sql, $data, $mode | \PDO::FETCH_GROUP);
  }
  
  /**
   * Executes the SQL query and returns all rows into an array
   * where the first column is a key and the other columns are the values.
   *
   * @param string $sql - SQL query to execute.
   * @param array $data - the input parameters for the SQL execution.
   * @param integer $mode - the fetch mode for this SQL statement.
   * @param mixed $arg - this argument have a different meaning depending on the value of the $mode parameter.
   * @param array $ctorargs - arguments of custom class constructor when the $mode parameter is PDO::FETCH_CLASS.
   * @return array
   * @access public
   */
  public function couples($sql, array $data = [], $mode = \PDO::FETCH_ASSOC, $arg = null, array $ctorargs = null)
  {
    return $this->execute($sql, $data, self::COUPLES, $mode);
  }
  
  /**
   * Returns an instance of class Aleph\DB\Reader that implements an iteration of rows from a query result set.
   *
   * @param string $sql - SQL query to execute.
   * @param array $data - input parameters for the SQL execution.
   * @param integer $mode - fetch mode for this SQL statement.
   * @param mixed $arg - this argument have a different meaning depending on the value of the $style parameter.
   * @param array $ctorargs - arguments of custom class constructor when the $style parameter is PDO::FETCH_CLASS.
   * @return Aleph\DB\Reader
   * @access public
   */
  public function query($sql, array $data = [], $mode = \PDO::FETCH_ASSOC, $arg = null, array $ctorargs = null)
  {
    return $this->execute($sql, $data, null, $mode);
  }
  
  /**
   * Returns the table list of the given database. If the database is not specified the current database is used.
   *
   * @param string $scheme - the schema (database name) of the tables.
   * @return array
   * @access public
   */
  public function getTableList($schema = null)
  {
    if (!$this->isConnected()) $this->connect();
    if ($schema === null)
    {
      if ($this->getEngine() == 'OCI') $schema = $this->idsn['username'];
      else $schema = $this->idsn['dbname'];
    }
    return $this->column($this->sql->tableList($schema));
  }
  
  /**
   * Returns the table metadata.
   *
   * @param string $table - the table name.
   * @return array
   * @access public
   */
  public function getTableInfo($table)
  {
    if (!$this->isConnected()) $this->connect();
    return $this->sql->normalizeTableInfo($this->row($this->sql->tableInfo($table)));
  }
  
  /**
   * Returns metadata of the table columns.
   *
   * @param string $table
   * @return array
   * @access public
   */
  public function getColumnsInfo($table)
  {
    if (!$this->isConnected()) $this->connect();
    return $this->sql->normalizeColumnsInfo($this->rows($this->sql->columnsInfo($table)));
  }
  
  /**
   * Inserts new record in the database table.
   * Method returns the ID of the last inserted row or sequence value.
   *
   * @param string $table - the table name.
   * @param mixed $data - the column metadata.
   * @param array $options - contains additional parameters required by some DBMS:
   * $options['updateOnKeyDuplicate'] - makes sense for MySQL DBMS. If this boolean parameter is specified and a row is inserted that would cause a duplicate value in a UNIQUE index or PRIMARY KEY, an UPDATE of the old row is performed.
   * $options['sequenceName'] - name of the sequence object (required by PostgreSQL and Oracle DBMS).
   * @return integer
   * @access public
   */
  public function insert($table, $data, array $options = null)
  {
    $this->execute($this->__get('sql')->insert($table, $data, $options)->build($data), $data);
    return $this->getLastInsertID(empty($options['sequenceName']) ? null : $options['sequenceName']);
  }
  
  /**
   * Updates existing rows in the database.
   * Method returns the number of rows affected by this SQL statement.
   *
   * @param mixed $table - information about the database tables whose rows are to be updated.
   * @param mixed $data - information about updating columns.
   * @param mixed $where - information about conditions of the updating.
   * @return integer
   * @access public 
   */
  public function update($table, $data, $where = null)
  {
    $this->execute($this->__get('sql')->update($table, $data)->where($where)->build($where), $where);
    return $this->getAffectedRows();
  }
  
  /**
   * Deletes existing rows in the database.
   * Method returns the number of rows affected by this SQL statement.
   *
   * @param mixed $table - information about the database tables whose rows are to be deleted.
   * @param mixed $where - information about conditions of the deleting.
   * @return integer
   * @access public 
   */
  public function delete($table, $where = null)
  {
    $this->execute($this->__get('sql')->delete($table)->where($where)->build($where), $where);
    return $this->getAffectedRows();
  }
  
  /**
   * Prepares a SQL statement to execution.
   *
   * @param \PDOStatement $st - the instance of SQL statement.
   * @param string $sql - the SQL query to be executed.
   * @param array $data - the data for the SQL query.
   * @access protected
   */
  protected function prepare($st, $sql, array $data)
  {
    if (is_numeric(key($data)))
    {
      $k = 1;
      foreach ($data as $v)
      {
        if (!is_array($v)) $st->bindValue($k, $v, self::getPDOType($v));
        else
        {        
          list($value, $type) = each($v);
          $st->bindValue($k, $value, $type);
        }
        $k++;
      }
      return;
    }
    foreach ($data as $k => $v) 
    {
      if (!is_array($v)) $st->bindValue($k, $v, self::getPDOType($v));
      else
      {
        list($value, $type) = each($v);
        $st->bindValue($k, $value, $type);
      }
    }
  }
  
  /**
   * Combines SQL query string and data in one string.
   *
   * @param string $sql - the parametrized SQL query.
   * @param array $data - the SQL data to be combined.
   * @return string
   * @access protected
   */
  protected function assemble($sql, array $data)
  {
    if (count($data) == 0) return $sql;
    if (is_numeric(key($data)))
    {
      foreach ($data as $k => $v) 
      {
        $v = is_array($v) ? $v[0] : $v;
        if (!is_numeric($v)) $v = $this->quote($v);
        $sql = preg_replace('/\?/', $v, $sql, 1);
      }
      return $sql;
    }
    foreach ($data as $k => &$v) if (!is_numeric($v)) $v = $this->quote($v);
    return strtr($sql, $data);
  }
}