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

class Picture
{
  const PIC_MANUAL = 0;
  const PIC_AUTOWIDTH = 1;
  const PIC_AUTOHEIGHT = 2;
  const PIC_AUTO = 3;

  protected $img = null;
  
  protected $info = [];

  public function __construct($image)
  {
    $this->readImageInfo($image);
  }
  
  public function getWidth()
  {
    return $this->info['width'];
  }
  
  public function getHeight()
  {
    return $this->info['height'];
  }
  
  public function getExtension($includeDot = false)
  {
    return str_replace('jpeg', 'jpg', image_type_to_extension($this->info['type'], $includeDot));
  }
  
  public function getSize()
  {
    return $this->info['size'];
  }
  
  public function getMimeType()
  {
    return $this->info['mime'];
  }
  
  public function getResource()
  {
    if (!is_resource($this->img)) 
    {
      $this->img = $this->info['create']($this->info['image']);
      if ($this->info['type'] == IMAGETYPE_PNG || $this->info['type'] == IMAGETYPE_GIF)
      {
        imagealphablending($this->img, false);
        imagesavealpha($this->img, true);
      }
    }
    return $this->img;
  }
  
  /**
   * Rotates the image with a given angle.
   * The method returns an image resource for the rotated image, or FALSE on failure.
   *
   * @param float $angle - rotation angle, in degrees. The rotation angle is interpreted as the number of degrees to rotate the image anticlockwise.
   * @param integer $bgcolor - specifies the color of the uncovered zone after the rotation.
   * @param integer $interpolationMethod - the interpolation method. See more details here: http://www.php.net/manual/en/function.imagesetinterpolation.php
   * @return resource|boolean
   * @access public
   */
  public function rotate($angle, $bgcolor = 0, $interpolationMethod = IMG_BILINEAR_FIXED)
  {
    $img = $this->getResource();
    imagesetinterpolation($img, $interpolationMethod);
    if (false !== $res = imagerotate($img, $angle, $bgcolor)) $this->img = $res;
    return $res;
  }
  
  /**
   * Crop the image using the given coordinates and size.
   * Return cropped image resource on success or FALSE on failure.
   *
   * @param integer $left - the X coordinate of the image fragment.
   * @param integer $top - the Y coordinate of the image fragment.
   * @param integer $width - the width of the image fragment.
   * @param integer $height - the height of the image fragment.
   * @param integer $bgcolor - the background color of the uncovered zone of the cropped image.
   * @return resource|boolean
   * @access public
   */
  public function crop($left, $top, $width, $height, $bgcolor = 0)
  {
    $img = $this->getResource();
    $new = imagecreatetruecolor($width, $height);
    if ($this->info['type'] == IMAGETYPE_PNG || $this->info['type'] == IMAGETYPE_GIF)
    {
      imagealphablending($new, false);
      imagesavealpha($new, true);
      imagefilledrectangle($new, 0, 0, $width, $height, $bgcolor);
    }
    $w = imagesx($img);
    $h = imagesy($img);
    $width = abs($width);
    $height = abs($height);
    $right = $left + $width - 1;
    $bottom = $top + $height - 1;
    if ($right < 0 || $bottom < 0 || $left >= $w || $top >= $h) return $this->img = $new;
    $x = $left; $y = $top;
    if ($x < 0) $x = 0;
    if ($y < 0) $y = 0;
    if ($right >= $w) $right = $w - 1;
    if ($bottom >= $h) $bottom = $h - 1;
    if (false !== $res = imagecopy($new, $img, $left < 0 ? -$left : 0, $top < 0 ? -$top : 0, $x, $y, $right - $x + 1, $bottom - $y + 1)) return $this->img = $new;
    return false;
  }
  
  /**
   * Scale an image using the given new width and height.
   *
   * @param integer $width - new width of the image.
   * @param integer $height - new height of the image.
   * @param integer $mode - 
   */
  public function scale($width, $height, $mode = self::PIC_AUTO, $maxWidth = null, $maxHeight = null)
  {
    $img = $this->getResource();
    $w = imagesx($img);
    $h = imagesy($img);
    list($width, $height) = $this->getRightSize($mode, $width, $height, $w, $h, $maxWidth, $maxHeight);
    $new = imagecreatetruecolor($width, $height);
    imagealphablending($new, false);
    imagesavealpha($new, true);
    if (false !== $res = imagecopyresampled($new, $img, 0, 0, 0, 0, $width, $height, $w, $h)) return $this->img = $new;
    return false;
  }

  public function save($filename = null, $type = null, array $options = null)
  {
    $filename = $filename ?: $this->info['image'];
    if (!$this->img)
    {
      if ($filename == $this->info['image']) return true;
      return copy($this->info['image'], $filename);
    }
    $type = $type ? strtolower($type) : $this->getExtension();
    switch ($type)
    {
      default:
      case 'png':
        return imagepng($this->img, $filename, isset($options['quality']) ? $options['quality'] : null, isset($options['filters']) ? $options['filters'] : null); 
      case 'jpeg':
      case 'jpg':
        return imagejpeg($this->img, $filename, isset($options['quality']) ? $options['quality'] : null);
      case 'gif':
        return imagegif($this->img, $filename);
      case 'wbmp':
        return imagewbmp($this->img, $filename, isset($options['foreground']) ? $options['foreground'] : null);
      case 'xbm':
        return imagexbm($this->img, $filename, isset($options['foreground']) ? $options['foreground'] : null);
      case 'webp':
        return imagewebp($this->img, $filename);
    }
  }
  
  protected function readImageInfo($image)
  {
    $info = getimagesize($image);
    $this->info['image'] = $image;
    $this->info['width'] = $info[0];
    $this->info['height'] = $info[1];
    $this->info['type'] = $info[2];
    $this->info['mime'] = $info['mime'];
    $this->info['size'] = filesize($image);    
    switch ($info['mime'])
    {
       case 'image/png':
         $this->info['create'] = 'imagecreatefrompng';
         break;
       case 'image/gif':
         $this->info['create'] = 'imagecreatefromgif';
         break;
       case 'image/webp':
         $this->info['create'] = 'imagecreatefromwebp';
         break;
       case 'image/wbmp':
       case 'image/vnd.wap.wbmp':
         $this->info['create'] = 'imagecreatefromwbmp';
         break;
       case 'image/xbm':
       case 'image/x-xbitmap':
         $this->info['create'] = 'imagecreatefromxbm';
         break;
       case 'image/xpm':
       case 'image/x-xpixmap':
         $this->info['create'] = 'imagecreatefromxpm';
         break;
       default:
         $this->info['create'] = 'imagecreatefromjpeg';
         break;
    }
  }

  protected function getRightSize($mode, $dstWidth, $dstHeight, $srcWidth, $srcHeight, $maxWidth, $maxHeight)
  {
    switch ($mode)
    {
      case self::PIC_MANUAL;
        $w = $dstWidth;
        $h = $dstHeight;
        if ($maxWidth > 0 && $w > $maxWidth) $w = $maxWidth;
        if ($maxHeight > 0 && $h > $maxHeight) $h = $maxHeight;
        break;
      case self::PIC_AUTOWIDTH:
        $h = $dstHeight;
        if ($maxHeight > 0 && $h > $maxHeight) $h = $maxHeight;
        $w = $h / $srcHeight * $srcWidth;
        if ($maxWidth > 0 && $w > $maxWidth) $w = $maxWidth;
        break;
      case self::PIC_AUTOHEIGHT:
        $w = $dstWidth;
        if ($maxWidth > 0 && $w > $maxWidth) $w = $maxWidth;
        $h = $w / $srcWidth * $srcHeight;
        if ($maxHeight > 0 && $h > $maxHeight) $h = $maxHeight;
        break;
      case self::PIC_AUTO:
      default:
        $w = $srcWidth;
        $h = $srcHeight;
        if ($maxWidth > 0 && $w > $maxWidth)
        {
          $w = $maxWidth;
          $h = $w / $srcWidth * $srcHeight;
        }
        if ($maxHeight > 0 && $h > $maxHeight)
        {
          $h = $maxHeight;
          $w = $h / $srcHeight * $srcWidth;
        }
        break;
    }
    return [(int)$w, (int)$h];
  }
}