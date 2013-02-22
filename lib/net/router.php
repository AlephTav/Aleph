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

/**
 * With this class you can route the requested URLs.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.core
 * @final
 */
class Router
{
  const ERR_ROUTER_1 = 'No action is defined. You should first call one of the following methods: secure(), redirect() or bind()';
  const ERR_ROUTER_2 = 'Delegate "[{var}]" is not callable.';
  const ERR_ROUTER_3 = 'URL component "[{var}]" doesn\'t exist. You should only use one of the following components: Net\URL::COMPONENT_ALL, Net\URL::COMPONENT_SOURCE, Net\URL::COMPONENT_QUERY, Net\URL::COMPONENT_PATH, Net\URL::COMPONENT_PATH_AND_QUERY';

  /**
   * Array of actions for the routing.
   * 
   * @var array $acts
   * @access protected
   */
  protected $acts = array();
  
  /**
   * Stores the link on the last invoked method.
   *
   * @var array $lact
   * @access protected
   */
  protected $lact = null;
  
  public function component($component)
  {
    if ($this->lact === null) throw new Core\Exception($this, 'ERR_ROUTER_1');
    if (!in_array($component, array(URL::COMPONENT_ALL, URL::COMPONENT_SOURCE, URL::COMPONENT_QUERY, URL::COMPONENT_PATH, URL::COMPONENT_PATH_AND_QUERY))) throw new Core\Exception($this, 'ERR_ROUTER_3');
    foreach ($this->lact[2] as $method)
    {
      $this->acts[$this->lact[0]][$method][$this->lact[1]]['component'] = $component;
    }
    return $this;
  }
  
  public function args($args)
  {
    if ($this->lact === null) throw new Core\Exception($this, 'ERR_ROUTER_1');
    if (!is_array($args)) $args = (array)$args;
    foreach ($this->lact[2] as $method)
    {
      $this->acts[$this->lact[0]][$method][$this->lact[1]]['args'] = array_merge($this->acts[$this->lact[0]][$method][$this->lact[1]]['args'], $args);
    }
    return $this;
  }
  
  public function coordinateParameterNames($flag)
  {
    if ($this->lact === null) throw new Core\Exception($this, 'ERR_ROUTER_1');
    foreach ($this->lact[2] as $method)
    {
      $this->acts[$this->lact[0]][$method][$this->lact[1]]['coordinateParameterNames'] = (bool)$flag;
    }
    return $this;
  }
  
  public function ignoreWrongDelegate($flag)
  {
    if ($this->lact === null) throw new Core\Exception($this, 'ERR_ROUTER_1');
    foreach ($this->lact[2] as $method)
    {
      $this->acts[$this->lact[0]][$method][$this->lact[1]]['ignoreWrongDelegate'] = (bool)$flag;
    }
    return $this;
  }
  
  public function extra($parameter = 'extra')
  {
    if ($this->lact === null) throw new Core\Exception($this, 'ERR_ROUTER_1');
    foreach ($this->lact[2] as $method)
    {
      $this->acts[$this->lact[0]][$method][$this->lact[1]]['extra'] = $parameter;
    }
    return $this;
  }
  
  /**
   * Removes the definite action for the given router type and HTTP methods.
   *
   * @param string $regex - a regex corresponding to the specified URL.
   * @param string $type - router type. It can be one of the following values: "bind", "redirect" or "secure".
   * @param array | string $methods - HTTP methods.
   */
  public function remove($regex, $type = null, $methods = '*')
  {
    $methods = $this->normalizeMethods($methods);
    if ($type === null)
    {
      foreach (array('secure', 'redirect', 'bind') as $type)
      {
        foreach ($methods as $method) unset($this->acts[$type][$method][$regex]);
      }
    }
    else
    {
      foreach ($methods as $method) unset($this->acts[$type][$method][$regex]);
    }
    $this->lact = null;
    return $this;
  }
  
  /**
   * Removes all router data of the certain type. 
   * If type is not set the methods removes all router data.
   *
   * @param string $type - router type. It can be one of the following values: "bind", "redirect" or "secure".
   * @return self
   * @access public
   */
  public function clean($type = null)
  {
    if ($type === null) $this->acts = array();
    else unset($this->acts[$type]);
    $this->lact = null;
    return $this;
  }

  /**
   * Enables or disables HTTPS protocol for the given URL template.
   *
   * @param string $regex - regex for the given URL.
   * @param boolean $flag
   * @param array | string $methods - HTTP methods for which the secure operation is permitted.
   * @return self
   * @access public
   */
  public function secure($regex, $flag, $methods = '*')
  {
    $methods = $this->normalizeMethods($methods);
    $action = function() use($flag)
    {
      $url = new URL();
      if ($url->isSecured() != $flag) 
      {
        $url->secure($flag);
        \Aleph::go($url->build());
      }
    };
    $data = array('action' => $action,
                  'args' => array(),
                  'params' => array(),
                  'component' => URL::COMPONENT_ALL, 
                  'methods' => $methods);
    foreach ($methods as $method) $this->acts['secure'][$method][$regex] = $data;
    $this->lact = array('secure', $regex, $methods);
    return $this;
  }
  
  /**
   * Sets the redirect for the given URL regex template.
   *
   * @param string $regex - regex URL template.
   * @param string $redirect - URL to redirect.
   * @param array | string $methods - HTTP methods for which the redirect is permitted.
   * @return self
   * @access public
   */
  public function redirect($regex, $redirect, $methods = '*')
  {
    $params = $this->parseURLTemplate($regex, $regex);
    $methods = $this->normalizeMethods($methods);
    $t = microtime(true);
    for ($k = 0, $n = count($params); $k < $n; $k++)
    {
      $redirect = preg_replace('/(?<!\\\)#((.(?!(?<!\\\)#))*.)./', md5($t + $k), $redirect);
    }
    $action = function() use($t, $redirect)
    {
      $url = $redirect;
      foreach (func_get_args() as $k => $arg)
      {
        $url = str_replace(md5($t + $k), $arg, $url);
      }
      \Aleph::go($url);
    };
    $data = array('action' => $action,
                  'args' => array(),
                  'params' => $params, 
                  'component' => URL::COMPONENT_PATH,
                  'methods' => $methods);
    foreach ($methods as $method) $this->acts['redirect'][$method][$regex] = $data;
    $this->lact = array('redirect', $regex, $methods);
    return $this;
  }
  
  /**
   * Binds an URL regex template with some action.
   *
   * @param string $regex - regex URL template.
   * @param closure | Aleph\Core\IDelegate | string $action
   * @param array | string $methods - HTTP methods for which the given action is permitted.
   * @return self
   * @access public
   */
  public function bind($regex, $action, $methods = '*')
  {
    $params = $this->parseURLTemplate($regex, $regex);
    $methods = $this->normalizeMethods($methods);
    $data = array('action' => $action, 
                  'args' => array(),
                  'params' => $params, 
                  'component' => URL::COMPONENT_PATH, 
                  'methods' => $methods, 
                  'coordinateParameterNames' => false, 
                  'ignoreWrongDelegate' => true);
    foreach ($methods as $method) $this->acts['bind'][$method][$regex] = $data;
    $this->lact = array('bind', $regex, $methods);
    return $this;
  }
  
  /**
   * Performs all actions matching all regex URL templates.
   *
   * @param string | array $methods - HTTP request methods.
   * @param string $url - the URL string to route.
   * @return \StdClass with two properties: result - a result of the acted action, success - indication that the action was worked out.
   * @access public
   */
  public function route($methods = null, $url = null)
  {
    $this->lact = null;
    $request = Request::getInstance();
    if (!is_array($methods)) 
    {
      if (!empty($methods)) $methods = explode('|', $methods);
      else $methods = array($request->method);
    }
    $res = new \StdClass();
    $res->success = false;
    $res->result = null;
    $urls = array();
    foreach (array('secure', 'redirect', 'bind') as $type)
    {
      if (empty($this->acts[$type])) continue;
      foreach ($this->acts[$type] as $method => $actions)
      {
        if (!in_array($method, $methods)) continue;
        foreach ($actions as $regex => $data)
        {
          if (isset($url)) $subject = $url;
          else
          {
            if (empty($urls[$data['component']])) $urls[$data['component']] = $request->url->build($data['component']);
            $subject = $urls[$data['component']];
          }
          if (!preg_match_all($regex, $subject, $matches)) continue;
          if ($data['action'] instanceof Core\Delegate) $act = $data['action'];
          else 
          {
            if (!($data['action'] instanceof \Closure))
            {
              foreach ($data['params'] as $k => $param)
              {
                $data['action'] = str_replace('#' . $param . '#', $matches[$param][0], $data['action'], $count);
                if ($count > 0) unset($data['params'][$k]);
              }
            }
            $act = new Core\Delegate($data['action']);
          }
          if (!$act->isCallable())
          {
            if (!empty($data['ignoreWrongDelegate'])) continue;
            throw new Core\Exception($this, 'ERR_ROUTER_2', (string)$act);
          }
          foreach ($data['params'] as &$param) $param = $matches[$param][0];
          if (!empty($data['extra'])) $data['args'][$data['extra']] = $data['params'];
          else $data['args'] = array_merge($data['args'], $data['params']);
          $params = $data['args'];
          if (!empty($data['coordinateParameterNames']))
          {
            $params = array();
            foreach ($act->getParameters() as $param) 
            {
              $name = $param->getName();
             if (array_key_exists($name, $data['args'])) $params[] = $data['args'][$name];
            } 
          }
          $res->result = $act->call($params);
          $res->success = true;
          return $res;
        }
      }
    }
    return $res;
  }
  
  /**
   * Parses URL templates for the routing.
   *
   * @param string $url
   * @param string $regex
   * @return array
   * @access private   
   */
  protected function parseURLTemplate($url, &$regex)
  {
    $params = array();
    $url = (string)$url;
    $path = preg_split('/(?<!\\\)\/+/', $url);
    $path = array_map(function($p) use(&$params)
    {
      if ($p == '') return '';
      preg_match_all('/(?<!\\\)#((?:.(?!(?<!\\\)#))*.)./', $p, $matches);
      foreach ($matches[0] as $k => $match)
      {
        $m = $matches[1][$k];
        $n = strpos($m, '|');
        if ($n !== false) 
        {
          $name = substr($m, 0, $n);
          $m = substr($m, $n + 1);
          if ($m == '') $m = '[^\/]*';
        }
        else 
        {
          $m = '[^\/]*';
          $name = $matches[1][$k];
        }
        $params[$name] = $name;
        $p = str_replace($match, '(?P<' . $name . '>' . $m . ')', $p);
      }
      return str_replace('\#', '#', $p);
    }, $path);
    $regex = '/^' . implode('\/', $path) . '$/';
    return $params;
  }
  
  /**
   * Returns array of HTTP methods in canonical form (in uppercase and without spaces).
   *
   * @param string|array - HTTP methods.
   * @return array
   * @access protected
   */
  protected function normalizeMethods($methods)
  {
    if ($methods == '*') return array('GET', 'PUT', 'POST', 'DELETE');
    if ($methods == '@') return array('GET', 'PUT', 'POST', 'DELETE', 'HEAD', 'OPTIONS', 'TRACE', 'CONNECT');
    $methods = is_array($methods) ? $methods : explode('|', $methods);
    foreach ($methods as &$method) $method = strtoupper(trim($method));
    return $methods;
  }
}