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
    Aleph\Net,
    Aleph\Web\POM;

class Page
{
  const ERR_PAGE_1 = 'Method [{var}] is not allowed to be invoked.';

  /**
   * Default cache of page classes.
   *
   * @var Aleph\Cache\Cache $cache
   * @access public
   * @static
   */
  public static $cache = null;
  
  public static $cacheGroup = 'pages';
  
  public static $cacheExpire = 0;
  
  public static $current = null;
  
  /**
   * Represents the view of a page.
   *
   * @var Aleph\Web\POM $view
   * @access public
   */
  public $view = null;
  
  /**
   * The URL for redirect if a page is not accessible.
   *
   * @var string
   * @access public
   */
  public $noAccessURL = null;
  
  /**
   * The URL for redirect if the user session is expired.
   *
   * @var string
   * @access public
   */
  public $noSessionURL = null;
  
  /**
   * The time (in seconds) of expiration cache of a page.
   *
   * @var integer
   * @access public
   */
  protected $expire = 0;
  
  protected $ajaxPermissions = ['Aleph\MVC\\', 'Aleph\Web\UI\POM\\'];
  
  protected $sequenceMethods = ['first' => ['parse', 'init', 'load', 'render', 'unload'],
                                'after' => ['assign', 'load', 'process', 'unload']];
       
  /**
   * The unique page identifier.
   *
   * @var string $UID
   * @access private
   */   
  private $UID = null;
  
  private $storage = null;
  
  public function __construct($template = null)
  {
    $this->UID = md5(get_class($this) . $template . \Aleph::getSiteUniqueID());
    $this->view = new POM\View($template);
    $this->storage = static::$cache ? static::$cache : \Aleph::getInstance()->getCache();
  }
  
  public function isExpired()
  {
    return $this->expire > 0 ? $this->storage->isExpired($this->UID) : true;
  }
  
  public function restore()
  {
    return $this->storage->get($this->UID);
  }
  
  /**
   * Returns the page cache object.
   *
   * @return Aleph\Cache\Cache
   * @access public
   */
  public function getCache()
  {
    return $this->storage;
  }

  /**
   * Returns the unique page ID.
   *
   * @return string
   * @access public
   */
  public function getPageID()
  {
    return $this->UID;
  }
  
  /**
   * Sets the unique page ID.
   *
   * @param string $UID
   * @access public
   */
  public function setPageID($UID)
  {
    $this->UID = $UID;
  }
  
  public function getSequenceMethods($first = true)
  {
    return $this->sequenceMethods[$first ? 'first' : 'after'];
  }

  public function get($id, $isRecursion = true)
  {
    return $this->view->get($id, $isRecursion);
  }

  /**
   * Checks accessibility of a page.
   *
   * @return boolean
   * @access public
   */
  public function access()
  {
    return true;
  }

  /**
   * Parses the page template.
   *
   * @access public
   */
  public function parse()
  {
    $this->view->parse();
  }
  
  public function init()
  {
    $this->view->invoke('init');
  }
  
  public function assign()
  {
    $data = Net\Request::getInstance()->data;
    if (!$this->view->assign($data['ajax-key'], isset($data['ajax-vs']['vs']) ? $data['ajax-vs']['vs'] : [], $data['ajax-vs']['ts']))
    {
      if ($this->noSessionURL) \Aleph::go($this->noSessionURL);
      \Aleph::reload();
    }
  }

  public function load()
  {
    $this->view->invoke('load');
  }
  
  /**
   * Renders the page HTML.
   *
   * @access public
   */
  public function render()
  {
    $html = $this->view->render();
    if ($this->expire > 0) $this->storage->set($this->UID, $html, $this->expire, 'pages');
    echo $html;
  }
  
  public function process()
  {
    $data = Net\Request::getInstance()->data;
    if (isset($data['ajax-method']))
    {
      $method = new Core\Delegate($data['ajax-method']);
      if (!$method->in($this->ajaxPermissions)) throw new Core\Exception($this, 'ERR_PAGE_1', $method);
      ob_start();
      $response = $method->call(empty($data['ajax-args']) ? [] : $data['ajax-args']);
      $output = trim(ob_get_contents());
      ob_end_clean();
      if (strlen($output)) $this->view->action('alert', $output);
    }
    $this->view->process($response);
  }
  
  public function unload()
  {
    $this->view->invoke('unload');
  }
}