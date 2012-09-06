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

namespace Aleph\Web;

use Aleph\Core;

class Ajax
{
  const ERR_AJAX_1 = 'Method [{var}] is not allowed to be invoked.';

  /**
   * Specifies whether or not to cache the server response.
   *
   * @var boolean $noCache
   * @access public
   */
  public $noCache = true;

  /**
   * Content type of the server response.
   *
   * @var string $contentType
   */
  public $contentType = 'text/html';
  
  public $charset = 'utf-8';

  /**
   * The variable keeping js-code of inquiry answers.
   *
   * @var array
   * @access protected
   */
  protected $actions = array();

  protected $parent = null;
  
  protected $request = null;

  /**
   * The instance of the class.
   *
   * @var object
   * @access protected
   */
  private static $instance = null;

  /**
   * Constructor.
   *
   * @access private
   */
  private function __construct()
  {
    $this->request = \Aleph::getInstance()->request();
    $this->parent = empty($this->request->data['ajax-upload']) ? '' : 'parent.';
  }
  
  /**
   * Private __clone() method prevents this object cloning.
   *
   * @access private
   */
  private function __clone(){}

  /**
   * Returns the instance of this class.
   *
   * @return self
   * @access public
   * @static
   */
  public static function getInstance()
  {
    if (self::$instance === null) self::$instance = new self();
    return self::$instance;
  }

  public function doit(array $permissions = null)
  {
    $fv = $this->request->data;
    if (empty($fv['ajax-method'])) return;
    $method = new Core\Delegate($fv['ajax-method']);
    if ($permissions && !$method->in($permissions)) throw new Core\Exception($this, 'ERR_AJAX_1', $method);
    ob_start();
    $response = $method->call(empty($fv['ajax-args']) ? array() : (array)json_decode($fv['ajax-args'], true));
    $this->script($this->parent . 'aleph.ajax.response = \'' . addslashes(json_encode($response)) . '\'');
    $cnt = trim(ob_get_contents());
    ob_end_clean();
    if ($cnt != '') $this->alert($cnt);
  }

  public function perform()
  {
    $actions = implode(';', $this->actions);
    header('Content-Type: ' . $this->contentType . '; charset=' . $this->charset);
    if ($this->noCache)
    {
      header('Expires: Sun, 3 Jan 1982 21:30:00 GMT');      
      header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0');
      header('Pragma: no-cache');
    }
    if ($this->isAjaxUpload()) $actions = '<script type="text/javascript">' . $script . '</script>';
    echo $actions;
  }
  
  public function isAjaxRequest()
  {
    return $this->request->isAjax;
  }
  
  public function isAjaxUpload()
  {
    return !empty($this->request->data['ajax-upload']);
  }

  /**
   * Returns array of all js-actions.
   *
   * @return array
   * @access public
   */
  public function getActions()
  {
    return $this->actions;
  }

  public function setActions(array $actions = array())
  {
    $this->actions = $actions;
    return $this;
  }

  public function alert($text, $time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'alert\', \'' . $this->quote($text) . '\', ' . (int)$time . ')';
    return $this; 
  }

  public function insert($id, $html, $time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'insert\', \'' . $this->replaceBreakups($id) . '\', \'' . $this->replaceBreakups($html) . '\', ' . (int)$time . ')';
    return $this;
  }

  public function replace($id, $html, $time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'replace\', \'' . $this->replaceBreakups($id) . '\', \'' . $this->replaceBreakups($html) . '\', ' . (int)$time . ')';
    return $this;
  }

  public function inject($id, $html, $mode = 'top', $time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'inject\', \'' . $this->replaceBreakups($id) . '\', \'' . $this->replaceBreakups($html) . '\', \'' . $this->replaceBreakups($mode) . '\', ' . (int)$time . ')';
    return $this;
  }

  public function remove($id, $time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'remove\', \'' . $this->replaceBreakups($id) . '\', ' . (int)$time . ')';
    return $this;
  }

  public function redirect($url, $time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'redirect\', \'' . $this->replaceBreakups($url) . '\', ' . (int)$time . ')';
    return $this;
  }

  public function reload($time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'reload\', \'' . (int)$time . '\')';
  }

  public function message($id, $html, $expire, $time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'message\', \'' . $this->replaceBreakups($id) . '\', \'' . $this->replaceBreakups($html) . '\', ' . (int)$expire . ', ' . (int)$time . ')';
    return $this;
  }

  public function js($src, $time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'js\', \'' . $this->replaceBreakups($src) . '\', ' . (int)$time . ')';
    return $this;
  }

  public function css($src, $time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'css\', \'' . $this->replaceBreakups($src) . '\', ' . (int)$time . ')';
    return $this;
  }

  public function display($id, $display = null, $expire = 0, $time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'display\', \'' . $this->replaceBreakups($id) . '\', ' . ($display === null ? 'undefined' : '\'' . $this->replaceBreakups($display) . '\'') . ',  ' . (int)$expire . ', ' . (int)$time . ')';
    return $this;
  }
  
  public function addClass($id, $class, $time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'addClass\', \'' . $this->replaceBreakups($id) . '\', \'' . $this->replaceBreakups($class) . '\', ' . (int)$time . ')';
    return $this;
  }
  
  public function removeClass($id, $class, $time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'removeClass\', \'' . $this->replaceBreakups($id) . '\', \'' . $this->replaceBreakups($class) . '\', ' . (int)$time . ')';
    return $this;
  }
  
  public function toggleClass($id, $class, $time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'toggleClass\', \'' . $this->replaceBreakups($id) . '\', \'' . $this->replaceBreakups($class) . '\', ' . (int)$time . ')';
    return $this;
  }
  
  public function focus($id, $time = 0)
  {
    $this->actions[] = $this->parent . 'aleph.ajax.action(\'focus\', \'' . $this->replaceBreakups($id) . '\', ' . (int)$time . ')';
    return $this;
  }

  public function script($script, $time = 0)
  {
    $this->actions[] = $this->replaceBreakups($time > 0 ? 'setTimeout(function(){' . $script . '}, ' . (int)$time . ')' : $script, false);
    return $this;
  }

  /**
   * Replaces breakup symbols in a string on their codes.
   *
   * @param string $str
   * @return string
   * @access private
   */
  private function replaceBreakups($str, $addSlashes = true)
  {
    $str = strtr($str, array("\r" => '&#0013;', "\n" => '&#0010;'));
    if (!$addSlashes) return $str;
    return addslashes($str);
  }

  /**
   * Returns a string with backslashes before breakup symbols, single quote and backslash. 
   *
   * @param  string $str
   * @return string
   * @access private
   */
  private function quote($str)
  {
    return strtr($str, array("\\" => "\\\\", "'" => "\'", "\r" => "\\r", "\n" => "\\n"));
  }
}