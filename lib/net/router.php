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

  /**
   * Array of actions for the routing.
   * 
   * @var array $acts   
   */
  protected $acts = array();
  
  protected $lact = null;
  
  public function component($component)
  {
    if ($this->lact === null) throw new Core\Exception($this, 'ERR_ROUTER_1');
    $this->acts[$this->lact[0]][$this->lact[1]]['component'] = $component;
    return $this;
  }
  
  public function methods($methods)
  {
    if ($this->lact === null) throw new Core\Exception($this, 'ERR_ROUTER_1');
    if ($methods == '*') $methods = 'GET|POST|PUT|DELETE';
    else if ($methods == '@') $methods = 'GET|POST|PUT|DELETE|HEAD|OPTIONS|TRACE|CONNECT';
    $this->acts[$this->lact[0]][$this->lact[1]]['methods'] = $methods;
    return $this;
  }
  
  public function args($args)
  {
    if ($this->lact === null) throw new Core\Exception($this, 'ERR_ROUTER_1');
    if (!is_array($args)) $args = (array)$args;
    $this->acts[$this->lact[0]][$this->lact[1]]['args'] = array_merge($this->acts[$this->lact[0]][$this->lact[1]]['args'], $args);
    return $this;
  }
  
  public function coordinateParameterNames($flag)
  {
    if ($this->lact === null) throw new Core\Exception($this, 'ERR_ROUTER_1');
    $this->acts[$this->lact[0]][$this->lact[1]]['coordinateParameterNames'] = $flag;
    return $this;
  }
  
  public function ignoreWrongDelegate($flag)
  {
    if ($this->lact === null) throw new Core\Exception($this, 'ERR_ROUTER_1');
    $this->acts[$this->lact[0]][$this->lact[1]]['ignoreWrongDelegate'] = $flag;
    return $this;
  }
  
  public function extra($parameter = 'extra')
  {
    if ($this->lact === null) throw new \Exception(err_msg(self::ERR_ROUTER_1));
    $this->acts[$this->lact[0]][$this->lact[1]]['extra'] = $parameter;
    return $this;
  }
  
  public function remove($regex, $type = null)
  {
    if ($type === null)
    {
      unset($this->acts['secure'][$regex]);
      unset($this->acts['redirect'][$regex]);
      unset($this->acts['bind'][$regex]);
    }
    else unset($this->acts[$type][$regex]);
    if ($this->lact !== null && $this->lact[0] == $type && $this->lact[1] == $regex) $this->lact = null;
    return $this;
  }
  
  public function clean($type = null)
  {
    if ($type === null) $this->acts = array();
    else unset($this->acts[$type]);
    $this->lact = null;
  }

  /**
   * Enables or disables HTTPS protocol for the given URL template.
   *
   * @param string $regex - regex for the given URL.
   * @param boolean $flag
   * @return self
   * @access public
   */
  public function secure($regex, $flag)
  {
    $action = function() use($flag)
    {
      $url = new URL();
      if ($url->isSecured() != $flag) 
      {
        $url->secure($flag);
        \Aleph::go($url->build());
      }
    };
    $this->acts['secure'][$regex] = array('action' => $action,
                                          'args' => array(),
                                          'params' => array(),
                                          'component' => URL::COMPONENT_ALL, 
                                          'methods' => 'GET|POST');
    $this->lact = array('secure', $regex);
    return $this;
  }
  
  /**
   * Sets the redirect for the given URL regex template.
   *
   * @param string $regex - regex URL template.
   * @param string $redirect - URL to redirect.
   * @return self
   * @access public
   */
  public function redirect($regex, $redirect)
  {
    $params = $this->parseURLTemplate($regex, $regex);
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
    $this->acts['redirect'][$regex] = array('action' => $action,
                                            'args' => array(),
                                            'params' => $params, 
                                            'component' => URL::COMPONENT_PATH, 
                                            'methods' => 'GET|POST');
    $this->lact = array('redirect', $regex);
    return $this;
  }
  
  /**
   * Binds an URL regex template with some action.
   *
   * @param string $regex - regex URL template.
   * @param closure | Aleph\Core\IDelegate | string $action
   * @return self
   * @access public
   */
  public function bind($regex, $action)
  {
    $params = $this->parseURLTemplate($regex, $regex);
    $this->acts['bind'][$regex] = array('action' => $action, 
                                        'args' => array(),
                                        'params' => $params, 
                                        'component' => URL::COMPONENT_PATH, 
                                        'methods' => 'GET|POST', 
                                        'coordinateParameterNames' => false, 
                                        'ignoreWrongDelegate' => true);
    $this->lact = array('bind', $regex);
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
      foreach ($this->acts[$type] as $regex => $data)
      {
        if (!is_array($data['methods'])) $data['methods'] = explode('|', $data['methods']);
        foreach ($data['methods'] as &$method) $method = strtoupper(trim($method));
        if (!array_intersect($methods, $data['methods'])) continue;
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
}