<?php

use Aleph\Core;

require_once(__DIR__ . '/../core/exception.php');

// Test class
class TestExceptionClass
{
  const ERR_1 = 'Error';
  const ERR_2 = 'Error: [{var}], [{var}]';
  
  public function throwError1()
  {
    throw new Core\Exception($this, 'ERR_1');
  }
  
  public function throwError2()
  {
    throw new Core\Exception($this, 'ERR_2', 'a', 'b');
  }
}

/**
 * Test for Aleph\Core\Exception;
 */
function test_exception()
{
  // Checks simple error message.
  try
  {
    throw new Core\Exception(false, 'Some error: [{var}], [{var}], [{var}]', 'a', 'b', 'c');
  }
  catch (Core\Exception $e)
  {
    if ($e->getMessage() !== 'Some error: a, b, c' || $e->getClass() !== '' || $e->getToken() !== 'Some error: [{var}], [{var}], [{var}]') return 'Simple error template is not correctly processed.';
  }
  // Checks token error message throwing from class.
  $class = new \TestExceptionClass();
  try
  {
    $class->throwError1();
  }
  catch (Core\Exception $e)
  {
    if ($e->getMessage() !== 'Error (Token: TestExceptionClass::ERR_1)' || $e->getClass() !== 'TestExceptionClass' || $e->getToken() !== 'ERR_1') return 'Token error template without parameters (throwing from class) is not correctly processed.';
    try
    {
      $class->throwError2();
    }
    catch (Core\Exception $e)
    {
      if ($e->getMessage() !== 'Error: a, b (Token: TestExceptionClass::ERR_2)' || $e->getClass() !== 'TestExceptionClass' || $e->getToken() !== 'ERR_2') return 'Token error template with parameters (throwing from class) is not correctly processed.';
    }
  }
  // Checks token error template throwing outside class.
  try
  {
    throw new Core\Exception('TestExceptionClass::ERR_1');
  }
  catch (Core\Exception $e)
  {
    if ($e->getMessage() !== 'Error (Token: TestExceptionClass::ERR_1)' || $e->getClass() !== 'TestExceptionClass' || $e->getToken() !== 'ERR_1') return 'Token error template without parameters (throwing outside class) is not correctly processed.';
  }
  try
  {
    throw new Core\Exception('\TestExceptionClass::ERR_2', 'a', 'b');
  }
  catch (Core\Exception $e)
  {
    if ($e->getMessage() !== 'Error: a, b (Token: TestExceptionClass::ERR_2)' || $e->getClass() !== 'TestExceptionClass' || $e->getToken() !== 'ERR_2') return 'Token error template with parameters (throwing outside class) is not correctly processed.';
  }
  return true;
}

return test_exception();