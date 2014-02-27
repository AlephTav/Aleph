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

namespace Aleph\Core;

use Aleph\Cache;

/**
 * This class is templator using php as template language.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.core
 */
class Template implements \ArrayAccess
{
  /**
   * Global template variables.
   *
   * @var array $globals
   * @access protedted
   * @static
   */
  protected static $globals = [];
  
  /**
   * An instance of Aleph\Cache\Cache class.
   *
   * @var Aleph\Cache\Cache $cache
   * @access protected
   */
  protected $cache = null;
  
  /**
   * Unique cache identifier of template.
   *
   * @var string $cacheID
   * @access protected
   */
  protected $cacheID = null;
  
  /**
   * Cache expiration time of template.
   *
   * @var integer $expire
   * @access protected
   */
  protected $expire = 0;

  /**
   * Template variables.
   *
   * @var array $vars
   * @access protected
   */
  protected $vars = [];
  
  /**
   * Template string or path to a template file. 
   *
   * @var string $template
   * @access protected
   */
  protected $template = null;
  
  /**
   * Returns array of global template variables.
   *
   * @return array
   * @access public
   * @static
   */
  public static function getGlobals()
  {
    return self::$globals;
  }

  /**
   * Sets global template variables.
   *
   * @param array $globals - new global template variables.
   * @param boolean $merge - determines whether new variables are merged with existing variables.
   * @access public
   * @static
   */
  public static function setGlobals(array $globals, $merge = false)
  {
    if (!$merge) self::$globals = $globals;
    else self::$globals = array_merge(self::$globals, $globals);
  }
  
  /**
   * Constructor.
   *
   * @param string $template - template string or path to a template file.
   * @param integer $expire - template cache life time in seconds.
   * @param string $cacheID - unique cache identifier of template.
   * @param Aleph\Cache\Cache - an instance of caching class.
   * @access public
   */
  public function __construct($template = null, $expire = 0, $cacheID = null, Cache\Cache $cache = null)
  {
    $this->template = $template;
    $this->expire = (int)$expire;
    if ($this->expire > 0) 
    {
      $this->setCache($cache ?: \Aleph::getInstance()->getCache());
      $this->setCacheID($cacheID);
    }
  }
  
  /**
   * Returns an instance of caching class.
   *
   * @return Aleph\Cache\Cache
   * @access public
   */
  public function getCache()
  {
    if ($this->cache === null) $this->cache = \Aleph::getInstance()->getCache();
    return $this->cache;
  }
  
  /**
   * Sets an instance of caching class.
   *
   * @param Aleph\Cache\Cache $cache
   * @access public
   */
  public function setCache(Cache\Cache $cache)
  {
    $this->cache = $cache;
  }
  
  /**
   * Returns unique cache identifier of template.
   *
   * @return string
   * @access public
   */
  public function getCacheID()
  {
    return $this->cacheID;
  }
  
  /**
   * Sets unique cache identifier of template.
   *
   * @param string $cacheID
   * @access public
   */
  public function setCacheID($cacheID)
  {
    $this->cacheID = $cacheID;
  }
  
  /**
   * Returns cache expiration time.
   *
   * @return integer
   * access public
   */
  public function getExpirationTime()
  {
    return $this->expire;
  }
  
  /**
   * Sets cache expiration time (in seconds).
   *
   * @param integer $expire
   * @access public
   */
  public function setExpirationTime($expire)
  {
    $this->expire = $expire;
  }
  
  /**
   * Checks whether or not a template cache lifetime is expired.
   *
   * @return boolean
   * @access public
   */
  public function isExpired()
  {
    if ((int)$this->expire <= 0) return true;
    return $this->getCache()->isExpired(md5($this->template));
  }

  /**
   * Returns array of template variables.
   *
   * @return array
   * @access public
   */
  public function getVars()
  {
    return $this->vars;
  }

  /**
   * Sets template variables.
   *
   * @param array $vars
   * @param boolean $merge - determines whether new variables are merged with existing variables.
   * @access public
   */
  public function setVars(array $vars, $merge = false)
  {
    if (!$merge) $this->vars = $vars;
    else $this->vars = array_merge($this->vars, $vars);
  }
  
  /**
   * Returns template string.
   *
   * @return string
   * @access public
   */
  public function getTemplate()
  {
    return $this->template;
  }
  
  /**
   * Sets template.
   *
   * @param string $template - template string or path to a template file.
   * @access public
   */
  public function setTemplate($template)
  {
    $this->template = $template;
  }

  /**
   * Sets new value of a global template variable.
   *
   * @param string $name - global variable name.
   * @param mixed $value - global variable value. 
   * @access public
   */
  public function offsetSet($name, $value)
  {
    self::$globals[$name] = $value;
  }

  /**
   * Checks whether or not a global template variable with the same name exists.
   *
   * @param string $name - global variable name.
   * @return boolean
   * @access public
   */
  public function offsetExists($name)
  {
    return isset(self::$globals[$name]);
  }

  /**
   * Deletes a global template variable.
   *
   * @param string $key - global variable name.
   * @access public
   */
  public function offsetUnset($name)
  {
    unset(self::$globals[$name]);
  }

  /**
   * Gets value of a global template variable.
   *
   * @param string $name - global variable name.
   * @return mixed
   * @access public
   */
  public function &offsetGet($name)
  {
    if (!isset(self::$globals[$name])) self::$globals[$name] = null;
    return self::$globals[$name];
  }

  /**
   * Sets value of a template variable.
   *
   * @param string $name - variable name.
   * @param mixed $value - variable value.
   * @access public
   */
  public function __set($name, $value)
  {
    $this->vars[$name] = $value;
  }

  /**
   * Returns value of a template variable.
   *
   * @param string $name - variable name.
   * @return mixed
   * @access public
   */
  public function &__get($name)
  {
    if (!isset($this->vars[$name])) $this->vars[$name] = null;
    return $this->vars[$name];
  }

  /**
   * Checks whether or not a template variable exist.
   *
   * @param string $name - variable name.
   * @return boolean
   * @access public
   */
  public function __isset($name)
  {
    return isset($this->vars[$name]);
  }

  /**
   * Deletes a template variable.
   *
   * @param string $name
   * @access public
   */
  public function __unset($name)
  {
    unset($this->vars[$name]);
  }

  /**
   * Returns a rendered template.
   *
   * @return string
   * @access public
   */
  public function render()
  {
    $render = function($tpl)
    {
      if (is_file($tpl->getTemplate())) 
      {
        ${'(_._)'} = $tpl; unset($tpl);
        extract(Template::getGlobals());
        extract(${'(_._)'}->getVars());
        ob_start();
        require(${'(_._)'}->getTemplate());
        return ob_get_clean();
      }
      return \Aleph::exe($tpl->getTemplate(), array_merge(Template::getGlobals(), $tpl->getVars()));
    };
    if ((int)$this->expire <= 0) return $render($this);
    $hash = $this->cacheID !== null ? $this->cacheID : md5($this->template);
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
        if (isset($this->vars[$name])) $parts[] = [$name, true];
        else $parts[] = [$part, false];
      }
      foreach ($tmp as $name => $tpl) $this->vars[$name] = $tpl;
      $cache->set($hash, $parts, $this->expire, 'templates');
    }
    else
    {
      $parts = $cache->get($hash);
    }
    $content = ''; $tmp = [];
    foreach ($parts as $part)
    {
      if ($part[1] === false) $content .= $part[0];
      else
      {
        $part = $part[0];
        if (isset($tmp[$part])) $content .= $tmp[$part];
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
   * @access public
   */
  public function show()
  {
    echo $this->render();
  }

  /**
   * Converts an instance of this class to a string.
   *
   * @return string
   * @access public
   */
  public function __toString()
  {
    try
    {
      return $this->render();
    }
    catch (\Exception $e)
    {
      \Aleph::exception($e);
    }
  }
}