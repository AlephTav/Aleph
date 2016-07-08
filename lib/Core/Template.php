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

namespace Aleph\Core;

use Aleph,
    Aleph\Cache;

/**
 * This class is templator using PHP as template language.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.core
 */
class Template implements \ArrayAccess
{  
    /**
     * Unique cache identifier of template.
     *
     * @var string
     */
    public $cacheID = '';
  
    /**
     * Cache expiration time of template.
     *
     * @var int
     */
    public $cacheExpire = 0;
  
    /**
     * Name of the cache group.
     *
     * @var string
     */
    public $cacheGroup = '';
  
    /**
     * An instance of Aleph\Cache\Cache class.
     *
     * @var \Aleph\Cache\Cache
     */
    protected $cache = null;

    /**
     * Template variables.
     *
     * @var array
     */
    protected $vars = [];
  
    /**
     * Template string or path to a template file. 
     *
     * @var string
     */
    protected $template = '';
  
    /**
     * Global template variables.
     *
     * @var array
     */
    private static $globals = [];
  
    /**
     * Returns array of global template variables.
     *
     * @return array
     */
    public static function getGlobals()
    {
        return self::$globals;
    }

    /**
     * Sets global template variables.
     *
     * @param array $globals A global template variables.
     * @param bool $merge Determines whether new variables are merged with existing variables.
     * @return void
     */
    public static function setGlobals(array $globals, bool $merge = false)
    {
        if (!$merge)
        {
            self::$globals = $globals;
        }
        else
        {
            self::$globals = array_merge(self::$globals, $globals);
        }
    }
  
    /**
     * Constructor.
     *
     * @param string $template The template string or path to a template file.
     * @param int $expire The template cache life time in seconds.
     * @param string $cacheID The unique cache identifier of template.
     * @param \Aleph\Cache\Cache An instance of caching class.
     * @return void
     */
    public function __construct(string $template = '', int $expire = 0, string $cacheID = '', Cache\Cache $cache = null)
    {
        $this->template = $template;
        $this->cacheExpire = (int)$expire;
        if ($this->cacheExpire > 0) 
        {
            $this->setCache($cache ?: Aleph::getCache());
            $this->cacheID = $cacheID;
        }
    }
  
    /**
     * Returns an instance of caching class.
     *
     * @return \Aleph\Cache\Cache
     */
    public function getCache() : Cache\Cache
    {
        if ($this->cache === null)
        {
            $this->cache = Aleph::getCache();
        }
        return $this->cache;
    }
  
    /**
     * Sets an instance of caching class.
     *
     * @param \Aleph\Cache\Cache $cache
     * @return void
     */
    public function setCache(Cache\Cache $cache)
    {
        $this->cache = $cache;
    }
  
    /**
     * Checks whether or not a template cache lifetime is expired.
     *
     * @return bool
     */
    public function isExpired() : bool
    {
        if ((int)$this->cacheExpire <= 0)
        {
            return true;
        }
        return $this->getCache()->isExpired($this->getCacheID());
    }

    /**
     * Returns array of template variables.
     *
     * @return array
     */
    public function getVars() : array
    {
        return $this->vars;
    }

    /**
     * Sets template variables.
     *
     * @param array $vars The template variables.
     * @param bool $merge Determines whether new variables should be merged with existing variables.
     * @return void
     */
    public function setVars(array $vars, bool $merge = false)
    {
        if (!$merge)
        {
            $this->vars = $vars;
        }
        else
        {
            $this->vars = array_merge($this->vars, $vars);
        }
    }
  
    /**
     * Returns template string.
     *
     * @return string
     */
    public function getTemplate() : string
    {
        return $this->template;
    }
  
    /**
     * Sets template.
     *
     * @param string $template Template string or path to a template file.
     * @return void
     */
    public function setTemplate(string $template)
    {
        $this->template = $template;
    }

    /**
     * Sets new value of a global template variable.
     *
     * @param string $name The global variable name.
     * @param mixed $value The global variable value. 
     * @return void
     */
    public function offsetSet(string $name, $value)
    {
        self::$globals[$name] = $value;
    }

    /**
     * Checks whether or not a global template variable with the same name exists.
     *
     * @param string $name The global variable name.
     * @return bool
     */
    public function offsetExists(string $name) : bool
    {
        return isset(self::$globals[$name]);
    }

    /**
     * Deletes a global template variable.
     *
     * @param string $key The global variable name.
     * @return void
     */
    public function offsetUnset(string $name)
    {
        unset(self::$globals[$name]);
    }

    /**
     * Gets value of a global template variable.
     *
     * @param string $name The global variable name.
     * @return mixed
     */
    public function &offsetGet(string $name)
    {
        if (!isset(self::$globals[$name]))
        {
            self::$globals[$name] = null;
        }
        return self::$globals[$name];
    }

    /**
     * Sets value of a template variable.
     *
     * @param string $name The variable name.
     * @param mixed $value The variable value.
     * @return void
     */
    public function __set(string $name, $value)
    {
        $this->vars[$name] = $value;
    }

    /**
     * Returns value of a template variable.
     *
     * @param string $name The variable name.
     * @return mixed
     */
    public function &__get(string $name)
    {
        if (!isset($this->vars[$name]))
        {
            $this->vars[$name] = null;
        }
        return $this->vars[$name];
    }

    /**
     * Checks whether or not a template variable exists.
     *
     * @param string $name The variable name.
     * @return bool
     */
    public function __isset(string $name) : bool
    {
        return isset($this->vars[$name]);
    }

    /**
     * Deletes a template variable.
     *
     * @param string $name The variable name.
     * @return void
     */
    public function __unset(string $name)
    {
        unset($this->vars[$name]);
    }

    /**
     * Returns a rendered template.
     *
     * @return string
     */
    public function render() : string
    {
        $render = function($tpl)
        {
            if (is_file($tpl->getTemplate())) 
            {
                ${'(_._)'} = $tpl; unset($tpl);
                extract(Template::getGlobals());
                extract(${'(_._)'}->getVars());
                ob_start();
                ob_implicit_flush(false);
                require(${'(_._)'}->getTemplate());
                return ob_get_clean();
            }
            return Aleph::exe($tpl->getTemplate(), array_merge(Template::getGlobals(), $tpl->getVars()));
        };
        if ((int)$this->cacheExpire <= 0)
        {
            return $render($this);
        }
        $hash = $this->getCacheID();
        $cache = $this->getCache();
        if ($cache->isExpired($hash))
        {
            $tmp = [];
            foreach (array_merge(self::$globals, $this->vars) as $name => $value) 
            {
                if ($value instanceof Template)
                {
                    $tmp[$name] = $this->vars[$name];
                    $this->vars[$name] = $hash . '<?php $' . $name . ';?>' . $hash;
                }
            }
            $content = $render($this); $parts = [];
            foreach (explode($hash, $content) as $part)
            {
                $name = substr($part, 7, -3);
                if (isset($this->vars[$name]))
                {
                    $parts[] = [$name, true];
                }
                else
                {
                    $parts[] = [$part, false];
                }
            }
            foreach ($tmp as $name => $tpl)
            {
                $this->vars[$name] = $tpl;
            }
            $cache->set($hash, $parts, $this->cacheExpire, $this->cacheGroup);
        }
        else
        {
            $parts = $cache->get($hash);
        }
        $content = ''; $tmp = [];
        foreach ($parts as $part)
        {
            if ($part[1] === false)
            {
                $content .= $part[0];
            }
            else
            {
                $part = $part[0];
                if (isset($tmp[$part]))
                {
                    $content .= $tmp[$part];
                }
                else
                {
                    $tmp[$part] = $this->vars[$part]->render();
                    $content .= $tmp[$part];
                }
            } 
        }
        return $content;
    }

    /**
     * Push a rendered template to a browser.
     *
     * @return void
     */
    public function show()
    {
        echo $this->render();
    }

    /**
     * Converts an instance of template to string.
     *
     * @return string
     */
    public function __toString() : string
    {
        try
        {
            return $this->render();
        }
        catch (\Throwable $e)
        {
            Aleph::exception($e);
        }
    }
  
    /**
     * Returns template cache ID.
     *
     * @return string
     */
    protected function getCacheID() : string
    {
        return (string)$this->cacheID !== '' ? $this->cacheID : md5($this->template);
    }
}