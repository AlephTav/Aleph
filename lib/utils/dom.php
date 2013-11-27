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
 
namespace Aleph\Utils;

use Aleph\Core;

/**
 * The class is extended operations with the DOM in HTML document.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.utils
 */
class DOMDocumentEx extends \DOMDocument
{
  /**
   * Error message templates.
   */
  const ERR_DOM_1 = 'DOM Element with ID = "[{var}]" is not found.';

  /**
   * Injecting modes.
   */
  const DOM_INJECT_TOP = 'top';
  const DOM_INJECT_BOTTOM = 'bottom';
  const DOM_INJECT_AFTER = 'after';
  const DOM_INJECT_BEFORE = 'before';

  /**
   * Creates a new DOMDocumentEx object.
   * 
   * @param string $version - the version number of the document as part of the XML declaration.
   * @param string $charset - the encoding of the document as part of the XML declaration.
   * @access public
   */
  public function __construct($version = '1.0', $charset = 'utf-8')
  {
    parent::__construct($version, $charset);
  }

  /**
   * The function parses the HTML contained in the string source. Unlike loading XML, HTML does not have to be well-formed to load. 
   * This function may also be called statically to load and create a DOMDocument object.
   * The static invocation may be used when no DOMDocument properties need to be set prior to loading.
   * 
   * @param string $source - the HTML string.
   * @param integer $options - bitwise OR of the libxml option constants.
   * @return boolean - returns TRUE on success or FALSE on failure. If called statically, returns a DOMDocument or FALSE on failure.
   * @access public
   */
  public function loadHTML($source, $options = 0)
  {
    if (!\Aleph::isErrorHandlingEnabled()) return parent::loadHTML($source);
    $level = ini_get('error_reporting');
    \Aleph::errorHandling(true, E_ALL & ~E_NOTICE & ~E_WARNING);
    $res = parent::loadHTML($source, $options);
    \Aleph::errorHandling(true, $level);
    return $res;
  }

  /**
   * The method parses the HTML document in the file named filename.
   * Unlike loading XML, HTML does not have to be well-formed to load.
   * 
   * @param string $filename - the path to the HTML file.
   * @param integer $options - bitwise OR of the libxml option constants.
   * @return string 
   * @access public
   */
  public function loadHTMLFile($filename, $options = 0)
  {
    return $this->loadHTML(file_get_contents($filename), $options);
  }

  /**
   * Returns the HTML code of the root node.
   * 
   * @return string 
   * @access public
   */
  public function getHTML()
  {
    return $this->getInnerHTML($this->documentElement->firstChild);
  }

  /**
   * Changes the inner HTML of the node.
   * if the node doesn't exist the exception will be thrown.
   * 
   * @param string $id - the node ID.
   * @param string $html - the node inner HTML.
   * @access public
   */
  public function insert($id, $html)
  {
    $el = $this->getElementById($id);
    if ($el === null) throw new Core\Exception($this, 'ERR_DOM_1', $id);
    $this->setInnerHTML($el, $html);
  }

  /**
   * Replaces the node by the given HTML.
   * if the node doesn't exist the exception will be thrown.
   * 
   * @param string $id - the node ID.
   * @param string $html - HTML for replacing.
   * @access public
   */
  public function replace($id, $html)
  {
    $el = $this->getElementById($id);
    if ($el === null) throw new Core\Exception($this, 'ERR_DOM_1', $id);
    $el->parentNode->replaceChild($this->HTMLToNode($html), $el);
  }

  /**
   * Adds HTML to the node.
   * The optional parameter specifies where the HTML will be added: at the beginning or end, before or after the node.
   * if the node doesn't exist the exception will be thrown.
   * 
   * @param string $id - the node ID.
   * @param string $html - the HTML for adding.
   * @param string $mode - the injecting mode.
   * @access public
   */
  public function inject($id, $html, $mode = self::DOM_INJECT_TOP)
  {
    $el = $this->getElementById($id);
    if ($el === null) throw new Core\Exception($this, 'ERR_DOM_1', $id);
    $node = $this->HTMLToNode($html);
    switch ($mode)
    {
      case self::DOM_INJECT_TOP:
        $el->firstChild ? $el->insertBefore($node, $el->firstChild) : $el->appendChild($node);
        break;
      case self::DOM_INJECT_BOTTOM:
        $el->appendChild($node);
        break;
      case self::DOM_INJECT_BEFORE:
        if ($el->parentNode) $el->parentNode->insertBefore($node, $el);
        break;
      case self::DOM_INJECT_AFTER:
        if ($el->parentNode) $el->nextSibling ? $el->parentNode->insertBefore($node, $el->nextSibling) : $el->parentNode->appendChild($node);
        break;
    }
  }

  /**
   * Returns the inner HTML of the given node.
   * 
   * @param DOMNode $node - the given node.
   * @return string 
   * @access public
   */
  public function getInnerHTML(\DOMNode $node)
  {
    foreach ($node->childNodes as $child)
    {
      $dom = new \DOMDocument();
      $dom->appendChild($dom->importNode($child, true));
      $html .= trim($dom->saveHTML());
    }
    return $html;
  }

  /**
   * Sets the inner HTML of th given node.
   * 
   * @param \DOMNode $node - the given node.
   * @param string $html - the node inner HTML.
   * @access public
   */
  public function setInnerHTML(\DOMNode $node, $html)
  {
    $node->nodeValue = '';
    foreach ($node->childNodes as $child) $node->removeChild($child);
    $node->appendChild($this->HTMLToNode($html));
  }

  /**
   * Converts the given HTML to the node object.
   * If HTML contains several nodes only the first one will be generated.
   * 
   * @param string $html - the HTML for conversion.
   * @return string
   * @access public
   */
  public function HTMLToNode($html)
  {
    if (!preg_match('/\A<[a-zA-Z].*/', $html)) return new \DOMText($html);
    $dom = new DOMDocumentEx($this->version, $this->encoding);
    $dom->loadHTML($html);
    $node = $this->importNode($dom->documentElement->firstChild->firstChild, true);
    return $node;
  }
   
  /**
   * Searches and returns an element by its ID.
   * Returns the DOMElement or NULL if the element is not found.
   * 
   * @param string $id - the unique id value for an element.
   * @return DOMElement
   * @access public
   */
  public function getElementById($id)
  {
    $el = parent::getElementById($id);
	   if ($el === null)
	   {
      $xp = new \DomXPath($this);
      $res = $xp->query('//*[@id = \'' . addslashes($id) . '\']'); 
      $el = $res->item(0); 
	   }
	   return $el;
  }
}