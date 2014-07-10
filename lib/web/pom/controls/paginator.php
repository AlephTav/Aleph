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

class Paginator extends Control
{
  const ERR_PAGINATOR_1 = 'Incorrect type value "[{var}]". Property "type" can take only one of the following values: "short" and "long".';

  const TYPE_SHORT = 'short';
  const TYPE_LONG = 'long';

  protected $ctrl = 'paginator';

  public function __construct($id, $total = null, $size = 10, $page = 0)
  {
    parent::__construct($id);
    $this->properties['type'] = self::TYPE_SHORT;
    $this->properties['total'] = $total;
    $this->properties['size'] = $size;
    $this->properties['page'] = $page;
    $this->properties['links'] = 3;
    $this->properties['last'] = null;
    $this->properties['callback'] = null;
    $this->properties['tag'] = 'div';
    $this->properties['tpl'] = ['active'   => '<span>#item#</span>',
                                'page'     => '<a href="javascript:;" onclick="#callback#">#item#</a>',
                                'next'     => '<a href="javascript:;" onclick="#callback#">Next</a>',
                                'prev'     => '<a href="javascript:;" onclick="#callback#">Previous</a>',
                                'first'    => '<a href="javascript:;" onclick="#callback#">First</a>',
                                'last'     => '<a href="javascript:;" onclick="#callback#">Last</a>',
                                'spacer'   => '<span>...</span>'];
  }
  
  public function setPage($page)
  {
    $this->properties['page'] = $page;
    return $this;
  }
  
  public function normalize()
  {
    $this->properties['total'] = (int)$this->properties['total'];
    $this->properties['size'] = (int)$this->properties['size'];
    $this->properties['links'] = (int)$this->properties['links'];
    $this->properties['page'] = (int)$this->properties['page'];
    if ($this->properties['total'] < 0) $this->properties['total'] = 0;
    if ($this->properties['size'] < 1) $this->properties['size'] = 1;
    if ($this->properties['links'] < 1) $this->prperties['links'] = 1;
    $this->properties['last'] = ceil($this->properties['total'] / $this->properties['size']) - 1;
    if ($this->properties['page'] < 0) $this->properties['page'] = 0;
    if ($this->properties['page'] > $this->properties['last']) $this->properties['page'] = $this->properties['last'];
    return $this;
  }
  
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    $this->normalize();
    $html = '<' . $this->properties['tag'] . $this->renderAttributes() . '>';
    if ($this->properties['total'] > $this->properties['size'])
    {
      $callback = strtr($this->properties['callback'], ['#total#' => $this->properties['total'], '#size#' => $this->properties['size'], '#last#' => $this->properties['last']]);
      $first = $this->replaceTplPart('first', 0, $callback);
      $prev = $this->replaceTplPart('prev', $this->properties['page'] - 1, $callback);
      $next = $this->replaceTplPart('next', $this->properties['page'] + 1, $callback);
      $last = $this->replaceTplPart('last', $this->properties['last'], $callback);
      $spacer = $this->properties['tpl']['spacer'];
      if ($this->properties['page'] != 0) $html .= $first . $prev;
      switch ($this->properties['type'])
      {
        case self::TYPE_SHORT:
          if ($this->properties['last'] <= 2 * $this->properties['links'])
          {
            for ($i = 0; $i <= $this->properties['last']; $i++)
            {
              $html .= $this->replaceTplPart($i == $this->properties['page'] ? 'active' : 'page', $i, $callback);
            }
          }
          else
          {
            if ($this->properties['page'] <= $this->properties['links'])
            {
              $a = 0;
              $b = $this->properties['page'] + $this->properties['links'];
            }
            else if ($this->properties['page'] > $this->properties['last'] - $this->properties['links'])
            {
              $a = $this->properties['page'] - $this->properties['links'];
              $b = $this->properties['last'];
            }
            else
            {
              $a = $this->properties['page'] - $this->properties['links'];
              $b = $this->properties['page'] + $this->properties['links'];
            }
            if ($a != 0) $html .= $spacer;
            for ($i = $a; $i <= $b; $i++)
            {
              $html .= $this->replaceTplPart($i == $this->properties['page'] ? 'active' : 'page', $i, $callback);
            }
            if ($b != $this->properties['last']) $html .= $spacer;
          }
          break;
        case self::TYPE_LONG:
          if ($this->properties['last'] <= 4 * $this->properties['links'])
          {
            for ($i = 0; $i <= $this->properties['last']; $i++)
            {
              $html .= $this->replaceTplPart($i == $this->properties['page'] ? 'active' : 'page', $i, $callback);
            }
          }
          else
          {
            $k = [0];
            $c = $this->properties['links'] - 1;
            $a = $this->properties['page'] - $this->properties['links'];
            $b = $this->properties['page'] + $this->properties['links'];
            $d = $this->properties['last'] - $c;
            if ($a - 1 <= $c) 
            {
              $k[] = max(3 * $c, $b);
              $k[] = $d;
              $k[] = $this->properties['last'];
            }
            else 
            {
              $k[] = $c;
              if ($d - 1 <= $b) 
              {
                $k[] = min($this->properties['last'] - 3 * $c, $a);
                $k[] = $this->properties['last'];
              }
              else
              {
                $k[] = $a;
                $k[] = $b;
                $k[] = $d;
                $k[] = $this->properties['last'];
              }
            }
            for ($j = 0, $count = count($k); $j < $count; $j += 2)
            {
              for ($i = $k[$j]; $i <= $k[$j + 1]; $i++)
              {
                $html .= $this->replaceTplPart($i == $this->properties['page'] ? 'active' : 'page', $i, $callback);
              }
              if ($j < $count - 2) $html .= $spacer;
            }
          }
          break;
        default:
          throw new Core\Exception($this, 'ERR_PAGINATOR_1', $this->properties['type']);
      }
      if ($this->properties['page'] != $this->properties['last']) $html .= $next . $last;
    }
    $html .= '</' . $this->properties['tag'] . '>';
    return $html;
  }
  
  protected function replaceTplPart($part, $page, $callback)
  {
    return strtr($this->properties['tpl'][$part], ['#item#' => $page + 1, '#page#' => $page, '#callback#' => str_replace('#page#', $page, $callback)]);
  }
}