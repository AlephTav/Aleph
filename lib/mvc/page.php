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

interface IPage
{
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
  const ERR_PAGE_1 = 'Incorrect page ID.';

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
  public $noSessionURL = null;
  
  public $cache = null;
  
  /**
   * The time (in seconds) of expiration cache of a page.
   *
   * @var integer
   * @access public
   */
  public $expire = 0;
  
  public $body = null;
  
  /**
   * An instance of \Aleph class.
   *
   * @var \Aleph
   * @access public
   */
  protected $a = null;
  
  protected $fv = null;
  
  protected $template = null;

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
    $this->template = $template;
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
  
  public function get($cid, $isRecursion = true)
  {
    return $this->body->get($cid, $isRecursion);
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
    $this->body->invokeMethod('init');
  }

  public function load()
  {
    $this->body->invokeMethod('load');
  }
  
  public function unload()
  {
    $this->body->invokeMethod('unload');
  }

  /**
   * Parses the page template.
   *
   * @access public
   */
  public function parse()
  {
    if (empty($this->a['pageTemplateCacheEnable']) || POM\Control::vsExpired(true))
    {
      $this->body = new POM\Body($this->pageID);
      $this->body->parse($this->template);
      POM\Control::vsSet($this->body, true, true);
      if (!empty($this->a['pageTemplateCacheEnable'])) POM\Control::vsPush(true);
    }
    else
    {
      POM\Control::vsPull(true);
      $this->body = POM\Control::vsGet($this->pageID);
    }
  }

  public function assign()
  {
    if (empty($this->fv['ajax-key']) || $this->fv['ajax-key'] != sha1($this->pageID)) throw new Core\Exception($this, 'ERR_PAGE_1');
    if (POM\Control::vsExpired()) 
    {
      if ($this->noSessionURL) \Aleph::go($this->noSessionURL);
      \Aleph::reload();
    }
    POM\Control::vsPull();
    $this->body = POM\Control::vsGet($this->pageID);
    $this->body->assign(empty($this->fv['ajax-vs']) ? array() : json_decode((string)$this->fv['ajax-vs'], true));
  }

  public function process()
  {
    $this->ajax->doit($this->ajaxPermissions);
   // $this->body->refresh();
    $this->ajax->perform();
    POM\Control::vsPush();
  }

  /**
   * Renders the page HTML.
   *
   * @access public
   */
  public function render()
  {
    $html = $this->body->render();
    POM\Control::vsPush();
    if ((int)$this->expire > 0) $this->cache->set($this->pageID, $html, $this->expire, '--pages');
    echo $html;
  }
}