<?php

namespace Aleph\Net;

require_once(__DIR__ . '/../core/delegate.php');
require_once(__DIR__ . '/../net/headers.php');
require_once(__DIR__ . '/../net/request.php');
require_once(__DIR__ . '/../net/router.php');

/**
 * Test for Aleph\Net\Router;
 */
function test_router()
{
  // Preparing environment.
  $foo = function(){return func_get_args();};
  // Testing bind() method.
  $msg = 'Method bind() doesn\'t work.';
  $router = new Router();
  $router->bind('category/#category|[^\#]+?#(/#ID#|)\##fragment|![0-9]+', $foo, 'GET');
  $res = $router->route('GET', 'category/my_category/123#!345');
  if ($res->success !== true || $res->result !== ['my_category', '123', '!345']) return $msg;
  $res = $router->route('GET', 'category/cat#!100');
  if ($res->success !== true || $res->result !== ['cat', '', '!100']) return $msg;
  $res = $router->route('POST', 'category/cat#!100');
  if ($res->success !== false) return $msg;
  $res = $router->route('GET', 'category/my_#category/123#!345');
  if ($res->success !== false) return $msg;
  // Checks full bind() method.
  $router->clean('bind');
  $router->bind('my.host.(com|net|org)/#path1#/#path2#/#path3#', $foo)
         ->component(URL::HOST | URL::PATH)
         ->validation(['path1' => '/^_a+_$/', 'path3' => '/^[0-9]*$/'])
         ->args(['arg1' => 'a', 'arg2' => 'b']);
  $res = $router->route('POST', 'my.host.net/_aaaa_/foo/333');
  if ($res->success !== true || $res->result !== ['a', 'b', '_aaaa_', 'foo', '333']) return 'Extended method bind() doesn\'t work.';
  // Checks redirect() method.
  $router->redirect('category/#category|[^\#]+?#(/#ID#|)\##fragment|![0-9]+', 'post/#ID#_#category#/#fragment#', 'GET', function($url){return $url;});
  $res = $router->route('GET', 'category/my_category/123#!345');
  if ($res->success !== true || $res->result !== 'post/123_my_category/!345') return 'Method redirect() doesn\'t work.';
  // Checks secure() method.
  $url = new URL();
  $router->secure('/^http.*/', true, '*', function($url){return $url;});
  $res = $router->route('POST', $url);
  $url->secure(true);
  if ($res->success !== true || $res->result !== $url->build()) return 'Method secure() doesn\'t work.';
  return true;
}

return test_router();