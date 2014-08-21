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

/**
 * This control can be used for representation any data as a table with pagination.
 *
 * The control has the following properties:
 * id - the logic identifier of the control.
 * visible - determines whether or not the control is visible on the client side.
 * tag - determines HTML tag of the container element.
 * expire - determines the cache lifetime (in seconds) of the render process. The default value is 0 (no cache).
 * source - the delegate for obtaining grid data or an array of data.
 * sort - determines the number of table column to sort. The sort order is determined by sign of the number: "+" - ascending and "-" - descending.
 * size - determines the number of data rows per page.
 * page - determines the page number.
 *
 * @version 1.0.0
 * @package aleph.web.pom
 */
class Grid extends Panel
{
  /**
   * The control type.
   *
   * @var string $ctrl
   * @access protected
   */
  protected $ctrl = 'grid';
  
  /**
   * The total number of rows.
   *
   * @var integer $count
   * @access protected
   */
  protected $count = null;
  
  /**
   * The rows of the obtained data set.
   *
   * @var array $rows
   * @access protected
   */
  protected $rows = null;
  
  /**
   * Constructor. Initializes the control properties and attributes.
   *
   * @param string $id - the logic identifier of the control.
   * @param string $template - the grid template or the path to the template file.
   * @access public
   */
  public function __construct($id, $template = null)
  {
    parent::__construct($id, $template);
    $this->properties['source'] = null;
    $this->properties['sort'] = -1;
    $this->properties['size'] = 10;
    $this->properties['page'] = 0;
  }
  
  /**
   * Sets sorting for the retrieving data.
   *
   * @param integer $sort - the number of table column to sort. The sort order is determined by sign of the number: "+" - ascending and "-" - descending.
   * @return self
   * @access public
   */
  public function setSort($sort)
  {
    $this->properties['sort'] = (int)$sort;
    return $this;
  }
  
  /**
   * Sets the number of data rows per page.
   *
   * @param integer $size - the rows number.
   * @return self
   * @access public
   */
  public function setSize($size)
  {
    $this->properties['size'] = (int)$size;
    return $this;
  }
  
  /**
   * Sets the page number.
   *
   * @param integer $page - the page number.
   * @return self
   * @access public
   */
  public function setPage($page)
  {
    $this->properties['page'] = (int)$page;
    return $this;
  }
  
  /**
   * Initializes the control.
   *
   * @return self
   * @access public
   */
  public function init()
  {
    foreach (['size', 'size1', 'size2'] as $id)
    {
      $ctrl = $this->get($id, false);
      if (!$ctrl) continue;
      if (!is_array($ctrl->options) || count($ctrl->options) == 0)
      {
        $ctrl->options = [10 => 10, 20 => 20, 30 => 30, 40 => 40, 50 => 50, 999999999 => 'All'];
      }
      unset($ctrl->multiple);
      $ctrl->value = $this->properties['size'];
      $ctrl->addEvent('changeSize', 'change', get_class($this) . '@' . $this->attributes['id'] . '->setSize', ['params' => ['js::this.value']]);
    }
    return $this;
  }
  
  /**
   * Returns string that represents the javascript for invoking sort method via Ajax request.
   *
   * @param integer $sort - the number of table column to sort.
   * @return string
   * @access public
   */
  public function getSortMethod($sort)
  {
    if (abs($this->properties['sort']) == $sort) $sort = $this->properties['sort'];
    return $this->method('setSort', [-$sort]);
  }
  
  /**
   * Returns the total number of rows.
   *
   * @param boolean $cache - determines whether the returning result will be stored in the protected property $count.
   * @return integer
   * @access public
   */
  public function getCount($cache = true)
  {
    if ($cache && $this->count !== null) return $this->count;
    if (is_array($this->properties['source'])) return $this->count = count($this->properties['source']);
    return $this->count = \Aleph::delegate($this->properties['source'], 'count', $this->properties);
  }
  
  /**
   * Returns the data rows.
   *
   * @param boolean $cache - determines whether the returning result will be stored in the protected property $rows.
   * @return array
   * @access public
   */
  public function getRows($cache = true)
  {
    if ($cache && $this->rows !== null) return $this->rows;
    if (is_array($this->properties['source']))
    {
      $tmp = $this->properties['source'];
      usort($tmp, [$this, 'cmp']);
      return $this->rows = array_slice($tmp, $this->properties['page'] * $this->properties['size'], $this->properties['size']);
    }
    return $this->rows = \Aleph::delegate($this->properties['source'], 'rows', $this->properties);
  }
  
  /**
   * If the property "source" is an array of data this method is used for sorting.
   *
   * @param array $a - the first argument to compare.
   * @param array $b - the second argument to compare.
   * @return integer - an integer less than, equal to, or greater than zero if the first argument is considered to be respectively less than, equal to, or greater than the second.
   * @access public
   */
  public function cmp($a, $b)
  {
    return 0;
  }
  
  /**
   * Returns the inner HTML of the grid.
   *
   * @return string
   * @access public
   */
  public function renderInnerHTML()
  {
    if ($this->tpl->isExpired())
    {
      $this->tpl->count = $this->getCount();
      $this->normalize();
      $this->tpl->rows = $this->getRows();
      $this->tpl->page = $this->properties['page'];
      $this->tpl->sort = $this->properties['sort'];
      $from = $to = 0;
      if ($this->count)
      {
        $from = $this->properties['size'] * $this->properties['page'] + 1;
        $to = $from + $this->properties['size'] - 1;
        if ($to > $this->count) $to = $this->count;
      }
      $this->tpl->from = $from;
      $this->tpl->to = $to;
      foreach (['pagination', 'pagination1', 'pagination2'] as $id)
      {
        $ctrl = $this->get($id, false);
        if (!$ctrl) continue;
        $ctrl->callback = $this->method('setPage', ['#page#']);
        $ctrl->total = $this->count;
        $ctrl->size = $this->properties['size'];
        $ctrl->page = $this->properties['page'];
      }
    }
    return parent::renderInnerHTML();
  }
  
  /**
   * Normalizes values of the properties page and size.
   *
   * @return self
   * @access public
   */
  public function normalize()
  {
    $this->properties['page'] = (int)$this->properties['page'];
    $this->properties['size'] = (int)$this->properties['size'];
    if ($this->properties['page'] < 0) $this->properties['page'] = 0;
    if ($this->properties['size'] < 1) $this->properties['size'] = 1;
    $last = ceil($this->count / $this->properties['size']) - 1;
    if ($last < 0) $last = 0;
    if ($this->properties['page'] > $last) $this->properties['page'] = $last;
  }
}