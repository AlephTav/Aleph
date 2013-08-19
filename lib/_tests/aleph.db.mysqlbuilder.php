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
  $q = $sql->insert('tb', '(MOW(),CURDATE(),CURTIME())')->build($data);
  if ($q !== 'INSERT INTO `tb` (MOW(),CURDATE(),CURTIME())' || !is_array($data) || count($data) != 0) return $error;
  $q = $sql->insert('tb', new DB\SQLExpression('(MOW(),CURDATE(),CURTIME())'))->build($data);
  if ($q !== 'INSERT INTO `tb` (MOW(),CURDATE(),CURTIME())' || !is_array($data) || count($data) != 0) return $error;
  $q = $sql->insert('tb', ['firstName' => 'John', 'lastName' => 'Smith', 'email' => 'johnsmith@gmail.com'])->build($data);
  if ($q !== 'INSERT INTO `tb` (`firstName`,`lastName`,`email`) VALUES (?,?,?)' || !is_array($data) || $data !== ['John', 'Smith', 'johnsmith@gmail.com']) return $error;
  $q = $sql->insert('tb', ['column1' => ['a', 'b'], 'column2' => 'foo', 'column3' => [1, 2, new DB\SQLExpression('NOW()')]])->build($data);
  if ($q !== 'INSERT INTO `tb` (`column1`,`column2`,`column3`) VALUES (?,?,?),(?,?,?),(?,?,NOW())' || !is_array($data) || $data != ['a', 'foo', 1, 'b', 'foo', 2, 'b', 'foo']) return $error;
  // Checks UPDATE queries.
  return true;
}

return test_mysqlbuilder();