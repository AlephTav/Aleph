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

namespace Aleph\Data\Converters;

use Aleph\Core,
    Aleph\Utils;

/**
 * This converter is intended for converting the given array to an array with another structure. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.data.converters
 */
class Collection extends Converter
{
  /**
   * Error message templates.
   */
  const ERR_CONVERTER_COLLECTION_1 = 'The given value is not an array.';
  const ERR_CONVERTER_COLLECTION_2 = 'Invalid conversion mode "[{var}]".';
  const ERR_CONVERTER_COLLECTION_3 = 'Transformation from "[{var}]" to "[{var}]" is impossible.';
  const ERR_CONVERTER_COLLECTION_4 = 'Keys "[{var}]" are incorrect.';

  /**
   * The mode of the array structure converting. The valid values are "transform", "reduce" and "exclude".
   *
   * @var string $mode
   * @access public
   */
  public $mode = 'transform';
  
  /**
   * The scheme that describes the new array structure and conversion method.
   *
   * @var array $scheme
   * @access public
   */
  public $scheme = [];
  
  /**
   * 
   */
  public $separator = '.';
  
  public $anyKeyAssociative = '$';
  
  public $anyKeyNumeric = '*';
  
  public function convert($entity)
  {
    if (!is_array($entity)) throw new Core\Exception($this, 'ERR_CONVERTER_COLLECTION_1');
    switch (strtolower($this->mode))
    {
      case 'transform':
        return $this->transform($entity);
      case 'reduce':
        return $this->reduce($entity);
      case 'exclude':
        return $this->exclude($entity);
    }
    throw new Core\Exception($this, 'ERR_CONVERTER_COLLECTION_2', $this->mode);
  }
  
  public function transform(array $array)
  {
    $new = [];
    foreach ($this->scheme as $from => $to)
    {
      $keys = $this->getKeys($to);
      foreach ($this->getValues($array, $from) as $info)
      {
        $a = &$new;
        foreach ($keys as $key)
        {
          if (is_array($key))
          {
            if (count($info[1]) == 0) throw new Core\Exception($this, 'ERR_CONVERTER_COLLECTION_3', $from, $to);
            if ($key[0] == '*')
            {
              array_shift($info[1]);
              $key = count($a);
            }
            else if ($key[0] == '$')
            {
              $key = array_shift($info[1]);
            }
          }
          if (!array_key_exists($key, $a)) $a[$key] = [];
          $a = &$a[$key];
        }
        $a = $info[0];
      }
    }
    return $new;
  }
  
  protected function reduce(array $array)
  {
    $new = [];
    foreach ($this->scheme as $from)
    {
      $keys = $this->getKeys($from);
      foreach ($this->getValues($array, $from, $keys) as $info)
      {
        $a = &$new;
        foreach ($keys as $key)
        {
          if (is_array($key))
          {
            if (count($info[1]) == 0) throw new Core\Exception($this, 'ERR_CONVERTER_COLLECTION_3', $from, $to);
            if ($key[0] == '*')
            {
              array_shift($info[1]);
              $key = count($a);
            }
            else if ($key[0] == '$')
            {
              $key = array_shift($info[1]);
            }
          }
          if (!array_key_exists($key, $a)) $a[$key] = [];
          $a = &$a[$key];
        }
        $a = $info[0];
      }
    }
    return $new;
  }
  
  protected function exclude(array $array)
  {
    $tmp = $array;
    foreach ($this->scheme as $keys)
    {
      foreach ($this->getValues($array, $keys, null, true) as $info)
      {
        Utils\Arrays::unsetByKeys($tmp, $info[1]);
      }
    }
    return $tmp;
  }
  
  protected function getValues(array $array, $from, array $keys = null, $allKeys = false)
  {
    $n = 0; $tmp = [];
    if (!$keys) $keys = $this->getKeys($from);
    foreach ($keys as $key)
    {
      if (is_array($key))
      {
        if (!is_array($array)) throw new Core\Exception($this, 'ERR_CONVERTER_COLLECTION_4', $from);
        foreach ($array as $k => $v)
        {
          if (is_array($v))
          {
            foreach ($this->getValues($v, $from, array_slice($keys, $n + 1), $allKeys) as $value) 
            {
              $value[1] = array_merge(array_merge($tmp, [$k]), $value[1]);
              yield $value;
            }
          }
          else
          {
            yield [$v, array_merge($tmp, [$k])];
          }
        }
        return;
      }
      else
      {
        if (!is_array($array) || !array_key_exists($key, $array)) throw new Core\Exception($this, 'ERR_CONVERTER_COLLECTION_4', $from);
        $array = $array[$key];
        if ($allKeys) $tmp[] = $key;
      }
      $n++;
    }
    yield [$array, $tmp];
  }
  
  protected function getKeys($keys)
  {
    if (!is_array($keys)) $keys = preg_split('/(?<!\\\)' . preg_quote($this->separator) . '/', $keys, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($keys as &$part)
    {
      if ($part == $this->anyKeyAssociative) $part = ['$'];
      else if ($part == $this->anyKeyNumeric) $part = ['*'];
      else if ($part == '\\' . $this->anyKeyAssociative) $part = $this->$this->anyKeyAssociative;
      else if ($part == '\\' . $this->anyKeyNumeric) $part = $this->$this->anyKeyNumeric;
      else $part = str_replace('\\' . $this->separator, $this->separator, $part);
    }
    return $keys;
  }
}