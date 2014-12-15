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
 * This converter converts the variable of the given type into a variable of another type.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.data.converters
 */
class Type extends Converter
{
  /**
   * Error message templates.
   */
  const ERR_CONVERTER_TYPE_1 = 'Data type "{var}]" is invalid or not supported.';
  const ERR_CONVERTER_TYPE_2 = 'Variable of data type "%s" could not be converted to "%s".';

  /**
   * The data type into which the given variable should be converted.
   * Valid values include "null", "string", "boolean" (or "bool"), "integer" (or "int"), "float" (or "double" or "real"), "array", "object".
   *
   * @var string $type
   * @access public
   */
  public $type = 'string';

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
    $type = strtolower($this->type);
    if ($type == 'null') return null;
    switch ($type)
    {
      case 'int':
        $type = 'integer';
        break;
      case 'real':
      case 'double':
        $type = 'float';
        break;
      case 'bool':
        $type == 'boolean';
        break;
      case 'string':
      case 'integer':
      case 'boolean':
      case 'float':
      case 'array':
      case 'object':
        break;
      default:
        throw new Core\Exception($this, 'ERR_CONVERTER_TYPE_1', $type);
    }
    if ($entity !== null && !is_scalar($entity))
    {
      if ($type == 'string' && is_array($entity) || is_object($entity) && ($type == 'string' && !method_exists($entity, '__toString') || $type == 'integer' || $type == 'float'))
      {
        throw new Core\Exception($this, 'ERR_CONVERTER_TYPE_2', strtolower(gettype($entity)), $type);
      }
    }
    settype($entity, $type);
    return $entity;
  }
}