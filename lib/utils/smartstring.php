<?php

namespace ClickBlocks\Utils;

class SmartString
{
  const CONTENT_XML = 'XML';

  protected $string = '';
  
  protected $parts = array();
  
  protected $patterns = array();

  public function __construct($string, $patterns)
  {
    if ($patterns == self::CONTENT_XML) $patterns = array(array('/<[a-zA-Z]{1}[^>]*>/ms', '/<\/[a-zA-Z]{1}[^>]*>|<[a-zA-Z]{1}[^>]*\/>/ms'));
    $this->patterns = $patterns;
    $this->clear();
    $this->string = $this->parse($string);
  }
  
  public function clear()
  {
    $this->string = '';
    $this->parts = array();
    return $this;
  }
  
  public function source()
  {
    return $this->string;
  }
  
  public function length()
  {
    return strlen($this->string);
  }
  
  public function add($string)
  {
    $this->string .= $this->parse($string);
    return $this;
  }
  
  public function substr($start, $length = null)
  {
    $this->string = substr($this->string, $start, $length);
    foreach (array('start', 'length') as $var)
    {
      $l = strlen($this->string);
      if ($$var < 0) 
      {
        $$var = $l + $$var;
        if ($$var < 0) $$var = 0;
      }
      if ($$var > $l) $$var = $l;
    }
    $this->reduceParts($start, $length);
    return $this;
  }
  
  public function first($length = null)
  {
    return $this->substr(0, $length);
  }
  
  public function last($length = null)
  {
    return $this->substr(0, -(int)$length);
  }
  
  public function __toString()
  {
    return $this->build();
  }
  
  public function build()
  {
    $tmp = $this->string;
    end($this->parts);
    while (($p = key($this->parts)) !== null)
    {
      $m = ''; $part = current($this->parts);
      foreach ($part as $prt) $m .= $prt[0];
      $tmp = substr($tmp, 0, $p) . $m . substr($tmp, $p);
      prev($this->parts);
    }
    return $tmp;
  }
  
  protected function parse($string)
  {
    $offset = strlen($this->string);
    $parts = array();
    foreach ($this->patterns as $pattern)
    {
      if (is_array($pattern)) 
      {
        $this->computePositions($string, $pattern[0], 0, $parts);
        $this->computePositions($string, $pattern[1], 1, $parts);
      }
      else
      {
        $this->computePositions($string, $pattern, -1, $parts);
      }
    }
    usort($parts, function($a, $b)
    {
      if ($a[1] == $b[1]) return $a[2] < $b[2] ? -1 : 1;
      return $a[1] < $b[1] ? -1 : 1;
    });
    $l = 0; $old = '';
    foreach ($parts as $k => $part)
    {
      if ($old && $old[0] == $part[0] && $old[1] == $part[1] && $old[2] == 0 && $part[2] == 1) $this->parts[$old[3]][$old[4]] = array($part[0], -1);
      else 
      {
        $old = $part;
        $part[1] -= $l;
        $len = strlen($part[0]);
        $string = substr($string, 0, $part[1]) . substr($string, $part[1] + $len);
        $l += strlen($part[0]);
        $part[1] += $offset;
        $this->parts[$part[1]][] = array($part[0], $part[2]);
        $old[3] = $part[1];
        $old[4] = count($this->parts[$part[1]]) - 1;
      }
    }
    return $string;
  }
  
  protected function reduceParts($start, $length)
  {
    $tmp = $stack = array();
    $end = $start + $length;
    foreach ($this->parts as $p => $part)
    {
      if ($p < $start || $p >= $end)
      {
        $p = $p < $start ? 0 : $end;
        if (!isset($tmp[$p])) $tmp[$p] = array();
        foreach ($part as $prt)
        {
          if ($prt[1] == -1) continue;
          $pr = array_pop($stack);
          if ($pr !== null && $pr[1] == 0 && $prt[1] == 1) 
          {
            array_pop($tmp[$p]);
            continue;
          }
          if ($pr !== null) $stack[] = $pr;
          $stack[] = $prt;
          $tmp[$p][] = $prt;
        }
      }
      else
      {
        $p -= $start;
        $tmp[$p] = array();
        foreach ($part as $prt) $tmp[$p][] = $prt;
        $stack = array();
      }
    }
    $this->parts = $tmp;
  }
  
  private function computePositions($string, $ptr, $k, &$parts)
  {
    preg_match_all($ptr, $string, $out, PREG_OFFSET_CAPTURE);
    foreach ($out[0] as &$part) $part[2] = $k;
    $parts = array_merge($parts, $out[0]);
  }
} 

?>