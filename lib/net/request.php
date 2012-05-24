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
 * Request Class provides easier interaction with variables of the current HTTP request.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
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