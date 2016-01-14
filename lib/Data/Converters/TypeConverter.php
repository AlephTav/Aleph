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

use Aleph\Utils;

/**
 * This converter converts the variable of the given type into a variable of another type.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.0
 * @package aleph.data.converters
 */
class TypeConverter extends Converter
{
    /**
     * Error message templates.
     */
    const ERR_CONVERTER_TYPE_1 = 'Data type "%s" is invalid or not supported.';
    const ERR_CONVERTER_TYPE_2 = 'Variable of data type "%s" could not be converted to "%s".';
    const ERR_CONVERTER_TYPE_3 = 'Cannot convert variable of type %s to a date string.';
    const ERR_CONVERTER_TYPE_4 = 'Cannot convert variable of type %s to a date object.';
    const ERR_CONVERTER_TYPE_5 = 'Callback is not defined.';

    /**
     * The data type into which the given variable should be converted.
     * Valid values include "null", "string", "boolean" (or "bool"), "integer" (or "int"),
     * "float" ("double" or "real"), "array", "object", date_string, date_object and callback.
     * You can also specify the additional parameter after type name using $typeDelimiter
     * or define the type as an array: [type name, additional parameter of the conversion].
     *
     * @var string|array $type
     * @access public
     */
    public $type = 'string';
    
    /**
     * Used to separate type name and additional parameter of the conversion.
     *
     * @var string $typeDelimiter
     * @access public
     */
    public $typeDelimiter = ':';

    /**
     * Set the type of the given variable $entity to the specified PHP data type.
     * Returns a variable of the specified type or throws exception if such conversion is not possible.
     *
     * @param mixed $entity
     * @return mixed
     * @access public
     */
    public function convert($entity)
    {
        @list($type, $param) = is_array($this->type) ? $this->type : explode($this->typeDelimiter, $this->type, 2);
        $type = $this->normalizeType($type);
        switch ($type)
        {
            case 'null':
                return null;
            case 'string':
            case 'boolean':
            case 'integer':
            case 'float':
            case 'array':
            case 'object':
                if ($entity !== null && !is_scalar($entity))
                {
                    if ($type == 'string' && is_array($entity) || is_object($entity) && 
                       ($type == 'string' && !method_exists($entity, '__toString') || $type == 'integer' || $type == 'float'))
                    {
                        throw new \LogicException(sprintf(static::ERR_CONVERTER_TYPE_2, strtolower(gettype($entity)), $type));
                    }
                }
                settype($entity, $type);
                if ($type == 'float' && $param !== null)
                {
                    $entity = round($entity, $param, PHP_ROUND_HALF_UP);
                }
                return $entity;
            case 'date_string':
                if ($entity === null)
                {
                    return '';
                }
                $param = $param !== null ? $param : 'c';
                if ($entity instanceof \DateTime)
                {
                    return $entity->format($param);
                }
                if (is_scalar($entity))
                {
                    return (new \DateTime($entity))->format($param);
                }
                throw new \LogicException(sprintf(static::ERR_CONVERTER_TYPE_3, gettype($entity)));
            case 'date_object':
                if ($entity instanceof \DateTime)
                {
                    return $entity;
                }
                if (is_scalar($entity))
                {
                    return $param !== null ? Utils\DT::createFromFormat($param, $entity) : new Utils\DT($entity);
                }
                throw new \LogicException(sprintf(static::ERR_CONVERTER_TYPE_4, gettype($entity)));
            case 'callback':
                if ($param === null)
                {
                    throw new \LogicException(sprintf(static::ERR_CONVERTER_TYPE_5));
                }
                return \Aleph::delegate($param, $entity);
        }
        throw new \LogicException(sprintf(static::ERR_CONVERTER_TYPE_1, $type));
    }
    
    /**
     * Returns normalized type name.
     *
     * @param string $type
     * @return string
     * @access private
     */
    private function normalizeType($type)
    {
        $type = strtolower($type);
        switch ($type)
        {
            case 'int':
                return 'integer';
            case 'real':
            case 'double':
                return 'float';
            case 'bool':
                return 'boolean';
        }
        return $type;
    }
}