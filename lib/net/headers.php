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

/**
 * Class provides access to HTTP headers of the server's request or response.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.net
 */
class Headers
{
  /**
   * HTTP headers of the server's request or response.
   *
   * @var array $headers
   * @access protected
   */
  protected $headers = [];

  /**
   * Array of content type aliases.
   *
   * @var array $contentTypeMap
   * @access protected
   */
  protected $contentTypeMap = ['text' => 'text/plain',
                               'html' => 'text/html',
                               'css' => 'text/css',
                               'js' => 'application/javascript',
                               'json' => 'application/json',
                               'xml' => 'application/xml'];

  /**
   * Array of instances of this class.
   * 
   * @var array $instance
   * @access private
   */           
  private static $instance = ['request' => null, 'response' => null];

  /**
   * Clones an object of this class. The private method '__clone' doesn't allow to clone an instance of the class.
   * 
   * @access private
   */
  private function __clone(){}
  
  /**
   * Constructor. Extracts HTTP headers.
   *
   * @param string $mode - type of headers (response or request).
   * @access public
   */
  private function __construct($mode)
  {
    if ($mode == 'request')
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
    }
    else
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
  }
  
  /**
   * Returns HTTP headers of the current HTTP request.
   *
   * @return self
   * @access public
   * @static
   */
  public static function getRequestHeaders()
  {
    if (self::$instance['request'] === null) self::$instance['request'] = new static('request');
    return self::$instance['request'];
  }
  
  /**
   * Returns HTTP headers of the server response.
   *
   * @return self
   * @access public
   * @static
   */
  public static function getResponseHeaders()
  {
    if (self::$instance['response'] === null) self::$instance['response'] = new static('response');
    return self::$instance['response'];
  }
  
  /**
   * Returns normalized header name.
   *
   * @param string $name
   * @return string
   */
  public static function normalizeHeaderName($name)
  {
    $name = strtr(trim($name), ['_' => ' ', '-' => ' ']);
    $name = ucwords(strtolower($name));
    return str_replace(' ', '-', $name);
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
   * Sets array of all HTTP headers.
   *
   * @param array $headers - new HTTP headers.
   * @access public
   */
  public function setHeaders(array $headers)
  {
    $this->headers = [];
    $this->merge($headers);
  }
  
  /**
   * Adds and merges array of new headers.
   *
   * @param array $headers
   * @access public
   */
  public function merge(array $headers)
  {
    foreach ($headers as $name => $value) $this->set($name, $value);
  }
  
  /**
   * Removes all headers.
   *
   * @access public
   */
  public function clean()
  {
    $this->headers = [];
  }
  
  /**
   * Returns TRUE if the HTTP header is defined and FALSE otherwise.
   *
   * @param string $name - the header name.
   */
  public function has($name)
  {
    return isset($this->headers[static::normalizeHeaderName($name)]);
  }
  
  /**
   * Returns value of an HTTP header.
   *
   * @param string $name - the header name.
   * @return string | boolean - return FALSE if a such header doesn't exist.
   */
  public function get($name)
  {
    $name = static::normalizeHeaderName($name);
    return isset($this->headers[$name]) ? $this->headers[$name] : false;
  }
  
  /**
   * Sets an HTTP header.
   *
   * @param string $name - the header name.
   * @param string $value - new header value.
   * @access public
   */
  public function set($name, $value)
  {
    $this->headers[static::normalizeHeaderName($name)] = (string)$value;
  }
  
  /**
   * Removes an HTTP header by its name.
   *
   * @param string $name - the header name.
   * @access public
   */
  public function remove($name)
  {
    unset($this->headers[static::normalizeHeaderName($name)]);
  }
  
  /**
   * Returns content type header.
   *
   * @return string
   * @access public
   */
  public function getContentType()
  {
    return $this->get('Content-Type');
  }
  
  /**
   * Sets content type header. You can use content type alias instead of some HTTP headers (that are determined by $contentTypeMap property).
   *
   * @param string $type - content type or its alias.
   * @access public
   */
  public function setContentType($type)
  {
    $type = isset($this->contentTypeMap[$type]) ? $this->contentTypeMap[$type] : $type;
    $this->headers['Content-Type'] = $type;
  }
  
  /**
   * Returns a list of content types acceptable by the client browser.
   * If header "Accept" is not set the methods returns empty array.
   *
   * @return array
   * @access public
   */
  public function getAcceptableContentTypes()
  {
    $types = $this->get('Accept');
    if ($types === false) return [];
    return preg_split('/\s*(?:,*("[^"]+"),*|,*(\'[^\']+\'),*|,+)\s*/', $types, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
  }
  
  /**
   * Returns the given date header as a DateTime instance.
   * If the date header is not set or not parseable the method returns FALSE.
   *
   * @param string $name - the date header name.
   * @return \DateTime | boolean
   * @access public
   */
  public function getDate($name)
  {
    $name = static::normalizeHeaderName($name);
    if (isset($this->headers[$name])) return \DateTime::createFromFormat(DATE_RFC2822, $this->headers[$name]);
    return false;
  }
  
  /**
   * Sets value of the given date header.
   *
   * @param string $name - the date header name.
   * @param string | \DateTime $date - the date header value.
   * @access public
   */
  public function setDate($name, $date = 'now')
  {
    $date = $date instanceof \DateTime ? clone $date : new \DateTime($date);
    $date->setTimezone(new \DateTimeZone('UTC'));
    $this->headers[static::normalizeHeaderName($name)] = $date->format('D, d M Y H:i:s') . ' GMT';
  }
  
  /**
   * Returns the headers as a string.
   *
   * @return string
   * @access public
   */
  public function __toString()
  {
    $headers = '';
    foreach ($this->headers as $name => $value) $headers .= $name . ': ' . $value . "\r\n";
    return $headers;
  }
}