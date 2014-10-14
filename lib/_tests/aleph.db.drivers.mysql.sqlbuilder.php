<?php

use Aleph\DB,
    Aleph\DB\Drivers\MySQL;

require_once(__DIR__ . '/../DB/SQLBuilder.php');
require_once(__DIR__ . '/../DB/Drivers/MySQL/SQLBuilder.php');

/**
 * Test for Aleph\DB\MySQLBuilder;
 */
function test_mysqlbuilder()
{
  $sql = new MySQL\SQLBuilder();
  // Checks method "wrap".
  $error = 'Method "wrap" does not work.';
  if ($sql->wrap('foo') !== '`foo`') return $error;
  if ($sql->wrap('`foo`') !== '`foo`') return $error;
  if ($sql->wrap('foo.foo') !== '`foo`.`foo`') return $error;
  if ($sql->wrap('foo.`foo`') !== '`foo`.```foo```') return $error;
  if ($sql->wrap('`foo`.`foo`') !== '`foo`.`foo`') return $error;
  if ($sql->wrap('foo', true) != '`foo`') return $error;
  if ($sql->wrap('fo`o') != '`fo``o`') return $error;
  if ($sql->wrap('foo`.`foo') != '`foo```.```foo`') return $error;
  // Checks method "quote".
  $error = 'Method "quote" does not work.';
  if ($sql->quote("'\x00\n\r\\\x1a", DB\SQLBuilder::ESCAPE_QUOTED_VALUE) !== '\'\\\'\\000\\n\\r\\\\\\032\'') return $error;
  if ($sql->quote("'\x00\n\r\\\x1a_%", DB\SQLBuilder::ESCAPE_LIKE) !== '\\\'\\000\\n\\r\\\\\\032\\_\\%') return $error;
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
  $q = $sql->insert('tb', ['c1' => [[1 => \PDO::PARAM_INT], ['a' => \PDO::PARAM_STR]], 'c2' => [2, 'b', [true => \PDO::PARAM_BOOL]], 'c3' => [[1 => \PDO::PARAM_BOOL]]])->build($data);
  if ($q !== 'INSERT INTO `tb` (`c1`, `c2`, `c3`) VALUES (?, ?, ?), (?, ?, ?), (?, ?, ?)' || !is_array($data) || $data !== [[1 => \PDO::PARAM_INT], 2, [1 => \PDO::PARAM_BOOL], ['a' => \PDO::PARAM_STR], 'b', [1 => \PDO::PARAM_BOOL], ['a' => \PDO::PARAM_STR], [true => \PDO::PARAM_BOOL], [1 => \PDO::PARAM_BOOL]]) return $error;  
  // Checks UPDATE queries.
  $error = 'Building of UPDATE queries does not work.';
  $q = $sql->update(new DB\SQLExpression('tb AS t'), 'col1 = 1, col2 = col2 + 1')->build($data);
  if ($q !== 'UPDATE tb AS t SET col1 = 1, col2 = col2 + 1' || !is_array($data) || count($data) != 0) return $error;
  $q = $sql->update(['tb1', 'tb2' => 't2', 'tb3' => 't3'], ['column1' => 1, new DB\SQLExpression('c = c + 1'), 'column2' => 'abc'])->build($data);
  if ($q !== 'UPDATE `tb1`, `tb2` `t2`, `tb3` `t3` SET `column1` = ?, c = c + 1, `column2` = ?' || !is_array($data) || $data != [1, 'abc']) return $error;
  // Checks DELETE queries.
  $error = 'Building of DELETE queries does not work.';
  $q = $sql->delete(new DB\SQLExpression('tb AS t'))->build($data);
  if ($q !== 'DELETE FROM tb AS t' || !is_array($data) || count($data) != 0) return $error;
  $q = $sql->delete('tb')->where('expire > CURDATE()')->build($data);
  if ($q !== 'DELETE FROM `tb` WHERE expire > CURDATE()' || !is_array($data) || count($data) != 0) return $error;
  // Checks SELECT queries
  $error = 'Building of SELECT queries does not work.';
  $q = $sql->select(['tb1' => 't1', 'tb2'], [new DB\SQLExpression('COUNT(*) AS c'), 't1.name', 't2.name' => 'category'], 'DISTINCTROW')
           ->join(['tb3' => 't3', 'tb4' => 't4'], [['or', ['=', 't3.column', new DB\SQLExpression('t1.column')], ['=', 't4.column', new DB\SQLExpression('tb2.column')]], 't3.ID IS NOT NULL', ['=', 't4', 1]])
           ->where([['=', 't1.column', 2], 't3.column > 6'])
           ->group(['t1.column', 'tb2.name'])
           ->having(['COUNT(*) < 10', ['=', 't1.ID', 3]])
           ->order(['t3.column' => 'DESC', 't1.name'])
           ->limit(10, 120)
           ->build($data);
  if ($q !== 'SELECT DISTINCTROW COUNT(*) AS c, `t1`.`name`, `t2`.`name` `category` FROM `tb1` `t1`, `tb2` INNER JOIN `tb3` `t3`, `tb4` `t4` ON (`t3`.`column` = t1.column OR `t4`.`column` = tb2.column) AND t3.ID IS NOT NULL AND `t4` = ? WHERE `t1`.`column` = ? AND t3.column > 6 GROUP BY `t1`.`column`, `tb2`.`name` HAVING COUNT(*) < 10 AND `t1`.`ID` = ? ORDER BY `t3`.`column` DESC, `t1`.`name` LIMIT 120, 10' || !is_array($data) || $data != [1, 2, 3]) return $error;
  $q = $sql->select('tb')->where([['<>', 'c1', 5], ['LIKE', 'c2', 'a'], ['NOT IN', 'c3', [1, 2, 3]], ['BETWEEN', 'c4', 5, 9], ['IS', 'c5', 'NULL']])->build($data);
  if ($q !== 'SELECT * FROM `tb` WHERE `c1` <> ? AND `c2` LIKE ? AND `c3` NOT IN (?, ?, ?) AND `c4` BETWEEN ? AND ? AND `c5` IS NULL' || !is_array($data) || $data !== [5, 'a', 1, 2, 3, 5, 9]) return $error;
  $q = $sql->select(['tb1' => 't1', 'tb2' => 't2', 'tb3'], ['c1', 'c2', 'schema.table.c3'])
           ->join(['tb4'], [['=', 'tb4.c1', 1]])
           ->join(['tb5'], [['=', 'tb5.c1', 2]])
           ->join(['tb6'], [['=', 'tb6.c1', 3]], 'LEFT')
           ->where([['=', 'tb6.c2', 4], ['=', 'tb5.c2', new DB\SQLExpression('tb4.c1')]])
           ->where('c3 IS NULL', 'OR')
           ->group(['c7', 'c8'])
           ->group(['c9'])
           ->having([['=', 'c3', 5], ['=', 'c4', [6 => \PDO::PARAM_INT]]])
           ->having([['=', 'c6', 7]])
           ->order(['c1' => 'DESC'])
           ->order(['c2'])
           ->limit(1, 10)
           ->build($data);
  if ($q !== 'SELECT `c1`, `c2`, `schema`.`table`.`c3` FROM `tb1` `t1`, `tb2` `t2`, `tb3` INNER JOIN `tb4` ON `tb4`.`c1` = ? INNER JOIN `tb5` ON `tb5`.`c1` = ? LEFT JOIN `tb6` ON `tb6`.`c1` = ? WHERE (`tb6`.`c2` = ? AND `tb5`.`c2` = tb4.c1) OR (c3 IS NULL) GROUP BY `c7`, `c8`, `c9` HAVING (`c3` = ? AND `c4` = ?) AND (`c6` = ?) ORDER BY `c1` DESC, `c2` LIMIT 10, 1'  || !is_array($data) || $data != [1, 2, 3, 4, 5, [6 => \PDO::PARAM_INT], 7]) return $error;
  return true;
}

return test_mysqlbuilder();