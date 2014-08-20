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

class Image extends Control
{
  protected $ctrl = 'image';

  public function __construct($id)
  {
    parent::__construct($id);
    $this->properties['autorefresh'] = false;
    $this->properties['fitsize'] = false;
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
          if ($this->properties['autorefresh']) $value .= (strpos($value, '?') === false ? '?' : '&') . 'p' . rand(0, 1000000);
        case 'width':
        case 'height':
          if ($this->properties['fitsize']) $this->refresh();
          break;
      }
    }
    return parent::attr($attribute, $value, $removeEmpty);
  }

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
          $this->setSize($this->attr('width'), $this->attr('height'), $size[0], $size[1]);
        }
      }
    }
    $html = '<img' . $this->renderAttributes() . ' />';
    if ($src !== null) $this->attributes['src'] = $src;
    return $html;
  }

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