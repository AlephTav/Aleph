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

use Aleph\Core;

/**
 * With this class you can route the requested URLs.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.1.3
 * @package aleph.http
 */
class Router
{
    /**
     * Error message templates.
     */
    const ERR_ROUTER_1 = 'No action is defined. You should first call one of the methods that specified any action (bind(), get(), post() etc.).';
    
    /**
     * This property is used as the cache of parsed URL templates.
     *
     * @var array
     */
    private static $rex = [];

    /**
     * Array of actions for the routing.
     * 
     * @var array
     */
    private $actions = [];
  
    /**
     * Stores the link on the last invoked method.
     *
     * @var array
     */
    private $lastAction = [];
    
    /**
     * The group options stack.
     *
     * @var array
     */
    private $groupOptions = [];
    
    /**
     * A callback that is called before each action.
     *
     * @var \Aleph\Core\Callback
     */
    private $beforeRouteCallback = null;
  
    /**
     * Sets URL component for the current URL template.
     *
     * @param int $component
     * @return static
     */
    public function component(int $component)
    {
        return $this->option('component', $component);
    }
  
    /**
     * Sets array of regular expressions that applied to appropriate variables of the URL template.
     * if at least one regular expression does not match with the appropriate URL template variable, then the action is not called.
     *
     * @param array $where
     * @return static
     */
    public function where(array $where)
    {
        return $this->option('validate', $where);
    }
  
    /**
     * Sets an associated array of additional parameters that will be passed to the action.
     *
     * @param array $args
     * @return static
     */
    public function args(array $args)
    {
        return $this->option('args', $args);
    }
  
    /**
     * If this parameter is set then all URL template variables (as associated array) are passed to extra parameter.
     *
     * @param string $parameter The name of action parameter determining as extra parameter.
     * @return static
     */
    public function extra($parameter = 'extra')
    {
        return $this->option('extra', $parameter);
    }
  
    /**
     * Determines whether the request should have the secure HTTPS protocol.
     *
     * @param bool $flag If equals TRUE, the request is supposed to be secure.
     * @return static
     */
    public function secure(bool $flag = true)
    {
        return $this->option('secure', $flag);
    }
    
    /**
     * Determines whether to synchronize the URL template variables and parameters of the action.
     *
     * @param bool $flag
     * @return static
     */
    public function sync(bool $flag = true)
    {
        return $this->option('sync', $flag);
    }
  
    /**
     * Removes the defined action for the given HTTP methods.
     *
     * @param string $regex A regex corresponding to the specified URL.
     * @param array|string $methods The HTTP methods.
     * @return static
     */
    public function remove(string $regex, $methods = '@')
    {
        $methods = $this->normalizeMethods($methods);
        foreach ($methods as $method)
        {
            unset($this->actions[$method][$regex]);
        }
        $this->lastAction = [];
        return $this;
    }
  
    /**
     * Removes all routes. 
     *
     * @return static
     */
    public function clean()
    {
        $this->actions = [];
        $this->lastAction = [];
        return $this;
    }
    
    /**
     * Sets a callback that will be called before each action.
     * This callback takes an array of binding parameters and should return modified version of it.
     * If the argument is FALSE the callback will be removed.
     * If the argument is NULL the method returns callback instance.
     *
     * @param mixed $callback
     * @return \Aleph\Core\Callback|null
     */
    public function onBeforeRoute($callback = null)
    {
        if ($callback === null)
        {
            return $this->beforeRouteCallback; 
        }
        $this->beforeRouteCallback = $callback === false ? null : new Core\Callback($callback);
    }
  
    /**
     * Binds some action (user-defined callback) with the given URL template (regex).
     *
     * @param array|string $methods HTTP methods for which the given action is permitted.
     * @param string $regex The regex URL template.
     * @param mixed $action A user-defined callback.
     * @return static
     */
    public function bind($methods, string $regex, $action)
    {
        $methods = $this->normalizeMethods($methods);
        $data = [
            'action' => $action,
            'component' => URL::PATH,
            'validate' => [],
            'args' => [],
            'sync' => false
        ];
        $options = end($this->groupOptions);
        if ($options)
        {
            foreach($options as $option => $value)
            {
                if ($option == 'prefix')
                {
                    $regex = $value . '/' . ltrim($regex, '/');
                }
                else if ($option == 'namespace')
                {
                    if (is_string($action) && $action !== '' && $action[0] != '\\')
                    {
                        $data['action'] = $value . '\\' . $action;
                    }
                }
                else
                {
                    $data[$option] = $value;
                }
            }            
        }
        foreach ($methods as $method)
        {
            $this->actions[$method][$regex] = $data;
        }
        $this->lastAction = [$regex, $methods];
        return $this;
    }
    
    /**
     * Binds some action with the given URL template for GET request.
     *
     * @param string $regex The regex URL template.
     * @param mixed $action A user-defined callback.
     * @return static
     */
    public function get(string $regex, $action)
    {
        return $this->bind('GET', $regex, $action);
    }
    
    /**
     * Binds some action with the given URL template for POST request.
     *
     * @param string $regex The regex URL template.
     * @param mixed $action A user-defined callback.
     * @return static
     */
    public function post(string $regex, $action)
    {
        return $this->bind('POST', $regex, $action);
    }
    
    /**
     * Binds some action with the given URL template for PUT request.
     *
     * @param string $regex The regex URL template.
     * @param mixed $action A user-defined callback.
     * @return static
     */
    public function put(string $regex, $action)
    {
        return $this->bind('PUT', $regex, $action);
    }
    
    /**
     * Binds some action with the given URL template for PATCH request.
     *
     * @param string $regex The regex URL template.
     * @param mixed $action A user-defined callback.
     * @return static
     */
    public function patch(string $regex, $action)
    {
        return $this->bind('PATCH', $regex, $action);
    }
    
    /**
     * Binds some action with the given URL template for DELETE request.
     *
     * @param string $regex The regex URL template.
     * @param mixed $action A user-defined callback.
     * @return static
     */
    public function delete(string $regex, $action)
    {
        return $this->bind('DELETE', $regex, $action);
    }
    
    /**
     * Binds some action with the given URL template for OPTIONS request.
     *
     * @param string $regex The regex URL template.
     * @param mixed $action A user-defined callback.
     * @return static
     */
    public function options(string $regex, $action)
    {
        return $this->bind('OPTIONS', $regex, $action);
    }
    
    /**
     * Defines the set of options that will be applied to bunch of routes.
     * Those routes are supposed to be specified during execution of a callback.
     *
     * @param array $options An array of options. The valid values: prefix, namespace, component, where, args, extra, sync and secure.
     * @param mixed $callback A user-defined callback.
     * @return static
     */
    public function group(array $options, $callback)
    {
        if ($this->groupOptions)
        {
            $old = end($this->groupOptions);
            if (isset($options['namespace']))
            {
                $namespace = trim($options['namespace'], '\\');
                $options['namespace'] = isset($old['namespace']) ? $old['namespace'] . '\\' . $namespace : $namespace;
            }
            if (isset($options['prefix']))
            {
                $prefix = trim($options['prefix'], '/');
                $options['prefix'] = isset($old['prefix']) ? $old['prefix'] . '/' . $prefix : $prefix;
            }
            $options = array_merge($old, $options);
        }
        $this->groupOptions[] = $options;
        (new Core\Callback($callback))->call([], $this);
        array_pop($this->groupOptions);
        return $this;
    }
  
    /**
     * Performs all actions matching all URL templates.
     * The method returns array of the following structure:
     * [
     *  'result' => ...an action execution's result..., 
     *  'status' => ...HTTP status code...,
     *  'methods' => [...HTTP methods that match request...]
     * ]
     *
     * @param \Aleph\Http\Request $request - the current request instance.
     * @return array
     */
    public function route(Request $request = null) : array
    {
        $this->lastAction = [];
        $request = $request ?: Request::createFromGlobals();
        $method = $request->getMethod();
        $url = $request->url;
        $res = [
            'result' => null,
            'status' => 200,
            'methods' => [$method]
        ];
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
                    $res['status'] = 403;
                    return $res;
                }
                $flag = true;
                foreach ($data['validate'] as $param => $rgx)
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
                if ($this->beforeRouteCallback)
                {
                    $data = ($this->beforeRouteCallback)($data);
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
                $action = new Core\Callback($data['action']);
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
                if (empty($data['sync']))
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
                $res['result'] = $action->call($params, $data);
                return $res;
            }
        }
        else
        {
            $methods = [];
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
                        $methods[$regex][] = $method;
                    }
                }
            }
            if ($methods = reset($methods))
            {
                $res['status'] = in_array($method, $methods) ? 405 : 501;
                $res['methods'] = $methods;
                return $res;
            }
        }
        $res['status'] = 404;
        return $res;
    }
  
    /**
     * Returns array of HTTP methods in canonical form (in uppercase and without spaces).
     *
     * @param string|array HTTP methods.
     * @return array
     */
    protected function normalizeMethods($methods) : array
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
     * @param string $regex The given URL template to be parsed.
     * @return array
     */
    private function parseURLTemplate(string &$regex) : array
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
     * @throws \BadMethodCallException If no action is specified.
     */
    private function option(string $option, $value)
    {
        if (!$this->lastAction)
        {
            throw new \BadMethodCallException(static::ERR_ROUTER_1);
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