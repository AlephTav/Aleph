<?php

use Aleph\Net;

require_once(__DIR__ . '/../Net/URL.php');

/**
 * Test for Aleph\Net\URL;
 */
function test_url()
{
  $url = new Net\URL('');
  // Checks empty constructor.
  if ($url->scheme !== '' || $url->fragment !== '' || $url->path !== [] || $url->query !== [] || 
      $url->source !== ['host' => '', 'port' => '', 'user' => '', 'pass' => '']) return 'Empty constructor is not working correctly.';
  // Checks URL::current() method.
  $server = isset($_SERVER) ? $_SERVER : null;
  $_SERVER['HTTPS'] = 'off';
  $_SERVER['PHP_AUTH_USER'] = 'user';
  $_SERVER['PHP_AUTH_PW'] = 'test';
  $_SERVER['HTTP_HOST'] = 'my.test.com';
  $_SERVER['SERVER_PORT'] = '8080';
  $_SERVER['REQUEST_URI'] = '/index.html?v1=val1&v2=var2';
  if (Net\URL::current() !== 'http://user:test@my.test.com:8080/index.html?v1=val1&v2=var2') 
  {
    $_SERVER = $server;
    return 'Method "current" is not working correctly (http).';
  }
  unset($_SERVER);
  if (Net\URL::current() !== false) 
  {
    $_SERVER = $server;
    return 'Method "current" is not working correctly (CLI).';
  }
  $_SERVER = $server;
  // Checks url parsing.
  $msg = 'Parsing is not working correctly.';
  $url = new Net\URL('index.php');
  if ($url->path !== ['index.php']) return $msg;
  $url->parse('http://my.host/index.php');
  if ($url->scheme !== 'http' || $url->path !== ['index.php'] || $url->source !== ['host' => 'my.host', 'port' => '', 'user' => '', 'pass' => '']) return $msg;
  $url->parse('HTTPS://user:test@my.test.com:8080/some%2Ftest///path/index.html?v1=val1&v2=val%3F2&v3[]=val31&v3[]=val32#frag%23ment');
  if ($url->scheme !== 'https' || $url->fragment !== 'frag#ment' || $url->path !== ['some/test', 'path', 'index.html'] || $url->query !== ['v1' => 'val1', 'v2' => 'val?2', 'v3' => ['val31', 'val32']] ||
      $url->source !== ['host' => 'my.test.com', 'port' => '8080', 'user' => 'user', 'pass' => 'test']) return $msg;
  // Checks url building.
  $msg = 'Building is not working correctly.';
  $url = new Net\URL('');
  $url->scheme = 'HTTPS';
  $url->fragment = 'fragment';
  $url->source = ['host' => 'my.host', 'port' => '6060', 'user' => 'user', 'pass' => 'pass'];
  $url->path = ['very', 'long', 'path', '', '', 'index.php'];
  $url->query = ['a' => 1, 'b' => 2, 'c' => ['a', 'b', 'c' => [1, 2, 3]]];
  if ($url->build() !== 'https://user:pass@my.host:6060/very/long/path/index.php?a=1&b=2&c%5B0%5D=a&c%5B1%5D=b&c%5Bc%5D%5B0%5D=1&c%5Bc%5D%5B1%5D=2&c%5Bc%5D%5B2%5D=3#fragment' ||
      $url->build(Net\URL::SCHEME) !== 'https://' ||
      $url->build(Net\URL::HOST) !== 'user:pass@my.host:6060' ||
      $url->build(Net\URL::PATH) !== 'very/long/path/index.php' ||
      $url->build(Net\URL::HOST | Net\URL::PATH) !== 'user:pass@my.host:6060/very/long/path/index.php' ||
      $url->build(Net\URL::QUERY) !== 'a=1&b=2&c%5B0%5D=a&c%5B1%5D=b&c%5Bc%5D%5B0%5D=1&c%5Bc%5D%5B1%5D=2&c%5Bc%5D%5B2%5D=3' ||
      $url->build(Net\URL::FRAGMENT) !== 'fragment' ||
      $url->build(Net\URL::PATH | Net\URL::QUERY | Net\URL::FRAGMENT) !== 'very/long/path/index.php?a=1&b=2&c%5B0%5D=a&c%5B1%5D=b&c%5Bc%5D%5B0%5D=1&c%5Bc%5D%5B1%5D=2&c%5Bc%5D%5B2%5D=3#fragment' ||
      $url->build(Net\URL::SCHEME | Net\URL::HOST | Net\URL::PATH) != 'https://user:pass@my.host:6060/very/long/path/index.php') return $msg;
  $url->fragment = '';
  $url->query = [];
  if ($url->build() !== 'https://user:pass@my.host:6060/very/long/path/index.php') return $msg;
  return true;
}

return test_url();