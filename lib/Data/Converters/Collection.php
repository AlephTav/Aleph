<?php
/**
 * Copyright (c) 2013 - 2015 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2015 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Data\Converters;

use Aleph\Core,
    Aleph\Utils;

/**
 * This converter is intended for converting the given array to an array with another structure. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.data.converters
 */
class Collection extends Converter
{
  /**
   * Error message templates.
   */
  const ERR_CONVERTER_COLLECTION_1 = 'The given value is not an array.';
  const ERR_CONVERTER_COLLECTION_2 = 'Invalid conversion mode "%s".';
  const ERR_CONVERTER_COLLECTION_3 = 'Transformation from "%s" to "%s" is impossible.';
  const ERR_CONVERTER_COLLECTION_4 = 'Keys "%s" are incorrect.';

  /**
   * The mode of the array structure converting. The valid values are "transform", "reduce" and "exclude".
   *
   * @var string $mode
   * @access public
   */
  public $mode = 'transform';
  
  /**
   * The schema that describes the new array structure and conversion ways.
   * The particular schema format depends on the value of $mode property.
   *
   * @var array $schema
   * @access public
   */
  public $schema = [];
  
  /**
   * The separator of the key names in the array schema.
   * If some array key contains the separator symbol you should escape it via backslash.
   *
   * @var string $separator
   * @access public
   */
  public $separator = '.';
  
  /**
   * This symbol corresponds to any elements with their keys of the transforming array in the array schema.
   * If some array key is the same as $keyAssociative you should escape it via backslash.
   *
   * @var string $keyAssociative
   * @access public
   */
  public $keyAssociative = '$';
  
  /**
   * This symbol corresponds to any elements without their keys of the transforming array in the array schema.
   * If some array key is the same as $keyNumeric you should escape it via backslash.
   *
   * @var string $keyAssociative
   * @access public
   */
  public $keyNumeric = '*';
  
  /**
   * Converts the given array to an array with other structure defining by the specified array schema.
   *
   * @param array $entity - the array to be converted.
   * @return array
   * @access public
   */
  public function convert($entity)
  {
    if (!is_array($entity)) throw new Core\Exception([$this, 'ERR_CONVERTER_COLLECTION_1']);
    switch (strtolower($this->mode))
    {
      case 'transform':
        return $this->transform($entity);
      case 'reduce':
        return $this->reduce($entity);
      case 'exclude':
        return $this->exclude($entity);
    }
    throw new Core\Exception([$this, 'ERR_CONVERTER_COLLECTION_2', $this->mode]);
  }
  
  /**
   * Changes the array structure according to the given transformation schema.
   *
   * @param array $array - the array to be transformed.
   * @return array
   * @access protected
   */
  protected function transform(array $array)
  {
    $new = [];
    foreach ($this->schema as $from => $to)
    {
      $keys = $this->getKeys($to);
      foreach ($this->getValues($array, $from) as $info)
      {
        $a = &$new;
        foreach ($keys as $key)
        {
          if (is_array($key))
          {
            if (count($info[1]) == 0) throw new Core\Exception([$this, 'ERR_CONVERTER_COLLECTION_3'], [$from, $to]);
            $key = array_shift($info[1]);
          }
          if (!array_key_exists($key, $a)) $a[$key] = [];
          $a = &$a[$key];
        }
        $a = $info[0];
      }
    }
    return $new;
  }
  
  /**
   * Returns the part of the given array that determining by the array schema.
   *
   * @param array $array - the array to be reduced.
   * @return array
   * @access protected
   */
  protected function reduce(array $array)
  {
    $new = [];
    foreach ($this->schema as $from)
    {
      $keys = $this->getKeys($from);
      foreach ($this->getValues($array, $from, $keys) as $info)
      {
        $a = &$new;
        foreach ($keys as $key)
        {
          if (is_array($key))
          {
            if (count($info[1]) == 0) throw new Core\Exception([$this, 'ERR_CONVERTER_COLLECTION_3'], [$from, $to]);
            $key = array_shift($info[1]);
          }
          if (!array_key_exists($key, $a)) $a[$key] = [];
          $a = &$a[$key];
        }
        $a = $info[0];
      }
    }
    return $new;
  }
  
  /**
   * Removes some part of the array according to the array schema and returns the remainder array.
   *
   * @param array $array - the array to be reduced.
   * @return array
   * @access protected
   */
  protected function exclude(array $array)
  {
    $tmp = $array;
    foreach ($this->schema as $keys)
    {
      foreach ($this->getValues($array, $keys, null, true) as $info)
      {
        Utils\Arrays::unsetByKeys($tmp, $info[1]);
      }
    }
    return $tmp;
  }
  
  /**
   * Sequentially returns the array elements according to their keys.
   *
   * @param array $array - the given array.
   * @param string $from - an element of the array schema that determines keys of array elements to be extracted.
   * @param array $keys - the same as $from but parsed to an array.
   * @param boolean $allKeys - determines whether the all keys is returned from the method or only keys captured by $keyAssociative or $keyNumeric.
   * @return mixed
   */
  protected function getValues(array $array, $from, array $keys = null, $allKeys = false)
  {
    $n = 0; $tmp = [];
    if (!$keys) $keys = $this->getKeys($from);
    foreach ($keys as $key)
    {
      if (is_array($key))
      {
        if (!is_array($array)) throw new Core\Exception([$this, 'ERR_CONVERTER_COLLECTION_4'], $from);
        $keys = array_slice($keys, $n + 1); $n = 0;
        foreach ($array as $k => $v)
        {
          if (is_array($v) && count($keys))
          {
            foreach ($this->getValues($v, $from, $keys, $allKeys) as $value) 
            {
              $value[1] = array_merge(array_merge($tmp, [$key[0] == '*' ? $n : $k]), $value[1]);
              yield $value;
            }
          }
          else
          {
            yield [$v, array_merge($tmp, [$key[0] == '*' ? $n : $k])];
          }
          $n++;
        }
        return;
      }
      else
      {
        if (!is_array($array) || !array_key_exists($key, $array)) throw new Core\Exception([$this, 'ERR_CONVERTER_COLLECTION_4'], $from);
        $array = $array[$key];
        if ($allKeys) $tmp[] = $key;
      }
      $n++;
    }
    yield [$array, $tmp];
  }
  
  /**
   * Returns an array of the normalized keys of the converting collection.
   *
   * @param string | array $keys - a string or an array of the collection keys.
   * @return array
   * @access protected
   */
  protected function getKeys($keys)
  {
    if (!is_array($keys)) $keys = preg_split('/(?<!\\\)' . preg_quote($this->separator) . '/', $keys, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($keys as &$part)
    {
      if ($part == $this->keyAssociative) $part = ['$'];
      else if ($part == $this->keyNumeric) $part = ['*'];
      else if ($part == '\\' . $this->keyAssociative) $part = $this->$this->keyAssociative;
      else if ($part == '\\' . $this->keyNumeric) $part = $this->$this->keyNumeric;
      else $part = str_replace('\\' . $this->separator, $this->separator, $part);
    }
    return $keys;
  }
}