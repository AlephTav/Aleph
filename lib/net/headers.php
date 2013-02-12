<?php

namespace Aleph\Net;

class Headers
{
  protected $headers = array();

  protected $contentTypeMap = array('text' => 'text/plain',
                                    'html' => 'text/html',
                                    'json' => 'application/json',
                                    'xml' => 'application/xml');

  /**
   * Array of instances of this class.
   * 
   * @var array $instance
   * @access private
   */           
  private static $instance = array('request' => null, 'response' => null);

  /**
   * Clones an object of this class. The private method '__clone' doesn't allow to clone an instance of the class.
   * 
   * @access private
   */
  private function __clone(){}
  
  /**
   * Constructor.
   *
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
  
  public static function getRequestHeaders()
  {
    if (self::$instance['request'] === null) self::$instance['request'] = new self('request');
    return self::$instance['request'];
  }
  
  public static function getResponseHeaders()
  {
    if (self::$instance['response'] === null) self::$instance['response'] = new self('response');
    return self::$instance['response'];
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
    $this->headers = array();
    $this->merge($headers);
  }
  
  public function merge(array $headers)
  {
    foreach ($headers as $name => $value) $this->set($name, $value);
  }
  
  public function clear()
  {
    $this->headers = array();
  }
  
  /**
   * Returns value of an HTTP header.
   *
   * @param string $name - HTTP headr name.
   * @return string | boolean - return FALSE if a such header doesn't exist.
   */
  public function get($name)
  {
    $name = self::normalizeHeaderName($name);
    return isset($this->headers[$name]) ? $this->headers[$name] : false;
  }
  
  /**
   * Sets an HTTP header.
   *
   * @param string $name - header name
   * @param string $value - new header value
   * @access public
   */
  public function set($name, $value)
  {
    $this->headers[self::normalizeHeaderName($name)] = $value;
  }
  
  /**
   * Removes an HTTP header by its name.
   *
   * @param string $name - HTTP header name.
   * @access public
   */
  public function remove($name)
  {
    unset($this->headers[self::normalizeHeaderName($name)]);
  }
  
  public function getContentType()
  {
    return $this->get('Content-Type');
  }
  
  public function setContentType($type)
  {
    $type = isset($this->contentTypeMap[$type]) ? $this->contentTypeMap[$type] : $type;
    $this->headers['Content-Type'] = $type;
  }

  public static function normalizeHeaderName($name)
  {
    $name = strtr(trim($name), array('_' => ' ', '-' => ' '));
    $name = ucwords(strtolower($name));
    return str_replace(' ', '-', $name);
  }
}