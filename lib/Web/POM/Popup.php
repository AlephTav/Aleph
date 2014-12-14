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

use Aleph\Core,
    Aleph\MVC;

/**
 * Represents the simple popup panel.
 *
 * The control has the following properties:
 * id - the logic identifier of the control.
 * visible - determines whether or not the control is visible on the client side.
 * tag - determines HTML tag of the container element.
 * expire - determines the cache lifetime (in seconds) of the render process. The default value is 0 (no cache).
 *
 * The special control attributes:
 * overlay - if this attribute is defined, the popup will have the overlay.
 * overlayClass - the CSS class of the popup overlay.
 * overlaySelector - the popup overlay selector.
 * closeByEscape - determines whether the popup should be closed when the escape button is pressed.
 * closeByDocument - determines whether the popup should be closed when the document is clicked.
 * closeButtons - the selector for buttons that should close the popup when they are clicked.
 *
 * @version 1.0.0
 * @package aleph.web.pom
 */
class Popup extends Panel
{
  /**
   * The control type.
   *
   * @var string $ctrl
   * @access protected
   */
  protected $ctrl = 'popup';

  /**
   * Non-standard control attributes, that should be rendered as "data-" attributes on the web page.
   *
   * @var array $dataAttributes
   * @access protected
   */
  protected $dataAttributes = ['overlay' => 1, 'overlayclass' => 1, 'overlayselector' => 1, 'closebyescape' => 1, 'closebydocument' => 1, 'closebuttons' => 1];
  
  /**
   * Shows the popup on the client side.
   *
   * @param boolean $center - if it is TRUE, the popup will be displayed in the center of the screen.
   * @param integer $delay - the delay before the popup appearance.
   * @return self
   * @access public
   */
  public function show($center = true, $delay = 0)
  {
    $this->view->action('$pom.get(\'' . $this->attributes['id'] . '\').show(' . ($center ? 'true' : 'false') . ')', $delay);
    return $this;
  }
  
  /**
   * Hides the popup on the client side.
   *
   * @param integer $delay - the delay before the popup disappearance.
   * @return self
   * @access public
   */
  public function hide($delay = 0)
  {
    $this->view->action('$pom.get(\'' . $this->attributes['id'] . '\').hide()', $delay);
    return $this;
  }
}