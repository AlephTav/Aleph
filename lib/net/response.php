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
 * Response Class provides easier interaction with variables of the current HTTP response and to build a new response.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
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