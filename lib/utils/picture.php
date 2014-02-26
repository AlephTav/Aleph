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

/**
 * Easy to use class that enables to crop, scale and rotate any image.
 * You can also use this class to get general information about an image.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.utils
 */
class Picture
{
  /**
   * resizing modes.
   */
  const PIC_MANUAL = 0;
  const PIC_AUTOWIDTH = 1;
  const PIC_AUTOHEIGHT = 2;
  const PIC_AUTO = 3;

  /**
   * The image resource identifier.
   *
   * @var resource $img
   * @access protected
   */
  protected $img = null;
  
  /**
   * The image information.
   *
   * @var array $info
   * @access protected
   */
  protected $info = [];
  
  /**
   * Returns a color identifier representing the color composed of the given RGB components and the transparency parameter alpha.
   * The colors parameters are integers between 0 and 255 or hexadecimals between 0x00 and 0xFF.
   *
   * @param integer $red - value of red component.
   * @param integer $green - value of green component.
   * @param integer $blue - value of blue component.
   * @param float $alpha - a value between 0 and 1. 0 indicates completely opaque while 1 indicates completely transparent.
   * @return integer
   * @access public
   * @static
   */
  public static function rgb2int($red, $green, $blue, $alpha = 0)
  {
    $c = abs((float)$alpha);
    if ($c > 1) $c = 1;
    $c = floor(127 * $alpha);
    $c <<= 8;
    $c += abs((int)$red) % 256;
    $c <<= 8;
    $c += abs((int)$green) % 256;
    $c <<= 8;
    $c += abs((int)$blue) % 256;
    return $c;
  }

  /**
   * Constructor. Reads information about the specified image.
   *
   * @param string $image - full path to the image file.
   * @access public
   */
  public function __construct($image)
  {
    $this->readImageInfo($image);
  }
  
  /**
   * Returns TRUE if the given image uses the RGB color model and FALSE otherwise.
   *
   * @return boolean
   * @access public   
   */
  public function isRGB()
  {
    return $this->info['channels'] == 3;
  }
  
  /**
   * Returns TRUE if the given image uses the CMYK color model and FALSE otherwise.
   *
   * @return boolean
   * @access public   
   */
  public function isCMYK()
  {
    return $this->info['channels'] == 4;
  }
  
  /**
   * Returns the image width.
   *
   * @return integer
   * @access public
   */
  public function getWidth()
  {
    return $this->info['width'];
  }
  
  /**
   * Return the image height.
   *
   * @return integer
   * @access public
   */
  public function getHeight()
  {
    return $this->info['height'];
  }
  
  /**
   * Returns suitable extension of the image file.
   *
   * @param boolean $includeDot - determines whether to prepend a dot to the extension or not.
   * @return string
   * @access public
   */
  public function getExtension($includeDot = false)
  {
    return str_replace('jpeg', 'jpg', image_type_to_extension($this->info['type'], $includeDot));
  }
  
  /**
   * Returns size (in bytes) of the image.
   *
   * @return string
   * @access public
   */
  public function getSize()
  {
    return $this->info['size'];
  }
  
  /**
   * Returns mime type of the image.
   *
   * @return string
   * @access public
   */
  public function getMimeType()
  {
    return $this->info['mime'];
  }
  
  /**
   * Returns number of bits for each color of the image.
   *
   * @return integer
   * @access public
   */
  public function getColorDepth()
  {
    return $this->info['bits'];
  }
  
  /**
   * Returns an image resource identifier.
   *
   * @return resource
   * @access public
   */
  public function getResource()
  {
    if (!is_resource($this->img)) 
    {
      switch ($this->info['mime'])
      {
         case 'image/png':
           $this->img = imagecreatefrompng($this->info['image']);
           break;
         case 'image/gif':
           $this->img = imagecreatefromgif($this->info['image']);
           break;
         case 'image/webp':
           $this->img = imagecreatefromwebp($this->info['image']);
           break;
         case 'image/wbmp':
         case 'image/vnd.wap.wbmp':
           $this->img = imagecreatefromwbmp($this->info['image']);
           break;
         case 'image/xbm':
         case 'image/x-xbitmap':
           $this->img = imagecreatefromxbm($this->info['image']);
           break;
         case 'image/xpm':
         case 'image/x-xpixmap':
           $this->img = imagecreatefromxpm($this->info['image']);
           break;
         default:
           $this->img = imagecreatefromjpeg($this->info['image']);
           break;
      }
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
   * Returns resource of the cropped image on success or FALSE on failure.
   *
   * @param integer $left - the X coordinate of the image fragment.
   * @param integer $top - the Y coordinate of the image fragment.
   * @param integer $width - the width of the image fragment.
   * @param integer $height - the height of the image fragment.
   * @param integer $bgcolor - the background color of the uncovered zone of the cropped image.
   * @param boolean $fixedSize - determines whether the crop has fixed size even if it is out of image limits.
   * @return resource|boolean
   * @access public
   */
  public function crop($left, $top, $width, $height, $bgcolor = 0, $fixedSize = true)
  {
    $img = $this->getResource();
    $w = imagesx($img);
    $h = imagesy($img);
    $width = abs($width);
    $height = abs($height);
    $right = $left + $width - 1;
    $bottom = $top + $height - 1;
    if ($right < 0 || $bottom < 0 || $left >= $w || $top >= $h) return $this->img = $this->createNewImage($width, $height, $bgcolor);
    $x = $left; $y = $top;
    if ($x < 0) $x = 0;
    if ($y < 0) $y = 0;
    if ($right >= $w) $right = $w - 1;
    if ($bottom >= $h) $bottom = $h - 1;
    if (!$fixedSize)
    {
      $left = $top = 0;
      $width = $right - $x + 1;
      $height = $bottom - $y + 1;
    }
    $new = $this->createNewImage($width, $height, $bgcolor);
    if (false !== $res = imagecopy($new, $img, $left < 0 ? -$left : 0, $top < 0 ? -$top : 0, $x, $y, $right - $x + 1, $bottom - $y + 1)) return $this->img = $new;
    return false;
  }
  
  /**
   * Resizes an image using the given new width and height.
   * Returns resource of the resized image on success or FALSE on failure.
   *
   * @param integer $width - new width of the image. This parameter is ignored if the resizing mode is PIC_AUTO or PIC_AUTOWIDTH.
   * @param integer $height - new height of the image. This parameter is ignored if the resizing mode is PIC_AUTO or PIC_AUTOWIDTH.
   * @param integer $mode - the resizing mode.
   * @param integer $maxWidth - the upper limit of the width of the resizing image.
   * @param integer $maxHeight - the upper limit of the height of the resizing image.
   * @return resource|boolean
   * @access public
   */
  public function resize($width, $height, $mode = self::PIC_AUTO, $maxWidth = null, $maxHeight = null)
  {
    $img = $this->getResource();
    $w = imagesx($img);
    $h = imagesy($img);
    list($width, $height) = $this->getRightSize($mode, $width, $height, $w, $h, $maxWidth, $maxHeight);
    $new = $this->createNewImage($width, $height);
    if (false !== $res = imagecopyresampled($new, $img, 0, 0, 0, 0, $width, $height, $w, $h)) return $this->img = $new;
    return false;
  }

  /**
   * Saves the changed image to a new file.
   * The method returns TRUE on success or FALSE on failure.
   *
   * @param string $filename - new image file.
   * @param string $type - type of the saving image. Valid values are "png", "jpg", "jpeg", "gif", "wbmp", "xbm", "webp". The default value is "png".
   * @param array $options - array of additional options for different image types.
   * @return boolean
   * @access public
   */
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
        return imagejpeg($this->img, $filename, isset($options['quality']) ? $options['quality'] : 100);
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
  
  /**
   * Reads information about an image.
   *
   * @param string $image - specifies the image file you wish to retrieve information about.
   * @access protected
   */
  protected function readImageInfo($image)
  {
    $info = getimagesize($image);
    $this->info['image'] = $image;
    $this->info['width'] = $info[0];
    $this->info['height'] = $info[1];
    $this->info['type'] = $info[2];
    $this->info['mime'] = $info['mime'];
    $this->info['bits'] = $info['bits'];
    $this->info['size'] = filesize($image);
    $this->info['channels'] = isset($info['channels']) ? $info['channels'] : 0;
  }

  /**
   * Returns new width and height for resizing image according to the specified scale mode.
   * New dimension of the image is returned as a two-element numeric array in which the first element is the width and the second one is the height.
   *
   * @param integer $mode - the resizing mode.
   * @param integer $dstWidth - the desired width of the resizing image. This parameter is ignored if the resizing mode is PIC_AUTO or PIC_AUTOWIDTH.
   * @param integer $dstHeight - the desired height of the resizing image. This parameter is ignored if the resizing mode is PIC_AUTO or PIC_AUTOHEIGHT.
   * @param integer $srcWidth - real width of the image.
   * @param integer $srcHeight - real height of the image.
   * @param integer $maxWidth - the upper limit of the width of the resizing image.
   * @param integer $maxHeight - the upper limit of the height of the resizing image.
   * @return array
   * @access protected
   */
  protected function getRightSize($mode, $dstWidth, $dstHeight, $srcWidth, $srcHeight, $maxWidth = 0, $maxHeight = 0)
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
  
  /**
   * Creates the new true color image of the given size and color.
   *
   * @param integer $width - the width of the new image.
   * @param integer $height - the height of the new image.
   * @param integer $bgcolor - the image background color.
   * @return resource
   * @access protected
   */
  protected function createNewImage($width, $height, $bgcolor = null)
  {
    $new = imagecreatetruecolor($width, $height);
    if ($this->info['type'] == IMAGETYPE_PNG || $this->info['type'] == IMAGETYPE_GIF)
    {
      imagealphablending($new, false);
      imagesavealpha($new, true);
    }
    if ($bgcolor !== null) imagefilledrectangle($new, 0, 0, $width, $height, $bgcolor);
    return $new;
  }
}