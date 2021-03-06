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

use Aleph;

/**
 * The base class for all converters.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.2
 * @package aleph.data.converters
 */
abstract class Converter
{
    /**
     * Error message templates.
     */
    const ERR_CONVERTER_1 = 'Invalid converter type "%s". The only following types are valid: "type", "text", "array".';

    /**
     * Creates and returns a converter object of the required type.
     * Converter type can be one of the following values: "type", "text", "collection".
     *
     * @param string $type The type of the converter instance.
     * @param array $params Initial values to be applied to the converter properties.
     * @return \Aleph\Data\Converters\Converter
     * @throws \InvalidArgumentException
     */
    final public static function getInstance(string $type, array $params = []) : Converter
    {
        $class = 'Aleph\Data\Converters\\' . $type;
        if (!\Aleph::loadClass($class))
        {
            throw new \InvalidArgumentException(sprintf(static::ERR_CONVERTER_1, $type));
        }
        $converter = new $class;
        foreach ($params as $k => $v)
        {
            $converter->{$k} = $v;
        }
        return $converter;
    }
  
    /**
     * Converts the entity from one data format to another according to the specified options.
     *
     * @param mixed $entity Tthe entity to convert.
     * @return mixed The converted data.
     */
    abstract public function convert($entity);
}