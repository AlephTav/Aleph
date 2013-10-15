<?php

namespace Aleph\Net;

use Aleph\Core;

class API
{
  const ERR_API_1 = 'Callback parameter is not set for resource "[{var}]".';

  protected static $map = array();
  protected static $request = null;
  protected static $response = null;
  protected static $contentType = 'json';
  protected static $namespace = '\\';
  protected static $convertErrors = false;
  
  public static function error(\Exception $e, array $info)
  {
    $a = \Aleph::getInstance();
    $response = Response::getInstance();
    if (!$a['debugging']) $response->stop(500, '');
    if (static::$convertErrors)
    {
      $response->convertOutput = true;
      $response->headers->setContentType('json');
      $response->stop(500, $info);
    }
    return true;
  }
  
  protected static function notFound($content = null)
  {
    self::$response->stop(404, $content);
  }

  public static function process()
  {
    \Aleph::getInstance()->setConfig(['customDebugMethod' => __CLASS__ . '::error']);
    self::$request = Request::getInstance();
    self::$response = Response::getInstance();
    self::$response->convertOutput = true;
    self::$response->headers->setContentType(static::$contentType);
    $namespace = static::$namespace;
    $process = function(array $resource, array $params = null) use ($namespace)
    {
      if (empty($resource['callback'])) throw new Core\Exception('Aleph\Net\API', 'ERR_API_1', $resource);
      if (isset($resource['contentType'])) API::$response->headers->setContentType($resource['contentType']);
      $callback = $resource['callback'];
      if ($callback[0] != '\\') $callback = $namespace . $callback;
      $callback = new Core\Delegate($callback);
      $api = $callback->getClassObject();
      $api->before($resource, $params);
      $result = call_user_func_array(array($api, $callback->getMethod()), $params);
      $api->after($resource, $params);
      API::$response->body = $result;
      API::$response->send();
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
                 ->component(empty($data['component']) ? URL::PATH : $data['component']);
        }
        else
        {
          $router->bind($resource, $process, $methods)
                 ->component(empty($data['component']) ? URL::PATH : $data['component'])
                 ->ignoreWrongDelegate(empty($data['ignoreWrongDelegate']) ? false : $data['ignoreWrongDelegate'])
                 ->coordinateParameterNames(empty($data['coordinateParameterNames']) ? false : $data['coordinateParameterNames'])
                 ->args(array('resource' => $data))
                 ->extra('params');
        }
      }
    }
    if (!$router->route()['success']) static::notFound();
  }
  
  protected function batch()
  {
    $result = array();
    $data = $this->request->body;
    foreach ($data as $request)
    {
      $this->request->method = $request['method'];
      $this->request->url->parse($request['url']);
      $this->request->data = $this->request->url->query;
      $this->request->input = $request['body'];
      $this->request->body = $this->request->convert();
      self::process();
      $result[] = $this->response->body;
    }
    return $result;
  }
  
  protected function before(array $resource, array &$params){}
  
  protected function after(array $resource, array &$params){}
}