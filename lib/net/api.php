<?php

namespace Aleph\Net;

use Aleph\Core;

class API
{
  const ERR_API_1 = 'Callback parameter is not set for resource "[{var}]".';

  protected static $map = array();
  protected static $request = null;
  protected static $response = null;
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
  
  protected static function notFound()
  {
    self::$response->stop(404, '');
  }

  public static function process()
  {
    \Aleph::getInstance()->config(array('customDebugMethod' => __CLASS__ . '::error'));
    $map = static::$map;
    $namespace = static::$namespace;
    self::$request = Request::getInstance();
    self::$response = Response::getInstance();
    self::$response->convertOutput = true;
    self::$response->headers->setContentType('json');
    $process = function($resource, array $params = null) use ($map, $namespace)
    {
      if (empty($map[$resource]['callback'])) throw new Core\Exception('Aleph\Net\API', 'ERR_API_1', $resource);
      $callback = $map[$resource]['callback'];
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
    foreach ($map as $resource => $data)
    {
      $data['methods'] = empty($data['methods']) ? 'GET' : $data['methods'];
      if (isset($data['secure']))
      {
        $router->secure($resource, $data['secure'])
               ->methods($data['methods'])
               ->component(empty($data['component']) ? URL::COMPONENT_ALL : $data['component']);
      }
      else if (isset($data['redirect']))
      {
        $router->redirect($resource, $data['redirect'])
               ->methods($data['methods'])
               ->component(empty($data['component']) ? URL::COMPONENT_PATH : $data['component']);
      }
      else
      {
        $router->bind($resource, $process)
               ->methods($data['methods'])
               ->component(empty($data['component']) ? URL::COMPONENT_PATH : $data['component'])
               ->ignoreWrongDelegate(empty($data['ignoreWrongDelegate']) ? false : $data['ignoreWrongDelegate'])
               ->args(array('resource' => $resource))
               ->extra('params');
      }
    }
    if (!$router->route()->success) static::notFound();
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
  
  protected function before($resource, array &$params){}
  
  protected function after($resource, array &$params){}
}