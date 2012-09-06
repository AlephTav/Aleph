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
    Aleph\Cache,
    Aleph\Web,
    Aleph\Web\UI\POM;

interface IPage //extends \ArrayAccess, \Countable
{
  public function cacheGet();
  public function cacheExpired();
  public function cacheSet($html);
  public function getPageID();
  public function access();
  public function parse();
  public function init();
  public function assign();
  public function load();
  public function process();
  public function render();
  public function unload();
}

class Page implements IPage
{
  public static $page = null;

  public static $defaultCache = null;
  
  /**
   * The url of a page to which the transition will be the case if the page is not accessable.
   *
   * @var string
   * @access public
   */
  public $noAccessURL = null;
  
  /**
   * The url of a page to which the transition will be the case if the user session is expired.
   *
   * @var string
   * @access public
   */
  public $noSessionURL = '/';
  
  /**
   * The time (in seconds) of expiration cache of a page.
   *
   * @var integer
   * @access public
   */
  public $expire = 0;
  
  /**
   * An instance of \Aleph class.
   *
   * @var \Aleph
   * @access public
   */
  protected $a = null;
  
  protected $fv = null;
  
  protected $tpl = null;
  
  protected $cache = null;

  /**
   * The instance of Aleph\Web\AJAX class.
   *
   * @var Aleph\Web\AJAX
   * @access public
   */
  protected $ajax = null;
  
  protected $pageID = null;
  
  protected $ajaxPermissions = array('Aleph\MVC\\', 'Aleph\Web\UI\POM\\');
  
  protected $sequenceMethods = array('first' => array('parse', 'init', 'load', 'render', 'unload'),
                                     'next' => array('assign', 'load', 'process', 'unload'));
  
  public function __construct($template = null, Cache\Cache $cache = null)
  {
    $this->a = \Aleph::getInstance();
    $this->fv = $this->a->request()->data;
    $this->ajax = Web\Ajax::getInstance();
    $this->cache = $cache ?: (self::$defaultCache instanceof Cache\Cache ? self::$defaultCache : $this->a->cache());
    $this->tpl = new Core\Template($template, 0, $this->cache);
    $this->pageID = md5(get_class($this) . $template . \Aleph::getSiteUniqueID());
  }

  /**
   * Returns the unique page ID.
   *
   * @return string
   * @access public
   */
  public function getPageID()
  {
    return $this->pageID;
  }
  
  public function getSequenceMethods($first = true)
  {
    return $this->sequenceMethods[$first ? 'first' : 'next'];
  }

  public function cacheGet()
  {
    if ($this->expire) return $this->cache->get($this->pageID);
  }

  public function cacheExpired()
  {
    if ($this->expire) return $this->cache->isExpired($this->pageID);
    return true;
  }
  
  public function cacheSet($html)
  {
    if ($this->expire) $this->cache->set($this->pageID, $html, $this->expire, '--pages');
  }
  
  public function get($cid)
  {
    return $this->body->get($cid);
  }

  /**
   * Checks accessability of a page.
   *
   * @return boolean
   * @access public
   */
  public function access()
  {
    return true;
  }
  
  public function init()
  {
    //$this->body->init();
  }

  public function load()
  {
    //$this->body->load();
  }
  
  public function unload()
  {
    //$this->body->unload();
  }

  /**
   * Parses the page template.
   *
   * @access public
   */
  public function parse()
  {
    if (empty($this->a['pageTemplateCacheEnable']) || $this->cache->isExpired($this->pageID . '_init_vs'))
    {
      $this->body = new POM\Body($this->pageID);
      $this->body->parse($this->tpl);
      if (!empty($this->a['pageTemplateCacheEnable'])) $this->cache->set($this->pageID . '_init_vs', POM\Control::getViewState(), $this->cache->getVaultLifeTime(), '--pages');
    }
    else
    {
      POM\Control::setViewState($this->cache->get($this->pageID . '_init_vs'));
      $this->body = POM\Control::restoreFromViewState($this->pageID);
    }
  }

  public function assign()
  {
    //if (empty($this->fv['ajax-key']) || $this->fv['ajax-key'] != sha1($this->pageID)) exit();
    //if ($this->cache->isExpired($this->pageID . '_vs')) \Aleph::go($this->noSessionURL);
    //POM\Control::setViewState($this->cache->get($this->pageID . '_vs'));
    //$this->body = POM\Control::restoreFromViewState($this->pageID);
    //$this->body->assign(empty($this->fv['ajax-vs']) ? array() : json_decode((string)$this->fv['ajax-vs'], true));
  }

  public function process()
  {
    $this->ajax->doit($this->ajaxPermissions);
    //$this->body->refresh();
    $this->ajax->perform();
    //$this->cache->set($this->pageID . '_vs', POM\Control::getViewState(), ini_get('session.gc_maxlifetime'), '--pages');
  }

  /**
   * Renders the page HTML.
   *
   * @access public
   */
  public function render()
  {
    $this->cacheSet($html = $this->body->render());
    $this->cache->set($this->pageID . '_vs', POM\Control::getViewState(), ini_get('session.gc_maxlifetime'), '--pages');
    echo $html;
  }
}