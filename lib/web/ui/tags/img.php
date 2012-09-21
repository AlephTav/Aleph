<?php

namespace Aleph\Web\UI\Tags;

class TagIMG extends Tag
{
  public function __construct($id = null, $src = null, $alt = null)
  {
    parent::__construct($id);
    $this->attributes['src'] = $src;
    $this->attributes['alt'] = $alt;
    $this->attributes['width'] = null;
    $this->attributes['height'] = null;
    $this->attributes['ismap'] = null;
    $this->attributes['usemap'] = null;
    $this->properties['autoRefresh'] = false;
    $this->properties['maxWidth'] = 0;
    $this->properties['maxHeight'] = 0;
  }

  public function render()
  {
    $src = $this->attributes['src'];
    if ($this->properties['autoRefresh']) $this->attributes['src'] .= ((strpos($this->attributes['src'], '?') === false) ? '?' : '&') . 'p' . rand(0, 1000000);
    $width = $this->attributes['width'];
    $height = $this->attributes['height'];
    if ($src != '')
    {
      $image = \Aleph::dir($src);
      if (is_file($image))
      {
        list($w, $h, $type, $attr) = getimagesize($image);
        $this->setSize($width, $height, $w, $h, $this->properties['maxWidth'], $this->properties['maxHeight']);
      }
    }
    $html = '<img' . $this->renderAttributes() . $this->renderEvents() . ' />';
    $this->attributes['width'] = $width;
    $this->attributes['height'] = $height;
    $this->attributes['src'] = $src;
    return $html;
  }

  protected function setSize($width, $height, $w, $h, $maxWidth, $maxHeight)
  {
    $width = (int)$width;
    $height = (int)$height;
    $maxWidth = (int)$maxWidth;
    $maxHeight = (int)$maxHeight;
    if ($width == 0 && $height > 0)
    {
      $nh = $height;
      if ($maxHeight > 0 && $nh > $maxHeight)
      {
        $nh = $maxHeight;
        $height = $maxHeight;
      }
      $nw = $height / $h * $w;
      if ($maxWidth > 0 && $nw > $maxWidth) $nw = $maxWidth;
    }
    else if ($width > 0 && $height == 0)
    {
      $nw = $width;
      if ($maxWidth > 0 && $nw > $maxWidth)
      {
        $nw = $maxWidth;
        $width = $maxWidth;
      }
      $nh = $width / $w * $h;
      if ($maxHeight > 0 && $nh > $maxHeight) $nh = $maxHeight;
    }
    else if ($width > 0 && $height > 0)
    {
      $nw = $width;
      $nh = $height;
      if ($maxWidth > 0 && $nw > $maxWidth) $nw = $maxWidth;
      if ($maxHeight > 0 && $nh > $maxHeight) $nh = $maxHeight;
    }
    else
    {
      $nw = $w;
      $nh = $h;
      if ($maxWidth > 0 && $nw > $maxWidth)
      {
        $nw = $maxWidth;
        $nh = $nw / $w * $h;
      }
      if ($maxHeight > 0 && $nh > $maxHeight)
      {
        $nh = $maxHeight;
        $nw = $nh / $h * $w;
      }
    }
    $this->attributes['width'] = floor($nw);
    $this->attributes['height'] = floor($nh);
  }
}