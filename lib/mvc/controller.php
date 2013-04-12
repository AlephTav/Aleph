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

namespace Aleph\MVC;

use Aleph\Core,
    Aleph\Cache;

/**
 * This class is designed for controlling of page classes.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.mvc
 */
class Controller
{
  const MSG_LOCKED_RESOURCE = 'The requested resource is currently locked.';
  
  public static $defaultCache = null;
  
  protected $map = array();
  
  protected $errHandlers = array();
  
  public function __construct(array $map = array(), Cache\Cache $cache = null)
  {
    $a = \Aleph::getInstance();
    if (!empty($a->locked))
    {
      $cache = $cache ?: (self::$defaultCache instanceof Cache\Cache ? self::$defaultCache : $a->cache());
      if (!empty($a->unlockKey) && (isset($_REQUEST[$a->unlockKey]) || !$cache->isExpired(\Aleph::getSiteUniqueID() . $a->unlockKey)))
      {
        if (isset($_REQUEST[$a->unlockKey])) $cache->set(\Aleph::getSiteUniqueID() . $a->unlockKey, true, isset($a->unlockKeyExpire) ? $a->unlockKeyExpire : 108000);
      }
      else
      {
        $a->response()->stop(423, isset($a->templateLock) ? file_get_contents(\Aleph::dir($a->templateLock)) : self::MSG_LOCKED_RESOURCE);
      }
    }
    $this->map = $map;
  }
  
  public function setErrorHandler($status, $callback)
  {
    $this->errHandlers[$status] = new Core\Delegate($callback);
    return $this;
  }
  
  public function getErrorHandler($status)
  {
    return isset($this->errHandlers[$status]) ? $this->errHandlers[$status] : false;
  }
 
  public function execute(IPage $page = null, $methods = null)
  {
    $a = \Aleph::getInstance();
    if ($page === null)
    {
      $router = $a->router();
      foreach (array('secure', 'redirect', 'bind') as $method)
      {
        if (empty($this->map[$method])) continue;
        foreach ($this->map[$method] as $url => $params)
        {
          if (is_array($params))
          {
            $router->{$method}($url, isset($params[0]) ? $params[0] : $params['action']);
            if (isset($params['component'])) $router->component($params['component']);
            if (isset($params['methods'])) $router->methods($params['methods']);
            if (isset($params['checkParameters'])) $router->checkParameters($params['checkParameters']);
            if (isset($params['ignoreWrongDelegate'])) $router->ignoreWrongDelegate($params['ignoreWrongDelegate']);
          }
          else
          {
            $router->{$method}($url, $params);
          }
        }
      }
      $res = $router->route($methods);
      if ($res->success === false)
      {
        if (isset($this->errHandlers[404]))
        {
          $this->errHandlers[404]->call(array($this));
          $a->response()->stop(404);
        }
        $a->response()->stop(404, 'The requested page is not found.');
      }
      else
      {
        if (!($res->result instanceof IPage)) return $res->result;
        $page = $res->result;
      }      
    }
    Page::$page = $page;
    if (!$page->access())
    {
      if (!empty($page->noAccessURL)) \Aleph::go($page->noAccessURL);
      else if (isset($this->errHandlers[403]))
      {
        $this->errHandlers[403]->call();
        $a->response()->stop(403);
      }
      $a->response()->stop(403, 'Access denied.');
    }
    if ($a->request()->method == 'GET' && !$a->request()->isAjax)
    {
      if ((int)$page->expire > 0 && !isset($page->cache[$page->getPageID()])) $a->response()->stop(200, $page->cache[$page->getPageID()]);
      foreach ($page->getSequenceMethods(true) as $method) $page->{$method}();
    }
    else
    {
      foreach ($page->getSequenceMethods(false) as $method) $page->{$method}();
    }
  }
}