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
   * COMPONENT_ALL corresponds whole URL
   * COMPONENT_SOURCE corresponds user:password@my.host.com:8080
   * COMPONENT_QUERY corresponds var1=val1&var2=val2&var3=val3
   * COMPONENT_PATH corresponds /one/two/three
   * COMPONENT_PATH_AND_QUERY corresponds /one/two/three?var1=val1&var2=val2&var3=val3#fragment
   * For more information about URL structure, please see http://en.wikipedia.org/wiki/Uniform_resource_locator
   */
  const COMPONENT_ALL = 'all';
  const COMPONENT_SOURCE = 'source';
  const COMPONENT_QUERY = 'query';
  const COMPONENT_PATH = 'path';
  const COMPONENT_PATH_AND_QUERY = 'path&query';
  
  /**
   * Error templates, throwing by URL class.
   */
  const ERR_URL_1 = 'URL component "[{var}]" doesn\'t exist.';
  
  /**
   * Scheme Component of a URL.
   *
   * @var    string
   * @access public
   */
  public $scheme = null;

  /**
   * Source Component of a URL.
   * Source represents associative array of the following structure: array('port' => ..., 'host' => ..., 'user' => ..., 'pass' => ...)
   *
   * @var    array
   * @access public
   */
  public $source = array();

  /**
   * Path Component of a URL.
   * Path represents array of separate parts of URL path component.
   * E.g.: URL path /one/two/three will be represented as array('one', 'two', 'three').
   *
   * @var    array
   * @access public
   */
  public $path = array();

  /**
   * Query Component of a URL.
   * Query represents associative array in which keys and values are names and values of query fields.
   * E.g.: URL query ?var1=val1&var2=val2&var3=val3 will be represented as array('var1' => 'val1', 'var2' => 'val2', 'var3' => 'val3')
   *
   * @var    array
   * @access public
   */
  public $query = array();

  /**
   * Fragment Component of a URL.
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
    $this->parse($url === null ? self::current() : '');
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
    if (!isset($_SERVER)) return false;
    return ((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF']);
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
    $this->source = array();
    $data = explode('@', $arr[4][0]);
    if (!isset($data[1]))
    {
      $data = explode(':', $data[0]);
      $this->source['port'] = isset($data[1]) ? $data[1] : '';
      $this->source['host'] = urldecode($data[0]);
      $this->source['user'] = '';
      $this->source['pass'] = '';
    }
    else
    {
      $d1 = explode(':', $data[1]);
      $this->source['port'] = $d1[1];
      $this->source['host'] = urldecode($d1[0]);
      $d2 = explode(':', $data[0]);
      $this->source['pass'] = urldecode($d2[1]);
      $this->source['user'] = urldecode($d2[0]);
    }
    $this->path = ($arr[5][0] != '') ? array_values(array_filter(explode('/', $arr[5][0]), function($el){return $el != '';})) : array();
    foreach ($this->path as &$part) $part = urldecode($part);
    $this->query = urldecode($arr[7][0]);
    parse_str($this->query, $this->query);
    $this->fragment = urldecode($arr[9][0]);
  }
  
  /**
   * Returns URL or a part of URL.
   * E.g. URL http://user:pass@my.host.com/some/path?p1=v1&p2=v2#frag
   * <code>
   * $url = new URL('http://user:pass@my.host.com/some/path?p1=v1&p2=v2#frag');
   * echo $url->build();                              // shows whole URL
   * echo $url->build(URL::COMPONENT_SOURCE);         // shows user:pass@my.host.com
   * echo $url->build(URL::COMPONENT_PATH);           // shows /some/path
   * echo $url->build(URL::COMPONENT_QUERY);          // shows p1=v1&p2=v2
   * echo $url->build(URL::COMPONENT_PATH_AND_QUERY); // shows /some/path?p1=v1&p2=v2#frag
   * </code>
   *
   * @param string $component - name of an URL component.
   * @return string
   * @throws Aleph\Core\Exception with token ERR_URL_1 if $component is not matched with allowed components.
   * @access public
   */
  public function build($component = self::COMPONENT_ALL)
  {
    switch ($component)
    {
      case self::COMPONENT_ALL: 
        return $this->getURL();
      case self::COMPONENT_SOURCE:
        return $this->getSource();
      case self::COMPONENT_PATH_AND_QUERY:
        return $this->getPathAndQuery();
      case self::COMPONENT_PATH:
        return $this->getPath();
      case self::COMPONENT_QUERY:
        return $this->getQuery();
    }
    throw new Core\Exception('Aleph\Aleph', 'ERR_URL_1', $component);
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
   * Creates and returns URL string.
   *
   * @return string
   * @access public
   */
  public function getURL()
  {
    return (empty($this->scheme) ? '' : strtolower($this->scheme) . '://') . $this->getSource() . $this->getPathAndQuery();
  }
  
  /**
   * Returns the Source Component of a URL.
   *
   * @return string
   * @access public
   */
  public function getSource()
  {
    $source = array();
    foreach (array('host', 'port', 'user', 'pass') as $k) $source[$k] = isset($this->source[$k]) ? $this->source[$k] : '';
    $credentials = $source['user'] ? $source['user'] . ':' . $source['pass'] : '';
    return ($credentials ? $credentials . '@' : '') . $source['host'] . ($source['port'] ? ':' . $source['port'] : '');
  }

  /**
   * Returns the Path and Query Components of a URL.
   *
   * @return string
   * @access public
   */
  public function getPathAndQuery()
  {
    $query = $this->getQuery();
    return $this->getPath() . ($query ? '?' . $query : '') . (empty($this->fragment) ? '' : '#' . $this->fragment);
  }

  /**
   * Returns the Path Component of a URL.
   *
   * @return string
   * @access public
   */
  public function getPath()
  {
    $path = isset($this->path) ? (array)$this->path : array();
    foreach ($path as &$part) $part = urlencode($part);
    return '/' . implode('/', array_filter($path, function($el){return $el != '';}));
  }

  /**
   * Returns the Query Component of a URL.
   *
   * @return string
   * @access public
   */
  public function getQuery()
  {
    return isset($this->query) ? http_build_query((array)$this->query) : '';
  }
  
  /**
   * Converts object of this class in string corresponding whole URL.
   * This method is equivalent to method getURL().
   *
   * @return string
   * @access public
   */
  public function __toString()
  {
    try
    {
      return $this->getURL();
    }
    catch (\Exception $e)
    {
      \Aleph::exception($e);
    }
  }
}