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
 * @version 1.1.0
 * @package aleph.core
 */
class Router
{
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
     * @var array $actions
     * @access private
     */
    private $actions = [];
  
    /**
     * Stores the link on the last invoked method.
     *
     * @var array $lastAction
     * @access private
     */
    private $lastAction = null;
  
    /**
     * Sets URL component for the current URL template.
     *
     * @param integer $component
     * @return static
     * @access public
     */
    public function component($component)
    {
        return $this->option('component', $component);
    }
  
    /**
     * Sets array of regular expressions that applied to appropriate variables of the URL template.
     * if at least one regular expression does not match with the appropriate URL template variable, then the action is not called.
     *
     * @param array $where
     * @return static
     * @access public   
     */
    public function where(array $where)
    {
        return $this->option('validation', $where);
    }
  
    /**
     * Sets an associated array of additional parameters that will be passed to the action.
     *
     * @param array $args
     * @return static
     * @access public
     */
    public function args(array $args)
    {
        return $this->option('args', $args);
    }
  
    /**
     * If this parameter is set then all URL template variables (as associated array) are passed to extra parameter.
     *
     * @param string $parameter - name of action parameter determining as extra parameter.
     * @return static
     * @access public
     */
    public function extra($parameter = 'extra')
    {
        return $this->option('extra', $parameter);
    }
  
    /**
     * Determines whether the request should have the secure HTTPS protocol.
     *
     * @param boolean $flag - if equals TRUE, the request is supposed to be secure.
     * @return static
     * @access public
     */
    public function secure($flag = true)
    {
        return $this->option('secure', (bool)$flag);
    }
    
    /**
     * Determines whether to synchronize the URL template variables and parameters of the action.
     *
     * @param boolean $flag
     * @return static
     * @access public
     */
    public function associateWithParameters($flag = true)
    {
        return $this->option('associate', (bool)$flag);
    }
  
    /**
     * Removes the defined action for the given HTTP methods.
     *
     * @param string $regex - a regex corresponding to the specified URL.
     * @param array|string $methods - the HTTP methods.
     * @return static
     * @access public
     */
    public function remove($regex, $methods = '@')
    {
        $methods = $this->normalizeMethods($methods);
        foreach ($methods as $method)
        {
            unset($this->actions[$method][$regex]);
        }
        $this->lastAction = null;
        return $this;
    }
  
    /**
     * Removes all routes. 
     *
     * @return static
     * @access public
     */
    public function clean()
    {
        $this->actions = [];
        $this->lastAction = null;
        return $this;
    }
  
    /**
     * Binds some action (delegate) with the given URL template (regex).
     *
     * @param array|string $methods - HTTP methods for which the given action is permitted.
     * @param string $regex - regex URL template.
     * @param mixed $action - a delegate.
     * @return static
     * @access public
     */
    public function bind($methods, $regex, $action)
    {
        $methods = $this->normalizeMethods($methods);
        $data = [
            'action' => $action,
            'component' => URL::PATH,
            'validation' => [],
            'args' => [],
            'associate' => false
        ];
        foreach ($methods as $method)
        {
            $this->actions[$method][$regex] = $data;
        }
        $this->lastAction = [$regex, $methods];
        return $this;
    }
  
    /**
     * Performs all actions matching all URL templates.
     *
     * @param Aleph\Net\Request $request - the current request instance.
     * @param mixed $status - a variable which the HTTP status will be written in.
     * @return mixed 
     * @access public
     */
    public function route(Request $request = null, &$status = null)
    {
        $this->lastAction = null;
        $request = $request ?: Request::createFromGlobals();
        $method = $request->getMethod();
        $url = $request->url;
        $urls = [];
        if (isset($this->actions[$method]))
        {
            foreach ($this->actions[$method] as $regex => $data)
            {
                if (empty($urls[$data['component']]))
                {
                    $urls[$data['component']] = $url->build($data['component']);
                }
                $data['params'] = $this->parseURLTemplate($regex);
                if (!preg_match($regex, $urls[$data['component']], $matches))
                {
                    continue;
                }
                if (!empty($data['secure']) && !$url->isSecured())
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
                if (is_string($data['action']))
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
                $action = new Core\Delegate($data['action']);
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
                if (empty($data['associate']))
                {
                    $params = $data['args'];
                }
                else
                {
                    $params = [];
                    foreach ($action->getParameters() as $param) 
                    {
                        $name = $param->getName();
                        if (array_key_exists($name, $data['args']))
                        {
                            $params[] = $data['args'][$name];
                        }
                    }
                }
                $status = 200;
                return $action->call($params);
            }
        }
        else
        {
            foreach ($this->actions as $method => $actions)
            {
                foreach ($actions as $regex => $data)
                {
                    if (empty($urls[$data['component']]))
                    {
                        $urls[$data['component']] = $url->build($data['component']);
                    }
                    $data['params'] = $this->parseURLTemplate($regex);
                    if (preg_match($regex, $urls[$data['component']], $matches))
                    {
                        $status = 405;
                        return;
                    }
                }
            }
        }
        $status = 404;
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
        if ($methods == '*')
        {
            return ['GET', 'PUT', 'POST', 'DELETE'];
        }
        if ($methods == '@')
        {
            return ['GET', 'PUT', 'POST', 'DELETE', 'HEAD', 'PATCH', 'OPTIONS', 'TRACE', 'CONNECT', 'PURGE'];
        }
        $methods = is_array($methods) ? $methods : explode('|', $methods);
        foreach ($methods as &$method)
        {
            $method = strtoupper(trim($method));
        }
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
        $rx = $regex;
        $params = [];
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
                if ($p == '')
                {
                    $p = '[^/]*?';
                }
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
     * @return static
     * @access private
     */
    private function option($option, $value)
    {
        if ($this->lastAction === null)
        {
            throw new \InvalidArgumentException('No action is defined. You should first call bind() method.');
        }
        if (is_array($value))
        {
            foreach ($this->lastAction[1] as $method)
            {
                $this->actions[$method][$this->lastAction[0]][$option] = array_merge($this->actions[$method][$this->lastAction[0]][$option], $value);
            }
        }
        else
        {
            foreach ($this->lastAction[1] as $method)
            {
                $this->actions[$method][$this->lastAction[0]][$option] = $value;
            }
        }
        return $this;
    }
}