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

namespace Aleph\DB\Sync\MySQL;

use Aleph\Core;

/**
 * Class for changing MySQL database structure.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.db.sync
 * @abstract
 */
class DBWriter extends \Aleph\DB\Sync\DBWriter
{
  /**
   * Makes changes in database structure and information tables data.
   * If changes were made in db structure then method returns the array of executed SQL queries.
   *
   * @param array $info - the array returned by method Synchronizer::compare.
   * @return array | NULL
   * @access public
   */
  public function write(array $info)
  {
    $this->queries = array();
    $params = $this->db->getParameters();
    $pdo = $this->db->getPDO();
    $dbName = $this->db->wrap($params['dbname'], false);
    $pdo->beginTransaction();
    try
    {
      $pdo->prepare('SET FOREIGN_KEY_CHECKS=0')->execute();
      foreach (array('insert', 'delete') as $type)
      {
        foreach ($info[$type] as $class => $data)
        {
          if ($class == 'tables' || $class == 'procedures' || $class == 'events' || $class == 'views')
          {
            foreach ($data as $name => $values)
            {
              if ($class == 'tables')
              {
                if ($type == 'insert') 
                {
                  $this->setData($pdo, 'insert', 'table', array('tbl_definition' => $values['definition']));
                  foreach ($values['triggers'] as $trg) $this->setData($pdo, $type, 'trigger', $trg);
                }
                else $this->setData($pdo, 'delete', 'table', $values['meta']);
              }
              else $this->setData($pdo, $type, substr($class, 0, -1), $values);
            }
          }
          else
          {
            foreach ($data as $table => $dta)
            {
              foreach ($dta as $name => $values)
              {
                $this->setData($pdo, $type, substr($class, 0, -1 - (int)($class == 'indexes')), $values);
              }
            }
          }
        }
      }
      foreach ($info['update'] as $class => $data)
      {
        if ($class == 'meta')
        {
          $this->setData($pdo, 'update', 'database', $data);
        }
        else if ($class == 'tables')
        {
          foreach ($data as $table => $tb)
          {
            if (isset($tb['meta']))
            {
              $this->setData($pdo, 'update', 'table', $tb['meta']);
            }
            else
            {
              foreach (array('columns', 'indexes', 'constraints', 'triggers') as $entity)
              {
                if (!isset($tb[$entity])) continue;
                $key = substr($entity, 0, -1 - (int)($entity == 'indexes'));
                if ($this->db->getSQL('update', $key) === false)
                {
                  foreach ($tb[$entity] as $values)
                  {
                    $this->setData($pdo, 'delete', $key, $values);
                    $this->setData($pdo, 'insert', $key, $values);
                  }
                }
                else
                {
                  foreach ($tb[$entity] as $values)
                  {
                    $this->setData($pdo, 'update', $key, $values);
                  }
                }
              }
            }
          }
        }
        else
        {
          foreach (array('procedures', 'events', 'views') as $entity)
          {
            if ($class != $entity) continue;
            $key = substr($entity, 0, -1);
            foreach ($data as $values)
            {
              $this->setData($pdo, 'delete', $key, $values);
              $this->setData($pdo, 'insert', $key, $values);
            }
          }
        }
      }
      foreach ($info['data'] as $values)
      {
        $this->setData($pdo, 'delete', 'data', $values);
        $this->setData($pdo, 'insert', 'data', $values);
      }
      $pdo->prepare('SET FOREIGN_KEY_CHECKS=1')->execute();
      $pdo->commit();
    }
    catch (\PDOException $e)
    {
      $pdo->rollBack();
      throw new Core\Exception($e->getMessage());
    }
    return $this->queries;
  }
}