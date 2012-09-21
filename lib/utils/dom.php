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

use Aleph\Web;

/**
 * The class is designed for extended operations with the html document as DOM.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.utils
 */
class DOMDocumentEx extends \DOMDocument
{
  const DOM_INJECT_TOP = 'top';
  const DOM_INJECT_BOTTOM = 'bottom';
  const DOM_INJECT_AFTER = 'after';
  const DOM_INJECT_BEFORE = 'before';

  /**
   * Constructor.
   * 
   * @param string $version
   * @param string $charset
   * @access public
   */
  public function __construct($version = '1.0', $charset = null)
  {
    $a = \Aleph::getInstance();
    parent::__construct($version, $charset ?: ($a['charset'] ?: 'utf-8'));
  }

  /**
   * Replaces some of the php tags in $source.
   * 
   * @param string $source 
   * @return string 
   * @access public
   */
  /*public function loadHTML($source)
  {
    \Aleph::debug(true, E_ALL & ~E_NOTICE & ~E_WARNING);
    $res = parent::loadHTML($source);
    \Aleph::debug(true, E_ALL & ~E_NOTICE);
    return $res;
  }*/

   /**
    * Loads the html file from the link.
    * Загружает html файл по ссылке.
    * 
    * @param string $filename 
    * @return string 
    * @access public
    */
   /*public function loadHTMLFile($filename)
   {
      Debugger::setErrorReporting(E_ALL & ~E_NOTICE & ~E_WARNING);
      $res = $this->loadHTML(file_get_contents($filename));
      Debugger::setErrorReporting(E_ALL & ~E_NOTICE);
      return $res;
   }*/

   /**
    * Converts encoded php tags.
    * Преобразует зашифрованные php теги.
    * 
    * @return string 
    * @access public
    */
   public function saveHTML()
   {
      return Web\XHTMLParser::decodePHPTags(parent::saveHTML());
   }

   /**
    * Save the file in $filename.
    * Сохраняет файл в $filename.
    * 
    * @param string $filename
    * @return mixed 
    * @access public
    */
   public function saveHTMLFile($filename)
   {
      return file_put_contents($filename, $this->saveHTML());
   }

   /**
    * Returns the html code of the whole node.
    * Возвращает html код всего узла.
    * 
    * @return string 
    * @access public
    */
   public function getHTML()
   {
      return $this->getInnerHTML($this->documentElement->firstChild);
   }

   /**
    * Adds a node $id code $html
    * Добавляет в узел $id код $html
    * 
    * @param string $id
    * @param string $html
    * @access public
    */
   public function insert($id, $html)
   {
      $el = $this->getElementById($id);
      if ($el == null) throw new \Exception(err_msg('ERR_DOM_1', array($id)));
      $this->setInnerHTML($el, $html);
   }

   /**
    * Replace a node $id code $html
    * Заменяет узел $id на $html
    * 
    * @param string $id
    * @param string $html
    * @access public
    */
   public function replace($id, $html)
   {
      $el = $this->getElementById($id);
      if ($el == null) throw new \Exception(err_msg('ERR_DOM_1', array($id)));
      $el->parentNode->replaceChild($this->HTMLToNode($html), $el);
   }

   /**
    * Adds $html to the node $id. The optional parameter specifies where 
    * to add (at the beginning or end, before or after the element).
    * Добавляет $html в узел $id. Необязаттельный параметр указывает, 
    * куда добавлять (в начало, в конец, до или после элемента).
    * 
    * @param string $id
    * @param string $html
    * @param string $mode
    * @access public
    */
   public function inject($id, $html, $mode = self::DOM_INJECT_TOP)
   {
      $el = $this->getElementById($id);
      if ($el == null) throw new \Exception(err_msg('ERR_DOM_1', array($id)));
      $node = $this->HTMLToNode($html);
      switch ($mode)
      {
         case self::DOM_INJECT_TOP:
           ($el->firstChild) ? $el->insertBefore($node, $el->firstChild) : $el->appendChild($node);
           break;
         case self::DOM_INJECT_BOTTOM:
           $el->appendChild($node);
           break;
         case self::DOM_INJECT_BEFORE:
           if ($el->parentNode) $el->parentNode->insertBefore($node, $el);
           break;
         case self::DOM_INJECT_AFTER:
           if ($el->parentNode) ($el->nextSibling) ? $el->parentNode->insertBefore($node, $el->nextSibling) : $el->parentNode->appendChild($node);
           break;
      }
   }

   /**
    * Returns the html code of $node, converting the php tags
    * Возвращает html код узла $node, преобразуя php теги
    * 
    * @param string $node
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
      return Web\XHTMLParser::decodePHPTags($html);
   }

   /**
    * Removes all child nodes in $node and adds a new
    * Удаляет все дочерние узлы в $node и добавляет новый
    * 
    * @param  \DOMNode string $node
    * @param string $html
    * @access public
    */
   public function setInnerHTML(\DOMNode $node, $html)
   {
      $node->nodeValue = '';
      foreach ($node->childNodes as $child) $node->removeChild($child);
      $node->appendChild($this->HTMLToNode($html));
   }

   /**
    * Returns the html converted into a node. 
    * If there are php tags, they will be encoded.
    * Возвращает html конвертированный в узел. 
    * Если присутствуют пхп теги, они будут закодированы.
    * 
    * @param string $html
    * @return string 
    * @access public
    */
   public function HTMLToNode($html)
   {
      if ($html == '') return new \DOMText('');
      $dom = new DOMDocumentEx($this->version, $this->encoding);
      $dom->loadHTML($html);
      $node = $this->importNode($dom->documentElement->firstChild->firstChild, true);
      if (!preg_match('/^<[a-zA-Z].*/', $html)) $node = new \DOMText(Web\XHTMLParser::encodePHPTags($html));
      return $node;
   }
   
   /**
    * Get the element by its ID. 
    * Extends standard feature processing option if the returned value is null
    * Получить элемент по его ID. Расширяет стандартную функцию, 
    * обрабатывая вариант, если возвращено значение null.
    * 
    * @param string $id
    * @return object
    * @access public
    */
   public function getElementById($id)
   {
      $el = parent::getElementById($id);
	  if ($el == null)
	  {
         $xp = new \DomXPath($this);
         $res = $xp->query('//*[@id = \'' . addslashes($id) . '\']'); 
         $el = $res->item(0); 
	  }
	  return $el;
   }
}