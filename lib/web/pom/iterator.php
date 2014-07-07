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

use Aleph\MVC;

/**
 * Implementation of the control iterator that is used in Panel class.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.web.pom
 */
class Iterator implements \Iterator
{
  /**
   * The control array to iterate.
   *
   * @var array $controls
   * @access private
   */
  private $controls = [];
  
  /**
   * The instance of the view object.
   *
   * @var Aleph\Web\POM\View $view
   * @access private
   */
  private $view = null;

  /**
   * Constructor. Initializes the private class properties.
   *
   * @param array $controls - the controls to iterate.
   * @access public
   */
  public function __construct(array $controls)
  {
    $this->controls = $controls;
    $this->view = MVC\Page::$current->view;
  }

  /**
   * Sets the internal pointer of the controls array to its first element.
   *
   * @access public
   */
  public function rewind()
  {
    reset($this->controls);
  }

  /**
   * Returns the control object that's currently being pointed to by the internal pointer.
   * If the internal pointer points beyond the end of the controls list or the control array is empty, it returns FALSE.
   *
   * @return mixed
   * @access public
   */
  public function current()
  {
    $obj = current($this->controls);
    if ($obj === false) return false;
    return $obj instanceof Control ? $obj : $this->view->get($obj);
  }

  /**
   * Returns the index element of the current array position.
   *
   * @return mixed
   */
  public function key()
  {
    return key($this->controls);
  }

  /**
   * Returns the control in the next place that's pointed to by the internal array pointer, or FALSE if there are no more controls.
   *
   * @return mixed
   */
  public function next()
  {
    return next($this->controls);
  }

  /**
   * Check if the current internal position is valid.
   *
   * @return boolean
   */
  public function valid()
  {
    return key($this->controls) !== null;
  }
}