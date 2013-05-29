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
   * The instance of this class.
   * 
   * @var Aleph\Net\Response $instance
   * @access private
   */           
  private static $instance = null;

  /**
   * HTTP status codes.
   *
   * @var array $codes
   * @access protected
   * @static
   */
  protected static $codes = [100 => 'Continue',
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
                             599 => 'Network connect timeout error'];

  /**
   * Instance of Aleph\Net\Headers class.
   *
   * @var Aleph\Net\Headers $headers
   * @access public
   */
  public $headers = null;
  
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
   * @var mixed $body
   * @access public
   */
  public $body = null;
  
  /**
   * Determines whether content of the response body needs to be converted according to content type or not.
   *
   * @var boolean $convertOutput
   * @access public
   */
  public $convertOutput = false;
  
  /**
   * Returns an instance of this class.
   * 
   * @return Aleph\Net\Response
   * @access public
   * @static
   */
  public static function getInstance()
  {
    if (self::$instance === null) self::$instance = new self();
    return self::$instance;
  }
  
  /**
   * Clones an object of this class. The private method '__clone' doesn't allow to clone an instance of the class.
   * 
   * @access private
   */
  private function __clone(){}
  
  /**
   * Constructor. Initializes all internal variables of the class.
   *
   * @access public
   */
  private function __construct()
  {
    $this->headers = Headers::getResponseHeaders();
  }
  
  /**
   * Returns an HTTP status message by its code. 
   * If such message doesn't exist the method returns FALSE.
   * 
   * @param integer $status
   * @return string | boolean
   * @access public
   * @static
   */
  public static function getMessage($status)
  {
    return isset(self::$codes[$status]) ? self::$codes[$status] : false;
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
    $this->headers->clear();
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
      $this->headers->merge(['Expires' => 'Sun, 3 Jan 1982 21:30:00 GMT',
                             'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0',
                             'Pragma' => 'no-cache']);
    }
    else
    {
      $expires = (int)$expires;
      $this->headers->set('Expires', gmdate('D, d M Y H:i:s', $expires) . ' GMT');
      $this->headers->set('Cache-Control', 'max-age=' . ($expires - time()));
    }
  }
  
  /**
   * Performs an HTTP redirect. Execution of this method halts the script execution. 
   *
   * @param string $url - redirect URL.
   * @param integer $status - redirect HTTP status code.
   * @access public
   */
  public function redirect($url, $status = 302)
  {
    $this->status = (int)$status;
    $this->headers->set('Location', $url);
    $this->send();
    exit;
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
    exit;
  }
  
  /**
   * Sends all HTTP response headers.
   *
   * @throws Aleph\Core\Exception with token ERR_RESPONSE_1 if new version value doesn't equal '1.0' or '1.1'
   * @throws Aleph\Core\Exception with token ERR_RESPONSE_2 if a such status code doesn't exist.
   * @access public
   */
  public function send()
  {  
    if (headers_sent()) return;
    if (empty($this->version)) $this->version = '1.1';
    if (empty($this->status)) $this->status = 200;
    if (empty($this->body)) $this->body = '';
    $isBody = ($this->status < 100 || $this->status >= 200) && $this->status != 204 && $this->status != 304 && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] != 'HEAD';
    $body = $this->convertOutput ? $this->convert() : $this->body;
    if ($this->version != '1.0' && $this->version != '1.1') throw new Core\Exception($this, 'ERR_RESPONSE_1');
    if (!isset($this->codes[$this->status])) throw new Core\Exception($this, 'ERR_RESPONSE_2', $this->status);
    $headers = $this->headers->getHeaders();
    if (!$isBody)
    {
      unset($headers['Content-Type']);
      unset($headers['Content-Length']);
    }
    if (substr(PHP_SAPI, 0, 3) === 'cgi') header('Status: ' . $this->status . ' ' . $this->codes[$this->status]);
    else header('HTTP/' . $this->version . ' ' . $this->status . ' ' . $this->codes[$this->status]);
    foreach ($headers as $name => $value) header($name . ': ' . $value);
    \Aleph::setOutput($isBody ? $body : '');
  }
  
  /**
   * Converts response body according to the given content type.
   *
   * @return string
   * @access protected
   */
  public function convert()
  {
    $type = $this->headers->get('Content-Type');
    if ($type === false) return $this->body;
    switch ($type)
    {
      case 'text/plain':
      case 'text/html':
        return is_scalar($this->body) ? $this->body : serialize($this->body);
      case 'application/json':
        return json_encode($this->body);
      case 'application/xml':
        $php2xml = function($data) use (&$php2xml)
        {
          $xml = '';
          if (is_array($data) || is_object($data))
          {
            foreach ($data as $key => $value) 
            {
              if (is_numeric($key)) $key = 'node';
              $xml .= '<' . $key . '>' . $php2xml($value) . '</' . $key . '>';
            }
          } 
          else 
          {
            $xml = htmlspecialchars($data, ENT_QUOTES);
          }
          return $xml;
        };
        return '<?xml version="1.0" encoding="UTF-8" ?><nodes>' . $php2xml($this->body) . '</nodes>';
    }
    return $this->body;
  }
}