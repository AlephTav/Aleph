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

use Aleph\Core;

/**
 * This converter is intended for converting the given string (or, in some cases, any value) into the specified data format. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.data.converters
 */
class Text extends Converter
{
  /**
   * Error message templates.
   */
  const ERR_CONVERTER_TEXT_1 = 'The converting data can be a compound type only for "any" input format. In the other cases it can be only scalar type.';
  const ERR_CONVERTER_TEXT_2 = 'Invalid input string format "[{var}]".';
  const ERR_CONVERTER_TEXT_3 = 'Invalid output data format "[{var}]".';
  const ERR_CONVERTER_TEXT_4 = 'Invalid output data format "[{var}]" for the compound type.';

  /**
   * The input string format.
   * It can be one of the following valid values: "any", "plain", "serialized", "json-encoded", "base64-encoded", "uu-encoded".
   *
   * @var string $input
   * @access public
   */
  public $input = 'any';
  
  /**
   * The output data format.
   * It can be one of the following valid values: "any", "plain", "serialized", "unserialized", "json-encoded", "json-decoded", "base64-encoded", "base64-decoded", "uu-encoded", "uu-decoded".
   *
   * @var string $output
   * @access public
   */
  public $output = 'any';
  
  /**
   * The charset of the input string.
   *
   * @var string $inputCharset
   * @access public
   */
  public $inputCharset = 'UTF-8';
  
  /**
   * The charset of the output data.
   *
   * @var string $outputCharset
   * @access public
   */
  public $outputCharset = 'UTF-8';

  /**
   * Converts the given value to the specified data format according to the given character sets.
   *
   * @param mixed $entity - any data to be converted.
   * @return mixed
   * @access public
   */
  public function convert($entity)
  {
    $input = strtolower($this->input);
    $output = strtolower($this->output);
    if (is_scalar($entity)) $entity = $this->convertEncoding($entity);
    else if ($input != 'any') throw new Core\Exception($this, 'ERR_CONVERTER_TEXT_1');
    switch ($input)
    {
      case 'any':
        if (!is_scalar($entity))
        {
          switch ($output)
          {
            case 'any':
            case 'unserialized':
            case 'json-decoded':
            case 'base64-decoded':
            case 'uu-decoded':
              break;
            case 'serialized':
              return $this->convertEncoding(serialize($entity));
            case 'json-encoded':
              return $this->convertEncoding(json_encode($entity));
            case 'plain':
            case 'base64-encoded':
            case 'uu-encoded':
              throw new Core\Exception($this, 'ERR_CONVERTER_TEXT_4', $output);
            default:
              throw new Core\Exception($this, 'ERR_CONVERTER_TEXT_3', $output);
          }
          break;
        }
      case 'plain':
        switch ($output)
        {
          case 'any':
          case 'plain':
            break;
          case 'serialized':
            $entity = serialize($entity);
            break;
          case 'unserialized':
            $entity = unserialize($entity);
            break;
          case 'json-encoded':
            $entity = json_encode($entity);
            break;
          case 'json-decoded':
            $entity = json_decode($entity, true);
            break;
          case 'base64-encoded':
            $entity = base64_encode($entity);
            break;
          case 'base64-decoded':
            $entity = base64_decode($entity);
            break;
          case 'uu-encoded':
            $entity = convert_uuencode($entity);
            break;
          case 'uu-decoded':
            $entity = convert_uudecode($entity);
            break;
          default:
            throw new Core\Exception($this, 'ERR_CONVERTER_TEXT_3', $output);
        }
        break;
      case 'serialized':
        switch ($output)
        {
          case 'plain':
          case 'serialized':
            break;
          case 'any':
          case 'unserialized':
          case 'json-decoded':
          case 'base64-decoded':
          case 'uu-decoded':
            $entity = unserialize($entity);
            break;
          case 'json-encoded':
            $entity = json_encode(unserialize($entity));
            break;
          case 'base64-encoded':
            $entity = base64_encode(unserialize($entity));
            break;
          case 'uu-encoded':
            $entity = convert_uuencode(unserialize($entity));
            break;
          default:
            throw new Core\Exception($this, 'ERR_CONVERTER_TEXT_3', $output);
        }
        break;
      case 'json-encoded':
        switch ($output)
        {
          case 'plain':
          case 'json-encoded':
            break;
          case 'any':
          case 'unserialized':
          case 'json-decoded':
          case 'base64-decoded':
          case 'uu-decoded':
            $entity = json_decode($entity, true);
            break;
          case 'serialized':
            $entity = serialize(json_decode($entity, true));
            break;
          case 'base64-encoded':
            $entity = base64_encode(json_decode($entity, true));
            break;
          case 'uu-encoded':
            $entity = convert_uuencode(json_decode($entity, true));
            break;
          default:
            throw new Core\Exception($this, 'ERR_CONVERTER_TEXT_3', $output);
        }
        break;
      case 'base64-encoded':
        switch ($output)
        {
          case 'plain':
          case 'base64-encoded':
            break;
          case 'any':
          case 'unserialized':
          case 'json-decoded':
          case 'base64-decoded':
          case 'uu-decoded':
            $entity = base64_decode($entity);
            break;
          case 'serialized':
            $entity = serialize(base64_decode($entity));
            break;
          case 'json-encode':
            $entity = json_encode(base64_decode($entity));
            break;
          case 'uu-encoded':
            $entity = convert_uuencode(base64_decode($entity));
            break;
          default:
            throw new Core\Exception($this, 'ERR_CONVERTER_TEXT_3', $output);
        }
        break;
      case 'uu-encoded':
        switch ($output)
        {
          case 'plain':
          case 'uu-encoded':
            break;
          case 'any':
          case 'unserialized':
          case 'json-decoded':
          case 'base64-decoded':
          case 'uu-decoded':
            $entity = convert_uudecode($entity);
            break;
          case 'serialized':
            $entity = serialize(convert_uudecode($entity));
            break;
          case 'json-encode':
            $entity = json_encode(convert_uudecode($entity));
            break;
          case 'base64-encoded':
            $entity = base64_encode(convert_uudecode($entity));
            break;
          default:
            throw new Core\Exception($this, 'ERR_CONVERTER_TEXT_3', $output);
        }
        break;
      default:
        throw new Core\Exception($this, 'ERR_CONVERTER_TEXT_2', $input);
    }
    return $entity;
  }
  
  /**
   * Converts the character encoding of the given string to $outputCharset from $inputCharset.
   *
   * @param string $entity - the string value to be converted.
   * @return string
   * @access protected
   */
  protected function convertEncoding($entity)
  {
    if (strpos($this->outputCharset, $this->inputCharset) !== 0) return $entity;
    if (function_exists('iconv')) return iconv($this->inputCharset, $this->outputCharset, $entity);
    if (function_exists('mb_convert_encoding ')) return mb_convert_encoding($entity, $this->outputCharset, $this->inputCharset);
    return $entity;
  }
}