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
  // Default message template about site locking.
  const MSG_LOCKED_RESOURCE = 'The requested resource is currently locked.';
  
  /**
   * Cache object which used for locking/unlocking of the application.
   *
   * @var Aleph\Cache\Cache $cache
   * @access public
   * @static
   */
  public static $cache = null;
  
  /**
   * Routing map.
   *
   * @var array $map
   * @access protected
   */
  protected $map = array();
  
  /**
   * Array of the basic HTTP error handlers (processing 404 and 403 errors is only available).
   *
   * @var array $handlers
   * @access protected
   */
  protected $handlers = array();
  
  /**
   * Constructor. Checks whether the site is locked or not. If the site is locked the appropriate message (template) will be displayed.
   *
   * @param array $map - routing map.
   * @param Aleph\Cache\Cache $cache - cache object for storing locked mark of the site.
   * @access public
   */
  public function __construct(array $map = array(), Cache\Cache $cache = null)
  {
    $a = \Aleph::getInstance();
    if (!empty($a['locked']))
    {
      $cache = $cache ?: (self::$cache instanceof Cache\Cache ? self::$cache : $a->cache());
      if (!empty($a['unlockKey']) && (isset($_REQUEST[$a['unlockKey']]) || !$cache->isExpired(\CB::getSiteUniqueID() . $a['unlockKey'])))
      {
        if (isset($_REQUEST[$a['unlockKey']])) $cache->set(\Aleph::getSiteUniqueID() . $a['unlockKey'], true, isset($a['unlockKeyExpire']) ? $a['unlockKeyExpire'] : 108000);
      }
      else
      {
        $a->response()->stop(423, isset($a['templateLock']) ? file_get_contents(\Aleph::dir($a['templateLock'])) : self::MSG_LOCKED_RESOURCE);
      }
    }
    $this->map = $map;
  }
  
  /**
   * Sets HTTP error handler. Processing of 404th and 403rd errors is only available.
   *
   * @param integer $status - HTTP code error.
   * @param string | \Closure | Aleph\Core\IDelegate $callback
   * @return self
   * @access public
   */
  public function setErrorHandler($status, $callback)
  {
    $this->handlers[$status] = new Core\Delegate($callback);
    return $this;
  }
  
  /**
   * Returns callback object that associated with HTTP error code or FALSE otherwise.
   *
   * @param integer $status - HTTP code error.
   * @return Aleph\Core\Delegate | boolean
   * @access public
   */
  public function getErrorHandler($status)
  {
    return isset($this->handlers[$status]) ? $this->handlers[$status] : false;
  }
 
 /**
   * Determines Aleph\MVC\Page class by URL and consistently calls its methods.
   *
   * @param Aleph\MVC\Page $page
   * @param string | array $methods - HTTP request methods.
   * @access public
   */
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