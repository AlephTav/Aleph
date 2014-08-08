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

namespace Aleph\DB\Sync\MySQL;

/**
 * Main class for all MySQL databases operations. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.db.sync
 */
class DBCore extends \Aleph\DB\Sync\DBCore
{
  /**
   * Array of SQL query templates for different database operations.
   *
   * @var array $sql
   * @access protected
   */
  protected $sql = array('info' => array('database' => 'SELECT * FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = db_name',
                                         'tables' => 'SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = db_name AND TABLE_TYPE = \'BASE TABLE\'',
                                         'table' => 'SHOW CREATE TABLE tbl_name',
                                         'columns' => 'SHOW FULL COLUMNS FROM tbl_name',
                                         'indexes' => 'SHOW INDEXES FROM tbl_name',
                                         'constraints' => 'SHOW CREATE TABLE tbl_name',
                                         'procedures' => 'SELECT * FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = db_name',
                                         'procedure' => 'SHOW CREATE prc_type prc_name',
                                         'triggers' => 'SHOW TRIGGERS FROM db_name LIKE tbl_name',
                                         'events' => 'SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = db_name',
                                         'views' => 'SELECT * FROM information_schema.VIEWS WHERE TABLE_SCHEMA = db_name',
                                         'view' => 'SHOW CREATE VIEW vw_name'),
                         'insert' => array('table' => 'tbl_definition',
                                           'column' => 'ALTER TABLE tbl_name ADD column_name column_definition',
                                           'index' => 'ALTER TABLE tbl_name ADD index_class index_name index_type (index_columns) COMMENT comment_value',
                                           'constraint' => 'ALTER TABLE tbl_name ADD CONSTRAINT fk_name FOREIGN KEY (fk_keys) REFERENCES fk_table (fk_links) ON DELETE fk_delete ON UPDATE fk_update',
                                           'trigger' => 'CREATE TRIGGER trigger_name trigger_time trigger_event ON tbl_name FOR EACH ROW trigger_body',
                                           'procedure' => 'CREATE sp_type sp_definition',
                                           'event' => 'CREATE EVENT event_name ON SCHEDULE event_schedule ON COMPLETION event_completion event_status COMMENT comment_value DO event_body',
                                           'view' => 'CREATE view_definition',
                                           'data' => 'INSERT INTO tbl_name (fields) VALUES values'),
                         'update' => array('database' => 'ALTER DATABASE db_name DEFAULT CHARACTER SET charset DEFAULT COLLATE collation',
                                           'table' => 'ALTER TABLE tbl_name ENGINE = engine_name COLLATE = collation_name COMMENT = comment_value options',
                                           'column' => 'ALTER TABLE tbl_name CHANGE column_name column_name column_definition',
                                           'index' => 'ALTER TABLE tbl_name DROP index_name, ADD index_class index_name index_type (index_columns) COMMENT comment_value',
                                           'view' => 'ALTER view_definition'),
                         'delete' => array('table' => 'DROP TABLE IF EXISTS tbl_name',
                                           'column' => 'ALTER TABLE tbl_name DROP COLUMN column_name',
                                           'index' => 'ALTER TABLE tbl_name DROP index_name',
                                           'constraint' => 'ALTER TABLE tbl_name DROP FOREIGN KEY fk_name',
                                           'trigger' => 'DROP TRIGGER IF EXISTS trigger_name',
                                           'procedure' => 'DROP sp_type IF EXISTS sp_name',
                                           'event' => 'DROP EVENT IF EXISTS event_name',
                                           'view' => 'DROP VIEW IF EXISTS view_name',
                                           'data' => 'TRUNCATE TABLE tbl_name'),
                         'data' => array('table' => 'SELECT * FROM tbl_name'));

  /**
   * Quotes a column name or table name for use in queries.
   *
   * @param string $name - column or table name.
   * @param boolean $isColumnName
   * @return string
   * @access public
   * @abstract
   */
  public function wrap($name, $isColumnName = true)
  {
    if (strlen($name) == 0 || $name == '*') return $name;
    return '`' . str_replace('`', '``', $name) . '`';
  }
  
  /**
   * Quotes a string value for use in queries.
   *
   * @param string $value
   * @param boolean $isLike - determines whether the quoting value is used in LIKE clause.
   * @return string
   * @access public
   * @abstract
   */
  public function quote($value, $isLike = false)
  {
    if ($isLike) $value = str_replace('\\', '\\\\\\\\', $value);
    else $value = str_replace('\\', '\\\\', $value);
    return "'" . str_replace('\'', '\'\'', $value) . "'";
  }
}