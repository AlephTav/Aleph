<?php

namespace Aleph\Web\UI;

use Aleph\Core,
    Aleph\Utils;

class Parser
{
  const ERR_PARSER_1 = 'Template should begin with "element" tag.';
  const ERR_PARSER_2 = 'Element should contain non-empty attribute "name".';

  protected $data = array();
  protected $tags = array('a', 'address', 'area', 'article', 'aside', 'audio', 'b', 'base', 'bb', 'bdi', 'bdo', 'blockquote', 'body', 'br', 'button', 'canvas', 'caption', 'cite', 'code', 'col', 'colgroup', 'command', 'data', 'datagrid', 'datalist', 'dd', 'del', 'details', 'dfn', 'div', 'dl', 'dt', 'em', 'embed', 'eventsource', 'fieldset', 'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hgroup', 'hr', 'html', 'i', 'iframe', 'img', 'input', 'ins', 'kbd', 'keygen', 'label', 'legend', 'li', 'link', 'mark', 'map', 'menu', 'meta', 'meter', 'nav', 'noscript', 'object', 'ol', 'optgroup', 'option', 'output', 'p', 'param', 'pre', 'progress', 'q', 'ruby', 'rp', 'rt', 's', 'samp', 'script', 'section', 'select', 'small', 'source', 'span', 'strong', 'style', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'time', 'title', 'tr', 'track', 'u', 'ul', 'var', 'video', 'wbr');
  protected $void = array('area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr');

  public function __construct($file = null)
  {
    if ($file) $this->parse($file);
  }
  
  public function parse($file)
  {
    return $this->parseTemplate(file_get_contents($file));
  }
  
  public function parseTemplate($template)
  {
    $dom = new Utils\DOMDocumentEx('1.0', 'UTF-8');
    $dom->loadHTML($template);
    $element = $dom->documentElement->firstChild->firstChild;
    if ($element->tagName != 'element') throw new Core\Exception($this, 'ERR_PARSER_1');
    if ($element->getAttribute('name') == '') throw new Core\Exception($this, 'ERR_PARSER_2');
    $data = array();
    $data['element'] = $element->getAttribute('name');
    $data['extends'] = $element->getAttribute('extends') ?: 'span';
    if (in_array($data['extends'], $this->tags))
    {
      $data['inherits'] = false;
      
    }
    $this->data = $data;
    return $data;
  }
  
  public function getElementName()
  {
    return isset($data['element']) ? $data['element'] : false;
  }
  
  public function getData()
  {
    print_r($this->data);
    return $this->data;
  }
}