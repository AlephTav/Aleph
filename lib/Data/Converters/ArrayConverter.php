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
 * ArrayConverter is intended for converting the given array to an array with another structure. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 2.0.0
 * @package aleph.data.converters
 */
class ArrayConverter extends Converter
{
    /**
     * Error message templates.
     */
    const ERR_CONVERTER_ARRAY_1 = 'Conversion mode "%s" is invalid.';
    const ERR_CONVERTER_ARRAY_2_1 = 'Invalid schema format. Each definition of element transformation should be a string or and array of two elements.';
    const ERR_CONVERTER_ARRAY_2_2 = 'Invalid schema format. Element "keys" of the transformation definition of input array elements is missing.';
    const ERR_CONVERTER_ARRAY_2_3 = 'Invalid schema format. Element "keys" of the transformation definition of output array elements is missing.';
    const ERR_CONVERTER_ARRAY_2_4 = 'Invalid schema format. Invalid key name prefix "%s" in the input element definition.';
    const ERR_CONVERTER_ARRAY_2_5 = 'Invalid schema format. Invalid key name prefix "%s" in the output element definition.';
    const ERR_CONVERTER_ARRAY_2_6 = 'Invalid schema format. Invalid value name prefix "%s" in the output element definition.';
    const ERR_CONVERTER_ARRAY_2_7 = 'Invalid schema format. Array of keys cannot be empty.';
    const ERR_CONVERTER_ARRAY_3 = 'Array element %s does not exist.';
    const ERR_CONVERTER_ARRAY_4 = 'Array element %s is not an array and cannot be iterated.';
    const ERR_CONVERTER_ARRAY_5 = 'Key with name "%s" that encountered in output element definition %s does not exist in input element definition %s';
    const ERR_CONVERTER_ARRAY_6 = 'Value with name "%s" that encountered in output element definition %s does not exist.';
    
    /**
     * Conversion modes.
     */
    const MODE_TRANSFORM = 0;
    const MODE_REDUCE = 1;
    const MODE_EXCLUDE = 2;
    
    /**
     * Modifier values.
     */
    const MODIFIER_REQUIRED = 'required';
    const MODIFIER_IGNORE = 'ignore';
    
    /**
     * The conversion mode.
     *
     * @var int
     */
    public $mode = self::MODE_TRANSFORM;
    
    /**
     * The schema that describes the new array structure and conversion ways.
     * The particular schema format depends on the value of $mode property.
     *
     * @var array
     */
    public $schema = [];
    
    /**
     * The delimiter of the key names in the transformation schema.
     * If some array key contains the delimiter symbol you should escape it via backslash.
     *
     * @var string
     */
    public $keyDelimiter = '.';
    
    /**
     * Used to separate data type and value.
     * To escape the type delimiter need to use backslash.
     *
     * @var string
     */
    public $typeDelimiter = '|';
    
    /**
     * Used to separate data type from additional type parameters.
     *
     * @var string
     */
    public $typeParamDelimiter = ':';
    
    /**
     * Represents key of array element that should be replaced by its index number.
     * It can be used only in the right part of the schema.
     *
     * @var string
     */
    public $indexKey = '*';
    
    /**
     * Name prefix of the named element keys.
     *
     * @var string
     */
    public $keyNamePrefix = '$';
    
    /**
     * Name prefix of the named element values.
     *
     * @var string
     */
    public $valueNamePrefix = '@';
    
    /**
     * Separates an element compound key definition and its value name.
     *
     * @var string
     */
    public $valueNameDelimiter = '=>';
    
    /**
     * Determines whether non existing elements should be ignored or not.
     *
     * @var bool
     */
    public $ignoreNonExistingElements = false;
    
    /** 
     * Determines whether to presrve partly existing elements
     * (when only part of compound key exists).
     *
     * @var bool
     */
    public $preservePartlyExistingElements = false;
    
    /**
     * Determines whether to treat objects as arrays.
     *
     * @var bool
     */
    public $treatObjectAsArray = true;
    
    /**
     * The default precision for float values.
     *
     * @var int
     */
    public $precision = 2;
    
    /**
     * Normalized schema.
     *
     * @var array
     */
    private $nschema = null;
    
    /**
     * List of value names.
     *
     * @var array
     */
    private $names = null;
    
    /**
     * Contains all iterating named values.
     *
     * @var array
     */
    private $cache = null;
    
    /**
     * Some unique string. Used to generate unique name for empty named keys.
     *
     * @var string
     */
    private $uid = null;
    
    /**
     * Instance of type converter.
     *
     * @var \Aleph\Data\Converters\TypeConverter
     */
    private $typeConverter = null;
    
    /**
     * Constructor. Initializes private properties.
     *
     * @access public
     */
    public function __construct()
    {
        $this->typeConverter = new TypeConverter();
    }
    
    /**
     * Converts the given array to an array with other structure defining by the specified array schema.
     *
     * @param array $entity The array to be converted.
     * @return array
     * @throws \LogicException
     */
    public function convert($entity)
    {
        $this->normalizeSchema();
        $this->before($entity);
        switch ($this->mode)
        {
            case static::MODE_TRANSFORM:
                $this->transform($entity);
                break;
            case static::MODE_REDUCE:
                $this->reduce($entity);
                break;
            case static::MODE_EXCLUDE:
                $this->exclude($entity);
                break;
            default:
                throw new \LogicException(sprintf(static::ERR_CONVERTER_ARRAY_1, $this->mode));
        }
        $this->after($entity);
        return $entity;
    }
    
    /**
     * Transforms an array.
     *
     * @param array $array An array to transform.
     * @return void
     */
    protected function transform(array &$array)
    {
        $res = [];
        foreach ($this->nschema as list($in, $out))
        {
            if ($out === false)
            {
                continue;
            }
            $gens = $values = [];
            $indexes = $in['indexes'];
            foreach ($out['names'] as $name)
            {
                $gens[$name] = $this->getCacheIterator($array, $this->nschema[$this->names[$name]][0]);
            }
            @list($valueType, $valueName) = $out['value'];
            foreach ($this->getCacheIterator($array, $in) as list($value, $keys))
            {
                $a = &$res;
                foreach ($out['names'] as $name)
                {
                    $values[$name] = $gens[$name]->current()[0];
                    $gens[$name]->next();
                }
                $k = 0;
                $partlyExists = false;
                foreach ($out['keys'] as $key)
                {
                    if (is_array($key))
                    {
                        @list($type, $key) = $key;
                        if ($type === $this->keyNamePrefix)
                        {
                            $key = $indexes[$key === '' ? $this->uid . $k++ : $key];
                            if ($this->preservePartlyExistingElements && !isset($keys[$key]))
                            {
                                $partlyExists = true;
                                break;
                            }
                            $key = $keys[$key];
                        }
                        else if ($type === $this->indexKey)
                        {
                            $key = count($a);
                        }
                        else
                        {
                            $key = $key === $in['name'] ? $value : $values[$key];
                        }
                    }
                    if (!is_array($a))
                    {
                        $a = [];
                    }
                    @$a = &$a[$key];
                }
                if ($valueType === $this->valueNamePrefix)
                {
                    $value = $valueName === $in['name'] ? $value : $values[$valueName];
                }
                else
                {
                    $key = $indexes[$valueName === '' ? $this->uid . $k : $valueName];
                    if (empty($partlyExists))
                    {
                        $value = $keys[$key];
                    }
                }
                if (isset($out['cast']))
                {
                    $value = $this->cast($value, $out['cast']);
                }
                $a = $value;
            }
        }
        $array = $res;
    }
    
    /**
     * Reduces an array by preserving some elements in it.
     *
     * @param array $array An array to reduce.
     * @return void
     */
    protected function reduce(array &$array)
    {
        $res = [];
        foreach ($this->nschema as $in)
        {
            $indexes = $in['indexes'];
            $modifier = isset($in['modifier']) ? $in['modifier'] : null;
            $required = $this->ignoreNonExistingElements && $modifier === static::MODIFIER_REQUIRED ||
                        !$this->ignoreNonExistingElements && $modifier !== static::MODIFIER_IGNORE;
            foreach ($this->getIterator($array, $in['keys'], [], $required) as list($value, $keys))
            {
                $a = &$res;
                $k = 0;
                $partlyExists = false;
                foreach ($in['keys'] as $key)
                {
                    if (is_array($key))
                    {
                        $key = $indexes[$key[1] === '' ? $this->uid . $k++ : $key[1]];
                        if ($this->preservePartlyExistingElements && !isset($keys[$key]))
                        {
                            $partlyExists = true;
                            break;
                        }
                        $key = $keys[$key];
                    }
                    $a = &$a[$key];
                }
                if (isset($in['cast']))
                {
                    $value = $this->cast($value, $in['cast']);
                }
                $a = $value;
            }
        }
        $array = $res;
    }
    
    /**
     * Reduces an array by removing some elements from it.
     *
     * @param array $array An array to reduce.
     * @return void
     */
    protected function exclude(array &$array)
    {
        foreach ($this->nschema as $keys)
        {
            $this->removeElements($array, $keys);
        }
    }
    
    /**
     * This method is automatically invoked before array conversion.
     * You can use it to perform some preparations before the main transformation.
     *
     * @param array $entity An array to transform.
     * @return void
     */
    protected function before(array &$entity){}
    
    /**
     * This method is automatically invoked after array conversion.
     * You can use it to perform some additional manipulations with the array.
     *
     * @param array $entity An array to transform.
     * @return void
     */
    protected function after(array &$entity){}
    
    /**
     * Converts value to the given data type.
     *
     * @param mixed $value A value to convert.
     * @param string|array $type The desired data type information.
     * @return mixed
     * @throws \LogicException
     */
    protected function cast($value, $type)
    {
        $this->typeConverter->type = $type;
        return $this->typeConverter->convert($value);
    }
    
    /**
     * Normalizes (parses) the schema.
     *
     * @param bool $force Allows to ignore cache and normalize the schema again.
     * @return void
     * @throws \LogicException
     */
    protected function normalizeSchema(bool $force = false)
    {
        if (!$force && is_array($this->nschema))
        {
            return;
        }
        $this->uid = uniqid('key', true);
        $this->nschema = $this->cache = $this->names = [];
        if ($this->mode == static::MODE_TRANSFORM)
        {
            $this->normalizeTransformationSchema();
        }
        else if ($this->mode == static::MODE_REDUCE)
        {
            $this->normalizeReduceSchema();
        }
        else if ($this->mode == static::MODE_EXCLUDE)
        {
            $this->normalizeExclusionSchema();
        }
        else
        {
            throw new \LogicException(sprintf(static::ERR_CONVERTER_ARRAY_1, $this->mode));
        }
    }
    
    /**
     * Normalizes the transformation schema.
     *
     * @return void
     * @throws \LogicException
     */
    private function normalizeTransformationSchema()
    {
        $n = 0;
        foreach ($this->schema as $in => $out)
        {
            if (!is_string($in))
            {
                if (!is_array($out) || count($out) < 2)
                {
                    throw new \LogicException(static::ERR_CONVERTER_ARRAY_2_1);
                }
                @list($in, $out) = $out;
            }
            $this->normalizeInputElementDefinition($in);
            $this->normalizeOutputElementDefinition($out, $in['name']);
            $this->nschema[] = [$in, $out];
            if ($in['name'] !== '')
            {
                $this->names[$in['name']] = $n;
            }
            ++$n;
        }
        foreach ($this->nschema as list($in, $out))
        {
            if ($out === false)
            {
                continue;
            }
            $n = 0;
            foreach ($out['keys'] as $key)
            {
                if (is_array($key))
                {
                    @list($type, $key) = $key;
                    if ($type === $this->keyNamePrefix)
                    {
                        $k = $key === '' ? $this->uid . $n++ : $key;
                        if (!isset($in['indexes'][$k]))
                        {
                            $k = $key === '' ? $this->keyNamePrefix . ($n - 1) : $k;
                            throw new \LogicException(sprintf(static::ERR_CONVERTER_ARRAY_5, $k, $this->getElementDefinition($out), $this->getElementDefinition($in)));
                        }
                    }
                    else if ($type === $this->valueNamePrefix)
                    {
                        if ($key !== '' && !isset($this->names[$key]))
                        {
                            throw new \LogicException(sprintf(static::ERR_CONVERTER_ARRAY_6, $key, $this->getElementDefinition($out)));
                        }
                    }
                }
            }
            @list($valueType, $valueName) = $out['value'];
            if ($valueType === $this->valueNamePrefix)
            {
                if ($valueName !== '' && !isset($this->names[$valueName]))
                {
                    throw new \LogicException(sprintf(static::ERR_CONVERTER_ARRAY_6, $valueName, $this->getElementDefinition($out)));
                }
            }
            else
            {
                $k = $valueName === '' ? $this->uid . $n : $valueName;
                if (!isset($in['indexes'][$k]))
                {
                    $k = $valueName === '' ? $this->keyNamePrefix . $n : $k;
                    throw new \LogicException(sprintf(static::ERR_CONVERTER_ARRAY_5, $k, $this->getElementDefinition($out), $this->getElementDefinition($in)));
                }
            }
        }
    }
    
    /**
     * Normalizes the reduce schema.
     *
     * @return void
     * @throws \LogicException
     */
    private function normalizeReduceSchema()
    {
        foreach ($this->schema as $in)
        {
            $this->normalizeInputElementDefinition($in);
            $this->nschema[] = $in;
        }
    }
    
    /**
     * Normalizes the exclusion schema.
     *
     * @return void
     * @throw \LogicException
     */
    private function normalizeExclusionSchema()
    {
        foreach ($this->schema as $keys)
        {
            $this->nschema[] = $this->splitInputKeys($keys);
        }
    }
    
    /**
     * Normalizes the definition of input array elements.
     *
     * @param string|array $in The definition of input array elements.
     * @throws \LogicException
     */
    private function normalizeInputElementDefinition(&$in)
    {
        $tmp = [];
        if (is_array($in))
        {
            if (!isset($in['keys']))
            {
                throw new \LogicException(static::ERR_CONVERTER_ARRAY_2_2);
            }
            $tmp['indexes'] = null;
            $tmp['keys'] = $this->splitInputKeys($in['keys'], $tmp['indexes']);
            if ($this->mode === static::MODE_TRANSFORM)
            {
                if (isset($in['name']))
                {
                    $tmp['name'] = $in['name'];
                    $this->normalizeInputValueName($tmp['name']);
                }
                else
                {
                    $tmp['name'] = '';
                }
            }
            if (isset($in['cast']))
            {
                $tmp['cast'] = $in['cast'];
                $this->normalizeValueType($tmp['cast']);
            }
            if (!empty($in['modifier']))
            {
                $tmp['modifier'] = $in['modifier'];
            }
        }
        else
        {
            $part = $this->extractPart($in, $this->typeDelimiter);
            if ($part !== '')
            {
                if ($part === static::MODIFIER_REQUIRED || $part === static::MODIFIER_IGNORE)
                {
                    $modifier = $part;
                    $valueType = $this->extractPart($in, $this->typeDelimiter);
                }
                else
                {
                    $valueType = $part;
                    $modifier = $this->extractPart($in, $this->typeDelimiter);
                }
            }
            if ($this->mode === static::MODE_TRANSFORM)
            {
                $valueName = $this->extractPart($in, $this->valueNameDelimiter);
                $this->normalizeInputValueName($valueName);
                $tmp['name'] = $valueName;
            }
            $tmp['indexes'] = null;
            $tmp['keys'] = $this->splitInputKeys($in, $tmp['indexes']);   
            if (!empty($valueType))
            {
                $this->normalizeValueType($valueType);
                $tmp['cast'] = $valueType;
            }
            if (!empty($modifier))
            {
                $tmp['modifier'] = $modifier;
            }
        }
        $in = $tmp;
    }
    
    /**
     * Normalizes the definition of output array elements.
     *
     * @param string|array|bool $in The definition of output array elements.
     * @param string $inValueName The name of the input element value.
     * @return void
     * @throws \LogicException
     */
    private function normalizeOutputElementDefinition(&$out, string $inValueName)
    {
        if ($out === false)
        {
            return;
        }
        if (is_array($out))
        {
            if (!isset($out['keys']))
            {
                throw new \LogicException(static::ERR_CONVERTER_ARRAY_2_3);
            }
            $tmp = [];
            $tmp['names'] = null;
            $tmp['keys'] = $this->splitOutputKeys($out['keys'], $inValueName, $tmp['names']);
            if (isset($out['value']))
            {
                $tmp['value'] = $out['value'];
                $this->normalizeOutputValueName($tmp['value']);
            }
            else
            {
                $tmp['value'] = [$this->valueNamePrefix, $inValueName];
            }
            if (isset($out['cast']))
            {
                $tmp['cast'] = $out['cast'];
                $this->normalizeValueType($tmp['cast']);
            }
            $out = $tmp;
        }
        else
        {
            $valueType = $this->extractPart($out, $this->typeDelimiter);
            $valueName = $this->extractPart($out, $this->valueNameDelimiter);
            $names = null;
            $out = ['keys' => $this->splitOutputKeys($out, $inValueName, $names)];
            if ($valueName !== '')
            {
                $this->normalizeOutputValueName($valueName);
                $out['value'] = $valueName;
            }
            else
            {
                $out['value'] = [$this->valueNamePrefix, $inValueName];
            }
            $out['names'] = $names;
            if ($valueType !== '')
            {
                $this->normalizeValueType($valueType);
                $out['cast'] = $valueType;
            }
        }
        if ($out['value'][0] !== $this->keyNamePrefix && $out['value'][1] !== $inValueName)
        {
            $out['names'][] = $out['value'][1];
            $out['names'] = array_unique($out['names']);
        }
    }
    
    /**
     * Splits compound key of an input element definition to parts.
     *
     * @param string|array $keys The compound key definition.
     * @param mixed $indexes A variable to store info about named key part of the compound key.
     * @return array
     */
    private function splitInputKeys($keys, &$indexes = null) : array
    {
        $k = 0;
        $indexes = [];
        $keys = $this->splitKeys($keys);
        foreach ($keys as $n => &$key)
        {
            $this->normalizeInputKey($key);
            if (is_array($key))
            {
                $indexes[$key[1] === '' ? $this->uid . $k++ : $key[1]] = $n;
            }
        }
        return $keys;
    }
    
    /**
     * Splits compound key of an output element definition to parts.
     *
     * @param string|array $keys The compound key definition.
     * @param string $inValueName The name of the input elements' value.
     * @param mixed $names A variable to store info about named values encountering in the compound key definition.
     * @return array
     */
    private function splitOutputKeys($keys, string $inValueName, &$names = null) : array
    {
        $keys = $this->splitKeys($keys);
        $names = [];
        foreach ($keys as &$key)
        {
            $this->normalizeOutputKey($key);
            if (is_array($key) && $key[0] === $this->valueNamePrefix && $key[1] !== $inValueName)
            {
                $names[] = $key[1];
            }
        }
        return $keys;
    }
    
    /**
     * Normalizes a key definition of an input compound key definition.
     *
     * @param string|array An input key definition.
     * @return void
     * @throws \LogicException
     */
    private function normalizeInputKey(&$key)
    {
        if (is_array($key))
        {
            $type = array_shift($key);
            $key = array_shift($key);
            if ($type !== $this->keyNamePrefix)
            {
                throw new \LogicException(sprintf(static::ERR_CONVERTER_ARRAY_2_4, $type));
            }
            if (strlen($key) == 0)
            {
                $key = '';
            }
            $key = [$type, $key];
        }
        else
        {
            $isNamedKey = false;
            $len = strlen($this->keyNamePrefix);
            if (strncmp($key, $this->keyNamePrefix, $len) == 0)
            {
                $key = substr($key, $len);
                $isNamedKey = true;
            }
            if ($this->mode === static::MODE_TRANSFORM)
            {
                $key = strtr($key, [
                    '\\' . $this->keyDelimiter => $this->keyDelimiter,
                    '\\' . $this->keyNamePrefix => $this->keyNamePrefix,
                    '\\' . $this->typeDelimiter => $this->typeDelimiter,
                    '\\' . $this->valueNameDelimiter => $this->valueNameDelimiter
                ]);
            }
            else if ($this->mode === static::MODE_REDUCE)
            {
                $key = strtr($key, [
                    '\\' . $this->keyDelimiter => $this->keyDelimiter,
                    '\\' . $this->keyNamePrefix => $this->keyNamePrefix,
                    '\\' . $this->typeDelimiter => $this->typeDelimiter
                ]);
            }
            else
            {
                $key = strtr($key, [
                    '\\' . $this->keyDelimiter => $this->keyDelimiter,
                    '\\' . $this->keyNamePrefix => $this->keyNamePrefix
                ]);
            }
            if ($isNamedKey)
            {
                $key = [$this->keyNamePrefix, $key];
            }
        }
    }
    
    /**
     * Normalizes a key definition of an output compound key definition.
     *
     * @param string|array An output key definition.
     * @return void
     * @throws \LogicException
     */
    private function normalizeOutputKey(&$key)
    {
        if (is_array($key))
        {
            $type = array_shift($key);
            $key = array_shift($key);
            if ($type !== $this->keyNamePrefix && $type !== $this->valueNamePrefix && $type !== $this->indexKey)
            {
                throw new \LogicException(sprintf(static::ERR_CONVERTER_ARRAY_2_5, $type));
            }
            if (strlen($key) == 0)
            {
                $key = '';
            }
            $key = [$type, $key];
        }
        else if ($key === $this->indexKey)
        {
            $key = [$this->indexKey];
        }
        else if ($key === '\\' . $this->indexKey)
        {
            $key = $this->indexKey;
        }
        else
        {
            $type = null;
            $len = strlen($this->valueNamePrefix);
            if (strncmp($key, $this->valueNamePrefix, $len) == 0)
            {
                $key = substr($key, $len);
                $type = $this->valueNamePrefix;
            }
            else
            {
                $len = strlen($this->keyNamePrefix);
                if (strncmp($key, $this->keyNamePrefix, $len) == 0)
                {
                    $key = substr($key, $len);
                    $type = $this->keyNamePrefix;
                }
            }
            $key = strtr($key, [
                '\\' . $this->keyDelimiter => $this->keyDelimiter,
                '\\' . $this->keyNamePrefix => $this->keyNamePrefix,
                '\\' . $this->valueNamePrefix => $this->valueNamePrefix,
                '\\' . $this->typeDelimiter => $this->typeDelimiter,
                '\\' . $this->valueNameDelimiter => $this->valueNameDelimiter
            ]);
            if ($type)
            {
                $key = [$type, $key];
            }
        }
    }
    
    /**
     * Normalizes the value name of input elements of an array.
     *
     * @param string $name The input value name.
     * @return void
     */
    private function normalizeInputValueName(string &$name)
    {
        $name = strtr($name, [
            '\\' . $this->typeDelimiter => $this->typeDelimiter,
            '\\' . $this->valueNameDelimiter => $this->valueNameDelimiter
        ]);
    }
    
    /**
     * Normalizes the value name of output elements of an array.
     *
     * @param string|array $name The output value name.
     * @return void
     * @throws \LogicException
     */
    private function normalizeOutputValueName(&$name)
    {
        if (is_array($name))
        {
            $type = array_shift($name);
            $name = array_shift($name);
            if ($type !== $this->keyNamePrefix && $type !== $this->valueNamePrefix)
            {
                throw new \LogicException(sprintf(static::ERR_CONVERTER_ARRAY_2_6, $type));
            }
            if (strlen($name) == 0)
            {
                $name = '';
            }
            $name = [$type, $name];
        }
        else
        {
            $type = null;
            $len = strlen($this->valueNamePrefix);
            if (strncmp($name, $this->valueNamePrefix, $len) == 0)
            {
                $name = substr($name, $len);
                $type = $this->valueNamePrefix;
            }
            else
            {
                $len = strlen($this->keyNamePrefix);
                if (strncmp($name, $this->keyNamePrefix, $len) == 0)
                {
                    $name = substr($name, $len);
                    $type = $this->keyNamePrefix;
                }
            }
            if (empty($type))
            {
                throw new \LogicException(sprintf(static::ERR_CONVERTER_ARRAY_2_6, substr($name, 0, 1)));
            }
            $name = strtr($name, [
                '\\' . $this->typeDelimiter => $this->typeDelimiter,
                '\\' . $this->valueNameDelimiter => $this->valueNameDelimiter,
                '\\' . $this->keyNamePrefix => $this->keyNamePrefix,
                '\\' . $this->valueNamePrefix => $this->valueNamePrefix
            ]);
            $name = [$type, $name];
        }
    }
    
    /**
     * Normalizes the data type of an element value.
     *
     * @param string|array The data type information.
     * @return void
     */
    private function normalizeValueType(&$type)
    {
        if (!is_array($type))
        {
            $type = str_replace('\\' . $this->typeDelimiter, $this->typeDelimiter, $type);
            $type = explode($this->typeParamDelimiter, $type, 2);
        }
        @list($type, $param) = $type;
        $type = strtolower($type);
        if ($type == 'callback')
        {
            if ($param)
            {
                if (strncmp($param, '::', 2) == 0)
                {
                    $param = get_class($this) . $param;
                }
                else if (strncmp($param, '->', 2) == 0)
                {
                    $param = [$this, substr($param, 2)];
                }
            }
        }
        else if ($type == 'float' || $type == 'double' || $type == 'real')
        {
            if ($param === null)
            {
                $param = $this->precision;
            }
        }
        $type = [$type, $param];
    }
    
    /**
     * Splits a string into key array by the key delimiter.
     *
     * @param steing|array The key array or compound key string.
     * @return array
     * @throws \LogicException
     */
    private function splitKeys($keys) : array
    {
        $keys = is_array($keys) ? array_values($keys) : preg_split('/(?<!\\\)' . preg_quote($this->keyDelimiter) . '/', $keys);
        if (!$keys)
        {
            throw new \LogicException(static::ERR_CONVERTER_ARRAY_2_7);
        }
        return $keys;
    }
    
    /**
     * Splits a string into two parts by the given delimiter.
     *
     * @param string $str A string to split.
     * @param string $delimiter A delimiter.
     * @return string
     */
    private function extractPart(string &$str, string $delimiter) : string
    {
        $lstr = strlen($str);
        $ldel = strlen($delimiter);
        if ($ldel <= $lstr)
        {
            ++$lstr;
            $pos = $lstr - $ldel;
            while (false !== $pos = strrpos($str, $delimiter, $pos - $lstr))
            {
                if ($pos == 0 || $str[$pos - 1] != '\\')
                {
                    $res = substr($str, $pos + $ldel);
                    $str = substr($str, 0, $pos);
                    return $res;
                }
            }
        }
        return '';
    }
    
    /**
     * Returns string representation of an input or output element definition.
     *
     * @param array $def An element definition.
     * @return string
     */
    private function getElementDefinition(array $def) : string
    {
        $res = '';
        $isInputElement = empty($def['value']);
        $keys = [];
        foreach ($def['keys'] as $key)
        {
            $type = '';
            if (is_array($key))
            {
                @list($type, $key) = $key;
            }
            if ($isInputElement)
            {
                $key = strtr($key, [
                    $this->keyDelimiter => '\\' . $this->keyDelimiter,
                    $this->keyNamePrefix => '\\' . $this->keyNamePrefix,
                    $this->typeDelimiter => '\\' . $this->typeDelimiter,
                    $this->valueNameDelimiter => '\\' . $this->valueNameDelimiter
                ]);
            }
            else
            {
                $key = strtr($key, [
                    $this->keyDelimiter => '\\' . $this->keyDelimiter,
                    $this->keyNamePrefix => '\\' . $this->keyNamePrefix,
                    $this->valueNamePrefix => '\\' . $this->valueNamePrefix,
                    $this->typeDelimiter => '\\' . $this->typeDelimiter,
                    $this->valueNameDelimiter => '\\' . $this->valueNameDelimiter
                ]);
            }
            $keys[] = $type . $key;
        }
        $res = implode($this->keyDelimiter, $keys);
        if ($isInputElement)
        {
            if ($def['name'] !== '')
            {
                $name = strtr($def['name'], [
                    $this->typeDelimiter => '\\' . $this->typeDelimiter,
                    $this->valueNameDelimiter => '\\' . $this->valueNameDelimiter
                ]);
                $res .= $this->valueNameDelimiter . $name;
            }
            if (!empty($def['modifier']))
            {
                $res .= $this->typeDelimiter . $def['modifier'];
            }
        }
        else
        {
            @list($type, $name) = $def['value'];
            if ($type !== $this->valueNamePrefix || $name !== '')
            {
                $name = strtr($name, [
                    $this->typeDelimiter => '\\' . $this->typeDelimiter,
                    $this->valueNameDelimiter => '\\' . $this->valueNameDelimiter,
                    $this->keyNamePrefix => '\\' . $this->keyNamePrefix,
                    $this->valueNamePrefix => '\\' . $this->valueNamePrefix
                ]);
                $res .= $this->valueNameDelimiter . $type . $name;
            }
        }
        if (!empty($def['cast']))
        {
            @list($type, $param) = $def['cast'];
            $type = str_replace($this->typeDelimiter, '\\' . $this->typeDelimiter, $type . $this->typeParamDelimiter . $param);
            $res .= $this->typeDelimiter . $type;
        }
        return $res;
    }
    
    /**
     * Caches sequentially returning array elements according to their keys.
     *
     * @param array $value the array to iterate.
     * @param array $keys Keys of array elements to iterate.
     * @return Generator
     */
    private function getCacheIterator(array $value, array $in) : \Generator
    {
        if ($in['name'] !== '' && isset($this->cache[$in['name']]))
        {
            foreach ($this->cache[$in['name']] as $value)
            {
                yield $value;
            }
        }
        else
        {
            $modifier = isset($in['modifier']) ? $in['modifier'] : null;
            $required = $this->ignoreNonExistingElements && $modifier === static::MODIFIER_REQUIRED ||
                        !$this->ignoreNonExistingElements && $modifier !== static::MODIFIER_IGNORE;
            foreach ($this->getIterator($value, $in['keys'], [], $required) as $value)
            {
                if (isset($in['cast']))
                {
                    $value[0] = $this->cast($value[0], $in['cast']);
                }
                if ($in['name'] !== '')
                {
                    $this->cache[$in['name']][] = $value;
                }
                yield $value;
            }
        }
    }
    
    /**
     * Sequentially returns the array elements according to their keys.
     *
     * @param array $value The array to iterate.
     * @param array $keys Keys of array elements to iterate.
     * @param array $keyValues Values of an array element's keys.
     * @param bool $required Determines whether to throw exception if some array element does not exist.
     * @return Generator
     */
    private function getIterator(array $value, array $keys, array $keyValues = [], bool $required = true) : \Generator
    {
        foreach ($keys as $n => $key)
        {
            if ($this->treatObjectAsArray && is_object($value))
            {
                $value = (array)$value;
            }
            if (is_array($key))
            {
                if (!is_array($value))
                {
                    $this->throwInvalidElementException(static::ERR_CONVERTER_ARRAY_4, $keyValues);
                }
                $keys = array_slice($keys, $n + 1);
                if ($this->preservePartlyExistingElements && count($value) == 0)
                {
                    yield [$value, $keyValues];
                    return;
                }
                if (count($keys))
                {
                    foreach ($value as $k => $v)
                    {
                        if ($this->treatObjectAsArray && is_object($v))
                        {
                            $v = (array)$v;
                        }
                        foreach ($this->getIterator($v, $keys, array_merge($keyValues, [$k]), $required) as $val) 
                        {
                            yield $val;
                        }
                    }
                }
                else
                {
                    foreach ($value as $k => $v)
                    {
                        yield [$v, array_merge($keyValues, [$k])];
                    }
                }
                return;
            }
            else
            {
                if (!is_array($value) || !array_key_exists($key, $value))
                {
                    if ($required)
                    {
                        $this->throwInvalidElementException(static::ERR_CONVERTER_ARRAY_3, array_merge($keyValues, [$key]));
                    }
                    $value = $this->preservePartlyExistingElements ? [] : null;
                }
                else
                {
                    $value = $value[$key];
                }
                $keyValues[] = $key;
            }
        }
        yield [$value, $keyValues];
        return;
    }
    
    /**
     * Removes array elements by their keys.
     *
     * @param array $array An array whose elements will be removed.
     * @param array $keys The elements' keys.
     * @param array $keyValues Values of an array element's keys.
     * @return void
     * @throws \RuntimeException
     */
    private function removeElements(array &$array, array $keys, array $keyValues = [])
    {
        $a = &$array;
        $last = count($keys) - 1;
        foreach ($keys as $n => $key)
        {
            if ($this->treatObjectAsArray && is_object($a))
            {
                $a = (array)$a;
            }
            if (is_array($key))
            {
                if (!is_array($a))
                {
                    $this->throwInvalidElementException(static::ERR_CONVERTER_ARRAY_4, $keyValues);
                }
                if ($n == $last)
                {
                    $a = [];
                }
                else
                {
                    $keys = array_slice($keys, $n + 1);
                    foreach ($a as $k => &$v)
                    {
                        if ($this->treatObjectAsArray && is_object($v))
                        {
                            $v = (array)$v;
                        }
                        $this->removeElements($v, $keys, array_merge($keyValues, [$k])); 
                    }
                }
                return;
            }
            if ($n == $last)
            {
                unset($a[$key]);
            }
            else
            {
                if (!is_array($a) || !array_key_exists($key, $a))
                {
                    return;
                }
                $a = &$a[$key];
                $keyValues[] = $key;
            }
        }
    }
    
    /**
     * Throws an exception for non-existing or invalid array elements.
     *
     * @param string $message The error message template.
     * @param array $keyValues Values of an array element's keys.
     * @throws \RuntimeException
     */
    private function throwInvalidElementException(string $message, array $keyValues)
    {
        $key = '';
        foreach ($keyValues as $k)
        {
            $key .= '[' . ((string)$k == (string)(int)$k ? (int)$k : "'" . $k . "'") . ']';
        }
        throw new \RuntimeException(sprintf($message, $key));
    }
}