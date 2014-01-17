<?php
/**
 * Copyright (c) 2012 Aleph Tav
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
 * @copyright Copyright &copy; 2012 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */
 
namespace Aleph\Net;

use Aleph\Core;

/**
 * URL Class is designed to modify existing URL strings and to construct new ones.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.net
 */
class URL
{ 
  /**
   * The following constants are used to get separate parts of a URL.
   * Eeach constant is related to a particular part of a URL.
   * E.g.: URL http://user:password@my.host.com:8080/one/two/three?var1=val1&var2=val2&var3=val3#fragment
   * Here: 
   * ALL corresponds whole URL.
   * SCHEME corresponds http://
   * HOST corresponds user:password@my.host.com:8080
   * QUERY corresponds var1=val1&var2=val2&var3=val3
   * PATH corresponds /one/two/three
   * FRAGMENT corresponds fragment
   * Also you can combine these constants to get needed part of URL.
   * For more information about URL structure, please see http://en.wikipedia.org/wiki/Uniform_resource_locator
   */
  const ALL = 31;
  const SCHEME = 1;
  const HOST = 2;
  const PATH = 4;
  const QUERY = 8;
  const FRAGMENT = 16;
  
  /**
   * Scheme component of a URL.
   *
   * @var    string
   * @access public
   */
  public $scheme = null;

  /**
   * Source component of a URL.
   * Source represents associative array of the following structure: ['host' => ..., 'port' => ..., 'user' => ..., 'pass' => ...]
   *
   * @var    array
   * @access public
   */
  public $source = [];

  /**
   * Path component of a URL.
   * Path represents array of separate parts of URL path component.
   * E.g.: URL path /one/two/three will be represented as ['one', 'two', 'three'].
   *
   * @var    array
   * @access public
   */
  public $path = [];

  /**
   * Query component of a URL.
   * Query represents associative array in which keys and values are names and values of query fields.
   * E.g.: URL query ?var1=val1&var2=val2&var3=val3 will be represented as ['var1' => 'val1', 'var2' => 'val2', 'var3' => 'val3']
   *
   * @var    array
   * @access public
   */
  public $query = [];

  /**
   * Fragment component of a URL.
   *
   * @var    string
   * @access public
   */
  public $fragment = null;

  /**
   * Constructs a new URL object.
   * - new URL() parses the current requested URL.
   * - new URL($url) parses the given URL - $url.
   *
   * @param string $url
   * @access public
   */
  public function __construct($url = null)
  {
    $this->parse($url === null ? static::current() : $url);
  }
  
  /**
   * Returns the current URL of a page, if http request was made and FALSE otherwise.
   *
   * @return string | boolean
   * @access public
   * @static
   */
  public static function current()
  {
    if (!isset($_SERVER['HTTP_HOST'])) return false;
    $url = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') && (empty($_SERVER['HTTP_X_FORWARDED_PROTO']) || $_SERVER['HTTP_X_FORWARDED_PROTO'] != 'https') ? 'http://' : 'https://';
    if (isset($_SERVER['PHP_AUTH_USER'])) $url .= $_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW'] . '@';
    $url .= $_SERVER['HTTP_HOST'];
    if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80) $url .= ':' . $_SERVER['SERVER_PORT'];
    $uri = '';
    if (isset($_SERVER['X_ORIGINAL_URL'])) $uri = $_SERVER['X_ORIGINAL_URL'];
    else if (isset($_SERVER['X_REWRITE_URL'])) $uri = $_SERVER['X_REWRITE_URL'];
    else if (isset($_SERVER['IIS_WasUrlRewritten']) && $_SERVER['IIS_WasUrlRewritten'] == '1' && !empty($_SERVER['UNENCODED_URL'])) $uri = $_SERVER['UNENCODED_URL'];
    else if (isset($_SERVER['REQUEST_URI'])) 
    {
      $uri = $_SERVER['REQUEST_URI'];
      if (strpos($uri, $url) === 0) $uri = substr($uri, strlen($url));
    }
    else if (isset($_SERVER['ORIG_PATH_INFO']))
    {
      $uri = $_SERVER['ORIG_PATH_INFO'];
      if (!empty($_SERVER['QUERY_STRING'])) $uri .= '?' . $_SERVER['QUERY_STRING'];
    }
    else if (isset($_SERVER['PHP_SELF'])) $uri = $_SERVER['PHP_SELF'];
    return $url . $uri;
  }
  
  /**
   * Parses URL. After parsing you can access to all URL components.
   *
   * @param string $url
   * @access public
   */
  public function parse($url)
  {
    preg_match_all('@^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?@', $url, $arr);
    $this->scheme = strtolower($arr[2][0]);
    $this->source = [];
    $data = explode('@', $arr[4][0]);
    if (empty($data[1]))
    {
      $data = explode(':', $data[0]);
      $this->source['host'] = urldecode($data[0]);
      $this->source['port'] = isset($data[1]) ? $data[1] : '';
      $this->source['user'] = '';
      $this->source['pass'] = '';
    }
    else
    {
      $d1 = explode(':', $data[1]);
      $this->source['host'] = urldecode($d1[0]);
      $this->source['port'] = isset($d1[1]) ? $d1[1] : '';
      $d2 = explode(':', $data[0]);
      $this->source['user'] = urldecode($d2[0]);
      $this->source['pass'] = urldecode(isset($d2[1]) ? $d2[1] : '');
    }
    $this->path = ($arr[5][0] != '') ? array_values(array_filter(explode('/', $arr[5][0]), 'strlen')) : [];
    foreach ($this->path as &$part) $part = urldecode($part);
    $this->query = $arr[7][0];
    parse_str($this->query, $this->query);
    $this->fragment = urldecode($arr[9][0]);
  }
  
  /**
   * Returns URL or a part of URL.
   * E.g. URL http://user:pass@my.host.com/some/path?p1=v1&p2=v2#frag
   * <code>
   * $url = new URL('http://user:pass@my.host.com/some/path?p1=v1&p2=v2#frag');
   * echo $url->build();                              // shows whole URL
   * echo $url->build(URL::SCHEME);                   // shows http://
   * echo $url->build(URL::HOST);                     // shows user:pass@my.host.com
   * echo $url->build(URL::PATH);                     // shows /some/path
   * echo $url->build(URL::QUERY);                    // shows p1=v1&p2=v2
   * echo $url->build(URL::PATH | URL::QUERY);        // shows /some/path?p1=v1&p2=v2
   * echo $url->build(URL::QUERY | URL::FRAGMENT);    // shows p1=v1&p2=v2#frag
   * </code>
   *
   * @param string $component - name of an URL component.
   * @return string
   * @access public
   */
  public function build($component = self::ALL)
  {
    $url = '';
    if ($component & self::SCHEME && $this->scheme) 
    {
      $url .= strtolower($this->scheme) . '://';
    }
    if ($component & self::HOST)
    {
      $credentials = $this->source['user'] ? $this->source['user'] . ':' . $this->source['pass'] : '';
      $url .= ($credentials ? $credentials . '@' : '') . $this->source['host'] . ($this->source['port'] ? ':' . $this->source['port'] : '');
    }
    if ($component & self::PATH)
    {
      $tmp = [];
      foreach ($this->path as $part) if (strlen($part)) $tmp[] = urlencode($part);
      if ($component & self::HOST && count($tmp)) $url .= '/';
      $url .= implode('/', $tmp);
    }
    if ($component & self::QUERY && count($this->query))
    {
      if (strlen($url) || $component & self::PATH) $url .= '?';
      $url .= http_build_query($this->query);
    }
    if ($component & self::FRAGMENT && $this->fragment)
    {
      if (strlen($url)) $url .= '#';
      $url .= urlencode($this->fragment);
    }
    return $url;
  }
  
  /**
   * Returns TRUE, if using HTTPS protocol or FALSE otherwise.
   *
   * @return boolean
   * @access public
   */
  public function isSecured()
  {
    return (isset($this->scheme) && strtolower($this->scheme) == 'https');
  }
  
  /**
   * Set or unset HTTPS protocol.
   * If using some other protocol (ftp, mailto etc.) then it will be replaced with HTTP/HTTPS protocol.
   *
   * @param boolean $flag - sign of the HTTPS protocol.
   * @access public
   */
  public function secure($flag = true)
  {
    $this->scheme = $flag ? 'https' : 'http';
  }
  
  /**
   * Converts object of this class in string corresponding whole URL.
   * This method is equivalent to method build().
   *
   * @return string
   * @access public
   */
  public function __toString()
  {
    try
    {
      return $this->build();
    }
    catch (\Exception $e)
    {
      \Aleph::exception($e);
    }
  }
}