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
 * The wrapper around the jQuery Upload plugin (http://blueimp.github.io/jQuery-File-Upload).
 *
 * The control has the following properties:
 * id - the logic identifier of the control.
 * visible - determines whether or not the control is visible on the client side.
 *
 * The special control attributes:
 * settings - the array that represents the Upload plugin configuration options (see more info here https://github.com/blueimp/jQuery-File-Upload/wiki/Options).
 * callback - the delegate to call when a file is uploaded.
 *
 * @version 1.0.0
 * @package aleph.web.pom
 */
class Upload extends Control
{
  /**
   * The control type.
   *
   * @var string $ctrl
   * @access protected
   */
  protected $ctrl = 'upload';
  
  /**
   * Non-standard control attributes, that should be rendered as "data-" attributes on the web page.
   *
   * @var array $dataAttributes
   * @access protected
   */
  protected $dataAttributes = ['settings' => 1, 'callback' => 1];
  
  /**
   * Initializes the control.
   *
   * @return self
   * @access public
   */
  public function __construct($id)
  {
    parent::__construct($id);
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
    $url = \Aleph::url('framework');
    $this->view->addJS(['src' => $url . '/web/js/jquery/upload/vendor/jquery.ui.widget.js']);
    $this->view->addJS(['src' => $url . '/web/js/jquery/upload/jquery.iframe-transport.js']);
    $this->view->addJS(['src' => $url . '/web/js/jquery/upload/jquery.fileupload.js']);
    return $this;
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
    return '<input type="file"' . $this->renderAttributes() . ' />';
  }
}