<?php
/**
 * Copyright (c) 2013 - 2016 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2016 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Data\Converters;

/**
 * This converter is intended for converting the given string (or, in some cases, any value) into the specified data format. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.data.converters
 */
class TextConverter extends Converter
{
    /**
     * Error message templates.
     */
    const ERR_CONVERTER_TEXT_1 = 'The converting data can be a compound type only for "any" input format. In the other cases it can be only scalar type.';
    const ERR_CONVERTER_TEXT_2 = 'Invalid input string format "%s".';
    const ERR_CONVERTER_TEXT_3 = 'Invalid output data format "%s".';
    const ERR_CONVERTER_TEXT_4 = 'Invalid output data format "%s" for the compound type.';
    
    /**
     * String formats.
     */
    const ANY = 'any';
    const PLAIN = 'plain';
    const JSON_ENCODED = 'json-encoded';
    const JSON_DECODED = 'json-decoded';
    const BASE64_ENCODED = 'base64-encoded';
    const BASE64_DECODED = 'base64-decoded';
    const UU_ENCODED = 'uu-encoded';
    const UU_DECODED = 'uu-decoded';
    const SERIALIZED = 'serialized';
    const UNSERIALIZED = 'unserialized';

    /**
     * The input string format.
     * It can be one of the following valid values: "any", "plain", "serialized", "json-encoded", "base64-encoded", "uu-encoded".
     *
     * @var string
     */
    public $input = self::ANY;
  
    /**
     * The output data format.
     * It can be one of the following valid values: "any", "plain", "serialized", "unserialized", "json-encoded", "json-decoded", "base64-encoded", "base64-decoded", "uu-encoded", "uu-decoded".
     *
     * @var string
     */
    public $output = self::ANY;
  
    /**
     * The charset of the input string.
     *
     * @var string
     */
    public $inputCharset = 'UTF-8';
  
    /**
     * The charset of the output data.
     *
     * @var string
     */
    public $outputCharset = 'UTF-8';

    /**
     * Converts the given value to the specified data format according to the given character sets.
     *
     * @param mixed $entity
     * @return mixed
     * @throws \LogicException
     */
    public function convert($entity)
    {
        $input = strtolower($this->input);
        $output = strtolower($this->output);
        if (is_scalar($entity))
        {
            $entity = $this->convertEncoding($entity);
        }
        else if ($input != self::ANY)
        {
            throw new \LogicException(static::ERR_CONVERTER_TEXT_1);
        }
        switch ($input)
        {
            case self::ANY:
                if (!is_scalar($entity))
                {
                    switch ($output)
                    {
                        case self::ANY:
                        case self::UNSERIALIZED:
                        case self::JSON_DECODED:
                        case self::BASE64_DECODED:
                        case self::UU_DECODED:
                            break;
                        case self::SERIALIZED:
                            return $this->convertEncoding(serialize($entity));
                        case self::JSON_ENCODED:
                            return $this->convertEncoding(json_encode($entity));
                        case self::PLAIN:
                        case self::BASE64_ENCODED:
                        case self::UU_ENCODED:
                            throw new \LogicException(sprintf(static::ERR_CONVERTER_TEXT_4, $output));
                        default:
                            throw new \LogicException(sprintf(static::ERR_CONVERTER_TEXT_3, $output));
                    }
                    break;
                }
            case self::PLAIN:
                switch ($output)
                {
                    case self::ANY:
                    case self::PLAIN:
                        break;
                    case self::SERIALIZED:
                        $entity = serialize($entity);
                        break;
                    case self::UNSERIALIZED:
                        $entity = unserialize($entity);
                        break;
                    case self::JSON_ENCODED:
                        $entity = json_encode($entity);
                        break;
                    case self::JSON_DECODED:
                        $entity = json_decode($entity, true);
                        break;
                    case self::BASE64_ENCODED:
                        $entity = base64_encode($entity);
                        break;
                    case self::BASE64_DECODED:
                        $entity = base64_decode($entity);
                        break;
                    case self::UU_ENCODED:
                        $entity = convert_uuencode($entity);
                        break;
                    case self::UU_DECODED:
                        $entity = convert_uudecode($entity);
                        break;
                    default:
                       throw new \LogicException(sprintf(static::ERR_CONVERTER_TEXT_3, $output));
                }
                break;    
            case self::SERIALIZED:
                switch ($output)
                {
                    case self::PLAIN:
                    case self::SERIALIZED:
                        break;
                    case self::ANY:
                    case self::UNSERIALIZED:
                    case self::JSON_DECODED:
                    case self::BASE64_DECODED:
                    case self::UU_DECODED:
                        $entity = unserialize($entity);
                        break;
                    case self::JSON_ENCODED:
                        $entity = json_encode(unserialize($entity));
                        break;
                    case self::BASE64_ENCODED:
                        $entity = base64_encode(unserialize($entity));
                        break;
                    case self::UU_ENCODED:
                        $entity = convert_uuencode(unserialize($entity));
                        break;
                    default:
                        throw new \LogicException(sprintf(static::ERR_CONVERTER_TEXT_3, $output));
                }
                break;
            case self::JSON_ENCODED:
                switch ($output)
                {
                    case self::PLAIN:
                    case self::JSON_ENCODED:
                        break;
                    case self::ANY:
                    case self::UNSERIALIZED:
                    case self::JSON_DECODED:
                    case self::BASE64_DECODED:
                    case self::UU_DECODED:
                        $entity = json_decode($entity, true);
                        break;
                    case self::SERIALIZED:
                        $entity = serialize(json_decode($entity, true));
                        break;
                    case self::BASE64_ENCODED:
                        $entity = base64_encode(json_decode($entity, true));
                        break;
                    case self::UU_ENCODED:
                        $entity = convert_uuencode(json_decode($entity, true));
                        break;
                    default:
                        throw new \LogicException(sprintf(static::ERR_CONVERTER_TEXT_3, $output));
                }
                break;
            case self::BASE64_ENCODED:
                switch ($output)
                {
                    case self::PLAIN:
                    case self::BASE64_ENCODED:
                        break;
                    case self::ANY:
                    case self::UNSERIALIZED:
                    case self::JSON_DECODED:
                    case self::BASE64_DECODED:
                    case self::UU_DECODED:
                        $entity = base64_decode($entity);
                        break;
                    case self::SERIALIZED:
                        $entity = serialize(base64_decode($entity));
                        break;
                    case 'json-encode':
                        $entity = json_encode(base64_decode($entity));
                        break;
                    case self::UU_ENCODED:
                        $entity = convert_uuencode(base64_decode($entity));
                        break;
                    default:
                        throw new \LogicException(sprintf(static::ERR_CONVERTER_TEXT_3, $output));
                }
                break;
            case self::UU_ENCODED:
                switch ($output)
                {
                    case self::PLAIN:
                    case self::UU_ENCODED:
                        break;
                    case self::ANY:
                    case self::UNSERIALIZED:
                    case self::JSON_DECODED:
                    case self::BASE64_DECODED:
                    case self::UU_DECODED:
                        $entity = convert_uudecode($entity);
                        break;
                    case self::SERIALIZED:
                        $entity = serialize(convert_uudecode($entity));
                        break;
                    case self::JSON_ENCODED:
                        $entity = json_encode(convert_uudecode($entity));
                        break;
                    case self::BASE64_ENCODED:
                        $entity = base64_encode(convert_uudecode($entity));
                        break;
                    default:
                        throw new \LogicException(sprintf(static::ERR_CONVERTER_TEXT_3, $output));
                }
                break;
            default:
                throw new \LogicException(sprintf(static::ERR_CONVERTER_TEXT_2, $input));
        }
        return $entity;
    }
  
    /**
     * Converts the character encoding of the given string to $outputCharset from $inputCharset.
     *
     * @param string $entity The string value to be converted.
     * @return string
     */
    protected function convertEncoding(string $entity) : string
    {
        if (strpos($this->outputCharset, $this->inputCharset) !== 0)
        {
            return $entity;
        }
        if (function_exists('iconv'))
        {
            return iconv($this->inputCharset, $this->outputCharset, $entity);
        }
        if (function_exists('mb_convert_encoding '))
        {
            return mb_convert_encoding($entity, $this->outputCharset, $this->inputCharset);
        }
        return $entity;
    }
}