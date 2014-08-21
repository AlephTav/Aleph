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
 * Represents the <img> HTML element.
 *
 * The control has the following properties:
 * id - the logic identifier of the control.
 * visible - determines whether or not the control is visible on the client side.
 * autoRefresh - determines whether the image is automatically refreshed by browser after changing its source (attribute "src").
 * fitSize - determines whether the real image dimensions should be fitted for the given width and height of the image control.
 * maxWidth - the desired width of the image if fitSize is TRUE.
 * maxHeight - the desired height of the image if fitSize is TRUE. 
 *
 * @version 1.0.0
 * @package aleph.web.pom
 */
class Image extends Control
{
  /**
   * The control type.
   *
   * @var string $ctrl
   * @access protected
   */
  protected $ctrl = 'image';

  /**
   * Constructor. Initializes the control properties and attributes.
   *
   * @param string $id - the logic identifier of the control.
   * @access public
   */
  public function __construct($id)
  {
    parent::__construct($id);
    $this->properties['autorefresh'] = false;
    $this->properties['fitsize'] = false;
    $this->properties['maxwidth'] = 0;
    $this->properties['maxheight'] = 0;
  }

  /**
   * Sets or returns attribute value.
   * If $value is not defined, the method returns the current attribute value. Otherwise, it will set new attribute value.
   *
   * @param string $attribute - the attribute name.
   * @param mixed $value - the attribute value.
   * @param boolean $removeEmpty - determines whether the empty attribute (having value NULL) should be removed.
   * @return mixed
   * @access public
   */
  public function &attr($attribute, $value = null, $removeEmpty = false)
  {
    if ($value !== null)
    {
      switch (strtolower($attribute))
      {
        case 'src':
          if ($this->properties['autorefresh'] || $this->properties['fitsize']) $this->refresh();
          break;
        case 'width':
        case 'height':
          if ($this->properties['fitsize']) $this->refresh();
          break;
      }
    }
    return parent::attr($attribute, $value, $removeEmpty);
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
    $src = $this->attr('src');
    if (!empty($src))
    {
      if ($this->properties['fitsize'])
      {
        $image = \Aleph::dir($src);
        if (is_file($image))
        {
          $size = getimagesize($image);
          $this->setSize($this->properties['maxwidth'], $this->properties['maxheight'], $size[0], $size[1]);
        }
      }
      if ($this->properties['autorefresh']) $this->attributes['src'] .= (strpos($src, '?') === false ? '?' : '&') . 'p' . rand(0, 1000000);
    }
    $html = '<img' . $this->renderAttributes() . ' />';
    if ($src !== null) $this->attributes['src'] = $src;
    return $html;
  }

  /**
   * Changes the given width and height of the image to fit with its real dimensions and proportions.
   *
   * @param integer $width - the desired image width.
   * @param integer $height - the desired image height.
   * @param integer $w - the real width of the image.
   * @param integer $h - the real height of the image.
   * @access protected
   */
  protected function setSize($width, $height, $w, $h)
  {
    $width = (int)$width;
    $height = (int)$height;
    if ($width == 0 && $height > 0)
    {
      $nh = $height;
      $nw = $height / $h * $w;
    }
    else if ($width > 0 && $height == 0)
    {
      $nw = $width;
      $nh = $width / $w * $h;
    }
    else if ($width > 0 && $height > 0)
    {
      $nh = $height;
      $nw = $height / $h * $w;
      if ($nw > $width)
      {
        $nh = $width / $nw * $nh;
        $nw = $width;
      }
    }
    else
    {
      $nw = $w;
      $nh = $h;
    }
    $this->attributes['width'] = ceil($nw);
    $this->attributes['height'] = ceil($nh);
  }
}