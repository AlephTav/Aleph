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
   * Cache expiration time of template.
   *
   * @var integer $expire
   * @access public
   */
  public $expire = 0;
  
  /**
   * Global template variables.
   *
   * @var array $globals
   * @access protedted
   * @static
   */
  protected static $globals = array();
  
  /**
   * An instance of Aleph\Cache\Cache class.
   *
   * @var Aleph\Cache\Cache $cache
   * @access protected
   */
  protected $cache = null;

  /**
   * Template variables.
   *
   * @var array $vars
   * @access protected
   */
  protected $vars = array();
  
  /**
   * Template string or path to a template file. 
   *
   * @var string $template
   * @access protected
   */
  protected $template = null;
  
  /**
   * Array names of template variables are instances of Template class.
   *
   * @var array $templates
   * @access protected
   */
  protected $templates = array();
  
  /**
   * Constructor.
   *
   * @param string $template - template string or path to a template file.
   * @param integer $expire - template cache life time in seconds.
   * @param Aleph\Cache\Cache - an instance of caching class.
   * @access public
   */
  public function __construct($template = null, $expire = 0, Cache\Cache $cache = null)
  {
    $this->template = $template;
    $this->expire = (int)$expire;
    if ($this->expire > 0) $this->setCache($cache ?: \Aleph::getInstance()->cache());
  }
  
  /**
   * Returns an instance of caching class.
   *
   * @return Aleph\Cache\Cache
   * @access public
   */
  public function getCache()
  {
    if ($this->cache === null) $this->cache = \Aleph::getInstance()->cache();
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
   * Checks whether or not a template cache lifetime is expired.
   *
   * @return boolean
   * @access public
   */
  public function isExpired()
  {
    return $this->getCache()->isExpired(md5($this->template));
  }

  /**
   * Returns array of template variables.
   *
   * @return array
   * @access public
   */
  public function getVariables()
  {
    return $this->vars;
  }

  /**
   * Sets template variables.
   *
   * @var array $variables
   * @access public
   */
  public function setVariables(array $variables)
  {
    $this->vars = $variables;
  }
  
  /**
   * Returns array of global template variables.
   *
   * @return array
   * @access public
   */
  public function getGlobals()
  {
    return self::$globals;
  }

  /**
   * Sets global template variables.
   *
   * @var array $globals
   * @access public
   */
  public function setGlobals(array $globals)
  {
    self::$globals = $globals;
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
   * @param string $key - unique identifier of variable name.
   * @param mixed $value - value of variable name. 
   * @access public
   */
  public function offsetSet($key, $value)
  {
    self::$globals[$key] = $value;
  }

  /**
   * Checks whether or not a global template variable with some unique identifier exist.
   *
   * @param string $key - unique identifier of a global variable name.
   * @return boolean
   * @access public
   */
  public function offsetExists($key)
  {
    return isset(self::$globals[$key]);
  }

  /**
   * Deletes a global template variable.
   *
   * @param string $key - unique identifier of variable name.
   * @access public
   */
  public function offsetUnset($key)
  {
    unset(self::$globals[$key]);
  }

  /**
   * Gets value of a global template variable.
   *
   * @param string $key - unique identifier of variable name.
   * @return mixed
   * @access public
   */
  public function &offsetGet($key)
  {
    if (!isset(self::$globals[$key])) self::$globals[$key] = null;
    return self::$globals[$key];
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
    if ($value instanceof Template) $this->templates[$name] = $name;
    else unset($this->templates[$name]);
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
    unset($this->templates[$name]);
  }

  /**
   * Adds array of template variables or global template variables.
   *
   * @param array $variables
   * @param boolean $isGlobal - determines whether global template variables (TRUE) or local template variables (FALSE) use.
   * @access public
   */
  public function assign(array $variables, $isGlobal = false)
  {
    if ($isGlobal) $this->globals = array_merge($this->globals, $variables);
    else $this->vars = array_merge($this->vars, $variables);
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
      ${'(_._)'} = $tpl;
      extract(${'(_._)'}->getVariables());
      extract(${'(_._)'}->getGlobals());
      ob_start();
      if (is_file(${'(_._)'}->getTemplate())) require(${'(_._)'}->getTemplate());
      else eval(\Aleph::ecode(' ?>' . ${'(_._)'}->getTemplate() . '<?php '));
      return ob_get_clean();
    };
    $tmp = array();
    if ($this->expire > 0)
    {
      $hash = md5($this->template);
      $cache = $this->getCache();
      if ($cache->isExpired($hash))
      {
        foreach ($this->templates as $name) 
        {
          $tmp[$name] = $this->vars[$name];
          $this->vars[$name] = $hash . '<?php $' . $name . ';?>' . $hash;
        }
        $content = $render($this);
        $parts = array();
        foreach (explode($hash, $content) as $part)
        {
          $name = substr($part, 7, -3);
          if (isset($this->vars[$name])) $parts[] = array($name, true);
          else $parts[] = array($part, false);
        }
        foreach ($tmp as $name => $tpl) $this->vars[$name] = $tpl;
        $cache->set($hash, $parts, $this->expire);
      }
      else
      {
        $parts = $cache->get($hash);
      }
      $content = ''; $tmp = array();
      foreach ($parts as $part)
      {
        if ($part[1])
        {
          if (isset($tmp[$part[0]])) $content .= $tmp[$part[0]];
          else $content .= $tmp[$part[0]] = $this->vars[$part[0]]->render();
        }        
        else $content .= $part[0];
      }
      return $content;
    }
    return $render($this);
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