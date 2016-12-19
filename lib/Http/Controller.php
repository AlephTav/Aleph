<?php
/**
 * Copyright (c) 2013 - 2016 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2016 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Http;

use Aleph,
    Aleph\Core,
    Aleph\Http\Exceptions;

/**
 * The base class for creating of the REST API system.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.1
 * @package aleph.http
 */
class Controller
{
    /**
     * Error message templates.
     */
    const ERR_API_1 = 'Callback parameter is not set for resource "%s".';
  
    /**
     * The current request object.
     *
     * @var \Aleph\Http\Request
     */
    private $request = null;
  
    /**
     * The current response object.
     *
     * @var \Aleph\Http\Response
     */
    private $response = null;
    
    /**
     * The router instance.
     *
     * @var \Aleph\Http\Router
     */
    private $router = null;
  
    /**
     * Determines whether any error information is converted
     * according to the defined content type and charset.
     *
     * @var bool
     */
    protected $convertErrors = false;
    
    /**
     * Constructor.
     *
     * @param \Aleph\Http\Request $request
     * @param \Aleph\Http\Response $response
     * @param \Aleph\Http\Router $router
     * @return void
     */
    public function __construct(Request $request = null, Response $response = null, Router $router = null)
    {
        Aleph::setErrorHandler([$this, 'errorHandler']);
        $this->request = $request ?: Request::createFromGlobals(true);
        $this->response = $response ?: Response::createFromGlobals(true);
        $this->router = $router ?: new Router();
        $this->adjustRouter();
    }
    
    /**
     * Returns the request instance.
     *
     * @return \Aleph\Http\Request
     */
    public function getRequest() : Request
    {
        return $this->request;
    }
    
    /**
     * Sets the new request instance.
     *
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
    
    /**
     * Returns the response instance.
     *
     * @return \Aleph\Http\Response
     */
    public function getResponse() : Response
    {
        return $this->response;
    }
    
    /**
     * Sets the new response instance.
     *
     * @param \Aleph\Http\Response $response
     * @return void
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }
    
    /**
     * Returns the router instance.
     *
     * @return \Aleph\Http\Router
     */
    public function getRouter() : Router
    {
        return $this->router;
    }
    
    /**
     * Sets the new router instance.
     *
     * @return void
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
        $this->adjustRouter();
    }
    
    /**
     * Performs the current HTTP request.
     *
     * @return void
     */
    final public function run()
    {
        $response =  $this->getResponse();
        try
        {
            $res = $this->route();
        }
        catch (Exceptions\BadRequestException $e)
        {
            $res = $this->badRequest($e->getMessage());
        }
        catch (Exceptions\UnauthorizedException $e)
        {
            $res = $this->unauthorized($e->getMessage());
        }
        catch (Exceptions\AccessDeniedException $e)
        {
            $res = $this->forbidden($e->getMessage());
        }
        catch (Exceptions\NotFoundException $e)
        {
            $res = $this->notFound($e->getMessage());
        }
        catch (Exceptions\MethodNotAllowedException $e)
        {
            $res = $this->notAllowed($e->getMethods(), $e->getMessage());
        }
        catch (Exceptions\NotImplementedException $e)
        {
            $res = $this->notImplemented($e->getMessage());
        }
        catch (Exceptions\Exception $e)
        {
            $res = $this->httpError($e->getStatusCode(), $e->getMessage());
        }
        if ($res instanceof Response)
        {
            $response = $res;
        }
        else
        {
            $response->setStatusCode(200)->setBody($res);
        }
        if (!$response->isSent())
        {
            $response->send();
        }
    }
    
    /**
     * The error and exception handler.
     * This method stops the script execution and sets the response status code to 500.
     *
     * @param \Throwable $e The exception that occurred.
     * @param array $info The exception information.
     * @return bool
     */
    public function errorHandler(\Throwable $e, array $info) : bool
    {
        if ($this->convertErrors)
        {
            if (!Aleph::get('debugging'))
            {
                $this->getResponse()->stop();
            }
            $this->getResponse()->stop(500, $this->getErrorContent(500, $info));
        }
        return true;
    }
    
    /**
     * This method is automatically called when the server cannot or will not process the request
     * due to something that is perceived to be a client error.
     * The method stops the script execution and sets the response status code to 400.
     *
     * @param mixed $content The response body.
     * @return \Aleph\Http\Response
     */
    public function badRequest($content = '') : Response
    {
        return $this->getResponse()->stop(400, $this->getErrorContent(400, $content), false);
    }
    
    /**
     * This method is automatically called when authentication is required and has failed or has not yet been provided.
     * The method stops the script execution and sets the response status code to 401.
     *
     * @param mixed $content The response body.
     * @return \Aleph\Http\Response
     */
    public function unauthorized($content = '') : Response
    {
        return $this->getResponse()->stop(401, $this->getErrorContent(401, $content), false);
    }
    
    /**
     * This method is automatically called when the request was a valid request, but the server is refusing to respond to it.
     * The method stops the script execution and sets the response status code to 403.
     *
     * @param mixed $content The response body.
     * @return \Aleph\Http\Response
     */
    public function forbidden($content = '') : Response
    {
        return $this->getResponse()->stop(403, $this->getErrorContent(403, $content), false);
    }
    
    /**
     * This method is automatically called when the current request does not match any API methods ($map's callbacks).
     * The method stops the script execution and sets the response status code to 404.
     *
     * @param mixed $content The response body.
     * @return \Aleph\Http\Response
     */
    public function notFound($content = '') : Response
    {
        return $this->getResponse()->stop(404, $this->getErrorContent(404, $content), false);
    }
  
    /**
     * This method is automatically called when a request was made of a resource using a request method not supported by that resource.
     * The method stops the script execution and sets the response status code to 405.
     *
     * @param array $methods The HTTP methods that supported by the resource.
     * @param mixed $content The response body.
     * @return \Aleph\Http\Response
     */
    public function notAllowed(array $methods = [], $content = '') : Response
    {
        $this->getResponse()->headers->set('Allow', implode(', ', $methods));
        return $this->getResponse()->stop(405, $this->getErrorContent(405, $content), false);
    }
    
    /**
     * This method is automatically called when the server either does not recognize the request method, or it lacks the ability to fulfill the request. 
     * The method stops the script execution and sets the response status code to 501.
     *
     * @param mixed $content The response body.
     * @return \Aleph\Http\Response
     */
    public function notImplemented($content = '') : Response
    {
        return $this->getResponse()->stop(501, $this->getErrorContent(501, $content), false);
    }
    
    /**
     * This method is automatically called when the server failed to process request and
     * the status code was not be equal one of the following codes: 400, 401, 403, 404, 405 and 501.
     *
     * @param int $statusCode The response HTTP status code.
     * @param mixed $content The response body.
     * @return \Aleph\Http\Response
     */
    public function httpError(int $statusCode, $content = '') : Response
    {
        return $this->getResponse()->stop($statusCode, $this->getErrorContent($statusCode, $content), false);
    }
    
    /**
     * Returns the response body if any HTTP error is occured.
     *
     * @param int $statusCode The HTTP response status code.
     * @param mixed $content The default error content.
     * @return mixed
     */
    protected function getErrorContent($statusCode, $content)
    {
        return $content;
    }
    
    /**
     * Preforms routing.
     *
     * @return mixed
     */
    protected function route()
    {
        return $this->getRouter()->route($this->getRequest());
    }
    
    /**
     * Automatically invokes before the API method call.
     *
     * @param string $class The class name of controller.
     * @param string $method The method of controller.
     * @param array $params The method parameters.
     * @return void
     */
    protected function before(string $class, string $method, array &$params){}
  
    /**
     * Automatically invokes after the API method call.
     *
     * @param string $class The class name of controller.
     * @param string $method The method of controller.
     * @param array $params The method parameters.
     * @param mixed $result The result of the method execution.
     * @return void
     */
    protected function after(string $class, string $method, array $params, &$result){}
    
    /**
     * Adjusts the matched route to call action() method before actual routing.
     *
     * @return void
     */
    private function adjustRouter()
    {
        $action = function(array $resource, array $params)
        {
            $this->action($resource, $params);
        };
        $this->router->onBeforeRoute(function(array $data) use($action)
        {
            $data['args'] = ['resource' => $data];
            $data['action'] = $action;
            $data['extra'] = 'params';
            $data['sync'] = false;
            return $data;
        });
    }
    
    /**
     * Executes an action on the controller.
     *
     * @param array $resource The requested resource (an action with options).
     * @param array $params The URL template variables.
     * @return mixed
     */
    private function action(array $resource, array $params = null)
    {
        $callback = $resource['action'];
        foreach ($params as $param => $value)
        {
            $callback = str_replace('#' . $param . '#', $value, $callback, $count);
            if ($count > 0)
            {
                unset($params[$param]);
            }
        }
        $callback = new Core\Callback($callback);
        if (!empty($resource['extra']))
        {
            $resource['args'][$resource['extra']] = $params;
        }
        else 
        {
            $resource['args'] = array_merge($resource['args'], $params);
        }
        if (empty($resource['sync']))
        {
            $params = $resource['args'];
        }
        else
        {
            $params = [];
            foreach ($callback->getParameters() as $param)
            {
                $name = $param->getName();
                if (array_key_exists($name, $resource['args']))
                {
                    $params[] = $resource['args'][$name];
                }
            }
        }
        $class = $callback->getClass();
        if ($callback->isStatic() || !is_a($class, self::class, true))
        {
            return $callback->call($params);
        }
        $method = $callback->getMethod();
        $obj = $class == self::class ? $this : new $class($this->getRequest(), $this->getResponse(), $this->getRouter());
        $obj->before($class, $method, $params);
        $result = call_user_func_array([$obj, $method], $params);
        $obj->after($class, $method, $params, $result);
        return $result;
    }
}