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

namespace Aleph\Web\POM;

use Aleph\Core,
    Aleph\MVC,
    Aleph\Web;

class Body extends Panel
{
  //const ERR_BODY_1 = "XHTML parse error! [{var}].[{var}]\nLine: [{var}], column: [{var}].";
  //const ERR_BODY_2 = '[{var}] with ID = "[{var}]" doesn\'t have "materpage" attribute.';
  
  public function __construct($id, $template = null)
  {
    parent::__construct($id, $template);
    $this->properties['tag'] = 'body';
  }
  
  /*public function parse($template)
  {
    static $stack, $parentID, $subjectID;
    $php = array();
    $shortTags = array('br' => 1, 'hr' => 1, 'img' => 1, 'input' => 1);
    $insideHead = false; $tag = '';
    $config = $this->a->getConfig();
    $exe = function($code) use ($config)
    {
      return \Aleph::exe($code, array('config' => $config));
    };
    $encodePHPTags = function($xhtml) use(&$php)
    {
      $tokens = token_get_all($xhtml);
      $xhtml = '';
      foreach ($tokens as $token)
      {
        if ($token[0] == T_INLINE_HTML) $xhtml .= $token[1];
        else if ($token[0] == T_OPEN_TAG || $token[0] == T_OPEN_TAG_WITH_ECHO) $tk = array($token[1]);
        else if ($token[0] == T_CLOSE_TAG) 
        {
          $tk[] = $token[1];
          $tk = implode('', $tk);
          $k = md5($tk);
          $php[$k] = $tk;
          $xhtml .= $k;
        }
        else $tk[] = is_array($token) ? $token[1] : $token;
      }
      return str_replace('&', '6cff047854f19ac2aa52aac51bf3af4a', $xhtml);
    };
    $decodePHPTags = function($obj) use(&$php, &$decodePHPTags, &$exe)
    {
      if ($obj instanceof IControl)
      {
        $attributes = $obj->getAttributes();
        foreach ($attributes as &$attribute) $attribute = $exe($decodePHPTags($attribute));
        $obj->setAttributes($attributes);
        $properties = $obj->getProperties();
        foreach ($properties as &$property) $property = $exe($decodePHPTags($property));
        $obj->setProperties($properties);
        if ($obj instanceof IPanel) $obj->tpl->setTemplate($decodePHPTags($obj->tpl->getTemplate()));
        return $obj;
      }
      else if (is_array($obj))
      {
        foreach ($obj as &$xhtml) $xhtml = $decodePHPTags($xhtml);
        return $obj;
      }
      return strtr(str_replace('6cff047854f19ac2aa52aac51bf3af4a', '&', $obj), $php);
    };
    $startParsing = function($parser, $tg, array $attributes) use(&$decodePHPTags, &$exe, &$stack, &$insideHead, &$tag, &$parentID, &$subjectID, $shortTags)
    {
      $tag = strtoupper($tg);
      if (isset(Control::$tags[$tag]))
      {
        if ($tag == 'TEMPLATE')
        {
        }
        else
        {
          $class = '\Aleph\Web\UI\POM\\' . $tg;
          $ctrl = new $class($attributes['id']);
          if ($ctrl instanceof IPanel)
          {
            if (!empty($attributes['masterpage']))
            {
              if (count($stack)) throw new Core\Exception('ERR_BODY_2', get_class($ctrl), $ctrl->id);
              $attributes['masterpage'] = $exe($decodePHPTags($attributes['masterpage']));
              $parent = MVC\Page::$page->body->parse(\Aleph::dir($attributes['masterpage']));
              $stack->push($parent);
              $parentID = isset($attributes['parentID']) ? $exe($decodePHPTags($attributes['parentID'])) : '';
              $subjectID = $ctrl->uniqueID;
              unset($attributes['masterpage']);
              unset($attributes['parentID']);
            }
          }
          foreach ($attributes as $k => $v) $ctrl->__set($k, $v);
          $stack->push($ctrl);
        }        
      }
      else
      {
        if (count($stack) == 0) 
        {
          $parentID = $subjectID = '';
          $stack = new \SplStack();
          $stack->push(MVC\Page::$page->body);
        }
        $ctrl = $stack->top();
        if ($insideHead && $ctrl instanceof Body)
        {
          $attributes = $decodePHPTags($attributes);
          if ($tag == 'TITLE') $ctrl->setTitle('', $attributes);
          else if ($tag == 'META') $ctrl->addMeta($attributes);
          else if ($tag == 'LINK' || $tag == 'STYLE') $ctrl->addCSS($attributes);
          else if ($tag == 'SCRIPT') $ctrl->addJS($attributes);
        }
        else
        {
          if ($tag == 'HEAD') $insideHead = true;
          $xhtml = '<' . $tg;
          if ($tag == 'BODY' && $ctrl instanceof Body)
          {
            $xhtml .= '<?=$__body_attributes;?>';
            foreach ($attributes as $k => $v) $ctrl->__set($k, $v);
          }
          else if (count($attributes))
          {
            $tmp = array();
            foreach ($attributes as $k => $v) $tmp[] = $k . '="' . $v . '"';
            $xhtml .= ' ' . implode(' ', $tmp);
          }
          $xhtml .= empty($shortTags[$tg]) ? '>' : ' />';
          if ($ctrl instanceof IPanel) $ctrl->tpl->setTemplate($ctrl->tpl->getTemplate() . $xhtml);
          else if (isset($ctrl->text)) $ctrl->text .= $xhtml;
        }
      }
    };
    $endParsing = function($parser, $tg) use(&$decodePHPTags, &$stack, &$insideHead, &$tag, &$parentID, &$subjectID, $shortTags)
    {
      $tag = strtoupper($tg);
      if (isset(Control::$tags[$tag]))
      {
        if ($tag == 'TEMPLATE') return;
        $ctrl = $decodePHPTags($stack->pop());
        if ($ctrl->uniqueID == $subjectID)
        {
          $parent = $stack->top();
          if ($parentID)
          {
            $parent = $parent->get($parentID, false);
            if ($parent === false) throw new Core\Exception('ERR_BODY_3', $parentID);
            if (!($parent instanceof IPanel)) throw new Core\Exception('ERR_BODY_4', $parentID, get_class($ctrl));
          }
          $parent->tpl->setTemplate(preg_replace('/<\?=\s*\$' . $ctrl->id . '\s*;?\?>/', '<?=$' . $ctrl->uniqueID . ';?>', $parent->tpl->getTemplate()));
          $parent->add($ctrl);
          $subjectID = $parentID = '';
        }
        else
        {
          $parent = $stack->top();
          $parent->add($ctrl);
          $parent->tpl->setTemplate($parent->tpl->getTemplate() . '<?=$' . $ctrl->uniqueID . ';?>');
        }
      }
      else if (empty($shortTags[$tg]))
      {
        $xhtml = '</' . $tg . '>';
        if ($tag == 'HEAD') 
        {
          $insideHead = false;
          $xhtml = '<?=$__head_entities;?>' . $xhtml;
        }
        else if ($tag == 'BODY')
        {
          $xhtml = '<?=$__body_entities;?>' . $xhtml;
        }
        if (!$insideHead)
        {
          $ctrl = $stack->top();
          if ($ctrl instanceof IPanel) $ctrl->tpl->setTemplate($ctrl->tpl->getTemplate() . $xhtml);
          else if (isset($ctrl->text)) $ctrl->text .= $xhtml;
        }
      }
      $tag = '';
    };
    $cdataParsing = function($parser, $content) use(&$decodePHPTags, &$stack, &$insideHead, &$tag)
    {
      $ctrl = $stack->top();
      if ($insideHead && $ctrl instanceof Body)
      {
        if ($tag == 'TITLE') 
        {
          $title = $ctrl->getTitle(true);
          $ctrl->setTitle($title['title'] . $decodePHPTags($content), $title['attributes']);
        }
        else if ($tag == 'LINK' || $tag == 'STYLE')
        {
          $id = count($ctrl->getAllCSS()) - 1;
          $css = $ctrl->getCSS($id);
          $ctrl->setCSS($id, $css['attributes'], $css['style'] . $decodePHPTags($content));
        }
        else if ($tag == 'SCRIPT')
        {
          $id = count($ctrl->getAllJS()) - 1;
          $js = $ctrl->getJS($id);
          $ctrl->setJS($id, $js['attributes'], $js['script'] . $decodePHPTags($content));
        }
        else if (preg_match('/^<!\-\-\[([a-zA-Z 0-9]+)\]>(.*)\<!\[endif\]\-\->$/is', trim($content), $matches))
        {
          $dom = new \DOMDocument();
          $dom->loadXML('<head>' . $matches[2] . '</head>');
          $dom = simplexml_import_dom($dom);
          foreach ($dom as $tg => $item)
          {
            $tg = strtoupper($tg);
            if ($tg != 'SCRIPT' && $tg != 'LINK' && $tg != 'STYLE') continue;
            $attr = array();
            foreach ($item->attributes() as $k => $v) $attr[$k] = $v;
            $attr['conditions'] = $matches[1];
            if ($tg == 'SCRIPT') $ctrl->addJS($attr, (string)$item);
            else $ctrl->addCSS($attr, (string)$item);
          }
        }
      }
      else
      {
        if ($ctrl instanceof IPanel) $ctrl->tpl->setTemplate($ctrl->tpl->getTemplate() . $content);
        else if (isset($ctrl->text)) $ctrl->text .= $content;
      }
    };
    $parser = xml_parser_create($this->a['charset'] ?: 'utf-8');
    $xhtml = is_file($template) ? file_get_contents($template) : $template;
    if (strlen($xhtml) == 0) return false;
    $xhtml = $encodePHPTags($xhtml);
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_set_element_handler($parser, $startParsing, $endParsing);
    xml_set_character_data_handler($parser, $cdataParsing);
    xml_set_default_handler($parser, $cdataParsing); 
    if (!xml_parse($parser, $xhtml))
    {
      $error = xml_error_string(xml_get_error_code($parser));
      $line = xml_get_current_line_number($parser);
      $column = xml_get_current_column_number($parser);
      $file = is_file($template) ? "\nFile: " . realpath($template) : '';
      throw new Core\Exception($this, 'ERR_BODY_1', $error, $file, $line, $column);
    }
    xml_parser_free($parser);
    $ctrl = $stack->pop();
    return $decodePHPTags($ctrl);
  }
  
  public function render()
  {
    $bottom = '';
    $top = '<title' . $this->renderAttributes((array)$this->title['attributes']) . '>' . htmlspecialchars($this->title['title']) . '</title>';
    foreach ($this->meta as $meta) $top .= '<meta' . $this->renderAttributes($meta) . ' />';
    foreach ($this->css as $css) 
    {
      $conditions = '';
      if (isset($css['attributes']['conditions']))
      {
        $conditions = $css['attributes']['conditions'];
        unset($css['attributes']['conditions']);
        $top .= '<!--[' . $conditions . ']>';
      }
      if (isset($css['attributes']['href'])) $top .= '<link' . $this->renderAttributes($css['attributes']) . ' />';
      else $top .= '<style' . $this->renderAttributes($css['attributes']) . '>' . $css['style'] . '</style>';
      if ($conditions) $top .= '<![endif]-->';
    }
    foreach ($this->js as $js)
    {
      $conditions = '';
      if (isset($js['attributes']['conditions']))
      {
        $conditions = $js['attributes']['conditions'];
        unset($js['attributes']['conditions']);
        $top .= '<!--[' . $conditions . ']>';
      }
      $place = $js['place'];
      $js = '<script' . $this->renderAttributes($js['attributes']) . '>' . $js['script'] . '</script>';
      if ($place == 'top') 
      {
        $top .= $js;
        if ($conditions) $top .= '<![endif]-->';
      }
      else 
      {
        $bottom .= $js;
        if ($conditions) $bottom .= '<![endif]-->';
      }
    }
    $attributes = $this->attributes;
    unset($attributes['id']);
    foreach ($this as $uniqueID => $ctrl) $this->tpl->{$uniqueID} = $ctrl->render();
    $this->tpl->__head_entities = $top;
    $this->tpl->__body_attributes = ' data-key="' . sha1(MVC\Page::$page->getPageID()) . '"' . $this->renderAttributes($attributes) . $this->renderEvents();
    $this->tpl->__body_entities = $bottom;
    $html = $this->tpl->render();
    return $html ? '<!DOCTYPE html>' . $html : '';
  }*/
}