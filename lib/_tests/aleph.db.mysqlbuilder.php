<?php

use Aleph\DB;

require_once(__DIR__ . '/../db/core/sqlbuilder.php');
require_once(__DIR__ . '/../db/core/mysql/mysqlbuilder.php');

/**
 * Test for Aleph\DB\MySQLBuilder;
 */
function test_mysqlbuilder()
{
  $sql = new DB\MySQLBuilder();
  // Checks method "wrap".
  $error = 'Method "wrap" does not work.';
  if ($sql->wrap('foo') !== '`foo`') return $error;
  if ($sql->wrap('`foo`') !== '`foo`') return $error;
  if ($sql->wrap('foo.foo') !== '`foo`.`foo`') return $error;
  if ($sql->wrap('foo.`foo`') !== '`foo`.`foo`') return $error;
  if ($sql->wrap('`foo`.`foo`') !== '`foo`.`foo`') return $error;
  if ($sql->wrap('foo', true) != '`foo`') return $error;
  if ($sql->wrap('fo`o') != '`fo``o`') return $error;
  if ($sql->wrap('foo`.`foo') != '`foo```.```foo`') return $error;
  // Checks method "quote".
  $error = 'Method "quote" does not work.';
  if ($sql->quote("'\x00\n\r\\\x1a") !== '\'\\\'\\000\\n\\r\\\\\\032\'') return $error;
  if ($sql->quote("'\x00\n\r\\\x1a_%", true) !== '\'\\\'\\000\\n\\r\\\\\\032\\_\\%\'') return $error;
  // Checks INSERT queries.
  $error = 'Building of INSERT queries does not work.';
  $q = $sql->insert('tb', '(MOW(), CURDATE(), CURTIME())')->build($data);
  if ($q !== 'INSERT INTO `tb` (MOW(), CURDATE(), CURTIME())' || !is_array($data) || count($data) != 0) return $error;
  $q = $sql->insert('tb', new DB\SQLExpression('(MOW(), CURDATE(), CURTIME())'))->build($data);
  if ($q !== 'INSERT INTO `tb` (MOW(), CURDATE(), CURTIME())' || !is_array($data) || count($data) != 0) return $error;
  $q = $sql->insert('tb', ['firstName' => 'John', 'lastName' => 'Smith', 'email' => 'johnsmith@gmail.com'])->build($data);
  if ($q !== 'INSERT INTO `tb` (`firstName`, `lastName`, `email`) VALUES (?, ?, ?)' || !is_array($data) || $data !== ['John', 'Smith', 'johnsmith@gmail.com']) return $error;
  $q = $sql->insert('tb', ['column1' => ['a', 'b'], 'column2' => 'foo', 'column3' => [1, 2, new DB\SQLExpression('NOW()')]])->build($data);
  if ($q !== 'INSERT INTO `tb` (`column1`, `column2`, `column3`) VALUES (?, ?, ?), (?, ?, ?), (?, ?, NOW())' || !is_array($data) || $data != ['a', 'foo', 1, 'b', 'foo', 2, 'b', 'foo']) return $error;
  $q = $sql->insert('tb', ['column1' => 1, 'column2' => new DB\SQLExpression('CURDATE()'), 'column3' => 'abc'], ['updateOnKeyDuplicate' => true])->build($data);
  if ($q !== 'INSERT INTO `tb` (`column1`, `column2`, `column3`) VALUES (?, CURDATE(), ?) ON DUPLICATE KEY UPDATE `column1` = ?, `column2` = CURDATE(), `column3` = ?' || !is_array($data) || $data != [1, 'abc', 1, 'abc']) return $error;
  // Checks UPDATE queries.
  $error = 'Building of UPDATE queries does not work.';
  $q = $sql->update(new DB\SQLExpression('tb AS t'), 'col1 = 1, col2 = col2 + 1')->build($data);
  if ($q !== 'UPDATE tb AS t SET col1 = 1, col2 = col2 + 1' || !is_array($data) || count($data) != 0) return $error;
  $q = $sql->update(['tb1', 'tb2' => 't2', 'tb3' => 't3'], ['column1' => 1, new DB\SQLExpression('c = c + 1'), 'column2' => 'abc'])->build($data);
  if ($q !== 'UPDATE `tb1`, `tb2` AS `t2`, `tb3` AS `t3` SET `column1` = ?, c = c + 1, `column2` = ?' || !is_array($data) || $data != [1, 'abc']) return $error;
  $q = $sql->update('tb', ['c1' => 'a', 'c2' => 'b', 'c3' => 'c'])->where(['c1' => new DB\SQLExpression('CURDATE()'), ['c2 LIKE c1', 'c3 LIKE c2'], 'or' => ['c2' => 3, new DB\SQLExpression('c3 IN (1,2,3)')]])->build($data);
  if ($q !== 'UPDATE `tb` SET `c1` = ?, `c2` = ?, `c3` = ? WHERE `c1` = CURDATE() AND c2 LIKE c1 AND c3 LIKE c2 AND (`c2` = ? OR c3 IN (1,2,3))' || !is_array($data) || $data != ['a', 'b', 'c', 3]) return $error;
  // Checks DELETE queries.
  $error = 'Building of DELETE queries does not work.';
  $q = $sql->delete(new DB\SQLExpression('tb AS t'))->build($data);
  if ($q !== 'DELETE FROM tb AS t' || !is_array($data) || count($data) != 0) return $error;
  $q = $sql->delete('tb')->where('expire > CURDATE()')->build($data);
  if ($q !== 'DELETE FROM `tb` WHERE expire > CURDATE()' || !is_array($data) || count($data) != 0) return $error;
  // Checks SELECT queries
  $error = 'Building of SELECT queries does not work.';
  $q = $sql->select(['tb1' => 't1', 'tb2'], [new DB\SQLExpression('COUNT(*) AS c'), 't1.name', 't2.name' => 'category'], 'DISTINCTROW')
           ->join(['tb3' => 't3', 'tb4' => 't4'], ['or' => ['t3.column' => new DB\SQLExpression('t1.column'), 't4.column' => new DB\SQLExpression('tb2.column')], 't3.ID IS NOT NULL', 't4' => 1])
           ->where(['t1.column' => 2, 't3.column > 6'])
           ->group(['t1.column', 'tb2.name'])
           ->having(['COUNT(*) < 10', 't1.ID' => 3])
           ->order(['t3.column' => 'DESC', 't1.name'])
           ->limit(10, 120)
           ->build($data);
  if ($q !== 'SELECT DISTINCTROW COUNT(*) AS c, `t1`.`name`, `t2`.`name` AS `category` FROM `tb1` AS `t1`,`tb2` INNER JOIN `tb3` AS `t3`, `tb4` AS `t4` ON (`t3`.`column` = t1.column OR `t4`.`column` = tb2.column) AND t3.ID IS NOT NULL AND `t4` = ? WHERE `t1`.`column` = ? AND t3.column > 6 GROUP BY `t1`.`column`, `tb2`.`name` HAVING COUNT(*) < 10 AND `t1`.`ID` = ? ORDER BY `t3`.`column` DESC, `t1`.`name` LIMIT 120, 10' || !is_array($data) || $data != [1, 2, 3]) return $error;
  return true;
}

return test_mysqlbuilder();