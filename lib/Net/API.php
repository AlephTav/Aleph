<?php
/**
 * Copyright (c) 2013 - 2015 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2015 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Net;

use Aleph\Core,
    Aleph\Data\Converters;

/**
 * The base class for creating of the RESTFul API system.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.net
 */
class API
{
  /**
   * Error message templates.
   */
  const ERR_API_1 = 'Callback parameter is not set for resource "%s".';

  /**
   * The map of the API methods.
   *
   * @var array $map
   * @access protected
   * @static
   */
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
   * The response content type. It can be regular MIME-type or its alias (if exists).
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
   * Prefix for URLs.
   *
   * @var string $urlPrefix
   * @access protected
   * @static
   */
  protected static $urlPrefix = '';
  
  /**
   * Determines whether the response body is converted according to the defined content type.
   *
   * @var boolean $convertOutput
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
   * @access public
   * @static
   */
  public static function error(\Exception $e, array $info)
  {
    $a = \Aleph::getInstance();
    if (!$a['debugging']) static::$response->stop(500, '');
    if (!static::$convertErrors) return true;
    static::$response->setContentType(static::$contentType, static::$outputCharset);
    static::$response->stop(500, static::convert($info));
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
      $api->after($resource, $params, $result);
      return $result;
    };
    $router = new Router();
    foreach (static::$map as $resource => $info)
    {
      if ($resource && $resource[0] == '@') $resource = static::$urlPrefix . substr($resource, 1);
      foreach ($info as $methods => $data)
      {
        if (empty($data['redirect']))
        {
          $router->bind($resource, $process, $methods)
                 ->ssl(empty($data['ssl']) ? false : $data['ssl'])
                 ->component(empty($data['component']) ? URL::PATH : $data['component'])
                 ->ignoreWrongDelegate(empty($data['ignoreWrongDelegate']) ? false : $data['ignoreWrongDelegate'])
                 ->coordinateParameterNames(empty($data['coordinateParameterNames']) ? false : $data['coordinateParameterNames'])
                 ->validation(empty($data['validation']) ? [] : $data['validation'])
                 ->args(['resource' => $data])
                 ->extra('params');
        }
        else
        {
          $router->redirect($resource, $data['redirect'], $methods)
                 ->ssl(empty($data['ssl']) ? false : $data['ssl'])
                 ->component(empty($data['component']) ? URL::PATH : $data['component'])
                 ->validation(empty($data['validation']) ? [] : $data['validation']);
        }
      }
    }
    $output = $router->route();
    if (!$output['success']) static::notFound();
    if (!static::$response->isSent())
    {
      static::$response->body = static::convert($output['result']);
      static::$response->send();
    }
  }
  
  /**
   * This method is automatically called when the current request does not match any API methods ($map's callbacks).
   * The method stops the script execution and sets the response status code to 404.
   *
   * @param mixed $content - the response body.
   * @access protected
   * @static
   */
  protected static function notFound($content = null)
  {
    static::$response->stop(404, $content !== null ? static::convert($content) : null);
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
  
  /**
   * The batch API method allowing to perform several independent between themselves API methods at once.
   *
   * @return array
   * @access protected
   */
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
  
  /**
   * Automatically invokes before the API method call.
   *
   * @param array $resource - the part of $map which corresponds the current request.
   * @param array $params - the values of the URL template variables.
   * @access protected
   */
  protected function before(array $resource, array &$params){}
  
  /**
   * Automatically invokes after the API method call.
   *
   * @param array $resource - the part of $map which corresponds the current request.
   * @param array $params - the values of the URL template variables.
   * @param mixed $result - the result of the API method execution.
   * @access protected
   */
  protected function after(array $resource, array $params, &$result){}
}