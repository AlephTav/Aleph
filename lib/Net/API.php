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

use Aleph\Core;

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
     * @access public
     * @static
     */
    public static $map = [];
  
    /**
     * The current request object.
     *
     * @var Aleph\Net\Request $request
     * @access public
     * @static
     */
    public static $request = null;
  
    /**
     * The current response object.
     *
     * @var Aleph\Net\Response $response
     * @access public
     * @static
     */
    public static $response = null;
  
    /**
     * The response content type. It can be regular MIME-type or its alias (if exists).
     *
     * @var string $contentType
     * @access public
     * @static
     */
    public static $contentType = 'json';
  
    /**
     * The output charset of the response body.
     *
     * @var string $charset
     * @access public
     * @static
     */
    public static $charset = 'UTF-8';

    /**
     * The namespace prefix for the callbacks that specified in the $map.
     *
     * @var string $namespace
     * @access public
     * @static
     */
    public static $namespace = '\\';
  
    /**
     * Prefix for URLs.
     *
     * @var string $urlPrefix
     * @access public
     * @static
     */
    public static $urlPrefix = '';
  
    /**
     * Determines whether the response body is converted according to the defined content type and charset.
     *
     * @var boolean $convertOutput
     * @access public
     * @static
     */
    public static $convertOutput = true;
  
    /**
     * Determines whether any error information is converted according to the defined content type and charset.
     *
     * @var boolean $convertErrors
     * @access public
     * @static
     */
    public static $convertErrors = true;
  
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
        if (!\Aleph::get('debugging'))
        {
            static::$response->stop(500, null);
        }
        if (!static::$convertErrors)
        {
            return true;
        }
        static::$response->stop(500, $info);
    }
    
    /**
     * This method is automatically called when the server cannot or will not process the request
     * due to something that is perceived to be a client error.
     * The method stops the script execution and sets the response status code to 400.
     *
     * @param mixed $content - the response body.
     * @access public
     * @static
     */
    public static function badRequest($content = null)
    {
        static::$response->stop(400, $content);
    }
    
    /**
     * This method is automatically called when authentication is required and has failed or has not yet been provided.
     * The method stops the script execution and sets the response status code to 401.
     *
     * @param mixed $content - the response body.
     * @access public
     * @static
     */
    public static function unauthorized($content = null)
    {
        static::$response->stop(401, $content);
    }
    
    /**
     * This method is automatically called when the request was a valid request, but the server is refusing to respond to it.
     * The method stops the script execution and sets the response status code to 403.
     *
     * @param mixed $content - the response body.
     * @access public
     * @static
     */
    public static function forbidden($content = null)
    {
        static::$response->stop(403, $content);
    }
    
    /**
     * This method is automatically called when the current request does not match any API methods ($map's callbacks).
     * The method stops the script execution and sets the response status code to 404.
     *
     * @param mixed $content - the response body.
     * @access public
     * @static
     */
    public static function notFound($content = null)
    {
        static::$response->stop(404, $content);
    }
  
    /**
     * This method is automatically called when a request was made of a resource using a request method not supported by that resource.
     * The method stops the script execution and sets the response status code to 405.
     *
     * @param array $methods - the HTTP methods that supported by the resource.
     * @param mixed $content - the response body.
     * @access public
     * @static
     */
    public static function notAllowed(array $methods = [], $content = null)
    {
        static::$response->headers->set('Allow', implode(', ', $methods));
        static::$response->stop(405, $content);
    }
    
    /**
     * This method is automatically called when the server either does not recognize the request method, or it lacks the ability to fulfill the request. 
     * The method stops the script execution and sets the response status code to 501.
     *
     * @param mixed $content - the response body.
     * @access public
     * @static
     */
    public static function notImplemented($content = null)
    {
        static::$response->stop(501, $content);
    }

    /**
     * Invokes and performs the requested API method.
     * This method is the entry point of the API class system.
     *
     * @access public
     * @static
     */
    final public static function run()
    {
        $class = get_called_class();
        $action = $class . '::action';
        \Aleph::setErrorHandler($class . '::error');
        static::$request = static::$request instanceof Request ? static::$request : Request::createFromGlobals(true);
        static::$response = static::$response instanceof Response ? static::$response : new Response();
        static::$response->setContentType(static::$contentType, static::$charset);
        $router = new Router();
        foreach (static::$map as $resource => $info)
        {
            if ($resource && $resource[0] == '@') 
            {
                $resource = static::$urlPrefix . substr($resource, 1);
            }
            foreach ($info as $methods => $data)
            {
                $router->bind($methods, $resource, $action)
                       ->secure(empty($data['secure']) ? false : $data['secure'])
                       ->component(empty($data['component']) ? URL::PATH : $data['component'])
                       ->where(empty($data['where']) ? [] : $data['where'])
                       ->associateWithParameters(empty($data['associateWithParameters']) ? false : $data['associateWithParameters'])
                       ->args(['resource' => $data])
                       ->extra('params');
            }
        }
        $res = $router->route(static::$request);
        if (headers_sent())
        {
            return;
        }
        static::$response->setStatusCode($res['status']);
        switch ($res['status'])
        {
            case 400:
                $res['result'] = static::badRequest();
                break;
            case 401:
                $res['result'] = static::unauthorized();
                break;
            case 403:
                $res['result'] = static::forbidden();
                break;
            case 404:
                $res['result'] = static::notFound();
                break;
            case 405:
                $res['result'] = static::notAllowed($res['methods']);
                break;
            case 501:
                $res['result'] = static::notImplemented();
                break;
        }
        if ($res['result'] instanceof Response)
        {
            $res['result']->send();
        }
        else
        {
            static::$response->setBody($res['result']);
            static::$response->send();
        }
    }
    
    /**
     * Executes an action on the controller.
     *
     * @param array $resource - the URL templater of the requested resource.
     * @param array $params - the URL template variables.
     * @return mixed
     * @access public
     * @static
     */
    public static function action(array $resource, array $params = null)
    {
        if (empty($resource['callback']))
        {
            throw new \RuntimeException(sprintf(static::ERR_API_1, $resource));
        }
        $callback = $resource['callback'];
        foreach ($params as $param => $value)
        {
            $callback = str_replace('#' . $param . '#', $value, $callback, $count);
            if ($count > 0)
            {
                unset($params[$param]);
            }
        }
        if ($callback[0] != '\\')
        {
            $callback = rtrim(static::$namespace, '\\') . '\\' . ltrim($callback, '\\');
        }
        $callback = new Core\Delegate($callback);
        if ($callback->isStatic()) 
        {
            return $callback->call($params);
        }
        $api = $callback->getClassObject($params);
        if ($api instanceof API)
        {
            $api->before($resource, $params);
            $result = call_user_func_array([$api, $callback->getMethod()], $params);
            $api->after($resource, $params, $result);
        }
        else
        {
            $result = call_user_func_array([$api, $callback->getMethod()], $params);
        }
        return $result;
    }
  
    /**
     * The batch API method allowing to perform several independent between themselves API methods at once.
     * The method returns execution result of all API endpoints. 
     *
     * @return array
     * @access protected
     */
    /*protected function batch()
    {
        $result = [];
        $data = json_decode(static::$request->getBody(), true);
        if (!is_array($data))
        {
            static::badRequest();
        }
        foreach ($data as $request)
        {
            static::$request->setMethod($request['method']);
            static::$request->url->parse($request['url']);
            if (!empty($request['get']))
            {
                static::$request->get->replace($request['get']);
            }
            $this->request->setBody($request['body']);
            self::run();
            $result[] = static::$response->getBody();
        }
        return $result;
    }*/
  
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