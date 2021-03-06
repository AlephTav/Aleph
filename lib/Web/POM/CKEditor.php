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

use Aleph\MVC,
    Aleph\Utils\PHP;

/**
 * The wrapper around the CKEditor (http://ckeditor.com/about).
 *
 * The control has the following properties:
 * id - the logic identifier of the control.
 * visible - determines whether or not the control is visible on the client side.
 * value - the value of the control (content of the CKEditor).
 *
 * The special control attributes:
 * settings - the array that represents the CKEditor configuration options (see more info here http://docs.ckeditor.com/#!/api/CKEDITOR.config).
 *
 * @version 1.0.0
 * @package aleph.web.pom
 */
class CKEditor extends Control
{
  /**
   * The control type.
   *
   * @var string $ctrl
   * @access protected
   */
  protected $ctrl = 'ckeditor';
  
  /**
   * Non-standard control attributes, that should be rendered as "data-" attributes on the web page.
   *
   * @var array $dataAttributes
   * @access protected
   */
  protected $dataAttributes = ['settings' => 1];

  /**
   * Constructor. Initializes the control properties and attributes.
   *
   * @param string $id - the logic identifier of the control.
   * @param string $value - the CKEditor content.
   * @access public
   */
  public function __construct($id, $value = null)
  {
    parent::__construct($id);
    $this->properties['value'] = $value;
    $this->attributes['settings'] = [];
  }
  
  /**
   * Initializes the control.
   *
   * @return self
   * @access public
   */
  public function init()
  {
    $this->view->addJS(['src' => \Aleph::url('framework') . '/web/js/ckeditor/ckeditor.js']);
    return $this;
  }
  
  /**
   * Cleans the CKEditor content.
   *
   * @return self
   * @access public
   */
  public function clean()
  {
    $this->properties['value'] = '';
    return $this;
  }

  /**
   * Validates the CKEditor content.
   *
   * @param Aleph\Web\POM\Validator $validator - the instance of the validator control.
   * @return boolean
   * @access public
   */
  public function validate(Validator $validator)
  {
    return $validator->check($this->prop('value'));
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
    unset($this->attributes['style']);
    return '<textarea' . $this->renderAttributes() . ' style="visibility:hidden;display:none;">' . $this->properties['value'] . '</textarea>';
  }
}