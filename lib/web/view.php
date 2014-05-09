<?php

namespace
{
  function ID($id)
  {
    return \Aleph\MVC\Page::$current->view->get($id)->id;
  }
}

namespace Aleph\Web\POM {

use Aleph\Core,
    Aleph\MVC,
    Aleph\Net,
    Aleph\Utils;

class View implements \ArrayAccess
{
  const ERR_VIEW_1 = "XHTML parse error! [{var}].[{var}]\nLine: [{var}], column: [{var}].";
  const ERR_VIEW_2 = "Property ID of element [{var}] is not defined or empty[{var}]\nLine: [{var}], column: [{var}].";
  const ERR_VIEW_3 = "Attribute \"path\" of element \"template\"[{var}] is not defined or such path does not exist.\nLine: [{var}], column: [{var}].";
  const ERR_VIEW_4 = "Path to the master template is not defined or incorrect.\nFile: [{var}]";
  const ERR_VIEW_5 = 'Page template should have only one element body containing all other web controls.';
  
  protected static $process = 0;
  
  protected static $emptyTags = ['br' => 1, 'hr' => 1, 'meta' => 1, 'link' => 1, 'img' => 1, 'embed' => 1, 'param' => 1, 'input' => 1, 'base' => 1, 'area' => 1, 'col' => 1];

  public $tpl = null;
  
  protected $vars = [];
  
  protected $controls = [];
  
  protected $actions = [];
  
  protected $dtd = '<!DOCTYPE html>';
  
  protected $title = ['title' => '', 'attributes' => []];
  
  protected $meta = [];
  
  protected $css = [];
  
  protected $js = ['top' => [], 'bottom' => []];
  
  private $UID = null;
  
  private $vs = [];
  
  private $ts = 0;
  
  public static function inParsing()
  {
    return static::$process > 0;
  }
  
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
      foreach ($vs['attributes'] as $attr => $value) $obj->{$attr} = static::evolute($value, $marks);
      foreach ($vs['properties'] as $prop => $value) $obj[$prop] = static::evolute($value, $marks);
      return $obj;
    }
    else if (is_array($obj))
    {
      foreach ($obj as &$xhtml) $xhtml = static::decodePHPTags($xhtml, $marks);
      return $obj;
    }
    return strtr(str_replace('6cff047854f19ac2aa52aac51bf3af4a', '&', $obj), $marks);
  }
  
  public static function evolute($value, array $marks)
  {
    $config = \Aleph::getInstance()->getConfig();
    $value = \Aleph::exe(static::decodePHPTags($value, $marks), ['config' => $config]);
    $php = isset($config['pom']['phpMark']) ? $config['pom']['phpMark'] : 'php::';
    if (substr($value, 0, strlen($php)) == $php) 
    {
      $value = substr($value, strlen($php));
      if (strlen($value) == 0) return;
      eval(\Aleph::ecode('$value = ' . $value . ';'));
    }
    return $value;
  }
  
  public static function analyze($template, array $vars = null)
  {
    $view = new static();
    $ctx = $view->parseTemplate($view->prepareTemplate($template, $vars));
    $res = ['dtd' => $view->getDTD(),
            'title' => $view->getTitle(true),
            'meta' => $view->getAllMeta(),
            'js' => $view->getAllJS(),
            'css' => $view->getAllCSS(),
            'html' => $ctx['html']];
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
  
  public static function getControlPlaceHolder($uniqueID)
  {
    return '<?php echo $' . $uniqueID . '; ?>'; 
  }

  public function __construct($template = null)
  {
    $this->tpl = new Core\Template($template);
  }
  
  public function offsetSet($name, $value)
  {
    $this->vars[$name] = $value;
  }

  public function offsetExists($name)
  {
    return isset($this->vars[$name]);
  }

  public function offsetUnset($name)
  {
    unset($this->vars[$name]);
  }

  public function &offsetGet($name)
  {
    if (!isset($this->vars[$name])) $this->vars[$name] = null;
    return $this->vars[$name];
  }
  
  public function getVars()
  {
    return $this->vars;
  }
  
  public function setVars(array $vars)
  {
    $this->vars = $vars;
  }
  
  public function getDTD()
  {
    return $this->dtd;
  }
  
  public function setDTD($dtd)
  {
    $this->dtd = $dtd;
  }
  
  public function setTitle($title, array $attributes = null)
  {
    $this->title = ['title' => $title, 'attributes' => $attributes ?: $this->title['attributes']];
  }
  
  public function getTitle($withAttributes = false)
  {
    if ($withAttributes) return $this->title;
    return $this->title['title'];
  }
  
  public function addMeta(array $attributes)
  {
    $this->meta[] = $attributes;
  }
  
  public function setMeta($id, array $attributes)
  {
    $this->meta[$id] = $attributes;
  }
  
  public function getMeta($id)
  {
    return isset($this->meta[$id]) ? $this->meta[$id] : false;
  }
  
  public function removeMeta($id)
  {
    unset($this->meta[$id]);
  }
  
  public function getAllMeta()
  {
    return $this->meta;
  }
  
  public function addCSS(array $attributes, $style = null, $order = null)
  {
    $this->css[] = ['style' => $style, 'attributes' => $attributes, 'order' => $order !== null ? (int)$order : count($this->css)];
  }
  
  public function setCSS($id, array $attributes, $style = null, $order = null)
  {
    $this->css[$id] = ['style' => $style, 'attributes' => $attributes, 'order' => $order !== null ? (int)$order : count($this->css)];
  }
  
  public function getCSS($id)
  {
    return isset($this->css[$id]) ? $this->css[$id] : false;
  }
  
  public function removeCSS($id)
  {
    unset($this->css[$id]);
  }
  
  public function getAllCSS()
  {
    return $this->css;
  }
  
  public function addJS(array $attributes, $script = null, $inHead = true, $order = null)
  {
    $place = $inHead ? 'top' : 'bottom';
    $this->js[$place][] = ['script' => $script, 'attributes' => $attributes, 'order' => $order !== null ? (int)$order : count($this->js[$place])];
  }
  
  public function setJS($id, array $attributes, $script = null, $inHead = true, $order = null)
  {
    $place = $inHead ? 'top' : 'bottom';
    $this->js[$place][$id] = ['script' => $script, 'attributes' => $attributes, 'order' => $order !== null ? (int)$order : count($this->js[$place])];
  }
  
  public function getJS($id, $inHead = true)
  {
    $place = $inHead ? 'top' : 'bottom';
    return isset($this->js[$place][$id]) ? $this->js[$place][$id] : false;
  }
  
  public function removeJS($id, $inHead = true)
  {
    unset($this->js[$inHead ? 'top' : 'bottom'][$id]);
  }
  
  public function getAllJS()
  {
    return $this->js;
  }
  
  public function action(/* $action, $param1, $param2, ..., $time = 0 */)
  {
    $args = func_get_args();
    switch ($args[0])
    {
      case 'alert':
        $this->actions[] = '$a.ajax.action(\'alert\', \'' .  $this->quote($args[1]) . '\', ' . (isset($args[2]) ? (int)$args[2] : 0) . ')';
        break;
      case 'script':
        $this->actions[] = '$a.ajax.action(\'script\', \'' . $this->replaceBreakups(json_decode($args[1])) . '\', ' . (isset($args[2]) ? (int)$args[2] : 0) . ')';
        break;
      default:
        $this->actions[] = '$a.ajax.action(\'script\', \'' . $this->replaceBreakups(json_decode($args[0])) . '\', ' . (isset($args[1]) ? (int)$args[1] : 0) . ')';
        break;
    }
  }
  
  public function attach(Control $ctrl)
  {
    $this->controls[$ctrl->id] = $ctrl;
    if ($ctrl instanceof Panel) foreach($ctrl as $child) $this->attach($child);
  }

  public function isAttached($ctrl)
  {
    $id = $ctrl instanceof Control ? $ctrl->id : $ctrl;
    return isset($this->controls[$id]) || isset($this->vs[$id]);
  }
  
  public function get($id, $isRecursion = true, Control $context = null)
  {
    if (isset($this->controls[$id])) return $this->controls[$id];
    if (isset($this->vs[$id]))
    {
      $vs = $this->vs[$id];
      $ctrl = new $vs['class']($vs['properties']['id']);
      $ctrl->setVS($vs);
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
    $ctrl = $this->searchControl(explode('.', $id), $controls, $isRecursion ? -1 : 0);
    if ($ctrl) $this->controls[$ctrl->id] = $ctrl;
    return $ctrl;
  }
  
  public function clean($id, $isRecursion = true)
  {
    $ctrl = $this->get($id);
    if (isset($ctrl['value'])) $ctrl->clean();
    if ($ctrl instanceof Panel)
    {
      foreach ($ctrl->getControls() as $child)
      {
        $vs = $this->getActualVS($child);
        if (isset($vs['properties']['value']))
        {
          if ($isRecursion && isset($vs['controls'])) $this->clean($vs['attributes']['id'], true);
          else if ($child instanceof Control) $child->clean();
          else $this->get($vs['attributes']['id'])->clean();
        }
      }
    }
  }
  
  public function invoke($method, $obj = null)
  {
    $vs = $this->getActualVS($obj ?: $this->UID);
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
  
  public function assign($UID, array $data, $timestamp)
  {
    $this->UID = $UID;
    if ($this->isExpired()) return false;
    $this->pull();
    $this->merge($data, $timestamp);
    return true;
  }
  
  public function process($data)
  {
    $this->compare();
    $this->push();
    $response = Net\Response::getInstance();
    $response->setContentType('json', isset(\Aleph::getInstance()['pom']['charset']) ? \Aleph::getInstance()['pom']['charset'] : 'utf-8');
    $response->cache(false);
    $response->body = json_encode(['script' => implode(';', $this->actions), 'data' => $data]);
    $response->send();
  }
  
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
        $this->controls = [$body->id => static::decodePHPTags($body, $ctx['marks'])];
        $this->commit();
        $this->controls = [];
      }
      $this->tpl->setTemplate($ctx['html']);
      $url = \Aleph::url('framework');
      $jquery = $url . '/web/js/jquery.min.js';
      $aleph = $url . '/web/js/aleph.min.js';
      foreach ($this->js['top'] as $id => $js)
      {
        if (isset($js['attributes']['src']))
        {
          if ($js['attributes']['src'] == $jquery) $hasjQuery = true;
          else if ($js['attributes']['src'] == $aleph) $hasAleph = true;
          if ($hasjQuery && $hasAleph) break;
        }
      }
      if (empty($hasjQuery)) $this->setJS('jquery', ['src' => $jquery], null, true, -1000);
      if (empty($hasAleph)) $this->setJS('aleph', ['src' => $aleph], null, true, -100);
      if (!empty($config['cacheEnabled'])) $this->push(true);
    }
  }
  
  public function render()
  {
    foreach ($this->controls as $uniqueID => $ctrl)
    {
      foreach ($ctrl->getEvents() as $eid => $event)
      {
        if ($event === false) $this->addJS([], '$a.pom.get(\'' . $uniqueID . '\').unbind(\'' . $eid . '\')', false);
        else $this->addJS([], '$a.pom.get(\'' . $uniqueID . '\').bind(\'' . $eid . '\', \'' . $event['type'] . '\', ' . $event['callback'] . (strlen($event['check']) ? ', ' . $event['check'] : '') .  ')', false);
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
    $this->push();
    return $this->dtd . $html;
  }
  
  protected function renderAttributes(array $attributes)
  {
    $tmp = [];
    foreach ($attributes as $attr => $value) 
    {
      if (strlen($value)) $tmp[] = $attr . '="' . (strpos($value, '<?') === false ? htmlspecialchars($value) : $value) . '"';
    }
    return ' ' . implode(' ', $tmp);
  }
  
  protected function getCacheID($init)
  {
    return $this->UID . ($init ? '_init_vs' : session_id() . '_vs');
  }
  
  protected function isExpired($init = false)
  {
    return MVC\Page::$current->getCache()->isExpired($this->getCacheID($init));
  }
  
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
  
  protected function commit()
  {
    $commit = function(Control $ctrl) use(&$commit)
    {
      $this->vs[$ctrl->id] = $ctrl->getVS();
      if ($ctrl instanceof Panel)
      {
        foreach ($ctrl->getControls() as $uniqueID => $ctrl)
        {
          if ($ctrl instanceof Control) $commit($ctrl);
        }        
      }
    };
    foreach ($this->controls as $ctrl) $commit($ctrl);
  }
  
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
    }
  }
  
  protected function compare()
  {
    $tmp = [];
    foreach ($this->controls as $uniqueID => $ctrl)
    {
      if ($ctrl->isRemoved())
      {
        $tmp[] = '$a.pom.remove(\'' . $uniqueID . '\');';
        unset($this->vs[$uniqueID]);
        continue;
      }
      $vs = $ctrl->getVS();
      if ($ctrl->isCreated())
      {
        $tmp[] = '$a.pom.create(\'' . $uniqueID . '\', \'' . $this->replaceBreakups($ctrl->render()) . '\', \'' . $ctrl->getCreationInfo()['mode'] . '\', \'' . $ctrl->getCreationInfo()['id'] . '\')';
      }
      else
      {
        $diff = $ctrl->compare($this->vs[$uniqueID]);
        if (is_array($diff)) 
        {
          foreach ($diff as $attr => &$value) $value = "'" . $attr . "': '" . $this->replaceBreakups($value) . "'";
          unset($value);
          $diff = '{' . implode(',', $diff) . '}';
          $removed = array_diff_key($this->vs[$uniqueID]['attributes'], $vs['attributes']);
          if (count($removed) == 0) $removed = '[]';
          else
          {
            foreach ($removed as $attr => &$value) $value = "'" . $attr . "'";
            $removed = '[' . implode(', ', $removed) . ']';
          }
        }
        else
        {
          $diff = "'" . $this->replaceBreakups($diff) . "'";
          $removed = '[]';
        }
        if ($diff != '{}' || $removed != '[]')
        {
          $tmp[] = '$a.pom.refresh(\'' . $uniqueID . '\', ' . $diff . ', ' . $removed . ', ' . $this->replaceBreakups(json_encode(array_keys($ctrl->getBaseAttributes()))) . ')';
        }
      }
      foreach ($ctrl->getEvents() as $eid => $event)
      {
        if ($event === false) $tmp[] = '$a.pom.get(\'' . $uniqueID . '\').unbind(\'' . $eid . '\')';
        else $tmp[] = '$a.pom.get(\'' . $uniqueID . '\').bind(\'' . $eid . '\', \'' . $event['type'] . '\', ' . $event['callback'] . (strlen($event['check']) ? ', ' . $event['check'] : '') .  ')';
      }
      $this->vs[$uniqueID] = $ctrl->getVS();
    }
    if ($tmp) array_unshift($this->actions, implode(';', $tmp));
  }  
  
  protected function prepareTemplate($template, array $vars = null)
  {
    $config = \Aleph::getInstance()['pom'];
    $prefix = empty($config['prefix']) ? 'c:' : strtolower($config['prefix']) . ':';
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
    while (strtolower(substr(ltrim($xhtml), 0, strlen($prefix) + 9)) == '<' . $prefix . 'template')
    {
      preg_match('/\A\s*<' . preg_quote($prefix) . 'template\s*placeholder\s*=\s*"([^"]*)"\s*>(.*)<\/' . preg_quote($prefix) . 'template>/i', $xhtml, $matches);
      $master = \Aleph::exe($matches[2], ['config' => $config]);
      if (!file_exists($master)) throw new Core\Exception($this, 'ERR_VIEW_4', $file);
      $file = $master;
      $xhtml = file_get_contents($master);
      $placeholders[\Aleph::exe($matches[1], ['config' => $config])] = ltrim(substr($xhtml, strlen($matches[0])));
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
            'placeholders' => $placeholders,
            'marks' => $marks,
            'stack' => new \SplStack(),
            'insideHead' => false,
            'tag' => '',      
            'html' => ''];
         
  }
  
  protected function parseTemplate(array $ctx)
  {
    $parseStart = function($parser, $tag, array $attributes) use(&$ctx)
    {
      $tag = $ctx['tag'] = strtolower($tag);
      $p = strpos($tag, $ctx['prefix']);
      if ($p === 0)
      {
        $tag = substr($tag, strlen($ctx['prefix']));
        if ($tag == 'template' || $tag == 'placeholder')
        {
          if ($tag == 'placeholder')
          {
            if (empty($attributes['id'])) return;
            $id = static::evolute($attributes['id'], $ctx['marks']);
            if (empty($ctx['placeholders'][$id])) return;
            $path = $ctx['placeholders'][$id];
          }
          else
          {
            $path = isset($attributes['path']) ? static::evolute($attributes['path'], $ctx['marks']) : null;
            if (!file_exists($path)) 
            {
              $line = xml_get_current_line_number($parser);
              $column = xml_get_current_column_number($parser);
              throw new Core\Exception($this, 'ERR_VIEW_3', $ctx['file'] ? ' in file "' . realpath($ctx['file']) . '"' : '.', $line, $column);
            }
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
          $res = static::analyze(static::evolute($attributes['template'], $ctx['marks']), $this->vars);
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
          $html .= static::getControlPlaceHolder('__head_entities');
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
        if ($tag == 'template' || $tag == 'placeholder') return;
        if (count($ctx['stack']) > 1)
        {
          $ctrl = $ctx['stack']->pop();
          $parent = $ctx['stack']->top();
          $parent->tpl->setTemplate($parent->tpl->getTemplate() . static::getControlPlaceHolder($ctrl->id));
          $parent->add($ctrl);
        }
        else
        {
          $ctrl = $ctx['stack']->pop();
          $ctx['html'] .= static::getControlPlaceHolder($ctrl->id);
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
    if (!xml_parse($parser, $ctx['xhtml']))
    {
      $error = xml_error_string(xml_get_error_code($parser));
      $line = xml_get_current_line_number($parser);
      $column = xml_get_current_column_number($parser);
      $file = $ctx['file'] ? "\nFile: " . realpath($ctx['file']) : '';
      throw new Core\Exception($this, 'ERR_VIEW_1', $error, $file, $line, $column);
    }
    xml_parser_free($parser);
    static::$process--;
    return $ctx;
  }
  
  protected function searchControl(array $cid, $controls, $deep = -1)
  {
    foreach ($controls as $obj)
    {
      $vs = $this->getActualVS($obj);
      if ($vs['properties']['id'] == $cid[0])
      {
        $m = 1; $n = count($cid);
        for ($k = 1; $k < $n; $k++)
        {
          if (!isset($vs['controls'])) break;
          $controls = $vs['controls']; $flag = false;
          foreach ($controls as $obj)
          {
            $vs = $this->getActualVS($obj);
            if ($vs['properties']['id'] == $cid[$k])
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
  
  private function getActualVS($obj)
  {
    return $obj instanceof Control ? $obj->getVS() : (isset($this->controls[$obj]) ? $this->controls[$obj]->getVS() : $this->vs[$obj]);
  }
  
  /**
   * Replaces breakup symbols in a string on their codes.
   *
   * @param string $str
   * @return string
   * @access private
   */
  private function replaceBreakups($str, $escapeQuote = true)
  {
    return strtr($str, $escapeQuote ? ["\r" => '&#0013;', "\n" => '&#0010;', "'" => "\'"] : ["\r" => '&#0013;', "\n" => '&#0010;']);
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
    return strtr($str, ["\\" => "\\\\", "'" => "\'", "\r" => "\\r", "\n" => "\\n"]);
  }
}

}