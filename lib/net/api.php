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
    Aleph\Data\Converters;

/**
 * The base class for creating of the RESTFul API system.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.net
 */
class API
{
  /**
   * Error message templates.
   */
  const ERR_API_1 = 'Callback parameter is not set for resource "[{var}]".';

  protected static $map = [];
  
  /**
   * The current request object.
   *
   * @var Aleph\Net\Request $request
   * @access protected
   * @static
   */
  protected static $request = null;
  
  /**
   * The current response object.
   *
   * @var Aleph\Net\Response $response
   * @access protected
   * @static
   */
  protected static $response = null;
  
  /**
   * The response conten type. It can be regular MIME-type or its alias (if exists).
   *
   * @var string $contentType
   * @access protected
   * @static
   */
  protected static $contentType = 'json';
  
  /**
   * The output charset of the response body.
   *
   * @var string $outputCharset
   * @access protected
   * @static
   */
  protected static $outputCharset = 'UTF-8';
  
  /**
   * The input charset of the response body.
   *
   * @var string $inputCharset
   * @access protected
   * @static
   */
  protected static $inputCharset = 'UTF-8';

  /**
   * The namespace prefix for the callbacks that specified in the $map.
   *
   * @var string $namespace
   * @access protected
   * @static
   */
  protected static $namespace = '\\';
  
  /**
   * Determines whether the response body is converted according to the defined content type.
   *
   * @var boolean $convertErrors
   * @access protected
   * @static
   */
  protected static $convertOutput = false;
  
  /**
   * Determines whether any error information is converted according to the defined content type.
   *
   * @var boolean $convertErrors
   * @access protected
   * @static
   */
  protected static $convertErrors = false;
  
  /**
   * The error and exception handler of the API class system.
   * This method stops the script execution and sets the response status code to 500.
   *
   * @param Exception $e - the exception that occurred.
   * @param array $info - the exception information.
   * @return mixed
   */
  public static function error(\Exception $e, array $info)
  {
    $a = \Aleph::getInstance();
    $response = Response::getInstance();
    if (!$a['debugging']) $response->stop(500, '');
    if (!static::$convertErrors) return true;
    $response->setContentType(static::$contentType, static::$outputCharset);
    $response->stop(500, static::convert($info));
  }

  /**
   * Invokes and performs the requested API method.
   * This method is the entry point of the API class system.
   *
   * @access public
   * @static
   */
  final public static function process()
  {
    \Aleph::getInstance()->setConfig(['customDebugMethod' => get_called_class() . '::error']);
    static::$request = Request::getInstance();
    static::$response = Response::getInstance();
    static::$response->setContentType(static::$contentType, static::$outputCharset);
    $namespace = static::$namespace;
    $process = function(array $resource, array $params = null) use ($namespace)
    {
      if (empty($resource['callback'])) throw new Core\Exception('Aleph\Net\API', 'ERR_API_1', $resource);
      $callback = $resource['callback'];
      if ($callback[0] != '\\') $callback = $namespace . $callback;
      $callback = new Core\Delegate($callback);
      $api = $callback->getClassObject();
      $api->before($resource, $params);
      $result = call_user_func_array([$api, $callback->getMethod()], $params);
      $api->after($resource, $params);
      return $result;
    };
    $router = new Router();
    foreach (static::$map as $resource => $info)
    {
      foreach ($info as $methods => $data)
      {
        if (isset($data['secure']))
        {
          $router->secure($resource, $data['secure'], $methods)
                 ->component(empty($data['component']) ? URL::ALL : $data['component']);
        }
        else if (isset($data['redirect']))
        {
          $router->redirect($resource, $data['redirect'], $methods)
                 ->ssl(empty($data['ssl']) ? false : $data['ssl'])
                 ->component(empty($data['component']) ? URL::PATH : $data['component']);
        }
        else
        {
          $router->bind($resource, $process, $methods)
                 ->ssl(empty($data['ssl']) ? false : $data['ssl'])
                 ->component(empty($data['component']) ? URL::PATH : $data['component'])
                 ->ignoreWrongDelegate(empty($data['ignoreWrongDelegate']) ? false : $data['ignoreWrongDelegate'])
                 ->coordinateParameterNames(empty($data['coordinateParameterNames']) ? false : $data['coordinateParameterNames'])
                 ->args(['resource' => $data])
                 ->extra('params');
        }
      }
    }
    $output = $router->route();
    if (!$output['success']) static::notFound();
    static::$response->body = static::convert($output['result']);
    static::$response->send();
  }
  
  /**
   * This method is automatically called when the current request does not match any API methods ($map's callbacks).
   * The method stops the script execution and sets the response status code to 404.
   *
   * @param string $content - the response body.
   * @access protected
   * @static
   */
  protected static function notFound($content = null)
  {
    self::$response->stop(404, static::convert($content));
  }
  
  /**
   * Converts the execution result of the requested API method to the specified text format according to the output charset.
   *
   * @param mixed $content - the response body.
   * @return string
   * @access protected
   * @static
   */
  protected static function convert($content)
  {
    if (!static::$convertOutput) return $content;
    switch (strtolower(static::$contentType))
    {
      case 'json':
      case 'application/json':
        $output = 'json-encoded';
        break;
      default:
        $output = 'any';
        break;        
    }
    $converter = new Converters\Text();
    $converter->output = $output;
    $converter->outputCharset = static::$outputCharset;
    $converter->inputCharset = static::$inputCharset;
    return $converter->convert($content);
  }
  
  protected function batch()
  {
    $result = [];
    $data = json_decode($this->request->body, true);
    foreach ($data as $request)
    {
      $this->request->method = $request['method'];
      $this->request->url->parse($request['url']);
      $this->request->data = $this->request->url->query;
      $this->request->body = $request['body'];
      self::process();
      $result[] = $this->response->body;
    }
    return $result;
  }
  
  protected function before(array $resource, array &$params){}
  
  protected function after(array $resource, array &$params){}
}