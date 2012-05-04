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
 * @version 1.0.0
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
    throw new Core\Exception('Aleph\Core\Aleph', 'ERR_URL_1', $component);
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
    return ((isset($this->scheme) && $this->scheme != '') ? strtolower($this->scheme) . '://' : '') . $this->getSource() . $this->getPathAndQuery();
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
    return $this->getPath() . ($query ? '?' . $query : '') . ((isset($this->fragment) && $this->fragment != '') ? '#' . $this->fragment : '');
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
      Core\Aleph::exception($e);
    }
  }
}

/**
 * Request Class provides easier interaction with variables of the current HTTP request.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.net
 */
class Request
{
  /**
   * Method of the current HTTP request.
   *
   * @var string $method
   * @access public
   */
  public $method = null;
  
  /**
   * Avaliable header array of the current HTTP-request.
   *
   * @var array $headers
   * @access public
   */
  public $headers = array();
  
  /**
   * Raw input data of the current HTTP-request.
   *
   * @var string $body
   * @access public
   */
  public $body = null;
  
  /**
   * Data from $_GET, $_POST and $_FILES arrays. 
   *
   * @var array $data
   * @access public
   */
  public $data = array();
  
  /**
   * AJAX request indicator.
   * If the current HTTP request is AJAX this property is TRUE and FALSE otherwise.
   *
   * @var boolean $isAjax
   * @access public
   */
  public $isAjax = null;
  
  /**
   * IP address of requesting client.
   *
   * @var string $ip
   * @access public
   */
  public $ip = null;
  
  /**
   * Aleph\Net\URL object appropriate to the current requested URL.
   *
   * @var Aleph\Net\URL $url
   * @access public
   */
  public $url = null;

  /**
   * Constructor. Initializes all internal variables of the class.
   *
   * @access public
   */
  public function __construct()
  {
    if (function_exists('apache_request_headers')) $this->headers = apache_request_headers();
    else
    {
      foreach ($_SERVER as $key => $value) 
      {
        if (strpos($key, 'HTTP_') === 0) 
        {
          $this->headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
        }
      }
    }
    $this->method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : false;
    $this->body = file_get_contents('php://input');
    $this->data = array_merge($_GET, $_POST, $_FILES);
    $this->isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') : false;
    $this->ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
    $this->url = new URL();
  }
}

/**
 * Response Class provides easier interaction with variables of the current HTTP response and to build a new response.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.net
 */
class Response
{
  /**
   * Error message templates throwing by Response class.
   */
  const ERR_RESPONSE_1 = 'Invalid HTTP version. Must be only 1.0 or 1.1';
  const ERR_RESPONSE_2 = 'Cannot set response status. Status code "[{var}]" is not a valid HTTP response code.';

  /**
   * HTTP status codes.
   *
   * @var array $codes
   * @access protected
   */
  protected $codes = array(100 => 'Continue',
                           101 => 'Switching Protocols',
                           102 => 'Processing',
                           103 => 'Checkpoint',
                           122 => 'Request-URI too long',
                           200 => 'OK',
                           201 => 'Created',
                           202 => 'Accepted',
                           203 => 'Non-Authoritative Information',
                           204 => 'No Content',
                           205 => 'Reset Content',
                           206 => 'Partial Content',
                           207 => 'Multi-Status',
                           208 => 'Already Reported',
                           226 => 'IM Used',
                           300 => 'Multiple Choices',
                           301 => 'Moved Permanently',
                           302 => 'Found',
                           303 => 'See Other',
                           304 => 'Not Modified',
                           305 => 'Use Proxy',
                           306 => 'Switch Proxy',
                           307 => 'Temporary Redirect',
                           308 => 'Resume Incomplete',
                           400 => 'Bad Request',
                           401 => 'Unauthorized',
                           402 => 'Payment Required',
                           403 => 'Forbidden',
                           404 => 'Not Found',
                           405 => 'Method Not Allowed',
                           406 => 'Not Acceptable',
                           407 => 'Proxy Authentication Required',
                           408 => 'Request Timeout',
                           409 => 'Conflict',
                           410 => 'Gone',
                           411 => 'Length Required',
                           412 => 'Precondition Failed',
                           413 => 'Request Entity Too Large',
                           414 => 'Request-URI Too Long',
                           415 => 'Unsupported Media Type',
                           416 => 'Requested Range Not Satisfiable',
                           417 => 'Expectation Failed',
                           418 => 'I\'m a teapot',
                           420 => 'Enhance Your Calm',
                           422 => 'Unprocessable Entity',
                           423 => 'Locked',
                           424 => 'Failed Dependency',
                           425 => 'Unordered Collection',
                           426 => 'Upgrade Required',
                           428 => 'Precondition Required',
                           429 => 'Too Many Requests',
                           431 => 'Request Header Fields Too Large',
                           444 => 'No Response',
                           449 => 'Retry With',
                           450 => 'Blocked by Windows Parental Controls',
                           499 => 'Client Closed Request',
                           500 => 'Internal Server Error',
                           501 => 'Not Implemented',
                           502 => 'Bad Gateway',
                           503 => 'Service Unavailable',
                           504 => 'Gateway Timeout',
                           505 => 'HTTP Version Not Supported',
                           506 => 'Variant Also Negotiates',
                           507 => 'Insufficient Storage',
                           508 => 'Loop Detected',
                           509 => 'Bandwidth Limit Exceeded',
                           510 => 'Not Extended',
                           511 => 'Network Authentication Required',
                           598 => 'Network read timeout error',
                           599 => 'Network connect timeout error');
  
  /**
   * Array of HTTP response headers.
   *
   * @var array $headers
   * @access protected
   */
  protected $headers = array();
  
  /**
   * Version of HTTP protocol.
   *
   * @var string $version
   * @access public
   */
  public $version = '1.1';
  
  /**
   * Status of HTTP response.
   *
   * @var integer $status
   * @access public
   */
  public $status = 200;
  
  /**
   * Body of HTTP resposne.
   *
   * @var string $body
   * @access public
   */
  public $body = null;
  
  /**
   * Constructor. Initializes all internal variables of the class.
   *
   * @access public
   */
  public function __construct()
  {
    if (function_exists('apache_response_headers')) $this->headers = apache_response_headers();
    else
    {
      foreach (headers_list() as $header) 
      {
        $header = explode(':', $header);
        $this->headers[array_shift($header)] = trim(implode(':', $header));
      }
    } 
  }
  
  /**
   * Returns an HTTP status message by its code. 
   * If such message doesn't exist the method returns FALSE.
   * 
   * @param integer $status
   * @return string | boolean
   * @access public
   */
  public function getMessage($status)
  {
    return isset($this->codes[$status]) ? $this->codes[$status] : false;
  }
  
  /**
   * Sets an HTTP header.
   *
   * @param string $name - header name
   * @param string $value - new header value
   * @access public
   */
  public function setHeader($name, $value)
  {
    $this->headers[$name] = $value;
  }
  
  /**
   * Returns value of an HTTP header.
   *
   * @param string $name - HTTP headr name.
   * @return string | boolean - return FALSE if a such header doesn't exist.
   */
  public function getHeader($name)
  {
    return isset($this->headers[$name]) ? $this->headers[$name] : false;
  }
  
  /**
   * Removes an HTTP header by its name.
   *
   * @param string $name - HTTP header name.
   * @access public
   */
  public function removeHeader($name)
  {
    unset($this->headers[$name]);
  }
  
  /**
   * Sets array of all HTTP headers.
   *
   * @param array $headers - new HTTP headers.
   * @access public
   */
  public function setHeaders(array $headers)
  {
    $this->headers = $headers;
  }
  
  /**
   * Returns array of all HTTP headers.
   *
   * @return array
   * @access public
   */
  public function getHeaders()
  {
    return $this->headers;
  }
  
  /**
   * Clears all variables of the current HTTP response.
   * This method removes all HTTP headers and body, sets HTTP protocol version to '1.1' and status code to 200.
   *
   * @return self
   * @access public
   */
  public function clear()
  {
    $this->headers = array();
    $this->version = '1.1';
    $this->status = 200;
    $this->body = null;
  }
  
  /**
   * Sets cache expire for a response.
   * If cache expire is FALSE or equals 0 then no cache will be set.
   * 
   * @param integer | boolean $expires - new cache expire time in seconds.
   * @access public   
   */
  public function cache($expires) 
  {
    if ($expires === false || (int)$expires <= 0) 
    {
      $this->headers['Expires'] = 'Thu, 19 Nov 1981 08:52:00 GMT';
      $this->headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0';
      $this->headers['Pragma'] = 'no-cache';
    }
    else
    {
      $expires = (int)$expires;
      $this->headers['Expires'] = gmdate('D, d M Y H:i:s', $expires) . ' GMT';
      $this->headers['Cache-Control'] = 'max-age=' . ($expires - time());
    }
  }
  
  /**
   * Performs an HTTP redirect. Execution of this method halts the script execution. 
   *
   * @param string $url - redirect URL.
   * @param integer $statuc - redirect HTTP status code.
   * @access public
   */
  public function redirect($url, $status = 302)
  {
    $this->status = (int)$status;
    $this->headers['Location'] = $url;
    $this->send();
  }
  
  /**
   * Stops the script execution with some message and HTTP status code.
   *
   * @param integer $status - status of an HTTP response.
   * @param string $message - some text.
   * @access public
   */
  public function stop($status = 500, $message = null)
  {
    if ($message !== null) $this->body = $message;
    $this->status = (int)$status;
    $this->send();
  }
  
  /**
   * Stops script execution and sends all HTTP response headers.
   *
   * @throws Aleph\Core\Exception with token ERR_RESPONSE_1 if new version value doesn't equal '1.0' or '1.1'
   * @throws Aleph\Core\Exception with token ERR_RESPONSE_2 if a such status code doesn't exist.
   * @access public
   */
  public function send()
  {  
    if (!isset($this->version)) $this->version = '1.1';
    if (!isset($this->status)) $this->status = 200;
    if (!isset($this->body)) $this->body = '';
    $isBody = ($this->status < 100 || $this->status >= 200) && 
              $this->status != 204 && 
              $this->status != 304 && 
              isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] != 'HEAD';
    if (!headers_sent()) 
    {
      if ($this->version != '1.0' && $this->version != '1.1') throw new Core\Exception($this, 'ERR_RESPONSE_1');
      if (!isset($this->codes[$this->status])) throw new Core\Exception($this, 'ERR_RESPONSE_2', $this->status);
      $headers = $this->headers;
      if (!$isBody)
      {
        unset($headers['Content-Type']);
        unset($headers['Content-Length']);
      }
      if (substr(PHP_SAPI, 0, 3) === 'cgi') header('Status: ' . $this->status . ' ' . $this->codes[$this->status]);
      else header('HTTP/' . $this->version . ' ' . $this->status . ' ' . $this->codes[$this->status]);
      foreach ($headers as $name => $value) header($name . ': ' . $value);
    }
    if ($isBody) echo $this->body;
    exit;
  }
}

namespace Aleph\Cache;

use Aleph\Core;

/**
 * Base abstract class for building of classes that intended for caching different data.
 *
 * @abstract
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.cache
 */
abstract class Cache implements \ArrayAccess, \Countable
{
  /**
   * The vault key of all cached data.  
   *
   * @var string $vaultKey
   * @access private
   */
  private $vaultKey = null;
  
  /**
   * If this property equals TRUE saveKey and removeKey methods cannot be invoked.
   * 
   * @var boolean $lock
   * @access private
   */
  private $lock = false;
  
  /**
   * The vault lifetime. Defined as 1 year by default.
   *
   * @var integer $vaultLifeTime - given in seconds.
   * @access protected
   */
  protected $vaultLifeTime = 31536000; // 1 year
  
  /**
   * Constructor of the class.
   *
   * @access public
   */
  public function __construct()
  {
    $this->vaultKey = 'vault_' . Core\Aleph::getSiteUniqueID();
  }
  
  /**
   * Checks whether the current type of cache is available or not.
   *
   * @return boolean
   * @access public
   * @static
   */
  public static function isAvailable()
  {
    return true;
  }
 
  /**
   * Conserves some data identified by a key into cache.
   *
   * @param string $key - a data key.
   * @param mixed $content - some data.
   * @param integer $expire - cache lifetime (in seconds).
   * @access public
   * @abstract
   */
  abstract public function set($key, $content, $expire);

  /**
   * Returns some data previously conserved in cache.
   *
   * @param string $key - a data key.
   * @return mixed
   * @access public
   * @abstract
   */
  abstract public function get($key);

  /**
   * Removes some data identified by a key from cache.
   *
   * @param string $key - a data key.
   * @access public
   * @abstract
   */
  abstract public function remove($key);

  /**
   * Checks whether the cache lifetime is expired or not.
   *
   * @param string $key - a data key.
   * @return boolean
   * @access public
   * @abstract
   */
  abstract public function isExpired($key);

  /**
   * Removes all previously conserved data from cache.
   *
   * @access public
   * @abstract
   */
  abstract public function clean();
  
  /**
   * Returns the number of data items previously conserved in cache.
   *
   * @return integer
   * @access public
   */
  public function count()
  {
    $vault = $this->getVault();
    return is_array($vault) ? count($vault) : 0;
  }
  
  /**
   * Checks whether a data item exists.
   *
   * @param string $key - a data key to check for.
   * @return boolean
   * @access public
   */
  public function offsetExists($key)
	 {
	   return ($this->isExpired($key) === false);
	 }
  
  /**
   * Returns the previously conserved data from cache.
   *
   * @param string $key - a data key.
   * @return mixed
   * @access public
   */
	 public function offsetGet($key)
	 {
		  return $this->get($key);
	 }

  /**
   * Conserves some data in cache. If the method is firstly invoked the cache lifetime value equals the value of the property "vaultLifeTime".
   *
   * @param string $key - a data key.
   * @param mixed $content
   * @access public
   */
 	public function offsetSet($key, $content)
	 {
    $vault = $this->getVault();
    $hash = md5($key);
    $expire = isset($vault[$hash]) ? $vault[$hash] : $this->vaultLifeTime;
    $this->set($key, $content, $expire);
	 }

  /**
   * Removes a data item from cache.
   *
   * @param string $key - a data key.
   * @access public
   */
 	public function offsetUnset($key)
 	{
    $this->remove($key);
 	}
  
  /**
   * Returns the previously conserved data from cache.
   *
   * @param string $key - a data key.
   * @return mixed
   * @access public
   */
  public function __get($key)
  {
    return $this[$key];
  }
  
  /**
   * Conserves some data in cache. If the method is firstly invoked the cache lifetime value equals the value of the property "vaultLifeTime".
   *
   * @param string $key - a data key.
   * @param mixed $content
   * @access public
   */
  public function __set($key, $content)
  {
    $this[$key] = $content;
  }
  
  /**
   * Checks whether a data item exists.
   *
   * @param string $key - a data key to check for.
   * @return boolean
   * @access public
   */
  public function __isset($key)
  {
    return isset($this[$key]);
  }
  
  /**
   * Removes a data item from cache.
   *
   * @param string $key - a data key.
   * @access public
   */
  public function __unset($key)
  {
    unset($this[$key]);
  }
  
  /**
   * Returns the vault of data keys conserved in cache before.
   *
   * @return array - returns a key array or NULL if empty.
   * @access public
   */
  public function getVault()
  {
    return $this->get($this->vaultKey);
  }
  
  /**
   * Returns the vault lifetime.
   *
   * @return integer
   * @access public
   */
  public function getVaultLifeTime()
  {
    return $this->vaultLifeTime;
  }
  
  /**
   * Sets the vault lifetime.
   *
   * @param integer $vaultLifeTime - new vault lifetime in seconds.
   * @access public
   */
  public function setVaultLifeTime($vaultLifeTime)
  {
    $this->vaultLifeTime = abs((int)$vaultLifeTime);
  }
  
  /**
   * Saves the key of caching data in the key vault.
   *
   * @param string $key - a key to save.
   * @param integer $expire - cache lifetime of data defined by the key.
   * @access protected
   */
  protected function saveKey($key, $expire)
  {
    if ($this->lock) return;
    $this->lock = true;
    $vault = $this->getVault();
    $vault[md5($key)] = array($key, abs((int)$expire));
    $this->set($this->vaultKey, $vault, $this->vaultLifeTime);
    $this->lock = false;
  }
  
  /**
   * Removes the key from the key vault.
   *
   * @param string $hash - a previoulsy saved key of cached data.
   * @access protected
   */
  protected function removeKey($key)
  {
    if ($this->lock) return;
    $this->lock = true;
    $vault = $this->getVault();
    unset($vault[md5($key)]);
    $this->set($this->vaultKey, $vault, $this->vaultLifeTime);
    $this->lock = false;
  }
}

/**
 * The class is intended for caching of different data using the file system.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.cache
 */
class File extends Cache
{  
  const ERR_CACHE_FILE_1 = 'Cache directory "[{var}]" is not writable.';

  /**
   * The directory in which cache files will be stored.
   *
   * @var string $dir
   * @access private   
   */
  private $dir = null;
   
  /**
   * Sets new directory for storing of cache files.
   * If this directory doesn't exist it will be created.
   *
   * @param string $path
   * @access public
   */
  public function setDirectory($path = null)
  {
    $dir = Core\Aleph::dir($path ?: 'cache');
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    if (!is_writable($dir)) throw new \Exception(Core\Aleph::error($this, 'ERR_CACHE_FILE_1', $dir));
    $this->dir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
  }
  
  /**
   * Returns the current cache directory.
   *
   * @return string
   * @access public
   */
  public function getDirectory()
  {
    return $this->dir;
  }
   
  /**
   * Conserves some data identified by a key into cache.
   *
   * @param string $key - a data key.
   * @param mixed $content - some data.
   * @param integer $expire - cache lifetime (in seconds).
   * @access public
   */  
  public function set($key, $content, $expire)
  {          
    if (!$this->dir) $this->setDirectory();
    $expire = abs((int)$expire);  
    $file = $this->dir . md5($key);
    file_put_contents($file, serialize($content));
    file_put_contents($file . '_expire', $expire);
    $this->saveKey($key, $expire);
  }

  /**
   * Returns some data perviously conserved in cache.
   *
   * @param string $key - a data key.
   * @return mixed
   * @access public
   */
  public function get($key)
  {                    
    if (!$this->dir) $this->setDirectory();
    $file = $this->dir . md5($key);
    if (is_file($file)) return unserialize(file_get_contents($file));
  }

  /**
   * Checks whether cache lifetime is expired or not.
   *
   * @param string $key - a data key.
   * @return boolean
   * @access public
   */
  public function isExpired($key)
  {                      
    if (!$this->dir) $this->setDirectory();
    $file = $this->dir . md5($key);
    if (!is_file($file))
    {
      if (is_file($file . '_expire')) unlink($file . '_expire');
      $this->removeKey($key);
      return true;
    }
    if (!is_file($file . '_expire')) 
    {
      if (is_file($file)) unlink($file);
      $this->removeKey($key);
      return true;
    }
    if (!($mtime = filemtime($file))) return true;      
    if ($mtime + (int)file_get_contents($file . '_expire') < time())
    {
      unlink($file);
      unlink($file . '_expire');
      $this->removeKey($key);
      return true;
    }
    return false;
  }

  /**
   * Removes some data identified by a key from cache.
   *
   * @param string $key - a data key.
   * @access public
   */
  public function remove($key)
  {             
    if (!$this->dir) $this->setDirectory();
    $file = $this->dir . md5($key);
    if (is_file($file)) unlink($file);
    if (is_file($file . '_expire')) unlink($file . '_expire');
    $this->removeKey($key);
  }
  
  /**
   * Removes all previously conserved data from cache.
   *
   * @access public
   */
  public function clean()
  {
    if (!$this->dir) $this->setDirectory();
    foreach (scandir($this->dir) as $file)
    {
      if ($file == '.' || $file == '..') continue; 
      $item = $this->dir . '/' . $file;
      if (is_file($item)) unlink($item);
    }
  }
   
  /**
   * Garbage collector that should be used for removing of expired cache data.
   *
   * @access public
   */
  public function gc()
  {
    if (!$this->dir) $this->setDirectory();
    foreach (scandir($this->dir) as $item)
    {
      if ($item == '..' || $item == '.' || substr($item, -7) == '_expire') continue;
      $file = $this->dir . $item;
      if (!is_file($file . '_expire'))
      {
        unlink($file);
        $this->removeKey($file);
        continue;
      }
      if (!($mtime = filemtime($file)) || $mtime + (int)file_get_contents($file . '_expire') < time())
      {
        unlink($file);
        unlink($file . '_expire');
        $this->removeKey($file);
      }
    }
  }
}

namespace Aleph\Core;

use Aleph\Cache,
    Aleph\Net;

/**
 * IDelegate interface is intended for building of Aleph framework callbacks.
 * Using such classes we can transform strings in certain format into callback if necessary. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.core
 */
interface IDelegate
{
  /**
   * Constructor. The only argument of it is string in Aleph framework format.
   * The following formats are possible:
   * 'function' - invokes a global function with name 'function'.
   * '->method' - invokes a method 'method' of object Aleph\Core\Aleph. 
   * '::method' - invokes a static method 'method' of class Aleph\Core\Aleph.
   * 'class::method' - invokes a static method 'method' of a class 'class'.
   * 'class->method' - invokes a method 'method' of a class 'class' with its constructor without arguments.
   * 'class[]' - creates an object of a class 'class' without sending any arguments in its constructor.
   * 'class[]::method' - the same as 'class::method'.
   * 'class[]->method' - the same as 'class->method'.
   * 'class[n]' - creates an object of a class 'class' with its constructor taking n arguments.
   * 'class[n]::method' - the same as 'class::method'.
   * 'class[n]->method' - invokes a method 'method' of a class 'class' with its constructor taking n arguments.
   *
   * @param string $callback - an Aleph framework callback string.
   * @access public
   */
  public function __construct($callback);
  
  /**
   * The magic method allowing to invoke an object of this class as a method.
   * The method can take different number of arguments.
   *
   * @return mixed
   * @access public
   */
  public function __invoke();
  
  /**
   * The magic method allowing to convert an object of this class to a callback string.
   *
   * @return string
   * @access public
   */
  public function __toString();
  
  /**
   * Invokes a callback's method or creating a class object.
   * For callbacks in format 'class[n]' and 'class[n]->method' first n arguments of the method
   * are arguments of constructor of a class 'class'.
   *
   * @param array $args - array of method arguments.
   * @return mixed
   * @access public
   */
  public function call(array $args = null);
  
  /**
   * Checks whether it is possible according to permissions to invoke this delegate or not.
   * Permission can be in the following formats:
   * - class name with method name: class::method, class->method
   * - class name without any methods.
   * - function name.
   * - namespace.
   * The method returns TRUE if a callback matches with one or more permissions and FALSE otherwise.
   *
   * @param string | array $permissions - permissions to check.
   * @return boolean
   * @access public
   */
  public function in($permissions);
  
  /**
   * Returns array of detail information of a callback.
   * Output array has the format array('class' => ... [string] ..., 
   *                                   'method' => ... [string] ..., 
   *                                   'static' => ... [boolean] ..., 
   *                                   'numargs' => ... [integer] ..., 
   *                                   'type' => ... [string] ...)
   *
   * @return array
   * @access public
   */
  public function getInfo();
}

/**
 * With this class you can transform a string in certain format into a method or function invoking.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.core
 */
class Delegate implements IDelegate
{
  /**
   * A string in the Aleph callback format.
   *
   * @var string $callback
   * @access protected
   */
  protected $callback = null;
  
  /**
   * Full name (class name with namespace) of a callback class.
   *
   * @var string $class
   * @access protected
   */
  protected $class = null;
  
  /**
   * Method name of a callback class.
   *
   * @var string $method
   * @access protected
   */
  protected $method = null;
  
  /**
   * Equals TRUE if a callback method is static and FALSE if it isn't.
   *
   * @var boolean $static
   * @access protected
   */
  protected $static = null;
  
  /**
   * Shows number of callback arguments that should be transmited into callback constructor.
   *
   * @var integer $numargs
   * @access protected
   */
  protected $numargs = null;
  
  /**
   * Can be equal 'function' or 'class' according to callback format.
   *
   * @var string $type
   * @access protected
   */
  protected $type = null;

  /**
   * Constructor. The only argument of it is string in Aleph framework format.
   * The following formats are possible:
   * 'function' - invokes a global function with name 'function'.
   * '->method' - invokes a method 'method' of object Aleph\Core\Aleph. 
   * '::method' - invokes a static method 'method' of class Aleph\Core\Aleph.
   * 'class::method' - invokes a static method 'method' of a class 'class'.
   * 'class->method' - invokes a method 'method' of a class 'class' with its constructor without arguments.
   * 'class[]' - creates an object of a class 'class' without sending any arguments in its constructor.
   * 'class[]::method' - the same as 'class::method'.
   * 'class[]->method' - the same as 'class->method'.
   * 'class[n]' - creates an object of a class 'class' with its constructor taking n arguments.
   * 'class[n]::method' - the same as 'class::method'.
   * 'class[n]->method' - invokes a method 'method' of a class 'class' with its constructor taking n arguments.
   *
   * @param string $callback - an Aleph framework callback string.
   * @access public
   */
  public function __construct($callback)
  {
    preg_match('/^([^\[:-]*)(\[([^\]]*)\])?(::|->)?([^:-]*)$/', $callback, $matches);
    if ($matches[4] == '' && $matches[2] == '')
    {
      $this->type = 'function';
      $this->method = $matches[1];
    }
    else
    {
      $this->type = 'class';
      $this->class = $matches[1] ?: 'Aleph\Core\Aleph';
      $this->numargs = (int)$matches[3];
      $this->static = ($matches[4] == '::');
      $this->method = $matches[5];
    }
    $this->callback = $callback;
  } 
  
  /**
   * The magic method allowing to convert an object of this class to a callback string.
   *
   * @return string
   * @access public
   */
  public function __toString()
  {
    return $this->callback;
  }
  
  /**
   * The magic method allowing to invoke an object of this class as a method.
   * The method can take different number of arguments.
   *
   * @return mixed
   * @access public
   */
  public function __invoke()
  {
    return $this->call(func_get_args());
  }
  
  /**
   * Invokes a callback's method or creating a class object.
   * For callbacks in format 'class[n]' and 'class[n]->method' first n arguments of the method
   * are arguments of constructor of a class 'class'.
   *
   * @param array $args - array of method arguments.
   * @return mixed
   * @access public
   */
  public function call(array $args = null)
  {
    $args = (array)$args;
    if ($this->type == 'function') return call_user_func_array($this->method, $args);
    else
    {
      if ($this->static) return call_user_func_array(array($this->class, $this->method), $args);
      if ($this->class == 'Aleph\Core\Aleph') $class = Aleph::instance();
      else
      {
        $class = new \ReflectionClass($this->class);
        if ($this->numargs == 0) $class = $class->newInstance();
        else $class = $class->newInstanceArgs(array_splice($args, 0, $this->numargs));
      }
      if ($this->method == '') return $class;
      return call_user_func_array(array($class, $this->method), $args);
    }
  }

  /**
   * Checks whether it is possible according to permissions to invoke this delegate or not.
   * Permission can be in the following formats:
   * - class name with method name: class::method, class->method
   * - class name without any methods.
   * - function name.
   * - namespace.
   * The method returns TRUE if a callback matches with one or more permissions and FALSE otherwise.
   *
   * @param string | array $permissions - permissions to check.
   * @return boolean
   * @access public
   */
  public function in($permissions)
  {
    if ($this->type == 'function')
    {
      $m = $this->split($this->method);
      foreach ((array)$permissions as $permission)
      {
        $p = $this->split($permission);
        if ($p[0] != '' && $m == $p || $p[0] == '' && $m[1] == $p[1]) return true;
      }
    }
    else if ($this->type == 'class')
    {
      $m = $this->split($this->class);
      foreach ((array)$permissions as $permission)
      {
        $info = explode($this->static ? '::' : '->', $permission);
        if (isset($info[1]) && $info[1] != '' && $info[1] != $this->method) continue;
        $p = $this->split($info[0]);
        if ($p[0] != '' && $m == $p || $p[0] == '' && $m[1] == $p[1]) return true;
      }
    }
    return false;
  }
  
  /**
   * Returns array of detail information of a callback.
   * Output array has the format array('class' => ... [string] ..., 
   *                                   'method' => ... [string] ..., 
   *                                   'static' => ... [boolean] ..., 
   *                                   'numargs' => ... [integer] ..., 
   *                                   'type' => ... [string] ...)
   *
   * @return array
   * @access public
   */
  public function getInfo()
  {
    return array('class' => $this->class, 
                 'method' => $this->method,
                 'static' => $this->static,
                 'numargs' => $this->numargs,
                 'type' => $this->type);
  } 
  
  /**
   * Splits full class name on two part: namespace and proper class name. Method returns these parts as an array.
   *
   * @param string $identifier - full class name.
   * @return array
   * @access protected
   */
  protected function split($identifier)
  {
    $ex = explode('\\', $identifier);
    return array(array_pop($ex), implode('', $ex));
  }
}

/**
 * Exception allows to generate exceptions with parameterized error messages which possible to get by their tokens.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.core
 */
class Exception extends \Exception
{
  /**
   * Name of a error message constant.
   * 
   * @var string $token
   * @access protected
   */
  protected $token = null;
  
  /**
   * Class name in which there is a constant of exception message.
   *
   * @var string $class
   * @access protected
   */
  protected $class = null;

  /**
   * Constructor. Constructor takes as arguments a class name with the error constant,
   * the error token (constant name) and values of parameters ​​of the error message.
   *
   * @param string $class
   * @param string $token
   * @params parameters of the error message.
   * @access public
   */
  public function __construct(/* $class, $token, $var1, $var2, ... */)
  {
    $this->class = func_get_arg(0);
    $this->token = func_get_arg(1);
    parent::__construct(call_user_func_array(array('Aleph\Core\Aleph', 'error'), func_get_args()));
  }
  
  /**
   * Returns class name in which there is a error message constant.
   *
   * @return string
   * @access public
   */
  public function getClass()
  {
    return $this->class;
  }
  
  /**
   * Returns token of the error message.
   *
   * @return string
   * @access public
   */
  public function getToken()
  {
    return $this->token;
  }
}

/**
 * General class of the framework.
 * With this class you can log error messages, profile your code, catche any errors, 
 * load classes, configure your application. Also this class allows routing and 
 * can be stroting any global objects. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.core
 * @final
 */
final class Aleph implements \ArrayAccess
{
  /**
   * Bug and debug templates.
   */
  const TEMPLATE_DEBUG = '<!doctype html><html><head><meta content="text/html; charset=UTF-8" http-equiv="Content-Type" /><title>Bug Report</title><body bgcolor="gold">The following error <pre>[{message}]</pre> has been catched in file <b>[{file}]</b> on line [{line}]<br /><br />[{fragment}]<b style="font-size: 14px;">Stack Trace:</b><pre>[{stack}]</pre></body></html>';
  const TEMPLATE_BUG = 'Sorry, server is not available at the moment. Please wait. This site will be working very soon!';
  
  /**
   * Error message templates throwing by Aleph class.
   */
  const ERR_GENERAL_1 = 'Class "[{var}]" is not found.';
  const ERR_GENERAL_2 = 'Method "[{var}]" of class "[{var}]" doesn\'t exist.';
  const ERR_GENERAL_3 = 'Property "[{var}]" of class "[{var}]" doesn\'t exist.';
  const ERR_GENERAL_4 = 'Autoload callback can only be Aleph callback (string value), Closure object or Aleph\Core\IDelegate instance.';
  const ERR_GENERAL_5 = 'Class "[{var}]" found in file "[{var}]" is duplicated in file "[{var}]".';
  const ERR_CONFIG_1 = 'File "[{var}]" is not correct ini file.';

  /**
   * The instance of this class.
   *
   * @var private $instance
   * @access private
   * @static
   */
  private static $instance = null;
  
  /**
   * Unique ID of the application (site).
   *
   * @var private $siteUniqueID
   * @access private
   * @static
   */
  private static $siteUniqueID = null;
  
  /**
   * Path to site root directory.
   *
   * @var string $root
   * @access private
   * @static
   */
  private static $root = null;
  
  /**
   * Array of timestamps.
   *
   * @var array @time
   * @access private
   * @static
   */
  private static $time = array();
  
  /**
   * Response body.
   *
   * @var string $output
   * @access private
   * @static
   */
  private static $output = null;
  
  /**
   * Array with information about some code that was executed by the operator eval.
   *
   * @var array $eval
   * @access private
   * @static
   */
  private static $eval = array();
  
  /**
   * Array of different global objects.
   *
   * @var array $registry
   * @access private
   * @static
   */
  private static $registry = array();
  
  /**
   * Marker of error handling.
   *
   * @var boolean $debug
   * @access private
   * @static
   */
  private static $debug = false;
  
  /**
   * Instance of the class Aleph\Cache\Cache (or its child).
   *
   * @var Aleph\Cache\Cache $cache
   * @access private
   */
  private $cache = null;
  
  /**
   * Instance of the class Aleph\Net\Request.
   *
   * @var Aleph\Net\Request $request
   * @access private
   */
  private $request = null;
  
  /**
   * Instance of the class Aleph\Net\Response.
   * 
   * @var Aleph\Net\Response $response
   * @access private
   */
  private $response = null;
  
  /**
   * Array of configuration variables.
   *
   * @var array $config
   * @access private  
   */
  private $config = array();
  
  /**
   * Array of paths to all classes of the applcation and framework.
   *
   * @var array $classes
   * @access private
   */
  private $classes = array();
  
  /**
   * Array of paths to classes to exclude them from the class searching.
   *
   * @var array $exclusions
   * @access private  
   */
  private $exclusions = array();
  
  /**
   * Direcotires for class searching.
   *
   * @var array $dirs
   * @access private
   */
  private $dirs = array();
  
  /**
   * Array of actions for the routing.
   * 
   * @var array $acts   
   */
  private $acts = array('methods' => array(), 'actions' => array());
  
  /**
   * File search mask.
   *
   * @var string $mask
   * @access private
   */
  private $mask = null;
  
  /**
   * Cache key for storing of paths to including classes.
   *
   * @var string $key
   * @access private
   */
  private $key = null;
  
  /**
   * Autoload callback. Can be a closure, an instance of Aleph\Core\IDelegate or a string in Aleph callback format.
   *
   * @var string | closure | Aleph\Core\IDelegate
   * @access private
   */
  private $alCallBack = null;
  
  /**
   * Returns an instance of this class.
   *
   * @return self
   * @access public
   * @static
   */
  public static function getInstance()
  {
    return self::$instance;
  }
  
  /** 
   * Returns array of all previously stored global objects.
   *
   * @return array
   * @static
   */
  public static function all()
  {
    return self::$registry;
  }
  
  /**
   * Returns a global object by its key.
   *
   * @param string $key - key of a global object.
   * @return mixed
   * @access public
   * @static
   */
  public static function get($key)
  {
    return isset(self::$registry[$key]) ? self::$registry[$key] : null;
  }
  
  /**
   * Stores a global object.
   *
   * @param string $key - key of a global object.
   * @param mixed $value - value of a global object.
   * @access public
   */
  public static function set($key, $value)
  {
    self::$registry[$key] = $value;
  }
  
  /**
   * Checks whether an global object exist or not.
   *
   * @param string $key - key of a global object.
   */
  public static function has($key)
  {
    return isset(self::$registry[$key]);
  }
  
  /**
   * Removes a global object from the storage.
   *
   * @param string $key - key of a global object.
   * @access public
   * @static
   */
  public static function remove($key)
  {
    unset(self::$registry[$key]);
  }
  
  /**
   * Sets value of the response body.
   *
   * @param string $output - new response body
   * @access public
   * @static
   */
  public static function setOutput($output)
  {
    self::$output = $output;
  }
  
  /**
   * Returns value of the response body.
   *
   * @return string
   * @access public
   * @static
   */
  public static function getOutput()
  {
    return self::$output;
  }
  
  /**
   * Returns site root directory.
   *
   * @return string
   * @access public
   * @static
   */
  public static function getRoot()
  {
    return self::$root;
  }
  
  /**
   * Sets start time point for some code part.
   *
   * @param string $key - time mark for some code part.
   * @access public
   * @static
   */
  public static function pStart($key)
  {
    self::$time[$key] = microtime(true);
  }
  
  /**
   * Returns execution time of some code part by its time mark.
   * If a such time mark doesn't exit then the method return false.
   *
   * @param string $key - time mark of some code part.
   * @return boolean | float
   * @static
   */
  public static function pStop($key)
  {
    if (!isset(self::$time[$key])) return false;
    return number_format(microtime(true) - self::$time[$key], 6);
  }

  /**
   * Returns the amount of memory, in bytes, that's currently being allocated to your PHP script.
   *
   * @return integer
   * @access public
   * @static
   */
  public static function getMemoryUsage()
  {
    return memory_get_usage(true);
  }
  
  /**
   * Returns the peak of memory, in bytes, that's been allocated to your PHP script.
   *
   * @return integer
   * @access public
   * @static
   */
  public static function getPeakMemoryUsage()
  {
    return memory_get_peak_usage(true);
  }
  
  /**
   * Returns the execution time (in seconds) of your PHP script. 
   *
   * @return integer
   * @access public
   * @static
   */
  public static function getExecutionTime()
  {
    return self::pStop('script_execution_time');
  }
  
  /**
   * Returns the request time (in seconds) of your PHP script. 
   *
   * @return integer
   * @access public
   * @static
   */
  public static function getRequestTime()
  {
    return number_format(microtime(true) - $_SERVER['REQUEST_TIME'], 6);
  }
  
  /**
   * Returns the unique ID of your application (site).
   *
   * @return string
   * @access public
   * @static
   */
  public static function getSiteUniqueID()
  {
    return self::$siteUniqueID;
  }
  
  /**
   * Creates and executes a delegate.
   *
   * @param string $callback - the Aleph callback string.
   * @params arguments of the callback.
   * @return mixed
   * @access public
   * @static
   */
  public static function delegate(/* $callback, $arg1, $arg2, ... */)
  {
    $params = func_get_args();
    $method = array_shift($params);
    if ($method instanceof \Closure) return call_user_func_array($method, $params);
    return foo(new Delegate($method))->call($params);
  }
  
  /**
   * Returns an error message by its token.
   *
   * @param string $class - class with the needed error message constant.
   * @param string $token - name of the needed error message constant.
   * @params values of parameters of the error message.
   * @return string
   * @access public
   * @static
   */
  public static function error(/* $class, $token, $var1, $var2, ... */)
  {
    $params = func_get_args();
    $class = array_shift($params);
    $class = is_object($class) ? get_class($class) : $class;
    $token = array_shift($params);
    $err = $token;
    if ($class != '')
    {
      $err = constant($class . '::' . $token);
      $token = $class . '::' . $token;
    }
    foreach ($params as $value)
    { 
      $err = preg_replace('/\[{var}\]/', $value, $err, 1);
    }
    return $class ? $err . ' (Token: ' . $token . ')' : $err;
  }
 
  /**
   * Collects and stores information about some eval's code. 
   *
   * @param string $code - the code that will be executed by eval operator.
   * @return string
   * @access public
   * @static
   */
  public static function ecode($code)
  {
    $e = new \Exception();
    self::$eval['trace'] = $e->getTrace();
    self::$eval['traceAsString'] = $e->getTraceAsString();
    self::$eval['rowcount'] = count(explode(PHP_EOL, isset(self::$eval['code']) ? self::$eval['code'] : ''));
    self::$eval['code'] = (isset(self::$eval['code']) ? self::$eval['code'] . PHP_EOL : '') . $code;
    return $code;
  }
  
  /**
   * Checks whether the error handing is set or not.
   *
   * @return boolean
   * @access public
   * @static
   */
  public static function isDebug()
  {
    return self::$debug;
  }
  
  /**
   * Enables and disables the debug mode.
   *
   * @param boolean $enable - if it equals TRUE then the debug mode is enabled and it is disabled otherwise.
   * @param integer $errorLevel - new error reporting level.
   * @access public
   * @static
   */
  public static function debug($enable = true, $errorLevel = null)
  {
    self::$debug = $enable;
    restore_error_handler();
    restore_exception_handler();
    if (!$enable)
    {
      error_reporting($errorLevel ?: ini_get('error_reporting'));
      return;
    }
    error_reporting($errorLevel ?: E_ALL | E_STRICT);
    set_exception_handler(array(__CLASS__, 'exception'));
    set_error_handler(function($errno, $errstr, $errfile, $errline)
    {
      throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }, $errorLevel);
  }
  
  /**
   * Set the debug output for an exception.
   *
   * @param \Exception $e
   * @param boolean $isFatalError
   * @access public
   * @static
   */
  public static function exception(\Exception $e, $isFatalError = false)
  {
    restore_error_handler();
    restore_exception_handler();
    $info = self::analyzeException($e);
    $info['isFatalError'] = $isFatalError;
    $config = (self::$instance !== null) ? self::$instance->config : array();
    $isDebug = isset($config['debugging']) ? (bool)$config['debugging'] : true;
    foreach (array('templateDebug', 'templateBug') as $var) $$var = isset($config[$var]) ? self::dir($config[$var]) : null;
    try
    {
      if (isset($config['logging']) && $config['logging'])
      {
        if (isset($config['customLogMethod']) && $config['customLogMethod'])
        {
          self::delegate($config['customLogMethod'], $info);
        }
        else
        {
          self::log($info);
        }
      }
    }
    catch (\Exception $e){}
    if ($isDebug && isset($config['customDebugMethod']) && $config['customDebugMethod'])
    {
      self::delegate($config['customDebugMethod'], $e, $info);
      return;
    }
    if (PHP_SAPI == 'cli')
    {
      if ($isDebug)
      {
        if ($isFatalError) $output = $info['fragment'];
        else $output = $info['message'] . PHP_EOL . $info['fragment'] . PHP_EOL . $info['stack'];
        self::$output = strip_tags(html_entity_decode($output));
      }
      else
      {
        self::$output = self::TEMPLATE_BUG;
      }
      return;
    }
    if ($isDebug)
    {
      $tmp = array();
      $info['stack'] = htmlspecialchars($info['stack']);
      foreach ($info as $k => $v) $tmp['[{' . $k . '}]'] = $v;
      $templateDebug = strtr((is_file($templateDebug) && is_readable($templateDebug)) ? file_get_contents($templateDebug) : self::TEMPLATE_DEBUG, $tmp);
      if (isset($_SESSION))
      {
        $hash = md5(microtime() . uniqid('', true));
        $_SESSION['__DEBUG_INFORMATION__'][$hash] = $templateDebug;
        $url = new Net\URL();
        $url->query['__DEBUG_INFORMATION__'] = $hash;
        self::go($url->getURL(), true, false);
      }
      else 
      {
        self::$output = $templateDebug;
      }
    }
    else
    {
      self::$output = (is_file($templateBug) && is_readable($templateBug)) ? file_get_contents($templateBug) : self::TEMPLATE_BUG;
    }
  }
  
  /**
   * Returns the full path to a directory specified by its alias.
   * 
   * @param string $dir - directory alias.
   * @return string
   * @access public
   * @static
   */
  public static function dir($dir)
  {
    if (self::$instance !== null)
    {
      $a = self::$instance;
      $dir = isset($a['dirs'][$dir]) ? $a['dirs'][$dir] : $dir;
      if (isset($dir[0]) && $dir[0] != '/' && $dir[0] != '\\') $dir = self::$root . DIRECTORY_SEPARATOR . $dir;
    }
    return str_replace((DIRECTORY_SEPARATOR == '\\') ? '/' : '\\', DIRECTORY_SEPARATOR, $dir);
  }
  
  /**
   * Returns a directory url relative to the site root.
   *
   * @param string $url - directory alias.
   * @return string
   */
  public static function url($url)
  {
    if (self::$instance !== null) $url = isset(self::$instance['dirs'][$url]) ? self::$instance['dirs'][$url] : $url;
    return '/' . str_replace('\\', '/', ltrim($url, '\\/'));
  }
  
  /**
   * Logs some data into log files.
   *
   * @param mixed $data - some data to log.
   * @access public
   * @static  
   */
  public static function log($data)
  {
    $path = self::dir('logs') . '/' . date('Y F');
    if (!is_dir($path)) mkdir($path, 0775, true);
    $file = $path . '/' . date('d H.i.s#') . microtime(true) . '.log';
    $info = array('IP' => $_SERVER['REMOTE_ADDR'],
                  'ID' => session_id(),
                  'time' => date('m/d/Y H:i:s:u'),
                  'url' => Net\URL::current(),
                  'SESSION' => $_SESSION,
                  'COOKIE' => $_COOKIE,
                  'GET' => $_GET,
                  'POST' => $_POST,
                  'FILES' => $_FILES,
                  'data' => $data);
    file_put_contents($file, serialize($info));
  }
  
  /**
   * Performs redirect to given URL.
   *
   * @param string $url
   * @param boolean $isNewWindow
   * @param boolean $immediately
   * @access public
   * @static
   */
  public static function go($url, $inNewWindow = false, $immediately = true)
  {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
    {
      if ($inNewWindow) self::$output = 'window.open(\'' . addslashes($url) . '\');';
      else self::$output = 'window.location.asign(\'' . addslashes($url) . '\');';
    }
    else
    {
      if ($inNewWindow) self::$output = '<script type="text/javascript">window.open(\'' . addslashes($url) . '\');</script>';
      else self::$output = '<script type="text/javascript">window.location.assign(\'' . addslashes($url) . '\');</script>';
    } 
    if ($immediately) exit;
  }
  
  /**
   * Performs the page reloading.
   *
   * @param boolean $immediately
   * @access public
   * @static
   */
  public static function reload($immediately = true)
  {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
    {
      self::$output = 'window.location.reload();';
    }
    else 
    {
      self::$output = '<script type="text/javascript">window.location.reload();</script>';
    }
    if ($immediately) exit;
  }

  /**
   * Initializes the Aleph framework.
   * The method returns new instance of this class.
   *
   * @return self
   * @access public
   * @static
   */
  public static function init()
  {
    if (self::$instance === null)
    {
      self::$time['script_execution_time'] = microtime(true);
      ini_set('display_errors', 1);
      ini_set('html_errors', 0);
      ini_set('unserialize_callback_func', 'spl_autoload_call');
      if (!isset($_SERVER['DOCUMENT_ROOT'])) $_SERVER['DOCUMENT_ROOT'] = __DIR__;
      self::$root = rtrim($_SERVER['DOCUMENT_ROOT'], '\\/');
      self::$siteUniqueID = md5(self::$root);
      if (!defined('NO_GZHANDLER') && extension_loaded('zlib') && !ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 4096);
      ob_start(function($html)
      {
        if (!Aleph::isDebug() || !preg_match('/(Fatal|Parse) error:(.*) in (.*) on line (\d+)/', $html, $res)) return Aleph::getOutput() ?: $html;
        Aleph::exception(new \ErrorException($res[2], 0, 1, $res[3], $res[4]), true);
        return Aleph::getOutput();
      });
      if (session_id() == '') session_start();
      else session_regenerate_id(true);
      if (isset($_GET['__DEBUG_INFORMATION__']) && isset($_SESSION['__DEBUG_INFORMATION__'][$_GET['__DEBUG_INFORMATION__']]))
      {
        self::$output = $_SESSION['__DEBUG_INFORMATION__'][$_GET['__DEBUG_INFORMATION__']];
        exit;
      }
      if (get_magic_quotes_gpc()) 
      {
        $func = function ($value) use (&$func) {return is_array($value) ? array_map($func, $value) : stripslashes($value);};
        $_GET = array_map($func, $_GET);
        $_POST = array_map($func, $_POST);
        $_COOKIE = array_map($func, $_COOKIE);
      }
      if (date_default_timezone_set(date_default_timezone_get()) === false) date_default_timezone_set('UTC');
      eval('function foo($foo) {return $foo;}');
      set_time_limit(0);
    }
    return self::$instance = new self();
  }
  
  /**
   * Analyzes an exception.
   *
   * @param \Exception $e
   * @return array - exception information.
   * @access private
   * @static  
   */
  private static function analyzeException(\Exception $e)
  {
    $msg = ucfirst(ltrim($e->getMessage()));
    $trace = $e->getTrace();
    $traceAsString = $e->getTraceAsString();
    $file = $e->getFile();
    $line = $e->getLine();
    if (self::$eval && (strpos($file, 'eval()\'d') !== false || strpos($msg, 'eval()\'d') !== false))
    {
      $findFunc = function($func, $code)
      {
        foreach (explode(PHP_EOL, $code) as $n => $row)
        {
          $tokens = token_get_all('<?php ' . $row . '?>');
          foreach ($tokens as $k => $token)
          {
            if ($token[0] != T_FUNCTION) continue;
            while ($token[0] != T_STRING)
            {
              $k++;
              $token = $tokens[$k];
            }
            if ($token[1] == $func) return $n + 1;
          }
        }
        return false;
      };
      $trace = self::$eval['trace'];
      $traceAsString = self::$eval['traceAsString'];
      $fragment = self::$eval['code'];
      if (preg_match('/([^\( ]+)\(\).*, called in ([^ ]+) on line (\d+)/', $msg, $matches))
      {
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        $foundLine = $findFunc($matches[1], $fragment);
        $frag1 = self::codeFragment($file, $line);
        $frag2 = self::codeFragment($matches[2], $matches[3]);
        $frag3 = self::codeFragment($fragment, $foundLine);
        $fragment = '<b>File in which the error has been catched:</b> ' . $file . $frag1 . '<b>File in which the callable is called:</b> ' . $matches[2] . $frag2 . '<b>eval()\'s code in which the callable is defined:</b> ' . $frag3;
        $msg .= ' in eval()\'s code on line ' . $foundLine;
      }
      else if (preg_match('/([^\( ]+)\(\).*, called in ([^\(]+)\((\d+)\) : eval\(\)\'d code on line (\d+)/', $msg, $matches))
      {
        $line = $findFunc($matches[1], $fragment);
        $matches[4] += self::$eval['rowcount'] - 1;
        $msg = preg_replace('/called in [^ ]+ : eval\(\)\'d code on line \d+/', 'called in eval()\'s on line ' . $matches[4], $msg);
        $frag1 = self::codeFragment($matches[2], $matches[3]);
        $frag3 = self::codeFragment($fragment, $matches[4]);
        $tmp = '<b>File in which the error has been catched:</b> ' . $matches[2] . $frag1;
        if (strpos($file, 'eval()\'d') !== false)
        {
          $frag2 = self::codeFragment($fragment, $line);
          $tmp .= '<b>eval()\'s code in which the callable is defined:</b> ' . $frag2;
          $msg .= ' in eval()\'s code on line ' . $line;
        }
        else 
        {
          $frag2 = self::codeFragment($file, $line);
          $tmp .= '<b>File in which the callable is defined:</b> ' . $matches[2] . $frag2;
          $msg .= ' in ' . $file . ' on line ' . $line;
        }
        $tmp .= '<b>eval()\'s code in which the callable is called:</b> ' . $frag3;
        $fragment = $tmp;
        $file = $matches[2];
        $line = $matches[3];
      }
      else
      {
        $line += self::$eval['rowcount'] - 1;
        $frag1 = self::codeFragment($trace[0]['file'], $trace[0]['line']);
        $frag2 = self::codeFragment($fragment, $line);
        $fragment = '<b>File in which the error has been catched:</b> ' . $trace[0]['file'] . $frag1 . '<b>eval()\'s code in which the error has been catched:</b> ' . $frag2;
        $msg .= ' in eval()\'s code on line ' . $line;
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
      }
    }
    else
    {
      if (preg_match('/([^\( ]+)\(\).*, called in ([^ ]+) on line (\d+)/', $msg, $matches))
      {
        $frag1 = self::codeFragment($file, $line);
        $frag2 = self::codeFragment($matches[2], $matches[3]);
        $fragment = '<b>File in which the callable is defined:</b> ' . $matches[2] . $frag1 . '<b>File in which the callable is called:</b> ' . $file . $frag2;
        $msg .= ' in ' . $file . ' on line ' . $line;
      }
      else
      {
        $fragment = self::codeFragment($file, $line);
      }
    }
    $info = array();
    if ($e instanceof Exception)
    {
      $info['class'] = $e->getClass();
      $info['token'] = $e->getToken();
    }
    $info['message'] = $msg;
    $info['stack'] = $traceAsString;
    $info['code'] = $e->getCode();
    $info['severity'] = method_exists($e, 'getSeverity') ? $e->getSeverity() : '';
    $info['file'] = $file;
    $info['line'] = $line;
    $info['fragment'] = $fragment;
    return $info;
  }
  
  /**
   * Returns the code fragment of the PHP script in which the error has occured.
   *
   * @param string $filename
   * @param integer $line
   * @return string
   * @access private
   * @static
   */
  private static function codeFragment($filename, $line)
  {
    $halfOfRows = 10;
    $minColumns = 100;
    $lines = explode("\n", str_replace("\r\n", "\n", (is_file($filename) && is_readable($filename)) ? file_get_contents($filename) : $filename));
    $count = count($lines);
    $total = 2 * $halfOfRows + 1;
    if ($count <= $total)
    {
      $start = 0;
      $end = $count - 1;
      $offset = 1;
    }
    else if ($line - $halfOfRows <= 0)
    {
      $start = 0;
      $end = $total - 1;
      $offset = 1;
    }
    else if ($line + $halfOfRows >= $count)
    {
      $end = $count - 1;
      $start = $count - $total;
      $offset = $start + 1;
    }
    else
    {
      $start = $line - $halfOfRows - 1;
      $end = $line + $halfOfRows - 1;
      $offset = $start + 1;
    }
    $lines = array_slice($lines, $start, $end - $start + 1);
    foreach ($lines as $k => &$str)
    {
      $str = rtrim(str_pad(($k + $offset) . '.', 6, ' ') . $str);
      if ($line == $k + $offset) 
      {
        $markedLine = $k + 1;
        $originalLength = strlen($str);
      }
      if (strlen($str) > $minColumns) $minColumns = strlen($str);
      $str = htmlspecialchars($str);
    }
    array_unshift($lines, $lines[] = str_repeat('-', $minColumns));
    if (isset($markedLine) && isset($lines[$markedLine])) $lines[$markedLine] = '<b style="background-color:red;color:white;"><i>' . str_pad($lines[$markedLine], $minColumns + strlen($lines[$markedLine]) - $originalLength, ' ',  STR_PAD_RIGHT). '</i></b>';
    return '<pre>' . implode("\n", $lines) . '</pre>';
  }
  
  /**
   * Constructor.
   *
   * @access private
   */
  private function __construct()
  {
    self::debug(true, E_ALL | E_STRICT);
    if (!self::$instance) spl_autoload_register(array($this, 'al'));
    $this->config = array();
    $this->classes = $this->dirs = $this->exclusions = array();
    $this->acts = array('methods' => array(), 'actions' => array());
    $this->key = 'autoload_' . self::$siteUniqueID;
    $this->mask = '/^.*\.php$/i';
    $this->autoload = '';
    $this->cache = new Cache\File();
    $this->request = new Net\Request();
    $this->response = new Net\Response();
  }
  
  /**
   * Private __clone() method prevents this object cloning.
   *
   * @access private
   */
  private function __clone(){}
  
  /**
   * Autoloads classes and intefaces.
   *
   * @param string $class
   * @param boolean $auto
   * @return boolean
   * @access private   
   */
  private function al($class, $auto = true)
  {
    $classes = $this->getClasses();
    if ($auto && $this->alCallBack)
    {
      if ($this->alCallBack instanceof \Closure) $this->alCallBack($class, $classes);
      else
      {
        $info = $this->alCallBack->getInfo();
        $this->al($info['class'], false);
        $this->alCallBack->call(array($class, $classes));
      }
      return true;
    }
    $cs = strtolower($class);
    if ($cs[0] != '\\') $cs = '\\' . $cs;
    if (class_exists($cs, false) || interface_exists($cs, false)) return true;
    if (isset($classes[$cs]) && is_file($classes[$cs]))
    {
      require_once($classes[$cs]);
      if (class_exists($cs, false) || interface_exists($cs, false)) return true;
    }
    if ($this->find($cs) === false)
    {
      if ($auto) 
      {
        self::exception(new Exception($this, 'ERR_GENERAL_1', $class));
        exit;
      }
      else return false;
    }
    return true;
  }
  
  /**
   * Finds a class or inteface to include into your PHP script.
   *
   * @param string $class
   * @param string $path
   * @return integer | boolean
   * @access private
   */
  private function find($class = null, $path = null)
  {
    if ($path) $paths = array($path => true);
    else
    {
      $paths = $this->dirs ?: array(self::$root => true);
      $this->classes = array();
      $first = true;
    }
    foreach ($paths as $path => $isRecursion)
    {
      foreach (scandir($path) as $item)
      {
        if ($item == '.' || $item == '..' || $item == '.svn' || $item == '.hg' || $item == '.git') continue; 
        $file = $path . '/' . $item;
        if (isset($this->exclusions[$file]) || array_search($file, (array)$this->exclusions) !== false) continue;
        if (is_file($file))
        {
          if (!preg_match($this->mask, $item)) continue;
          $tokens = token_get_all(file_get_contents($file));
          $namespace = null;
          foreach ($tokens as $n => $token)
          {
            if ($token[0] == T_NAMESPACE) 
            {
              $ns = ''; $tks = $tokens; $k = $n;
              do
              {
                $tkn = $tks[++$k];
                if ($tkn[0] == T_STRING || $tkn[0] == T_NS_SEPARATOR) $ns .= $tkn[1];
              }
              while ($tkn != ';');
              $namespace = $ns . '\\';
            }
            else if ($token[0] == T_CLASS || $token[0] == T_INTERFACE)
            {
              $tks = $tokens; $k = $n;
              do
              {
                $tkn = $tks[++$k];
              }
              while ($tkn[0] != T_STRING);
              $cs = strtolower('\\' . $namespace . $tkn[1]);
              if (isset($this->classes[$cs])) 
              {
                $normalize = function($dir)
                {
                  return str_replace((DIRECTORY_SEPARATOR == '\\') ? '/' : '\\', DIRECTORY_SEPARATOR, $dir);
                };
                self::exception(new Exception($this, 'ERR_GENERAL_5', '\\' . $namespace . $tkn[1], $normalize($this->classes[$cs]), $normalize($file)));
                exit;
              }
              $this->classes[$cs] = $file;
            }
          }
        }
        else if ($isRecursion && is_dir($file)) $this->find($class, $file);
      }
    }
    $flag = false;
    if (isset($first)) 
    {
      $this->setClasses($this->classes);
      if ($class !== null)
      {
        foreach ($this->classes as $cs => $file)
        {
          if ($cs == $class)
          {
            require_once($file);
            return (class_exists($class, false) || interface_exists($class, false));
          }
        }
      }
    }
    return count($this->classes);
  }
  
  /**
   * Parses URL templates for the routing.
   *
   * @param string $url
   * @param string $key
   * @param string $regex
   * @return array
   * @access private   
   */
  private function parseURLTemplate($url, &$key, &$regex)
  {
    $params = array();
    $url = (string)$url;
    $path = preg_split('/(?<!\\\)\/+/', $url);
    $path = array_map(function($p) use(&$params)
    {
      preg_match_all('/(?<!\\\)#((?:.(?!(?<!\\\)#))+.)./', $p, $matches);
      foreach ($matches[0] as $k => $match)
      {
        $m = $matches[1][$k];
        $n = strpos($m, '|');
        if ($n !== false) 
        {
          $name = substr($m, 0, $n);
          $m = substr($m, $n + 1);
          if ($m == '') $m = '[^\/]*';
        }
        else 
        {
          $m = '[^\/]*';
          $name = $matches[1][$k];
        }
        $params[$name] = $name;
        $p = str_replace($match, '(?P<' . $name . '>' . $m . ')', $p);
      }
      return str_replace('\#', '#', $p);
    }, $path);
    $key = $url ? md5($url) : 'default';
    $regex = '/^' . implode('\/', $path) . '$/';
    return $params;
  }
  
  /**
   * Loads or returns configuration data.
   *
   * @param string | array $param
   * @param boolean $replace
   * @return array | self
   * @access public
   */
  public function config($param = null, $replace = false)
  {
    if ($param === null) return $this->config;
    if (is_array($param)) 
    {
      if ($replace) 
      {
        $this->config = $param;
        return $this;
      }
      $data = $param;
    }
    else
    {
      $data = parse_ini_file($param, true);
      if ($data === false) throw new Exception($this, 'ERR_CONFIG_1', $param);
    }
    if ($replace) $this->config = array();
    foreach ($data as $section => $properties)
    {
      if (is_array($properties)) foreach ($properties as $k => $v) $this->config[$section][$k] = $v;
      else $this->config[$section] = $properties;
    }
    return $this;
  }
  
  /**
   * Sets or returns the cache object.
   *
   * @param Aleph\Cache\Cache $cache
   * @return Aleph\Cache\Cache
   * @access public
   */
  public function cache(Cache\Cache $cache = null)
  {
    if ($cache === null) return $this->cache;
    return $this->cache = $cache;
  }
  
  /**
   * Returns the instance of an Aleph\Net\Request object.
   *
   * @return Aleph\Net\Request
   * @access public
   */
  public function request()
  {
    return $this->request;
  }
  
  /**
   * Returns the instance of an Aleph\Net\Response object.
   *
   * @return Aleph\Net\Response
   * @access public
   */
  public function response()
  {
    return $this->response;
  }
  
  /**
   * Sets array of class paths.
   *
   * @param array $classes
   * @access public
   */
  public function setClasses(array $classes)
  {
    $this->classes = $classes;
    $this->cache->set($this->key, $this->classes, $this->cache->getVaultLifeTime());
  }
  
  /**
   * Returns array of class paths.
   *
   * @return array
   * @access public
   */
  public function getClasses()
  {
    if (!$this->classes) $this->classes = (array)$this->cache->get($this->key); 
	   return $this->classes;
  }
  
  /**
   * Sets array of classes that shouldn't be included in the class searching.
   *
   * @param array $exclusions
   * @access public
   */
  public function setExclusions(array $exclusions)
  {
    $this->exclusions = $exclusions;
  }
  
  /**
   * Returns array of classes that shouldn't be included in the class searching.
   *
   * @return array
   * @access public
   */
  public function getExclusions()
  {
    return $this->exclusions;
  }
  
  /**
   * Sets list of directories for the class searching.
   * List of directories should be an associative array 
   * in which its keys are directory paths and its values are boolean values 
   * determining whether recursive search is possible (TRUE) or not (FALSE).
   *
   * @param array $directories
   * @access public
   */
  public function setDirectories(array $directories)
  {
    $this->dirs = $directories;
  }
  
  /**
   * Returns list of directories for the class searching.
   *
   * @return array
   * @access public
   */
  public function getDirectories()
  {
    return $this->dirs;
  }
  
  /**
   * Sets search file mask.
   *
   * @param string $mask
   * @access public
   */
  public function setMask($mask)
  {
    $this->mask = $mask;
  }
  
  /**
   * Returns search file mask.
   *
   * @return string
   * @access public
   */
  public function getMask()
  {
    return $this->mask;
  }
  
  /**
   * Sets autoload callback. Callback can be a closure, an instance of Aleph\Core\IDelegate or Aleph callback string.
   *
   * @param string | closure | Aleph\Core\IDelegate
   * @access public
   */
  public function setAutoload($callback)
  {
    if (is_array($callback) || is_object($callback) && !($callback instanceof \Closure) && !($callback instanceof IDelegate)) throw new Exception($this, 'ERR_GENERAL_4');
    if (!is_object($callback)) $callback = new Delegate($callback);
    $this->alCallBack = $callback;
  }
  
  /**
   * Returns autoload callback.
   *
   * @return closure | Aleph\Core\IDelegate
   * @access public
   */
  public function getAutoload()
  {
    return $this->alCallBack;
  }
  
  /**
   * Searches all classes of the application or only a single class.
   *
   * @param string $class - a single class to search and include.
   * @return integer | boolean
   * @access public
   */
  public function load($class = null)
  {
    if ($class === null) return $this->find();
    return $this->al($class, false);
  }
  
  /**
   * Enables or disables HTTPS protocol for the given URL template.
   *
   * @param string $url - regex for the given URL.
   * @param boolean $flag
   * @param array | string $methods - HTTP request methods.
   * @access public
   */
  public function secure($url, $flag, $methods = 'GET|POST')
  {
    $action = function() use($flag)
    {
      $url = new Net\URL();
      if ($url->isSecured() != $flag) 
      {
        $url->secure($flag);
        Aleph::go($url->build());
      }
    };
    $key = $url ? md5($url) : 'default';
    $methods = is_array($methods) ? $methods : explode('|', $methods);
    $this->acts['actions'][$key] = array('params' => array(), 'action' => $action, 'regex' => $url);
    foreach ($methods as $method) $this->acts['methods'][strtolower($method)][$key] = 1;
  }
  
  /**
   * Sets the redirect for the given URL regex template.
   *
   * @param string $url - regex URL template.
   * @param string $redirect - URL to redirect.
   * @param array | string $methods - HTTP request methods.
   * @access public
   */
  public function redirect($url, $redirect, $methods = 'GET|POST')
  {
    $params = $this->parseURLTemplate($url, $key, $regex);
    $t = microtime(true); $k = 0;
    foreach ($params as $param)
    {
      $redirect = preg_replace('/(?<!\\\)#((.(?!(?<!\\\)#))+.)./', md5($t + $k), $redirect);
      $k++;
    }
    $action = function() use($t, $redirect)
    {
      $url = $redirect;
      foreach (func_get_args() as $k => $arg)
      {
        $url = str_replace(md5($t + $k), $arg, $url);
      }
      Aleph::go($url);
    };
    $methods = is_array($methods) ? $methods : explode('|', $methods);
    $this->acts['actions'][$key] = array('params' => $params, 'action' => $action, 'regex' => $regex);
    foreach ($methods as $method) $this->acts['methods'][strtolower($method)][$key] = 1;
  }
  
  /**
   * Binds an URL regex template with some action.
   *
   * @param string $url - regex URL template.
   * @param closure | Aleph\Core\IDelegate | string $action
   * @param array | string $methods - HTTP request methods.
   * @access public
   */
  public function bind($url, $action, $methods = 'GET|POST')
  {
    $params = $this->parseURLTemplate($url, $key, $regex);
    $tmp = array();
    if ($action instanceof \Closure)
    {
      foreach (foo(new \ReflectionFunction($action))->getParameters() as $param) 
      {
        $name = $param->getName();
        if (isset($params[$name])) $tmp[] = $params[$name];
      }
    }
    else 
    {
      if (!($action instanceof \Delegate)) $action = new Delegate($action);
      $info = $action->getInfo();
      if ($info['type'] == 'function')
      {
        foreach (foo(new \ReflectionFunction($action))->getParameters() as $param) 
        {
          $name = $param->getName();
          if (isset($params[$name])) $tmp[] = $params[$name];
        }
      }
      else if ($info['type'] == 'class')
      {
        foreach (foo(new \ReflectionClass($info['class']))->getMethod($info['method'] ?: '__construct')->getParameters() as $param)
        {
          $name = $param->getName();
          if (isset($params[$name])) $tmp[] = $params[$name];
        }
      }
    }
    $params = $tmp;
    $methods = is_array($methods) ? $methods : explode('|', $methods);
    $this->acts['actions'][$key] = array('params' => $params, 'action' => $action, 'regex' => $regex);
    foreach ($methods as $method) $this->acts['methods'][strtolower($method)][$key] = 1;
  }
  
  /**
   * Performs all actions matching all regex URL templates.
   *
   * @param string | array - HTTP request methods.
   * @param string $url - regex URL template.
   * @param string $component - URL component.
   * @return mixed
   * @access public
   */
  public function route($methods = null, $url = null, $component = Net\URL::COMPONENT_PATH)
  {
    $methods = is_array($methods) ? $methods : ($methods ? explode('|', $methods) : array());
    if (count($methods) == 0) 
    {
      if (!isset($this->request->method)) return;
      $methods = array($this->request->method);
    }
    if ($url === null)
    {
      if (isset($this->request->url) && $this->request->url instanceof Net\URL) 
      {
        $url = $this->request->url->build($component);
      }
      else 
      {
        $url = foo(new Net\URL())->build($component);
      }
    }
    foreach ($methods as $method)
    {
      $method = strtolower($method);
      if (!isset($this->acts['methods'][$method])) continue;
      foreach ($this->acts['methods'][$method] as $key => $flag)
      {
        $action = $this->acts['actions'][$key];
        if (preg_match_all($action['regex'], $url, $matches))
        {
          $act = $action['action'];
          $params = array();
          foreach ($action['params'] as $param) $params[] = $matches[$param][0];
          if ($act instanceof \Closure) return call_user_func_array($act, $params);
          return $act->call($params);
        }
      }
    }
  }
  
  /**
   * Sets new value of the configuration variable.
   *
   * @param mixed $var - the configuration variable name.
   * @param mixed $value - new value of a configuration variable.
   * @access public
   */
  public function offsetSet($var, $value)
  {
    $this->config[$var] = $value;
  }

  /**
   * Returns whether the requested configuration variable exist.
   *
   * @param mixed $var - name of the configuration variable.
   * @return boolean
   * @access public   
   */
  public function offsetExists($var)
  {
    return isset($this->config[$var]);
  }

  /**
   * Removes the requested configuration variable.
   *
   * @param mixed $var - name of the configuration variable.
   * @access public
   */
  public function offsetUnset($var)
  {
    unset($this->config[$var]);
  }

  /**
   * Returns value of the configuration variable.
   *
   * @param mixed $var - name of the configuration variable.
   * @return mixed
   * @access public
   */
  public function &offsetGet($var)
  {
    if (!isset($this->config[$var])) $this->config[$var] = null;
    return $this->config[$var];
  }
}

?>