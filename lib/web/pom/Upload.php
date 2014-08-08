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

namespace Aleph\Web\POM;

use Aleph\MVC,
    Aleph\Utils\PHP;

class Upload extends Control
{
  protected $ctrl = 'upload';
  
  protected $dataAttributes = ['settings' => 1, 'callback' => 1];
  
  public function __construct($id)
  {
    parent::__construct($id);
    $this->attributes['settings'] = [];
  }
  
  public function init()
  {
    $url = \Aleph::url('framework');
    $this->view->addJS(['src' => $url . '/web/js/jquery/upload/vendor/jquery.ui.widget.js']);
    $this->view->addJS(['src' => $url . '/web/js/jquery/upload/jquery.iframe-transport.js']);
    $this->view->addJS(['src' => $url . '/web/js/jquery/upload/jquery.fileupload.js']);
    return $this;
  }

  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    return '<input type="file"' . $this->renderAttributes() . ' />';
  }
}