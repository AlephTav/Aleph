<?php
/**
 * Copyright (c) 2014 Aleph Tav
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
 * @copyright Copyright &copy; 2014 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace
{
  /**
   * Returns unique identifier of a control object by its logic ID.
   * If control with given logic ID does not exist then the method returns NULL.
   *
   * @param string $id - unique or logic identifier of a control.
   * @return string
   */
  function ID($id)
  {
    if (false !== $ctrl = \Aleph\MVC\Page::$current->view->get($id)) return $ctrl->id;
  }
}

namespace Aleph\Web\POM {

use Aleph\Core,
    Aleph\MVC,
    Aleph\Net,
    Aleph\Utils;

/**
 * This class represents View component in MVC design pattern.
 * It contains methods that useful for any manipulation of the HTML, CSS, JS and UI of the web page. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.web.pom
 */    
class View implements \ArrayAccess
{
  // Error message templates.
  const ERR_VIEW_1 = "XHTML parse error! [{var}].[{var}]\nLine: [{var}], column: [{var}].";
  const ERR_VIEW_2 = "Property ID of element [{var}] is not defined or empty[{var}]\nLine: [{var}], column: [{var}].";
  const ERR_VIEW_3 = "Attribute \"path\" of element \"template\"[{var}] is not defined or such path does not exist.\nLine: [{var}], column: [{var}].";
  const ERR_VIEW_4 = "Path to the master template is not defined or incorrect.\nFile: [{var}]";
  const ERR_VIEW_5 = 'Page template should have only one element body containing all other web controls.';
  
  // Mark of PHP code string in HTML attributes.
  const PHP_MARK = 'php::';
  
  // Mark of JS code string in HTML attributes.
  const JS_MARK = 'js::';
  
  /**
   * An instance of Aleph\Core\Template class that contains the page HTML.
   *
   * @var Aleph\Core\Template $tpl
   * @access public
   */
  public $tpl = null;
  
  /**
   * Contains the number of parsing threads.
   * This property is used for determining that the parsing process takes place in the moment.
   *
   * @var integer $process
   * @access protected
   * @static
   */
  protected static $process = 0;
  
  /**
   * List of all empty HTML tags.
   *
   * @var array $emptyTags
   * @access protected
   * @static
   */
  protected static $emptyTags = ['br' => 1, 'hr' => 1, 'meta' => 1, 'link' => 1, 'img' => 1, 'embed' => 1, 'param' => 1, 'input' => 1, 'base' => 1, 'area' => 1, 'col' => 1];

  /**
   * Array of variables for template preprocessing.
   *
   * @var array $vars
   * @access protected
   */
  protected $vars = [];
  
  /**
   * Array of control objects that used for quick access to the required control object.
   *
   * @var array $controls
   * @access protected
   */
  protected $controls = [];
  
  /**
   * Array of JS commands that should be performed on the client side.
   *
   * @var array $actions
   * @access protected   
   */
  protected $actions = [];
  
  /**
   * DTD of the page HTML.
   *
   * @var string $dtd
   * access protected
   */
  protected $dtd = '<!DOCTYPE html>';
  
  /**
   * Title of the page and attributes of the HTML <title> tag.
   *
   * @var array $title
   * @access protected
   */
  protected $title = ['title' => '', 'attributes' => []];
  
  /**
   * Array of the meta information (meta tags) of the page.
   *
   * @var array $meta
   * @access protected    
   */
  protected $meta = [];
  
  /**
   * Array of all CSS files which placed on the page.
   *
   * @var array $css
   * @access protected
   */
  protected $css = [];
  
  /**
   * Array of all JS files which placed on the page.
   *
   * @var array $js
   * @access protected
   */
  protected $js = ['top' => [], 'bottom' => []];
  
  /**
   * Unique identifier of the page view.
   *
   * @var string $UID 
   * @access private
   */
  private $UID = null;
  
  /**
   * Array of the control view states.
   *
   * @var array $vs
   * @access private   
   */
  private $vs = [];
  
  /**
   * The timestamp of the last synchronization of the server and client side controls.
   *
   * @var integer $ts
   * @access private
   */
  private $ts = 0;
  
  /**
   * Returns TRUE if the view in state of parsing and FALSE otherwise.
   *
   * @return boolean
   * @access public
   * @static
   */
  public static function inParsing()
  {
    return static::$process > 0;
  }
  
  /**
   * Encodes PHP tags in HTML template of the page in order that the parsing is made possible.
   * It returns HTML templates of the page in which all PHP code fragments are encoded.
   *
   * @param string $xhtml - the page HTML template containing PHP code.
   * @param mixed $marks - used for storing encoded fragments of PHP code.
   * @return string
   * @access public
   * @static
   */
  public static function encodePHPTags($xhtml, &$marks)
  {
    $marks = [];
    $tokens = token_get_all($xhtml);
    $xhtml = '';
    foreach ($tokens as $token)
    {
      if ($token[0] == T_INLINE_HTML) $xhtml .= $token[1];
      else if ($token[0] == T_OPEN_TAG || $token[0] == T_OPEN_TAG_WITH_ECHO) $tk = [$token[1]];
      else if ($token[0] == T_CLOSE_TAG) 
      {
        $tk[] = $token[1];
        $tk = implode('', $tk);
        $k = md5($tk);
        $marks[$k] = $tk;
        $xhtml .= $k;
      }
      else $tk[] = is_array($token) ? $token[1] : $token;
    }
    return str_replace('&', '6cff047854f19ac2aa52aac51bf3af4a', $xhtml);
  }
    
  /**
   * Decodes previously encoded fragments of PHP code in HTML template or in control properties and attributes.
   * Returns the given object with decoded fragments of PHP code.
   *
   * @param mixed $obj - an object to decode. It can be a control object, an array or the page template.
   * @param array $marks - array of the previously stored PHP code fragments.
   * @return mixed
   * @access public
   * @static
   */
  public static function decodePHPTags($obj, array $marks)
  {
    if ($obj instanceof Control)
    {
      if ($obj instanceof Panel) 
      {
        $obj->tpl->setTemplate(static::decodePHPTags($obj->tpl->getTemplate(), $marks));
        foreach ($obj as $ctrl) static::decodePHPTags($ctrl, $marks);
      }
      $vs = $obj->getVS();
      unset($vs['attributes']['id']);
      foreach ($vs['attributes'] as $attr => $value) if (is_scalar($value)) $obj->{$attr} = static::evaluate($value, $marks);
      foreach ($vs['properties'] as $prop => $value) if (is_scalar($value)) $obj[$prop] = static::evaluate($value, $marks);
      return $obj;
    }
    else if (is_array($obj))
    {
      foreach ($obj as &$xhtml) $xhtml = static::decodePHPTags($xhtml, $marks);
      return $obj;
    }
    return strtr(str_replace('6cff047854f19ac2aa52aac51bf3af4a', '&', $obj), $marks);
  }
  
  /**
   * Executes PHP code in the given string.
   * The result is a new string with executed PHP code or a PHP object if the given string is a marked string of PHP code.
   *
   * @param string $value - some string containing PHP code or being a string of PHP code with PHP code marker in the beginning.
   * @param array $marks - the previously stored encoded fragments of PHP code in the page template.
   * @return mixed
   * @access public
   * @static
   */
  public static function evaluate($value, array $marks = null)
  {
    $value = \Aleph::exe(static::decodePHPTags($value, $marks ?: []), ['config' => \Aleph::getInstance()->getConfig()]);
    if (substr($value, 0, strlen(self::PHP_MARK)) == self::PHP_MARK)
    {
      $value = substr($value, strlen(self::PHP_MARK));
      if (strlen($value) == 0) return;
      eval(\Aleph::ecode('$value = ' . htmlspecialchars_decode($value) . ';'));
    }
    return $value;
  }
  
  /**
   * Parses arbitrary template and returns information about this template and its controls.
   *
   * @param string $template - template string or path to the template file.
   * @param array $vars - array of PHP variables for preprocessing of the template.
   * @return array
   * @access public
   * @static
   */
  public static function analyze($template, array $vars = null)
  {
    $view = new static();
    $ctx = $view->parseTemplate($view->prepareTemplate($template, $vars));
    $res = ['dtd' => $view->getDTD(),
            'title' => $view->getTitle(true),
            'meta' => $view->getAllMeta(),
            'js' => $view->getAllJS(),
            'css' => $view->getAllCSS(),
            'html' => static::decodePHPTags($ctx['html'], $ctx['marks'])];
    if (static::inParsing())
    {
      $res['controls'] = $ctx['controls'];
      $res['marks'] = $ctx['marks'];
    }
    else
    {
      $res['controls'] = static::decodePHPTags($ctx['controls'], $ctx['marks']);
    }
    return $res;
  }
  
  /**
   * Returns template placeholder of the given control.
   *
   * @param string $uniqueID - unique identifier of the control.
   * @return string
   * @access public
   * @static
   */
  public static function getControlPlaceholder($uniqueID)
  {
    return '<?php echo $' . $uniqueID . '; ?>'; 
  }

  /**
   * Constructor. Initializes $tpl property.
   *
   * @param string $template - template string or path to the page template file.
   * @access public
   */
  public function __construct($template = null)
  {
    $this->tpl = new Core\Template($template);
    if (isset($_SESSION['__DOWNLOAD__'])) 
    {
      $data = $_SESSION['__DOWNLOAD__'];
      unset($_SESSION['__DOWNLOAD__']);
      Net\Response::getInstance()->download($data['file'], $data['filename'], $data['contentType'], $data['deleteAfterDownload']);
    }
  }
  
  /**
   * Sets template variable for preprocessing.
   *
   * @param string $name - name of variable.
   * @param mixed $value - value of variable.
   * @access public
   */
  public function offsetSet($name, $value)
  {
    $this->vars[$name] = $value;
  }

  /**
   * Returns TRUE if a PHP variable (for template preprocessing) with the given name exists and FALSE otherwise.
   *
   * @param string $name - name of PHP variable.
   * @access public
   */
  public function offsetExists($name)
  {
    return isset($this->vars[$name]);
  }

  /**
   * Removes PHP variable for preprocessing.
   *
   * @param string $name - name of variable.
   * @access public
   */
  public function offsetUnset($name)
  {
    unset($this->vars[$name]);
  }

  /**
   * Returns value of the PHP variable that used for the template preprocessing.
   *
   * @param string $name - name of variable.
   * @return mixed
   * @access public
   */
  public function &offsetGet($name)
  {
    if (!isset($this->vars[$name])) $this->vars[$name] = null;
    return $this->vars[$name];
  }
  
  /**
   * Returns array of PHP variables for preprocessing.
   *
   * @return array
   * @access public
   */
  public function getVars()
  {
    return $this->vars;
  }
  
  /**
   * Sets PHP variables for the template preprocessing.
   *
   * @param array $vars - PHP variables.
   * @access public
   */
  public function setVars(array $vars)
  {
    $this->vars = $vars;
  }
  
  /**
   * Returns DTD of the web page.
   *
   * @return string
   * @access public
   */
  public function getDTD()
  {
    return $this->dtd;
  }
  
  /**
   * Sets DTD of the page.
   *
   * @param string $dtd
   * @access public
   */
  public function setDTD($dtd)
  {
    $this->dtd = $dtd;
  }
  
  /**
   * Sets title of the web page.
   *
   * @param string $title - the page title.
   * @param array $attributes - attributes of <title> tag.
   * @access public
   */
  public function setTitle($title = null, array $attributes = null)
  {
    $this->title = ['title' => $title !== null ? $title : $this->title['title'], 
                    'attributes' => $attributes !== null ? $attributes : $this->title['attributes']];
  }
  
  /**
   * Returns title of the web page.
   *
   * @param boolean $withAttributes - determines whether to return title with attributes of <title> tag.
   * @return array|string
   * @access public
   */
  public function getTitle($withAttributes = false)
  {
    if ($withAttributes) return $this->title;
    return $this->title['title'];
  }
  
  /**
   * Adds meta information on the web page.
   *
   * @param array $attributes - attributes of <meta> tag.
   * @access public   
   */
  public function addMeta(array $attributes)
  {
    $this->meta[] = $attributes;
  }
  
  /**
   * Adds meta information on the web page.
   *
   * @param string $id - unique identifier of the given meta information.
   * @param array $attributes - attributes of <meta> tag.
   * @access public
   */
  public function setMeta($id, array $attributes)
  {
    $this->meta[$id] = $attributes;
  }
  
  /**
   * Returns meta information that associated with the given identifier.
   * If no meta information associated with the given identifier, it returns FALSE.
   *
   * @param string $id - unique identifier of the meta information.
   * @return array
   * @access public
   */
  public function getMeta($id)
  {
    return isset($this->meta[$id]) ? $this->meta[$id] : false;
  }
  
  /**
   * Removes meta information associated with the given identifier.
   *
   * @param string $id - unique identifier of the meta information.
   * @access public
   */
  public function removeMeta($id)
  {
    unset($this->meta[$id]);
  }
  
  /**
   * Returns all meta data that added on the page.
   *
   * @return array
   * @access public
   */
  public function getAllMeta()
  {
    return $this->meta;
  }
  
  /**
   * Adds CSS file or css string on the web page.
   * If attributes "href" is defined it will be used as a unique identifier of this CSS.
   *
   * @param array $attributes - attributes of <style> (or <link>) tag.
   * @param string $style - CSS string of the inline style.
   * @param integer $order - some integer number that determines order of the attaching styles.
   * @access public
   */
  public function addCSS(array $attributes, $style = null, $order = null)
  {
    $this->css[isset($attributes['href']) ? $attributes['href'] : count($this->css)] = ['style' => $style, 'attributes' => $attributes, 'order' => $order !== null ? (int)$order : count($this->css)];
  }
  
  /**
   * Adds CSS associated with the given identifier on the web page.
   *
   * @param string $id - unique identifier of the CSS.
   * @param array $attributes - attributes of the attaching styles.
   * @param string $style - CSS string of the inline style.
   * @param integer $order - some integer number that determines order of the attaching styles.
   * @access public
   */
  public function setCSS($id, array $attributes, $style = null, $order = null)
  {
    $this->css[$id] = ['style' => $style, 'attributes' => $attributes, 'order' => $order !== null ? (int)$order : count($this->css)];
  }
  
  /**
   * Returns CSS information associated with the given identifier.
   * If CSS with such identifier does not exists, it returns FALSE.
   *
   * @param string $id - unique identifier of CSS.
   * @return array
   * @access public
   */
  public function getCSS($id)
  {
    return isset($this->css[$id]) ? $this->css[$id] : false;
  }
  
  /**
   * Removes CSS by its identifier.
   *
   * @param string $id - unique identifier of CSS.
   * @access public
   */
  public function removeCSS($id)
  {
    unset($this->css[$id]);
  }
  
  /**
   * Returns all CSS attached to the page.
   *
   * @return array
   * @access public   
   */
  public function getAllCSS()
  {
    return $this->css;
  }
  
  /**
   * Adds JS on the web page.
   * If attribute "src" is defined, it will be used as a unique identifier of the adding JS.
   *
   * @param array $attributes - attributes of <script> tag.
   * @param string $script - JS code string of inline script.
   * @param boolean $inHead - determines whether the given script is placed on head section or before closed <body> tag.
   * @param integer $order - determines the order in which scripts are attached to the page.
   * @access public
   */
  public function addJS(array $attributes, $script = null, $inHead = true, $order = null)
  {
    $place = $inHead ? 'top' : 'bottom';
    $this->js[$place][isset($attributes['src']) ? $attributes['src'] : count($this->js[$place])] = ['script' => $script, 'attributes' => $attributes, 'order' => $order !== null ? (int)$order : count($this->js[$place])];
  }
  
  /**
   * Adds JS associated with some unique identifier on the web page.
   *
   * @param string $id - unique identifier of the JS.
   * @param array $attributes - attributes of <script> tag.
   * @param string $script - JS code string of inline script.
   * @param boolean $inHead - determines whether the given script is placed on head section or before closed <body> tag.
   * @param integer $order - determines the order in which scripts are attached to the page.
   * @access public
   */
  public function setJS($id, array $attributes, $script = null, $inHead = true, $order = null)
  {
    $place = $inHead ? 'top' : 'bottom';
    $this->js[$place][$id] = ['script' => $script, 'attributes' => $attributes, 'order' => $order !== null ? (int)$order : count($this->js[$place])];
  }
  
  /**
   * Returns information about attached JS code.
   * If script with the given identifier does not exist, it returns FALSE.
   *
   * @param string $id - unique identifier of the script.
   * @param boolean - determines whether the required script is placed on head section or before closed <body> tag.
   * @return array
   * @access public
   */
  public function getJS($id, $inHead = true)
  {
    $place = $inHead ? 'top' : 'bottom';
    return isset($this->js[$place][$id]) ? $this->js[$place][$id] : false;
  }
  
  /**
   * Removes JS code from the page.
   *
   * @param string $id - unique identifier of the script.
   * @param boolean - determines whether the required script is placed on head section or before closed <body> tag.
   * @access public
   */
  public function removeJS($id, $inHead = true)
  {
    unset($this->js[$inHead ? 'top' : 'bottom'][$id]);
  }
  
  /**
   * Returns all attached to the page scripts.
   *
   * @return array
   * @access public
   */
  public function getAllJS()
  {
    return $this->js;
  }
  
  /**
   * Establishes JS code that will be performed on the client side.
   *
   * @param string $action - command name which determines actions that should be executed on the client side.
   * @param mixed $param1 - the first parameter of the command.
   * ...
   * @param mixed $paramn - the last parameter of the command.
   * @param integer $delay - time delay of the command execution.
   * @access public
   */
  public function action(/* $action, $param1, $param2, ..., $delay = 0 */)
  {
    $args = func_get_args();
    $config = \Aleph::getInstance()->getConfig();
    $act = strtolower($args[0]);
    switch ($act)
    {
      case 'alert':
      case 'redirect':
      case 'focus':
      case 'remove':
      case 'script':
        $act = '$a.ajax.action(\'' . $act . '\', ' .  Utils\PHP\Tools::php2js($args[1], true, self::JS_MARK) . ', ' . (isset($args[2]) ? (int)$args[2] : 0) . ')';
        break;
      case 'reload':
        $act = '$a.ajax.action(\'reload\', ' . (isset($args[1]) ? (int)$args[1] : 0) . ')';
        break;
      case 'addclass':
      case 'removeclass':
      case 'toggleclass':
      case 'insert':
      case 'replace':
        $act = '$a.ajax.action(\'' . $act . '\', ' .  Utils\PHP\Tools::php2js($args[1], true, self::JS_MARK) . ', ' .  Utils\PHP\Tools::php2js($args[2], true, self::JS_MARK) . ', ' . (isset($args[3]) ? (int)$args[3] : 0) . ')';
        break;
      case 'display':
      case 'message':
      case 'inject':
        $act = '$a.ajax.action(\'' . $act . '\', ' .  Utils\PHP\Tools::php2js($args[1], true, self::JS_MARK) . ', ' .  Utils\PHP\Tools::php2js(isset($args[2]) ? $args[2] : null, true, self::JS_MARK) . ', ' .  Utils\PHP\Tools::php2js(isset($args[3]) ? $args[3] : null, true, self::JS_MARK) . ', ' . (isset($args[4]) ? (int)$args[4] : 0) . ')';
        break;
      case 'download':
        $_SESSION['__DOWNLOAD__'] = ['file' => $args[1], 'filename' => isset($args[2]) ? $args[2] : null, 'contentType' => isset($args[3]) ? $args[3] : null, 'deleteAfterDownload' => isset($args[4]) ? $args[4] : false];
        $act = '$a.ajax.action(\'reload\')';
        break;
      default:
        $act = '$a.ajax.action(\'script\', ' . Utils\PHP\Tools::php2js($args[0], true, self::JS_MARK) . ', ' . (isset($args[1]) ? (int)$args[1] : 0) . ')';
        break;
    }
    if (Net\Request::getInstance()->isAjax) $this->actions[] = $act;
    else $this->addJS([], $act, false);
  }
  
  /**
   * Attaches control object to the view.
   *
   * @param Aleph\Web\POM\Control $ctrl - a control to be added to the view.
   * @access public
   */
  public function attach(Control $ctrl)
  {
    $this->controls[$ctrl->id] = $ctrl;
    if ($ctrl instanceof Panel) foreach($ctrl as $child) $this->attach($child);
  }

  /**
   * Returns TRUE if the given control is attached to the view and FALSE otherwise.
   *
   * @param string|Aleph\Web\POM\Control $ctrl - a control object or its unique identifier.
   * @return boolean
   * @access public
   */
  public function isAttached($ctrl)
  {
    $id = $ctrl instanceof Control ? $ctrl->id : $ctrl;
    return isset($this->controls[$id]) || isset($this->vs[$id]);
  }
  
  /**
   * Searches the required control in the page object model and returns its instance.
   * The method returns FALSE if the required control is not found.
   *
   * @param string $id - unique or logic identifier of a control.
   * @param boolean $searchRecursively - determines whether to recursively search the control inside panels.
   * @param Aleph\Web\POM\Control $context - the panel, only inside which the search should be performed.
   * @return Aleph\Web\POM\Control
   * @access public
   */
  public function get($id, $searchRecursively = true, Control $context = null)
  {
    if (isset($this->controls[$id])) 
    {
      $ctrl = $this->controls[$id];
      if ($context && !isset($context->getControls()[$id])) return false;
      if ($ctrl->isRemoved()) return false;
      return $ctrl;
    }
    else if (isset($this->vs[$id]))
    {
      $vs = $this->vs[$id];
      $ctrl = new $vs['class']($vs['properties']['id']);
      $ctrl->setVS($vs);
      if ($context && !isset($context->getControls()[$id])) return false;
      $this->controls[$ctrl->id] = $ctrl;
      return $ctrl;
    }
    if ($context) $controls = $context->getControls();
    else if (isset($this->controls[$this->UID])) 
    {
      if ($id == $this->controls[$this->UID]['id']) return $this->controls[$this->UID];
      $controls = $this->controls[$this->UID]->getControls();
    }
    else if (isset($this->vs[$this->UID])) 
    {
      if ($id == $this->vs[$this->UID]['properties']['id']) return $this->get($this->UID, false);
      $controls = $this->vs[$this->UID]['controls'];
    }
    else return false;
    $ctrl = $this->searchControl(explode('.', $id), $controls, $searchRecursively ? -1 : 0);
    if ($ctrl) $this->controls[$ctrl->id] = $ctrl;
    return $ctrl;
  }
  
  /**
   * Restores value of the control property "value" to default.
   * If the given control is a panel, its children will also restore their properties "value".
   *
   * @param string $id - unique or logic identifier of the control.
   * @param boolean $searchRecursively - determines whether the method should be recursively applied to all child panels of the given panel.
   * @access public
   */
  public function clean($id, $searchRecursively = true)
  {
    $ctrl = $this->get($id);
    if (isset($ctrl['value'])) $ctrl->clean();
    if ($ctrl instanceof Panel)
    {
      foreach ($ctrl->getControls() as $child)
      {
        $vs = $this->getActualVS($child);
        if ($searchRecursively && isset($vs['controls'])) $this->clean($vs['attributes']['id'], true);
        else if (isset($vs['properties']['value']))
        {
          if ($child instanceof Control) $child->clean();
          else $this->get($vs['attributes']['id'])->clean();
        }
      }
    }
  }
  
  /**
   * Checks or unchecks checkboxes of the panel.
   *
   * @param string $id - unique or logic identifier of the panel.
   * @param boolean $flag - determines whether a checkbox will be checked or not.
   * @param boolean $searchRecursively - determines whether the method should be recursively applied to all child panels of the given panel.
   * @access public
   */
  public function check($id, $flag = true, $searchRecursively = true)
  {
    $ctrl = $this->get($id);
    if ($ctrl instanceof CheckBox) $ctrl->check($flag);
    else if ($ctrl instanceof Panel)
    {
      foreach ($ctrl->getControls() as $child)
      {
        $vs = $this->getActualVS($child);
        if ($searchRecursively && isset($vs['controls'])) $this->check($vs['attributes']['id'], $flag, true);
        if ($vs['class'] == 'Aleph\Web\POM\CheckBox')
        {
          if ($child instanceof Control) $child->check($flag);
          else $this->get($vs['attributes']['id'])->check($flag);
        }
      }
    }
  }
  
  /**
   * For every control invokes the required method if the control has it.
   *
   * @param string $method - the required control method.
   * @param mixed $id - a control object or control ID. If this parameter is not defined, the unique ID of body control is used.
   * @access public
   */
  public function invoke($method, $id = null)
  {
    $vs = $this->getActualVS($id ?: $this->UID);
    if (isset($vs['controls']))
    {
      foreach ($vs['controls'] as $uniqueID)
      {
        $this->invoke($method, $uniqueID);
      }
    }
    if (!empty($vs['methods'][$method])) 
    {
      $this->get($vs['attributes']['id'])->{$method}();
    }
  }
  
  /**
   * Returns array of the validators with the given validation group.
   *
   * @param string $groups - comma separated validation groups or symbol "*", which means all validators.
   * @return array
   * @access public
   */
  public function getValidators($groups = 'default')
  {
    $validators = [];
    $ids = array_merge(array_keys($this->vs), array_keys($this->controls));
    if ($groups == '*')
    {
      foreach ($ids as $id)
      {
        $vs = $this->getActualVS($id);
        if ($vs && $vs['properties']['visible'] && is_subclass_of($vs['class'], 'Aleph\Web\POM\Validator') && empty($vs['attributes']['locked'])) 
        {
          $validators[] = $this->get($id);
        }
      }
    }
    else
    {
      $groups = array_map('trim', explode(',', $groups));
      foreach ($ids as $id)
      {
        $vs = $this->getActualVS($id);
        if ($vs && $vs['properties']['visible'] && is_subclass_of($vs['class'], 'Aleph\Web\POM\Validator') && empty($vs['attributes']['locked'])) 
        {
          $group = isset($vs['group']) ? $vs['group'] : 'default';
          foreach ($groups as $grp)
          {
            if (preg_match('/,\s*' . preg_quote($grp) . '\s*,/', ',' . $group . ','))
            {
              $validators[] = $this->get($id);
              break;
            }
          }
        }
      }
    }
    return $validators;
  }
  
  /**
   * Launches group of validators.
   * It returns TRUE if all validated controls are valid and FALSE otherwise. 
   *
   * @param string $groups - comma separated validation groups or symbol "*", which means all validators.
   * @param string $classInvalid - style class that will be applied to invalid controls.
   * @param string $classValid - style class that will be applied to valid controls.
   * @return boolean
   * @access public
   */
  public function validate($groups = 'default', $classInvalid = null, $classValid = null)
  {
    $validators = $this->getValidators($groups);
    usort($validators, function($a, $b)
    {
      return (int)$a->index - (int)$b->index;
    });
    $flag = true;
    $result = [];
    foreach ($validators as $validator)
    {
      if ($validator->validate())
      {
        foreach ($validator->getResult() as $id => $res)
        {
          if (!isset($result[$id])) $result[$id] = true;
        }
      }
      else
      {
        $flag = false;
        foreach ($validator->getResult() as $id => $res)
        {
          if (!$res) $result[$id] = false;
          else if (!isset($result[$id])) $result[$id] = true;
        }
      }
    }
    foreach ($result as $id => $res)
    {
      $ctrl = $this->get($id);
      $hasContainer = $ctrl->hasContainer();
      if ($res) $ctrl->removeClass($classInvalid, $hasContainer)->addClass($classValid, $hasContainer);
      else $ctrl->removeClass($classValid, $hasContainer)->addClass($classInvalid, $hasContainer);
    }
    return $flag;
  }
  
  /**
   * Sets group of validators to valid state.
   *
   * @param string $groups - comma separated validation groups or symbol "*", which means all validators.
   * @param string $classInvalid - style class that was applied to invalid controls.
   * @param string $classValid - style class that was applied to valid controls.
   * @access public
   */
  public function reset($groups = 'default', $classInvalid = null, $classValid = null)
  {
    foreach ($this->getValidators($groups) as $validator)
    {
      foreach ($validator->getControls() as $id)
      {
        $ctrl = $this->get($id);
        if ($ctrl) 
        {
          $hasContainer = $ctrl->hasContainer();
          $ctrl->removeClass($classInvalid, $hasContainer)
               ->addClass($classValid, $hasContainer);
        }
      }
      $validator->state = true;
    }
  }
  
  /**
   * Merges attribute values of the client side controls with the server side controls.
   * It returns FALSE if the current session is expired and TRUE otherwise. 
   *
   * @param string $UID - unique identifier of the view.
   * @param array $data - changes of control attributes received from the client side.
   * @param integer $timestamp - the time of obtaining of the given changes.
   * @return boolean
   * @access public
   */
  public function assign($UID, array $data, $timestamp)
  {
    $this->UID = $UID;
    if ($this->isExpired()) return false;
    $this->pull();
    $this->merge($data, $timestamp);
    return true;
  }
  
  /**
   * Forms HTTP response for the result of the Ajax request execution.
   *
   * @param mixed $data - result of the Ajax request execution.
   * @access public
   */
  public function process($data)
  {
    $this->compare();
    $this->push();
    $response = Net\Response::getInstance();
    $response->setContentType('json', isset(\Aleph::getInstance()['pom']['charset']) ? \Aleph::getInstance()['pom']['charset'] : 'utf-8');
    $response->cache(false);
    if (count($this->actions) == 0 && strlen($data) == 0) $response->body = '';
    else
    {
      $sep = uniqid();
      $response->body = $sep . implode(';', $this->actions) . $sep . json_encode($data);
    }
    $response->send();
  }

  /**
   * Parses the view template.
   *
   * @access public
   */   
  public function parse()
  {
    $config = \Aleph::getInstance()['pom'];
    $template = $this->tpl->getTemplate();
    $this->UID = 'v' . sha1(get_class(MVC\Page::$current) . $template . serialize($this->vars) . \Aleph::getSiteUniqueID());
    if (!empty($config['cacheEnabled']) && !$this->isExpired(true)) $this->pull(true);
    else
    {
      $ctx = $this->parseTemplate($this->prepareTemplate($template, $this->vars));
      $body = array_pop($ctx['controls']);
      if ($body)
      {
        if (!($body instanceof Body) || count($ctx['controls'])) throw new Core\Exception($this, 'ERR_VIEW_5');
        $this->controls[$body->id] = $body;
        $this->commit();
        static::decodePHPTags($body, $ctx['marks']);
        $src = \Aleph::url('framework') . '/web/js/jquery/jquery.min.js';
        if (empty($this->js['top'][$src])) $this->addJS(['src' => $src], null, true, -1000);
        $src = \Aleph::url('framework') . '/web/js/aleph.full.min.js';
        if (empty($this->js['top'][$src])) $this->addJS(['src' => $src], null, true, -999);
      }
      $this->tpl->setTemplate($ctx['html']);
      if (!empty($config['cacheEnabled'])) 
      {
        $this->commit();
        $this->push(true);
      }
    }
  }
  
  /**
   * Renders the view HTML.
   *
   * @return string
   * @access public
   */
  public function render()
  {
    foreach ($this->controls as $uniqueID => $ctrl)
    {
      foreach ($ctrl->getEvents() as $eid => $event)
      {
        if ($event === false) $this->addJS([], '$a.pom.get(\'' . $uniqueID . '\').unbind(\'' . $eid . '\')', false);
        else $this->addJS([], '$a.pom.get(\'' . $uniqueID . '\').bind(\'' . $eid . '\', \'' . $event['type'] . '\', ' . $event['callback'] . (empty($event['options']['check']) ? ', false' : ', ' . $event['options']['check']) . (empty($event['options']['toContainer']) ? ', false' : ', true') . ')', false);
      }
    }
    $sort = function($a, $b){return $a['order'] - $b['order'];};
    uasort($this->css, $sort);
    uasort($this->js['top'], $sort);
    uasort($this->js['bottom'], $sort);
    $head = '<title' . $this->renderAttributes($this->title['attributes']) . '>' . htmlspecialchars($this->title['title']) . '</title>';
    foreach ($this->meta as $meta) $head .= '<meta' . $this->renderAttributes($meta) . ' />';
    foreach ($this->css as $css) 
    {
      $conditions = '';
      if (isset($css['attributes']['conditions']))
      {
        $conditions = $css['attributes']['conditions'];
        unset($css['attributes']['conditions']);
        $head .= '<!--[' . $conditions . ']>';
      }
      if (empty($css['attributes']['type'])) $css['attributes']['type'] = 'text/css';
      if (isset($css['attributes']['href']) && empty($css['attributes']['rel'])) $css['attributes']['rel'] = 'stylesheet';
      if (isset($css['attributes']['href'])) $head .= '<link' . $this->renderAttributes($css['attributes']) . ' />';
      else $head .= '<style' . $this->renderAttributes($css['attributes']) . '>' . $css['style'] . '</style>';
      if ($conditions) $head .= '<![endif]-->';
    }
    foreach ($this->js['top'] as $js)
    {
      $conditions = '';
      if (isset($js['attributes']['conditions']))
      {
        $conditions = $js['attributes']['conditions'];
        unset($js['attributes']['conditions']);
        $head .= '<!--[' . $conditions . ']>';
      }
      if (empty($js['attributes']['type'])) $js['attributes']['type'] = 'text/javascript';
      $head .= '<script' . $this->renderAttributes($js['attributes']) . '>' . $js['script'] . '</script>';
      if ($conditions) $head .= '<![endif]-->';
    }
    if (count($this->js['bottom']))
    {
      $bottom = '';
      foreach ($this->js['bottom'] as $js) $bottom .= rtrim($js['script'], ';') . ';';
      if (strlen($bottom)) $head .= '<script type="text/javascript">$(function(){' . $bottom . '});</script>';
    }
    $this->tpl->__head_entities = $head;
    if (false !== $body = $this->get($this->UID)) $this->tpl->{$this->UID} = $body->render();
    $html = $this->tpl->render();
    $this->commit();
    $this->push();
    return $this->dtd . $html;
  }
  
  /**
   * Renders attributes of an HTML tag.
   *
   * @param array $attributes - tag attributes.
   * @return string
   * @access protected
   */
  protected function renderAttributes(array $attributes)
  {
    $tmp = [];
    foreach ($attributes as $attr => $value) 
    {
      if (strlen($value)) $tmp[] = $attr . '="' . (strpos($value, '<?') === false ? htmlspecialchars($value) : $value) . '"';
    }
    return ' ' . implode(' ', $tmp);
  }
  
  /**
   * Returns view state cache ID.
   *
   * @param boolean $init - if it is TRUE, the cache ID of initial view state is returned and otherwise the cache ID of intermediate view states is returned.
   * @return string
   * @access protected
   */
  protected function getCacheID($init)
  {
    return $this->UID . ($init ? '_init_vs' : session_id() . '_vs');
  }
  
  /**
   * Returns TRUE if the current session is expired and TRUE otherwise.
   *
   * @param boolean $init - determines whether need to check cache expiration of the initial view state.
   * @return boolean
   * @access protected
   */
  protected function isExpired($init = false)
  {
    return MVC\Page::$current->getCache()->isExpired($this->getCacheID($init));
  }
  
  /**
   * Extracts the control view states from the cache.
   *
   * @param boolean $init - determines whether need to extract the initial view state of the controls.
   * @access protected
   */
  protected function pull($init = false)
  {
    $vs = MVC\Page::$current->getCache()->get($this->getCacheID($init));
    if ($init)
    {
      $this->title = $vs['title'];
      $this->meta = $vs['meta'];
      $this->css = $vs['css'];
      $this->js = $vs['js'];
      $this->dtd = $vs['dtd'];
      $this->tpl->setTemplate($vs['tpl']);
    }
    $this->vs = $vs['vs'];
    $this->ts = $vs['ts'];
    Core\Template::setGlobals($vs['globals']);
  }
  
  /**
   * Puts the control view states into the cache.
   *
   * @param boolean $init - determines whether the initial view state of the controls is put into the cache.
   * @access protected
   */
  protected function push($init = false)
  {
    $vs = ['vs' => $this->vs, 'globals' => Core\Template::getGlobals(), 'ts' => $init ? 0 : (new Utils\DT('now', null, 'UTC'))->getTimestamp()];
    if ($init)
    {
      $vs['title'] = $this->title;
      $vs['meta'] = $this->meta;
      $vs['css'] = $this->css;
      $vs['js'] = $this->js;
      $vs['dtd'] = $this->dtd;
      $vs['tpl'] = $this->tpl->getTemplate();
    }
    $cache = MVC\Page::$current->getCache();
    $cache->set($this->getCacheID($init), $vs, $init ? $cache->getVaultLifeTime() : ini_get('session.gc_maxlifetime'), 'pages');
  }
  
  /**
   * Calculates the view state of controls and stores them in $vs property.
   *
   * @access protected
   */
  protected function commit()
  {
    $commit = function(Control $ctrl) use(&$commit)
    {
      $this->vs[$ctrl->id] = $ctrl->getVS();
      $this->controls[$ctrl->id] = $ctrl;
      if ($ctrl instanceof Panel)
      {
        foreach ($ctrl as $uniqueID => $child)
        {
          $commit($child);
        }
      }
    };
    foreach ($this->controls as $ctrl) $commit($ctrl);
  }
  
  /**
   * Merges new values of control attributes with their old values.
   *
   * @param array $data - new attribute values.
   * @param integer $timestamp - the time when the changes of attribute values occurred.
   * @access protected
   */
  protected function merge(array $data, $timestamp)
  {
    if ($timestamp > $this->ts) foreach ($data as $uniqueID => $info)
    {
      if (empty($this->vs[$uniqueID])) continue;
      if (isset($info['value']) && array_key_exists('value', $this->vs[$uniqueID]['properties']))
      {
        $this->vs[$uniqueID]['properties']['value'] = $info['value'];
      }
      if (isset($info['attrs']))
      {
        $this->vs[$uniqueID]['attributes'] = array_merge($this->vs[$uniqueID]['attributes'], $info['attrs']);
      }
      if (isset($info['removed']))
      {
        foreach ($info['removed'] as $attr) unset($this->vs[$uniqueID]['attributes'][$attr]);
      }
    }
  }
  
  /**
   * Compares the current values of attributes and properties of the controls with their old values 
   * and forms JS code for refreshing the changed controls on the client side. 
   *
   * @access protected
   */
  protected function compare()
  {
    if (count($this->controls) == 0) return;
    $tmp = ['created' => [], 'removed' => [], 'refreshed' => []];
    $events = []; $flag = false;
    foreach ($this->controls as $uniqueID => $ctrl)
    {
      if ($ctrl->isRemoved())
      {
        $tmp['removed'][$uniqueID] = $ctrl instanceof Panel;
        unset($this->vs[$uniqueID]);
        $flag = true;
        continue;
      }
      if ($ctrl->isCreated())
      {
        $mode = $ctrl->getCreationInfo()['mode'];
        $id = $ctrl->getCreationInfo()['id'];
        if ($mode && $id)
        {
          $tmp['created'][$uniqueID] = ['html' => $ctrl->render(), 'mode' => $ctrl->getCreationInfo()['mode'], 'id' => $ctrl->getCreationInfo()['id']];
          $flag = true;
        }
      }
      else if (isset($this->vs[$uniqueID]))
      {
        $cmp = $ctrl->compare($this->vs[$uniqueID]);
        if (!is_array($cmp) || count($cmp['attrs']) || count($cmp['removed']))
        {
          $tmp['refreshed'][$uniqueID] = $cmp;
          $flag = true;
        }
      }
      foreach ($ctrl->getEvents() as $eid => $event)
      {
        if ($event === false) $events[] = '$a.pom.get(\'' . $uniqueID . '\').unbind(\'' . $eid . '\')';
        else $events[] = '$a.pom.get(\'' . $uniqueID . '\').bind(\'' . $eid . '\', \'' . $event['type'] . '\', ' . $event['callback'] . (empty($event['options']['check']) ? ', false' : ', ' . $event['options']['check']) . (empty($event['options']['toContainer']) ? ', false' : ', true') . ')';
      }
      $this->vs[$uniqueID] = $ctrl->getVS();
    }
    if ($flag)
    {
      array_unshift($events, '$a.pom._refreshPOMTree(' . Utils\PHP\Tools::php2js($tmp, true, self::JS_MARK) . ')');
      array_unshift($this->actions, implode(';', $events));
    }
  }  
  
  /**
   * Prepares template to the parsing.
   * It returns the detailed information about the template. 
   *
   * @param string $template - template string or path to template file.
   * @param array $vars - template variables for the template preprocessing.
   * @return array
   * @access protected
   */
  protected function prepareTemplate($template, array $vars = null)
  {
    $cfg = \Aleph::getInstance()->getConfig();
    $config = \Aleph::getInstance()['pom'];
    $prefix = empty($config['prefix']) ? '' : strtolower($config['prefix']) . ':';
    $ppOpenTag = empty($config['ppOpenTag']) ? '<!--{' : $config['ppOpenTag'];
    $ppCloseTag = empty($config['ppCloseTag']) ? '}-->' : $config['ppCloseTag'];
    if (is_file($template))
    {
      $file = $template;
      $xhtml = file_get_contents($template);
    }
    else
    {
      $file = false;
      $xhtml = $template;
    }
    $placeholders = [];
    $qprefix = preg_quote($prefix);
    while (strtolower(substr(ltrim($xhtml), 0, strlen($prefix) + 9)) == '<' . $prefix . 'template')
    {
      preg_match('/\A\s*<' . $qprefix . 'template\s*placeholder\s*=\s*"([^"]*)"\s*>(.*)<\/' . $qprefix . 'template>/i', $xhtml, $matches);
      $master = \Aleph::exe($matches[2], ['config' => $cfg]);
      if (!file_exists($master)) throw new Core\Exception($this, 'ERR_VIEW_4', $file);
      $xhtml = ltrim(substr($xhtml, strlen($matches[0])));
      $placeholders[\Aleph::exe($matches[1], ['config' => $cfg])] = $xhtml;
      $file = $master;
      $xhtml = file_get_contents($master);
      foreach ($placeholders as $id => $content)
      {
        $xhtml = preg_replace('/<' . $qprefix . 'placeholder\s*id\s*=\s*"' . preg_quote($id) . '"\s*(\/>|>(.*)<\/' . $qprefix . 'placeholder>)/i', $content, $xhtml, -1, $count);
      }
    }
    if (strpos($xhtml, $ppOpenTag) !== false)
    {
      $xhtml = new Core\Template(strtr(static::encodePHPTags($xhtml, $marks), [$ppOpenTag => '<?php ', $ppCloseTag => '?>']));
      if ($vars) $xhtml->setVars($vars);
      $xhtml = $xhtml->render();
    }
    else
    {
      $xhtml = static::encodePHPTags($xhtml, $marks);
    }
    return ['file' => $file,
            'xhtml' => $xhtml,
            'charset' => isset($config['charset']) ? $config['charset'] : 'utf-8',
            'prefix' => $prefix,
            'controls' => [],
            'marks' => $marks,
            'stack' => new \SplStack(),
            'insideHead' => false,
            'tag' => '',      
            'html' => ''];
         
  }
  
  /**
   * Parses the template.
   *
   * @param array $ctx - array of the template detailed information.
   * @return array - the updated detailed information about the template.
   * @access protected
   */
  protected function parseTemplate(array $ctx)
  {
    $parseStart = function($parser, $tag, array $attributes) use(&$ctx)
    {
      $tag = $ctx['tag'] = strtolower($tag);
      $p = strpos($tag, $ctx['prefix']);
      if ($p === 0)
      {
        $tag = substr($tag, strlen($ctx['prefix']));
        if ($tag == 'template')
        {
          $path = isset($attributes['path']) ? static::evaluate($attributes['path'], $ctx['marks']) : null;
          if (!file_exists($path)) 
          {
            $line = xml_get_current_line_number($parser);
            $column = xml_get_current_column_number($parser);
            throw new Core\Exception($this, 'ERR_VIEW_3', $ctx['file'] ? ' in file "' . realpath($ctx['file']) . '"' : '.', $line, $column);
          }
          $res = static::analyze($path, $this->vars);
          $ctx['marks'] = array_merge($ctx['marks'], $res['marks']);
          if (count($ctx['stack']) > 0)
          {
            $parent = $ctx['stack']->top();
            $parent->tpl->setTemplate($parent->tpl->getTemplate() . $res['html']);
            foreach ($res['controls'] as $control) $parent->add($control);
          }
          else
          {
            $ctx['html'] .= $res['html'];
            $ctx['controls'][] = $ctx['stack']->pop();
            $ctx['controls'] = array_merge($ctx['controls'], $res['controls']);
          }
          return;
        }
        if ($tag == 'body')
        {
          $ctrl = new Body('body');
          $vs = $ctrl->getVS();
          $vs['attributes']['id'] = $this->UID;
          $ctrl->setVS($vs);
        }
        else
        {
          if (empty($attributes['id'])) 
          {
            $line = xml_get_current_line_number($parser);
            $column = xml_get_current_column_number($parser);
            throw new Core\Exception($this, 'ERR_VIEW_2', $tag, $ctx['file'] ? ' in file "' . realpath($ctx['file']) . '"' : '.', $line, $column);
          }
          $ctrl = '\Aleph\Web\POM\\' . $tag;
          $ctrl = new $ctrl($attributes['id']);
        }
        if (($ctrl instanceof Panel) && isset($attributes['template']))
        {
          $res = static::analyze(static::evaluate($attributes['template'], $ctx['marks']), $this->vars);
          $ctx['marks'] = array_merge($ctx['marks'], $res['marks']);
          foreach ($res['controls'] as $control) $ctrl->add($control);
          $ctrl->tpl->setTemplate($res['html']); 
          unset($attributes['template']);
        }
        foreach ($attributes as $k => $v) 
        {
          if (strtolower(substr($k, 0, 5)) == 'attr-') $ctrl->{substr($k, 5)} = $v;
          else if (isset($ctrl[$k])) $ctrl[$k] = $v;
          else $ctrl->{$k} = $v;
        }
        $ctx['stack']->push($ctrl);
      }
      else
      {
        if ($ctx['insideHead'])
        {
          switch ($tag)
          {
            case 'title':
              $this->setTitle('', static::decodePHPTags($attributes, $ctx['marks']));
              return;
            case 'meta':
              $attributes = static::decodePHPTags($attributes, $ctx['marks']);
              if (isset($attributes['id'])) $this->setMeta($attributes['id'], $attributes);
              else $this->addMeta($attributes);
              return;
            case 'style':
            case 'link':
              $attributes = static::decodePHPTags($attributes, $ctx['marks']);
              if (isset($attributes['id'])) $this->setCSS($attributes['id'], $attributes);
              else $this->addCSS($attributes);
              return;
            case 'script':
              $attributes = static::decodePHPTags($attributes, $ctx['marks']);
              if (isset($attributes['id'])) $this->setJS($attributes['id'], $attributes);
              else $this->addJS($attributes);
              return;
          }
        }
        $html = '<' . $tag;
        if (count($attributes))
        {
          $tmp = [];
          foreach ($attributes as $k => $v) $tmp[] = $k . '="' . $v . '"';
          $html .= ' ' . implode(' ', $tmp);
        }
        $html .= empty(static::$emptyTags[$tag]) ? '>' : ' />';
        if ($tag == 'head') 
        {
          $ctx['insideHead'] = true;
          $html .= static::getControlPlaceholder('__head_entities');
        }
        if (!count($ctx['stack'])) $ctx['html'] .= $html;
        else
        {
          $ctrl = $ctx['stack']->top();
          if ($ctrl instanceof Panel) $ctrl->tpl->setTemplate($ctrl->tpl->getTemplate() . $html);
          else if (isset($ctrl['value'])) $ctrl['value'] .= $html;
          else $ctrl['text'] .= $html;
        }
      }
    };
    $parseEnd = function($parser, $tag) use(&$ctx)
    {
      $ctx['tag'] = '';
      $tag = strtolower($tag);
      $p = strpos($tag, $ctx['prefix']);
      if ($p === 0)
      {
        $tag = substr($tag, strlen($ctx['prefix']));
        if ($tag == 'template') return;
        if (count($ctx['stack']) > 1)
        {
          $ctrl = $ctx['stack']->pop();
          $parent = $ctx['stack']->top();
          $parent->tpl->setTemplate($parent->tpl->getTemplate() . static::getControlPlaceholder($ctrl->id));
          $parent->add($ctrl);
        }
        else
        {
          $ctrl = $ctx['stack']->pop();
          $ctx['html'] .= static::getControlPlaceholder($ctrl->id);
          $ctx['controls'][] = $ctrl;
        }
      }
      else
      {
        $html = '';
        if (empty(static::$emptyTags[$tag]))
        {
          if (!$ctx['insideHead'] || $tag != 'title' && $tag != 'script' && $tag != 'style') $html = '</' . $tag . '>';
        }
        if ($tag == 'head') $ctx['insideHead'] = false;
        if (!count($ctx['stack'])) $ctx['html'] .= $html;
        else
        {
          $ctrl = $ctx['stack']->top();
          if ($ctrl instanceof Panel) $ctrl->tpl->setTemplate($ctrl->tpl->getTemplate() . $html);
          else if (isset($ctrl['value'])) $ctrl['value'] .= $html;
          else $ctrl['text'] .= $html;
        }
      }
    };
    $parseCData = function($parser, $content) use(&$ctx)
    {    
      if ($ctx['insideHead'])
      {
        switch ($ctx['tag'])
        {
          case 'title':
            $title = $this->getTitle(true);
            $this->setTitle($title['title'] . static::decodePHPTags($content, $ctx['marks']), $title['attributes']);
            break;
          case 'style':
          case 'link':
            $content = static::decodePHPTags($content, $ctx['marks']);
            $css = array_pop($this->css);
            if (isset($css['attributes']['id'])) $this->setCSS($css['attributes']['id'], $css['attributes'], $css['style'] . $content);
            else $this->addCSS($css['attributes'], $css['style'] . $content);
            return;
          case 'script':
            $content = static::decodePHPTags($content, $ctx['marks']);
            $js = array_pop($this->js['top']);
            if (isset($js['attributes']['id'])) $this->setJS($js['attributes']['id'], $js['attributes'], $js['script'] . $content);
            else $this->addJS($js['attributes'], $js['script'] . $content);
            return;
          default:
            $content = trim($content);
            if (preg_match('/^<!\-\-\[([a-zA-Z 0-9]+)\]>(.*)\<!\[endif\]\-\->$/is', $content, $matches))
            {
              $dom = new \DOMDocument();
              $dom->loadXML('<head>' . $matches[2] . '</head>');
              $dom = simplexml_import_dom($dom);
              foreach ($dom as $t => $item)
              {
                $t = strtolower($t);
                if ($t != 'script' && $t != 'link' && $t != 'style') continue;
                $attr = [];
                foreach ($item->attributes() as $k => $v) $attr[$k] = (string)$v;
                $attr['conditions'] = $matches[1];
                if ($t == 'script') 
                {
                  if (isset($attr['id'])) $this->setJS($attr['id'], $attr, (string)$item);
                  else $this->addJS($attr, (string)$item);
                }
                else
                {
                  if (isset($attr['id'])) $this->setCSS($attr['id'], $attr, (string)$item);
                  else $this->addCSS($attr, (string)$item);
                }
              }
            }
            else
            {
              $ctx['html'] .= $content;
            }
            break;
        }
      }
      else
      {
        if (!count($ctx['stack'])) $ctx['html'] .= $content;
        else
        {
          $ctrl = $ctx['stack']->top();
          if ($ctrl instanceof Panel) $ctrl->tpl->setTemplate($ctrl->tpl->getTemplate() . $content);
          else if (isset($ctrl['value'])) $ctrl['value'] .= $content;
          else $ctrl['text'] .= $content;
        }
      }
    };
    preg_match('/^<!doctype[^>]+>/i', $ctx['xhtml'], $matches);
    if (isset($matches[0])) $this->dtd = $matches[0];
    static::$process++;
    $parser = xml_parser_create($ctx['charset']);
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_set_element_handler($parser, $parseStart, $parseEnd);
    xml_set_character_data_handler($parser, $parseCData);
    xml_set_default_handler($parser, $parseCData);
    $flag = strtolower(substr($ctx['xhtml'], 0, 9)) != '<!doctype';
    if ($flag) $xhtml = '<root>' . $ctx['xhtml'] . '</root>';
    else $xhtml = $ctx['xhtml'];
    if (!xml_parse($parser, $xhtml))
    {
      $error = xml_error_string(xml_get_error_code($parser));
      $line = xml_get_current_line_number($parser);
      $column = xml_get_current_column_number($parser);
      $file = $ctx['file'] ? "\nFile: " . realpath($ctx['file']) : '';
      throw new Core\Exception($this, 'ERR_VIEW_1', $error, $file, $line, $column);
    }
    xml_parser_free($parser);
    if ($flag) $ctx['html'] = substr($ctx['html'], 6, -7);
    static::$process--;
    return $ctx;
  }
  
  /**
   * Searches the control in POM.
   *
   * @param array $cid - the logic control identifier.
   * @param array $controls - controls that contained in the panel.
   * @param integer $deep - the nested level of the control.
   * @return Aleph\Web\POM\Control
   * @access private
   */
  private function searchControl(array $cid, array $controls, $deep = -1)
  {
    foreach ($controls as $obj)
    {
      $vs = $this->getActualVS($obj);
      if ($vs && $vs['properties']['id'] == $cid[0])
      {
        $m = 1; $n = count($cid);
        for ($k = 1; $k < $n; $k++)
        {
          if (!isset($vs['controls'])) break;
          $controls = $vs['controls']; $flag = false;
          foreach ($controls as $obj)
          {
            $vs = $this->getActualVS($obj);
            if ($vs && $vs['properties']['id'] == $cid[$k])
            {
              $m++;
              $flag = true;
              break;
            }
          }
          if (!$flag) break;
        }
        if ($m == $n) break;
        return false;
      }
      else if (isset($vs['controls']) && $deep != 0) 
      {
        $ctrl = $this->searchControl($cid, $vs['controls'], $deep > 0 ? $deep - 1 : -1);
        if ($ctrl !== false) return $ctrl;
      }
    }
    return empty($n) ? false : ($obj instanceof Control ? $obj : $this->get($vs['attributes']['id']));
  }
  
  /**
   * Returns the actual view state of the control.
   *
   * @param mixed $obj - the control object or its ID.
   * @return array|boolean - returns FALSE if a control with such ID does not exist.
   */
  private function getActualVS($obj)
  {
    if ($obj instanceof Control)
    {
      if ($obj->isRemoved()) return false;
      return $obj->getVS();
    } 
    if (isset($this->controls[$obj])) 
    {
      if ($this->controls[$obj]->isRemoved()) return false;
      return $this->controls[$obj]->getVS();
    }
    return $this->vs[$obj];
  }
}

}