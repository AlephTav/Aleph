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
 * With this class you can route the requested URLs.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.core
 */
class Router
{
  // Error message templates.
  const ERR_ROUTER_1 = 'No action is defined. You should first call one of the following methods: secure(), redirect() or bind()';
  
  /**
   * This property is used as the cache of parsed URL templates.
   *
   * @var array $rex
   * @access private
   */
  private static $rex = [];

  /**
   * Array of actions for the routing.
   * 
   * @var array $acts
   * @access private
   */
  private $acts = [];
  
  /**
   * Stores the link on the last invoked method.
   *
   * @var array $lact
   * @access private
   */
  private $lact = null;
  
  /**
   * Sets URL-component for the current URL template.
   *
   * @param integer $component
   * @return self
   * @access public
   */
  public function component($component)
  {
    return $this->option('component', $component);
  }
  
  /**
   * Sets array of regular expressions that applied to appropriate variables of the URL template.
   * if at least one regular expression does not match with the appropriate URL template variable, then the callback is not called.
   *
   * @param array $validation
   * @return self
   * @access public   
   */
  public function validation(array $validation)
  {
    return $this->option('validation', $validation);
  }
  
  /**
   * Sets an associated array of additional parameters that will be passed to the callback.
   *
   * @param array $args
   * @return self
   * @access public
   */
  public function args(array $args)
  {
    return $this->option('args', $args);
  }
  
  /**
   * Determines whether to synchronize the URL template variables and parameters of the callback.
   *
   * @param boolean $flag
   * @return self
   * @access public
   */
  public function coordinateParameterNames($flag = true)
  {
    return $this->option('coordinateParameterNames', (bool)$flag);
  }
  
  /**
   * If this parameter is set then all URL template variables (as associated array) are passed to extra parameter.
   *
   * @param string $parameter - name of callback parameter determining as extra parameter.
   * @return self
   * @access public
   */
  public function extra($parameter = 'extra')
  {
    return $this->option('extra', $parameter);
  }
  
  /**
   * Determines whether the request should have the secured HTTPS protocol.
   *
   * @param boolean $flag - if equals TRUE, the request is supposed to be secured.
   * @return self
   * @access public
   */
  public function ssl($flag)
  {
    return $this->option('ssl', (bool)$flag);
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
      foreach (['secure', 'redirect', 'bind'] as $type)
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
    if ($type === null) $this->acts = [];
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
   * @param mixed $callback - a delegate that will be invoked before the redirect.
   * @return self
   * @access public
   */
  public function secure($regex, $flag, $methods = '*', $callback = null)
  {
    $methods = $this->normalizeMethods($methods);
    $action = function() use($flag, $callback)
    {
      $url = new URL();
      if ($url->isSecured() != $flag) 
      {
        $url->secure($flag);
        $url = $url->build();
        if ($callback === null) \Aleph::go($url);
        return \Aleph::delegate($callback, $url);
      }
    };
    $data = ['action' => $action,
             'args' => [],
             'component' => URL::ALL,
             'validation' => []];
    foreach ($methods as $method) $this->acts['secure'][$method][$regex] = $data;
    $this->lact = ['secure', $regex, $methods];
    return $this;
  }
  
  /**
   * Sets the redirect for the given URL regex template.
   *
   * @param string $regex - regex URL template.
   * @param string $redirect - URL to redirect.
   * @param array | string $methods - HTTP methods for which the redirect is permitted.
   * @param mixed $callback - a delegate that will be invoked before the redirect.
   * @return self
   * @access public
   */
  public function redirect($regex, $redirect, $methods = '*', $callback = null)
  {
    $methods = $this->normalizeMethods($methods);
    $data = ['args' => [],
             'component' => URL::PATH,
             'validation' => [],
             'redirect' => $redirect,
             'callback' => $callback];
    foreach ($methods as $method) $this->acts['redirect'][$method][$regex] = $data;
    $this->lact = ['redirect', $regex, $methods];
    return $this;
  }
  
  /**
   * Binds an URL regex template with some action.
   *
   * @param string $regex - regex URL template.
   * @param mixed $action - a delegate.
   * @param array | string $methods - HTTP methods for which the given action is permitted.
   * @return self
   * @access public
   */
  public function bind($regex, $action, $methods = '*')
  {
    $methods = $this->normalizeMethods($methods);
    $data = ['action' => $action, 
             'args' => [],
             'component' => URL::PATH,
             'validation' => [],
             'coordinateParameterNames' => false, 
             'ignoreWrongDelegate' => false];
    foreach ($methods as $method) $this->acts['bind'][$method][$regex] = $data;
    $this->lact = ['bind', $regex, $methods];
    return $this;
  }
  
  /**
   * Performs all actions matching all URL templates.
   *
   * @param mixed $status - a variable which the result HTTP status will be written in.
   * @param string | array $methods - HTTP request methods.
   * @param string | Aleph\Net\URL $url - the URL string to route.
   * @return array with two elements: result - a result of the acted action, success - indication that the action was worked out.
   * @access public
   */
  public function route(&$status, $methods = null, $url = null)
  {
    $this->lact = null;
    $request = Request::getInstance();
    if ($methods === null) 
    {
      $methods = [$request->method];
    }
    else
    {    
      $methods = $this->normalizeMethods($methods);
    }
    if ($url === null) 
    {
      $url = $request->url;
    }
    else if (!($url instanceof URL))
    {
      $url = new URL($url);
    }
    $urls = [];
    foreach (['secure', 'redirect', 'bind'] as $type)
    {
      if (empty($this->acts[$type])) 
      {
        continue;
      }
      foreach ($this->acts[$type] as $method => $actions)
      {
        if (!in_array($method, $methods)) 
        {
          continue;
        }
        foreach ($actions as $regex => $data)
        {
          $this->prepareAction($type, $regex, $data);
          if (empty($urls[$data['component']]))
          {
            $urls[$data['component']] = $url->build($data['component']);
          }
          if (!preg_match($regex, $urls[$data['component']], $matches))
          {
            continue;
          }
          if (!empty($data['ssl']) && !$url->isSecured())
          {
            $status = 403;
            return;
          }
          $flag = true;
          foreach ($data['validation'] as $param => $rgx)
          {
            if (isset($matches[$param]) && !preg_match($rgx, $matches[$param]))
            {
              $flag = false;
              break;
            }
          }
          if (!$flag)
          {
            continue;
          }
          if ($data['action'] instanceof Core\Delegate)
          {
            $act = $data['action'];
          }
          else 
          {
            if (!($data['action'] instanceof \Closure))
            {
              foreach ($data['params'] as $k => $param)
              {
                if (!empty($matches[$param]))
                {
                  $data['action'] = str_replace('#' . $param . '#', $matches[$param], $data['action'], $count);
                  if ($count > 0)
                  {
                    unset($data['params'][$k]);
                  }
                }
              }
            }
            $act = new Core\Delegate($data['action']);
          }
          foreach ($data['params'] as &$param)
          {
            $param = isset($matches[$param]) ? $matches[$param] : null;
          }
          if (!empty($data['extra']))
          {
            $data['args'][$data['extra']] = $data['params'];
          }
          else 
          {
            $data['args'] = array_merge($data['args'], $data['params']);
          }
          $params = $data['args'];
          if (!empty($data['coordinateParameterNames']))
          {
            $params = [];
            foreach ($act->getParameters() as $param) 
            {
              $name = $param->getName();
              if (array_key_exists($name, $data['args']))
              {
                $params[] = $data['args'][$name];
              }
            } 
          }
          $status = 200;
          return $act->call($params);
        }
      }
    }
    $status = 404;
    return;
  }
  
  /**
   * Prepares action for performing.
   *
   * @param string $type - the action type. It must be one of the following values: "secure", "redirect" or "bind".
   * @param string $regex - the URL template associated with the requested action.
   * @param array $data - the additional data for the given action.
   * @access private
   */
  private function prepareAction($type, &$regex, array &$data)
  {
    if ($type == 'bind') 
    {
      $data['params'] = $this->parseURLTemplate($regex);
    }
    else if ($type == 'redirect')
    {
      $redirect = $data['redirect'];
      $callback = $data['callback'];
      $params = $this->parseURLTemplate($regex);
      $t = microtime(true); $k = 0;
      foreach ($params as $name)
      { 
        $redirect = str_replace('#' . $name . '#', md5($t + $k), $redirect);
        $k++;
      }
      $action = function() use($t, $redirect, $callback)
      {
        $url = $redirect;
        foreach (func_get_args() as $k => $arg)
        {
          $url = str_replace(md5($t + $k), $arg, $url);
        }
        if ($callback === null) \Aleph::go($url);
        return \Aleph::delegate($callback, $url);
      };
      $data['params'] = $params;
      $data['action'] = $action;
    }
    else
    {
      $data['params'] = [];
    }
  }
  
  /**
   * Returns array of HTTP methods in canonical form (in uppercase and without spaces).
   *
   * @param string|array - HTTP methods.
   * @return array
   * @access private
   */
  private function normalizeMethods($methods)
  {
    if ($methods == '*') return ['GET', 'PUT', 'POST', 'DELETE'];
    if ($methods == '@') return ['GET', 'PUT', 'POST', 'DELETE', 'HEAD', 'PATCH', 'OPTIONS', 'TRACE', 'CONNECT'];
    $methods = is_array($methods) ? $methods : explode('|', $methods);
    foreach ($methods as &$method) $method = strtoupper(trim($method));
    return $methods;
  }
  
  /**
   * Parses URL templates for the routing and returns an array of the template variables.
   *
   * @param string $regex - the given URL template to be parsed.
   * @return array
   * @access private   
   */
  private function parseURLTemplate(&$regex)
  {
    if (isset(self::$rex[$regex]))
    {
      list($regex, $params) = self::$rex[$regex];
      return $params;
    }
    $rx = $regex; $params = [];
    $regex = preg_replace_callback('/(?:\A|[^\\\])(?:\\\\\\\\)*#(.*?[^\\\](?:\\\\\\\\)*)(?:#|\z)/', function($matches) use (&$params)
    {
      $n = strpos($matches[1], '|');
      if ($n === false) 
      {
        $p = '[^/]*?';
        $name = $matches[1];
      }
      else
      {
        $name = substr($matches[1], 0, $n);
        $p = substr($matches[1], $n + 1);
        if ($p == '') $p = '[^/]*?';
      }
      $params[$name] = $name;
      $n = strlen($matches[0]);
      $n = $n - strlen($matches[1]) - ($matches[0][$n - 1] == '#' ? 1 : 0) - 1;
      $p = '(?P<' . $name . '>' . $p . ')';
      return substr($matches[0], 0, $n) . $p . substr($matches[0], $n + strlen($p));
    }, $regex);
    $regex = '#\A' . $regex . '\z#';
    self::$rex[$rx] = [$regex, $params];
    return $params;
  }
  
  /**
   * Adds new option to the current action.
   *
   * @param string $option
   * @param mixed $value
   * @return self
   * @access private
   */
  private function option($option, $value)
  {
    if ($this->lact === null) throw new Core\Exception([$this, 'ERR_ROUTER_1']);
    if (is_array($value))
    {
      foreach ($this->lact[2] as $method) $this->acts[$this->lact[0]][$method][$this->lact[1]][$option] = array_merge($this->acts[$this->lact[0]][$method][$this->lact[1]][$option], $value);
    }
    else
    {
      foreach ($this->lact[2] as $method) $this->acts[$this->lact[0]][$method][$this->lact[1]][$option] = $value;
    }
    return $this;
  }
}