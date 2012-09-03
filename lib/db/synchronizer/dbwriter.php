<?php

namespace Aleph\DB\Sync;

abstract class DBWriter implements IWriter
{
  protected $db =  null;
  
  protected $queries = array();
  
  public function __construct(DBCore $db)
  {
    $this->db = $db;
    $this->queries = array();
  }
  
  public function getQueries()
  {
    return $this->queries;
  }
  
  protected function setData(\PDO $pdo, $class, $type, array $params = null)
  {
    $sql = $this->db->getSQL($class, $type, $params);
    $this->queries[] = $sql;
    $pdo->prepare($sql)->execute();
  }
}