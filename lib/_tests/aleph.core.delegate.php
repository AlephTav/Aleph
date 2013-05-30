<?php

namespace Aleph\Core;

require_once(__DIR__ . '/../core/delegate.php');

/**
 * Test for Aleph\Core\Delegate;
 */
function test_delegate()
{
  if (!test_delegate_parse()) return 'Parsing of string delegate doesn\'t work.';
  if (!test_delegate_in()) return 'Checking of permissions doesn\'t work.';
  return true;
}

/**
 * Test for parsing of delegates (method Aleph\Core\Delegate::__construct).
 */
function test_delegate_parse()
{
  if ((new Delegate('foo'))->getInfo() !== ['class' => null, 'method' => 'foo', 'static' => null, 'numargs' => null, 'cid' => null, 'type' => 'function']) return false;
  if ((new Delegate('test::foo'))->getInfo() !== ['class' => 'test', 'method' => 'foo', 'static' => true, 'numargs' => 0, 'cid' => null, 'type' => 'class']) return false;
  if ((new Delegate('test->foo'))->getInfo() !== ['class' => 'test', 'method' => 'foo', 'static' => false, 'numargs' => 0, 'cid' => null, 'type' => 'class']) return false;
  if ((new Delegate('test[123]->foo'))->getInfo() !== ['class' => 'test', 'method' => 'foo', 'static' => false, 'numargs' => 123, 'cid' => null, 'type' => 'class']) return false;
  if ((new Delegate('test@ctrl->foo'))->getInfo() !== ['class' => 'test', 'method' => 'foo', 'static' => false, 'numargs' => 0, 'cid' => 'ctrl', 'type' => 'control']) return false;
  if ((new Delegate('test[]'))->getInfo() !== ['class' => 'test', 'method' => '__construct', 'static' => false, 'numargs' => 0, 'cid' => null, 'type' => 'class']) return false;
  if ((new Delegate('test[123]'))->getInfo() !== ['class' => 'test', 'method' => '__construct', 'static' => false, 'numargs' => 123, 'cid' => null, 'type' => 'class']) return false;
  return true;
}

/**
 * Test for checking of permissions (method Aleph\Core\Delegate::in).
 */
function test_delegate_in()
{
  $f = 1;
  $d = new Delegate('Aleph\Net\URL::current');
  $f &= $d->in('');
  $f &= $d->in('Aleph\\');
  $f &= $d->in('Aleph\Net\\');
  $f &= $d->in('Aleph\Net\URL');
  $f &= $d->in('Aleph\Net\URL::current');
  $f &= $d->in(['Aleph\Core\\', 'Aleph\\']);
  $f &= !$d->in('Aleph\Core\\');
  $f &= !$d->in('Aleph\Net\URL->current');
  return (bool)$f;
}

return test_delegate();