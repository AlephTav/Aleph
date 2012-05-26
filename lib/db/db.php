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
    Aleph\Cache;

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

  /**
   * These constants affect on format output data of method "execute".
   */
  const DB_EXEC = 'exec';
  const DB_COLUMN = 'column';
  const DB_COLUMNS = 'columns';
  const DB_ROW = 'row';
  const DB_ROWS = 'rows';
  const DB_COUPLES = 'couples';
  
  /**
   * An instance of PDO class.
   *
   * @var PDO $_pdo_
   * @access private
   */
  private $_pdo_ = null;
  
  /**
   * An instance of Aleph\DB\SQLBuilder class.
   *
   * @var Aleph\DB\SQLExpression $_sql_
   * @access private
   */
  private $_sql_ = null;
  
  private $_dsn_ = array();
  
  /**
   * Contains regular expression patterns for query caching.
   *
   * @var array $patterns
   * @access protected
   */
  protected $patterns = array();
  
  /**
   * Contains logs of query executing.
   *
   * @var array $logs
   */
  protected $logs = array();
  
  /**
   * if this variable is TRUE then each query execution will be logged, otherwise won't.   
   *
   * @var boolean $enableLogging
   * @access public
   */
  public $logging = false;
  
  /**
   * An instance of Aleph\Cache\Cache class.
   *
   * @var Aleph\Cache\Cache $cache
   * @access protected
   */
  protected $cache = null;
  
  /**
   * Contains a number of affected rows as result of the last operation. 
   */
  protected $affectedRows = null;
  
  /**
   * The mapping between PDO driver and database engines.
   *
   * @var array $engines
   * @access protected
   */
  protected $engines = array('mysql' => 'MySQL',
                             'mysqli' => 'MySQL',
                             'pgsql' => 'PostgreSQL',
                             'sqlite' => 'SQLite',
                             'sqlite2' => 'SQLite',
                             'mssql' => 'MSSQL',
                             'dblib' => 'MSSQL',
                             'sqlsrv' => 'MSSQL',
                             'oci' => 'OCI');
  
  /**
   * Query cache lifetime in seconds.
   *
   * @var integer $cacheExpire
   * @access public
   */
  public $cacheExpire = false;
  
  /**
   * Prefix of all cache key for storing of query results.
   *
   * @var string $cachePrefix
   * @access public
   */
  public $cachePrefix = 'db_';
  
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
  public $options = array();

  /**
   * Constructor of this class.
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
  }
  
  public function __sleep()
  {
    $this->_pdo_ = null;
    return array_keys(get_object_vars($this));
  }
  
  public function __wakeup()
  {
    $this->connect($this->_dsn_['dsn'], $this->_dsn_['username'], $this->_dsn_['password'], $this->_dsn_['options']);
  }
  
  public function __get($param)
  {
    if ($param == 'pdo')
    {
      if ($this->_pdo_ instanceof \PDO) return $this->_pdo_;
      $this->connect($this->dsn, $this->username, $this->password, $this->options);
      return $this->_pdo_;
    }
    if ($param == 'sql')
    {
      if ($this->_sql_ instanceof SQLBuilder) return $this->_sql_;
      $this->connect($this->dsn, $this->username, $this->password, $this->options);
      return $this->_sql_;
    }
    throw new Core\Exception('Aleph', 'ERR_GENERAL_3', $param, get_class($this));
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

  public function connect($dsn = null, $username = null, $password = null, array $options = null)
  {
    if ($dsn === null) $dsn = $this->dsn;
    if ($username === null) $username = $this->username;
    if ($password === null) $password = $this->password;
    if ($options === null) $options = $this->options;
    if (!$dsn) throw new Core\Exception($this, 'ERR_DB_1');
    $this->disconnect();
    $this->_dsn_ = array('dsn' => $dsn);
    $dsn = explode(':', $dsn);
    $this->_dsn_['driver'] = strtolower($dsn[0]);
    $dsn = explode(';', $dsn[1]);
    foreach ($dsn as $v)
    {
      $v = explode('=', $v);
      $this->_dsn_[strtolower(trim($v[0]))] = trim($v[1]);
    }
    $this->_dsn_['username'] = $username;
    $this->_dsn_['password'] = $password;
    $this->_dsn_['options'] = $options;
    $this->_pdo_ = ($this->getEngine() == 'MSSQL') ? new MSSQL($this->_dsn_['dsn'], $username, $password, $options) : new \PDO($this->_dsn_['dsn'], $username, $password, $options);
    $this->_pdo_->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    if (!empty($this->charset))
    {
      if (in_array($this->_dsn_['driver'], array('mysql', 'mysqli', 'pgsql'))) $this->_pdo_->exec('SET NAMES ' . $this->_pdo_->quote($this->charset));
    }
    $this->_sql_ = SQLBuilder::getInstance($this->getEngine());
  }

  public function disconnect()
  {
    $this->_pdo_ = $this->_sql_ = null;
    $this->_dsn_ = array();
  }
  
  public function isConnected()
  {
    return $this->_pdo_ instanceof \PDO;
  }

  public function getDBName()
  {
    return $this->isConnected() ? $this->_dsn_['dbname'] : false;
  }
  
  public function getHost()
  {
    return $this->isConnected() ? $this->_dsn_['host'] : false;
  }
  
  public function getPort()
  {
    return $this->isConnected() ? $this->_dsn_['port'] : false;
  }
  
  public function getDriver()
  {
    return $this->isConnected() ? $this->_dsn_['driver'] : false;
  }
   
  public function getDSN()
  {
    return $this->isConnected() ? $this->_dsn_ : false;
  }
  
  public function getEngine()
  {
    return $this->isConnected() ? $this->engines[$this->_dsn_['driver']] : false;
  }
   
  public function getLastError()
  {
    return $this->pdo->errorInfo();
  }
   
  public function getLogs()
  {
    return $this->logs;
  }
   
  public static function getAvailableDrivers()
  {
    return \PDO::getAvailableDrivers();
  }
  
  public static function getPDOType($var)
  {
    if (is_int($var)) return \PDO::PARAM_INT;
    if (is_bool($var)) return \PDO::PARAM_BOOL;
    if (is_null($var)) return \PDO::PARAM_NULL;
    return \PDO::PARAM_STR;
  }
  
  public function getColumnCase()
  {
    return $this->pdo->getAttribute(\PDO::ATTR_CASE);
  }

  public function setColumnCase($value)
  {
    $this->pdo->setAttribute(\PDO::ATTR_CASE, $value);
  }

  public function getNullConversion()
  {
    return $this->pdo->getAttribute(\PDO::ATTR_ORACLE_NULLS);
  }

  public function setNullConversion($value)
  {
    $this->pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, $value);
  }
  
  public function getAutoCommit()
  {
    return $this->pdo->getAttribute(\PDO::ATTR_AUTOCOMMIT);
  }
   
  public function setAutoCommit($value)
  {
    $this->pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, $value);
  }
  
  public function getPersistent()
  {
    return $this->pdo->getAttribute(\PDO::ATTR_PERSISTENT);
  }

  public function setPersistent($value)
  {
    return $this->pdo->setAttribute(\PDO::ATTR_PERSISTENT, $value);
  }
  
  public function getClientVersion()
  {
    return $this->pdo->getAttribute(\PDO::ATTR_CLIENT_VERSION);
  }

  public function getConnectionStatus()
  {
    return $this->pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
  }

  public function getPrefetch()
  {
    return $this->pdo->getAttribute(\PDO::ATTR_PREFETCH);
  }

  public function getServerInfo()
  {
    return $this->pdo->getAttribute(\PDO::ATTR_SERVER_INFO);
  }

  public function getServerVersion()
  {
    return $this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
  }
  
  public function getTimeout()
  {
    return $this->pdo->getAttribute(\PDO::ATTR_TIMEOUT);
  }
  
  public function getAffectedRows()
  {
    return $this->affectedRows;
  }
  
  public function getLastInsertID($sequenceName = null)
  {
    return $this->pdo->lastInsertId($sequenceName);
  }

  public function wrap($name, $isTableName = false)
  {
    return $this->sql->wrap($name, $isTableName);
  }

  public function quote($value)
  {
    return $this->pdo->quote($value);
  }
  
  public function cacheAddSQLPattern($sql, $expire = 0)
  {
    if ($expire !== false) $expire = abs((int)$expire);
    $this->patterns[$sql] = $expire;
  }

  public function cacheDropSQLPattern($sql)
  {
    unset($this->patterns[$sql]);
  }

  public function execute($sql, array $data = array(), $type = self::DB_EXEC, $style = \PDO::FETCH_BOTH)
  {
    if ($this->cacheExpire !== false && $type != self::DB_EXEC)
    {
      $assembly = $this->assemble($sql, $data);
      $key = $this->cachePrefix . $assembly;
      $flag = true;
      foreach ($this->patterns as $pattern => $expire)
      {
        if (preg_match('@' . str_replace('@', '\@', $pattern) . '@isU', $sql))
        {
          $flag = ($expire !== false);
          break;
        }
      }
      if ($flag && !$this->getCache()->isExpired($key)) return $this->getCache()->get($key);
    }
    $st = $this->pdo->prepare($sql);
    $this->prepare($st, $sql, $data);
    if ($this->logging)
    {
      \Aleph::pStart('db_sql_log');
      $st->execute();
      $duration = \Aleph::pStop('db_sql_log');
      $this->affectedRows = $st->rowCount();
      try {throw new \Exception();}
      catch (\Exception $e) {$stack = $e->getTraceAsString();}
      $this->logs[$this->dsn['dsn']][] = array('sql' => $assembly ?: $this->assemble($sql, $data), 
                                               'type' => $type, 
                                               'style' => $style, 
                                               'duration' => $duration, 
                                               'timestamp' => time(), 
                                               'affectedRows' => $this->affectedRows, 
                                               'stack' => $stack);
    }
    else
    {
      $st->execute();
      $this->affectedRows = $st->rowCount();
    }
    switch ($type)
    {
      case self::DB_EXEC:
        $res = $this->affectedRows;
        break;
      case self::DB_COLUMN:
        $res = $st->fetchColumn();
        if (is_array($res)) $res = end($res);
        break;
      case self::DB_COLUMNS:
        $res = array(); while ($row = $st->fetch($style)) $res[] = array_shift($row);
        break;
      case self::DB_ROW:
        $res = $st->fetch($style);
        if ($res === false) $res = array();
        break;
      case self::DB_ROWS:
        $res = $st->fetchAll($style);
        break;
      case self::DB_COUPLES:
        $rows = $st->fetchAll(\PDO::FETCH_NUM); $res = array();
        if (is_array($rows[0])) foreach ($rows as $v) $res[$v[0]] = $v[1];
        break;
    }
    if ($this->cacheExpire !== false && $type != self::DB_EXEC && $flag) 
    {
      if (empty($expire)) $expire = $this->cacheExpire ?: $this->getCache()->getVaultLifeTime();
      $this->getCache()->set($key, $res, $expire, 'db_sql_queries');
    }
    return $res;
  }

  public function column($sql, array $data = array())
  {
    return $this->execute($sql, $data, self::DB_COLUMN);
  }

  public function columns($sql, array $data = array())
  {
    return $this->execute($sql, $data, self::DB_COLUMNS);
  }

  public function row($sql, array $data = array(), $style = \PDO::FETCH_ASSOC)
  {
    return $this->execute($sql, $data, self::DB_ROW, $style);
  }
  
  public function rows($sql, array $data = array(), $style = \PDO::FETCH_ASSOC)
  {
    return $this->execute($sql, $data, self::DB_ROWS, $style);
  }
  
  public function couples($sql, array $data = array())
  {
    return $this->execute($sql, $data, self::DB_COUPLES);
  }
  
  public function insert($table, $data, $sequenceName = null)
  {
    $this->execute($this->sql->insert($table, $data)->build($data), $data);
    return $this->getLastInsertID($sequenceName);
  }
  
  public function update($table, $data, $where = null)
  {
    $this->execute($this->sql->update($table, $data)->where($where)->build($where), $where);
    return $this->getAffectedRows();
  }
  
  public function delete($table, $where = null)
  {
    $this->execute($this->sql->delete($table)->where($where)->build($where), $where);
    return $this->getAffectedRows();
  }

  public function beginTransaction()
  {
    $this->pdo->beginTransaction();
  }
  
  public function commit()
  {
    $this->pdo->commit();
  }
  
  public function rollBack()
  {
    $this->pdo->rollBack();
  }
  
  public function inTransaction()
  {
    return $this->pdo->inTransaction();
  }
  
  public function getColumnsInfo($table)
  {
    return $this->sql->normalizeColumnInfo($this->rows($this->sql->columnsInfo($table)));
  }
  
  public function getTableInfo($table)
  {
    return $this->sql->normalizeTableInfo($this->row($this->sql->tableInfo($table)));
  }
  
  protected function prepare(\PDOStatement $st, $sql, array $data)
  {
    if (is_numeric(key($data)))
    {
      $k = 1;
      foreach ($data as $v)
      {
        if (is_array($v)) $st->bindValue($k, $v[0], $v[1]);
        else $st->bindValue($k, $v, self::getPDOType($v));
        $k++;
      }
      return;
    }
    foreach ($data as $k => $v) 
    {
      if (is_array($v)) $st->bindValue($k, $v[0], $v[1]);
      else $st->bindValue($k, $v, self::getPDOType($v));
    }
  }
  
  protected function assemble($sql, array $data)
  {
    if (count($data) == 0) return $sql;
    if (is_numeric(key($data)))
    {
      foreach ($data as $k => $v) 
      {
        $sql = preg_replace('/\?/', is_array($v) ? $v[0] : $v, $sql, 1);
      }
      return $sql;
    }
    return strtr($sql, $data);
  }
}