<?php

namespace Aleph\Core;

require_once(__DIR__ . '/../Core/Delegate.php');

/**
 * Test for Aleph\Core\Delegate;
 */
function test_delegate()
{
  if (!test_delegate_parse()) return 'Parsing of string delegate doesn\'t work.';
  if (!test_delegate_isPermitted()) return 'Checking of permissions doesn\'t work.';
  return true;
}

/**
 * Test for parsing of delegates (method Aleph\Core\Delegate::__construct).
 */
function test_delegate_parse()
{
  try
  {
    new Delegate(0);
  }
  catch (Exception $e)
  {
    if ($e->getToken() != 'ERR_DELEGATE_1') return false;
  }
  try
  {
    new Delegate(['Test', 'test' => 'foo']);
  }
  catch (Exception $e)
  {
    if ($e->getToken() != 'ERR_DELEGATE_1') return false;
  }
  try
  {
    new Delegate(['Test', new \stdClass()]);
  }
  catch (Exception $e)
  {
    if ($e->getToken() != 'ERR_DELEGATE_1') return false;
  }
  if ((new Delegate('foo'))->getInfo() !== ['class' => null, 'method' => 'foo', 'static' => false, 'numargs' => null, 'cid' => null, 'type' => 'function']) return false;
  if ((new Delegate('test::foo'))->getInfo() !== ['class' => 'test', 'method' => 'foo', 'static' => true, 'numargs' => 0, 'cid' => null, 'type' => 'class']) return false;
  if ((new Delegate('test->foo'))->getInfo() !== ['class' => 'test', 'method' => 'foo', 'static' => false, 'numargs' => 0, 'cid' => null, 'type' => 'class']) return false;
  if ((new Delegate('test[123]->foo'))->getInfo() !== ['class' => 'test', 'method' => 'foo', 'static' => false, 'numargs' => 123, 'cid' => null, 'type' => 'class']) return false;
  if ((new Delegate('test@ctrl->foo'))->getInfo() !== ['class' => 'test', 'method' => 'foo', 'static' => false, 'numargs' => 0, 'cid' => 'ctrl', 'type' => 'control']) return false;
  if ((new Delegate('test[]'))->getInfo() !== ['class' => 'test', 'method' => '__construct', 'static' => false, 'numargs' => 0, 'cid' => null, 'type' => 'class']) return false;
  if ((new Delegate('\test[123]'))->getInfo() !== ['class' => 'test', 'method' => '__construct', 'static' => false, 'numargs' => 123, 'cid' => null, 'type' => 'class']) return false;
  if ((new Delegate('test::parent::foo'))->getInfo() !== ['class' => 'test', 'method' => 'parent::foo', 'static' => true, 'numargs' => 0, 'cid' => null, 'type' => 'class']) return false;
  $class = new \stdClass();
  $d = new Delegate($class);
  if ((string)$d !== 'stdClass[]' || $d->getInfo() !== ['class' => $class, 'method' => '__construct', 'static' => false, 'numargs' => 0, 'cid' => null, 'type' => 'class']) return false;
  $class = new \ReflectionClass('Aleph');
  $d = new Delegate($class);
  if ((string)$d !== 'ReflectionClass[1]' || $d->getInfo() !== ['class' => $class, 'method' => '__construct', 'static' => false, 'numargs' => 1, 'cid' => null, 'type' => 'class']) return false;
  $d = new Delegate(['Aleph', 'all']);
  if ((string)$d !== 'Aleph::all' || $d->getInfo() !== ['class' => 'Aleph', 'method' => 'all', 'static' => true, 'numargs' => 0, 'cid' => null, 'type' => 'class']) return false;
  $d = new Delegate([$class, 'isAbstract']);
  if ((string)$d !== 'ReflectionClass[1]->isAbstract' || $d->getInfo() !== ['class' => $class, 'method' => 'isAbstract', 'static' => false, 'numargs' => 1, 'cid' => null, 'type' => 'class']) return false;
  return true;
}

/**
 * Test for checking of permissions (method Aleph\Core\Delegate::isPermitted).
 */
function test_delegate_isPermitted()
{
  $permissions = ['permitted' => ['/^Aleph\\\\(MVC|Web\\\\POM)\\\\[^\\\\]*$/i'],
                  'forbidden' => ['/^Aleph\\\\Web\\\\POM\\\\[^\\\\]*\[\d*\]->offset(Set|Get|Unset|Exists)$/i']];

  $d1 = new Delegate('Aleph\MVC\MyPage->test');
  $d2 = new Delegate('Aleph\Web\POM\Control@myctrl->offsetSet');
  $d3 = new Delegate('Aleph\Web\POM\Control@myctrl->__set');

  $f = 1;
  $f &= (int)$d1->isPermitted($permissions);
  $f &= (int)!$d2->isPermitted($permissions);
  $f &= (int)$d3->isPermitted($permissions);
  return (bool)$f;
}

return test_delegate();