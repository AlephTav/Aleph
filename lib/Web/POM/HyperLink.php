<?php
/**
 * Copyright (c) 2013 - 2015 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2015 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Web\POM;

/**
 * Represents the <a> HTML element.
 *
 * The control has the following properties:
 * id - the logic identifier of the control.
 * visible - determines whether or not the control is visible on the client side.
 * text - the inner HTML of the <a> element.
 *
 * @version 1.0.0
 * @package aleph.web.pom
 */
class HyperLink extends Control
{
  /**
   * The control type.
   *
   * @var string $ctrl
   * @access protected
   */
  protected $ctrl = 'hyperlink';
  
  /**
   * Constructor. Initializes the control properties and attributes.
   *
   * @param string $id - the logic identifier of the control.
   * @param string $href - the URL of the page the link goes to.
   * @param string $text - the inner HTML of the <a> element.
   * @access public
   */
  public function __construct($id, $href = null, $text = null)
  {
    parent::__construct($id);
    if (strlen($href)) $this->attributes['href'] = $href;
    $this->properties['text'] = $text;
  }
  
  /**
   * Returns HTML of the control.
   *
   * @return string
   * @access public
   */
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    return '<a' . $this->renderAttributes() . '>' . $this->properties['text'] . '</a>';
  }
}