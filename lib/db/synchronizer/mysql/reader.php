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

namespace Aleph\DB\Sync;

/**
 * Class for reading MySQL database structure.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.db.sync
 * @abstract
 */
class MySQLReader extends DBReader
{
  /**
   * Reads database structure.
   *
   * @return array - you can find the format of returned array in file /lib/db/synchronizer/structure_db.txt
   * @access public
   */
  public function read()
  {
    if ($this->info) return $this->info;
    $params = $this->db->getParameters();
    $pdo = $this->db->getPDO();
    $dbName = $this->db->wrap($params['dbname'], false);
    $dbNameQuoted = $this->db->quote($params['dbname']);
    $this->info = array();
    $this->info['meta']['driver'] = $params['driver'];
    $this->info['meta']['db_name'] = $dbName;
    $this->info['meta'] += $this->getData($pdo, 'database', array('db_name' => $dbNameQuoted));
    $this->info['tables'] = array();
    $tables = $this->getData($pdo, 'tables', array('db_name' => $dbNameQuoted));
    $data = array();
    foreach ($tables as $table => $meta)
    {
      $tblName = $this->db->wrap($table, false);
      $tblNameQuoted = $this->db->quote($table);
      if ($this->infoTablesPattern && preg_match($this->infoTablesPattern, $table))
      {
        $st = $pdo->prepare($this->db->getSQL('data', 'table', array('tbl_name' => $tblName)));
        $st->execute();
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);
        if (count($rows) > 0)
        {
          $values = array();
          $fields = array_keys($rows[0]);
          foreach ($fields as &$field) $field = $this->db->wrap($field);
          foreach ($rows as $row) 
          {
            $tmp = array();
            foreach ($row as $value)
            {
              $tmp[] = is_numeric($value) ? $value : $this->db->quote($value);
            }
            $values[] = '(' . implode(', ', $tmp) . ')';
          }
          $fields = implode(', ', $fields);
          $values = implode(', ', $values);
          $data[$table] = array('tbl_name' => $tblName, 'fields' => $fields, 'values' => $values);
        }
      }
      $tbInfo = array();
      $tbInfo['meta'] = $meta;
      $tbInfo['definition'] = $this->getData($pdo, 'table', array('tbl_name' => $tblName));
      $tbInfo['columns'] = $this->getData($pdo, 'columns', array('tbl_name' => $tblName));
      $tbInfo['indexes'] = $this->getData($pdo, 'indexes', array('tbl_name' => $tblName));
      $tbInfo['constraints'] = $this->getData($pdo, 'constraints', array('tbl_name' => $tblName));
      $tbInfo['triggers'] = $this->getData($pdo, 'triggers', array('db_name' => $dbName, 'tbl_name' => $this->db->quote($table, true)));
      $this->info['tables'][$table] = $tbInfo;
    }
    $this->info['procedures'] = $this->getData($pdo, 'procedures', array('db_name' => $dbNameQuoted));
    $this->info['events'] = $this->getData($pdo, 'events', array('db_name' => $dbNameQuoted));
    $this->info['views'] = $this->getData($pdo, 'views', array('db_name' => $dbNameQuoted));
    $this->info['data'] = $data;
    return $this->info;
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
    $st = parent::getData($pdo, $type, $params);
    $rows = array();
    if ($type == 'database')
    {
      $tmp = $st->fetch(\PDO::FETCH_ASSOC);
      $rows['charset'] = $tmp['DEFAULT_CHARACTER_SET_NAME'];
      $rows['collation'] = $tmp['DEFAULT_COLLATION_NAME'];
    }
    else if ($type == 'tables')
    {
      while ($row = $st->fetch(\PDO::FETCH_ASSOC))
      {
        $tmp = array();
        $tmp['tbl_name'] = $this->db->wrap($row['TABLE_NAME'], false);
        $tmp['engine_name'] = $row['ENGINE'];
        $tmp['collation_name'] = $row['TABLE_COLLATION'];
        $tmp['comment_value'] = $this->db->quote($row['TABLE_COMMENT']);      
        $tmp['options'] = $row['CREATE_OPTIONS'];
        $rows[$row['TABLE_NAME']] = $tmp;
      }
    }
    else if ($type == 'table')
    {
      $rows = $st->fetchAll(\PDO::FETCH_NUM);
      $rows = $rows[0][1];
    }
    else if ($type == 'columns')
    {
      while ($row = $st->fetch(\PDO::FETCH_ASSOC)) 
      {
        $def = $row['Type'] . (strlen($row['Collation']) ? ' COLLATE ' . $this->db->quote($row['Collation']) : '');
        $def .= $row['Null'] == 'YES' ? ' NULL' : ' NOT NULL';
        $def .= strlen($row['Default']) ? ' DEFAULT ' . (substr($row['Type'], 0, 3) == 'bit' || $row['Default'] == 'CURRENT_TIMESTAMP' ? $row['Default'] : $this->db->quote($row['Default'])) : '';
        $def .= $row['Extra'] == 'auto_increment' ? ' AUTO_INCREMENT' : '';
        $def .= substr($row['Extra'], 0, 2) == 'on' ? ' ' . $row['Extra'] : '';
        $def .= strlen($row['Comment']) ? ' COMMENT ' . $this->db->quote($row['Comment']) : '';
        $tmp = array();
        $tmp['column_name'] = $this->db->wrap($row['Field']);
        $tmp['column_definition'] = $def;
        $tmp['tbl_name'] = $params['tbl_name'];
        $rows[$row['Field']] = $tmp;
      }
    }
    else if ($type == 'indexes')
    {
      while ($row = $st->fetch(\PDO::FETCH_ASSOC)) 
      {
        if (isset($rows[$row['Key_name']])) $tmp['columns'] = $rows[$row['Key_name']]['columns'];
        else 
        {
          $tmp = array();
          $tmp['type'] = $row['Index_type'];
          $tmp['isUnique'] = $row['Non_unique'] != 1;
        }
        $tmp['columns'][] = array($row['Column_name'], $row['Sub_part']);
        $tmp['comment'] = $row['Comment'];
        $rows[$row['Key_name']] = $tmp;
      }
      $tmp = $rows; $rows = array();
      foreach ($tmp as $index => $row)
      {
        if ($row['type'] == 'FULLTEXT' || $row['type'] == 'SPATIAL') 
        {
          $class = $row['type'];
          $row['type'] = '';
        }
        else
        {
          $class = $row['isUnique'] ? 'UNIQUE' : '';
          $row['type'] = 'USING ' . $row['type'];
        }
        foreach ($row['columns'] as &$column) $column = $this->db->wrap($column[0]) . (strlen($column[1]) ? '(' . $column[1] . ')' : '');
        $rows[$index] = array('index_name' => $index == 'PRIMARY' ? 'PRIMARY KEY' : 'INDEX ' . $this->db->wrap($index),
                              'index_class' => $index == 'PRIMARY' ? '' : $class,
                              'index_type' => $row['type'],
                              'index_columns' => implode(', ', $row['columns']),
                              'comment_value' => $this->db->quote($row['comment']),
                              'tbl_name' => $params['tbl_name']);
      }
    }
    else if ($type == 'constraints')
    {
      $name = function(&$line)
      {
        $k = 0;
        while (isset($line[$k]))
        {
          $s = $line[$k++];
          if ($s == '`') 
          {
            if (isset($line[$k]) && $line[$k] == '`') $k++;
            else
            {
              $name = substr($line, 0, $k - 1);
              $line = substr($line, $k);
              return $name;
            }
          }
        }
      };
      $lines = $st->fetchAll(\PDO::FETCH_NUM);
      $lines = explode("\n", $lines[0][1]);
      foreach ($lines as $line)
      {
        $line = rtrim(trim($line), ',');
        if (substr($line, 0, 10) != 'CONSTRAINT') continue;
        $tmp = array();
        $line = substr($line, strpos($line, '`') + 1);
        $constr = $name($line);
        foreach (array('keys' => ')', 'table' => ' ', 'links' => ')') as $k => $v)
        do
        {
          $line = substr($line, strpos($line, '`') + 1);
          $tmp[$k][] = $name($line);
        }
        while ($line[0] != $v);
        if (preg_match('/ON DELETE (CASCADE|SET NULL|NO ACTION|RESTRICT)/', $line, $matches)) $tmp['delete'] = $matches[1];
        else $tmp['delete'] = 'RESTRICT';
        if (preg_match('/ON UPDATE (CASCADE|SET NULL|NO ACTION|RESTRICT)/', $line, $matches)) $tmp['update'] = $matches[1];
        else $tmp['update'] = 'RESTRICT';
        $rows[$constr] = $tmp;
      }
      $tmp = $rows; $rows = array();
      foreach ($tmp as $constraint => $row)
      {
        foreach (array('keys', 'table', 'links') as $entity)
        {
          foreach ($row[$entity] as &$value) $value = $this->db->wrap($value);
        }
        $rows[$constraint] = array('fk_name' => $this->db->wrap($constraint),
                                   'fk_keys' => implode(', ', $row['keys']),
                                   'fk_table' => implode('.', $row['table']),
                                   'fk_links' => implode(', ', $row['links']),
                                   'fk_delete' => $row['delete'],
                                   'fk_update' => $row['update'],
                                   'tbl_name' => $params['tbl_name']);
      }
    }
    else if ($type == 'procedures')
    {
      while ($row = $st->fetch(\PDO::FETCH_ASSOC))
      {
        $tmp = array();
        $tmp['sp_name'] = $this->db->wrap($row['ROUTINE_NAME'], false);
        $tmp['sp_type'] = $row['ROUTINE_TYPE'];
        $tmp['sp_definition'] = $this->getData($pdo, 'procedure', array('prc_type' => $tmp['sp_type'], 'prc_name' => $tmp['sp_name']));
        $rows[$row['ROUTINE_NAME']] = $tmp; 
      }
    }
    else if ($type == 'procedure')
    {
      $tmp = $st->fetch(\PDO::FETCH_NUM);
      $tmp = preg_replace('/^CREATE DEFINER=.* ' . $params['prc_type'] . '/', '', $tmp[2]);
      return $tmp;
    }
    else if ($type == 'triggers')
    {
      while ($row = $st->fetch(\PDO::FETCH_ASSOC))
      {
        $tmp = array();
        $tmp['trigger_name'] = $this->db->wrap($row['Trigger']);
        $tmp['trigger_event'] = $row['Event'];
        $tmp['trigger_time'] = $row['Timing'];
        $tmp['trigger_body'] = $row['Statement'];
        $tmp['tbl_name'] = $this->db->wrap($row['Table'], false);
        $rows[$row['Trigger']] = $tmp;
      }
    }
    else if ($type == 'events')
    {
      $statuses = array('SLAVESIDE_DISABLED' => 'DISABLE ON SLAVE', 'ENABLED' => 'ENABLE', 'DISABLED' => 'DISABLE');
      while ($row = $st->fetch(\PDO::FETCH_ASSOC))
      {
        $interval = $row['INTERVAL_FIELD'] ? $row['INTERVAL_VALUE'] . ' ' . $row['INTERVAL_FIELD'] : '';
        $tmp = array();
        $tmp['event_name'] = $this->db->wrap($row['EVENT_NAME']);
        if ($row['EVENT_NAME'] == 'ONE TIME')
        {
          $tmp['event_schedule'] = $this->db->quote($row['EXECUTE_AT']);
          if ($interval) $tmp['event_schedule'] .= ' INTERVAL ' . $interval;
        }
        else
        {
          $tmp['event_schedule'] = 'EVERY ' . $interval;
          if ($row['STARTS'] != '') $tmp['event_schedule'] .= ' STARTS ' . $this->db->quote($row['STARTS']);
          if ($row['ENDS'] != '') $tmp['event_schedule'] .= ' ENDS ' . $this->db->quote($row['ENDS']);
        }
        $tmp['event_status'] = $statuses[$row['STATUS']];
        $tmp['event_completion'] = $row['ON_COMPLETION'];
        $tmp['event_body'] = $row['EVENT_DEFINITION'];
        $tmp['comment_value'] = $this->db->quote($row['EVENT_COMMENT']);
        $rows[$row['EVENT_NAME']] = $tmp;
      }
    }
    else if ($type == 'views')
    {
      while ($row = $st->fetch(\PDO::FETCH_ASSOC))
      {
        $tmp = array();
        $tmp['view_name'] = $this->db->wrap($row['TABLE_NAME']);
        $tmp['view_definition'] = $this->getData($pdo, 'view', array('vw_name' => $this->db->wrap($row['TABLE_NAME'], false)));
        $rows[$row['TABLE_NAME']] = $tmp; 
      } 
    }
    else if ($type == 'view')
    {
      $tmp = $st->fetch(\PDO::FETCH_NUM);
      $tmp = preg_replace('/ DEFINER=.* /U', ' ', $tmp[1]);
      return substr($tmp, 7); 
    }
    return $rows;
  }
}