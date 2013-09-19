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

namespace Aleph\Net;

use Aleph\Core;

/**
 * ValidatorJSON compares the given JSON with another JSON or checks whether the given JSON has the specified structure.
 * The logic of JSON schema validation is determined by http://json-schema.org/latest/json-schema-validation.html#anchor17 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.net
 */
class ValidatorJSON extends Validator
{
  /**
   * Error message templates.
   */
  const ERR_VALIDATOR_JSON_1 = 'The validating entity is not valid JSON.';
  const ERR_VALIDATOR_JSON_2 = 'Property $schema has invalid JSON schema.';
  const ERR_VALIDATOR_JSON_3 = 'Property $json has invalid JSON string.';
  const ERR_VALIDATOR_JSON_4 = 'Property: "[{var}]". Type "[{var}]" is invalid.';
  const ERR_VALIDATOR_JSON_5 = 'Property: "[{var}]". Use of "exclusiveMinimum" requires presence of "minimum".';
  const ERR_VALIDATOR_JSON_6 = 'Property: "[{var}]". Use of "exclusiveMaximum" requires presence of "maximum".';
  const ERR_VALIDATOR_JSON_7 = 'Property: "[{var}]". Invalid "multipleOf" value, should be a number that greater than 0.';
  const ERR_VALIDATOR_JSON_8 = 'Property: "[{var}]". Invalid item values.';
  const ERR_VALIDATOR_JSON_9 = 'Property: "[{var}]". Dependency value should either be an object or an array.';
  const ERR_VALIDATOR_JSON_10 = 'Property: "[{var}]". Elements of "[{var}]" value should be a valid JSON Schema.';
  const ERR_VALIDATOR_JSON_11 = 'Property: "[{var}]". Value of keyword "not" should be a valid JSON Schema.';
  const ERR_VALIDATOR_JSON_12 = '$ref "[{var}]" should be a valid a JSON Reference.';
  
  /**
   * JSON string or path to the JSON file to be compared with.
   *
   * @var string $json
   * @access public
   */
  public $json = null;
  
  /**
   * The JSON schema which the validating JSON should correspond to. 
   *
   * @var string $schema
   * @access public
   */
  public $schema = null;
  
  /**
   * The parsed JSON schema for comparison.
   *
   * @var \stdClass $rootSchema
   * @access private
   */
  private $rootSchema = null;
  
   /**
   * Validates a JSON string.
   *
   * @param string $entity - the JSON for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->empty && $this->isEmpty($entity)) return $this->reason = true;
    $original = $entity;
    $entity = json_decode($entity);
    if ($entity === null && strtolower($original) != 'null') throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_1');
    if ($this->schema !== null)
    {
      $schema = json_decode(is_file($this->schema) ? file_get_contents($this->schema) : $this->schema);
      if ($schema === null) throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_2');
      if (!$this->checkSchema($entity, $schema)) return false;
    }
    if ($this->json === null) return $this->reason = true;
    $entity = json_decode($entity, true);
    $original = is_file($this->json) ? file_get_contents($this->json) : $this->json;
    $json = json_decode($original, true);
    if ($json === null && $original !== null) throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_3');
    if ($entity === $json) return $this->reason = true;
    $this->reason = ['code' => 1, 'reason' => 'JSON are not equal'];
    return false;
  }
  
  /**
   * Checks JSON schema of the given JSON entity.
   * The method returns TRUE if the given JSON has the required scheme and FALSE otherwise.
   *
   * @param mixed $entity - the JSON entity to validate.
   * @param \stdClass $schema - the JSON schema for comparison.
   * @return boolean
   * @access protected
   */
  protected function checkSchema($entity, \stdClass $schema)
  {
    $this->rootSchema = $schema;
    $this->reason = ['code' => 0, 'reason' => 'invalid schema', 'details' => []];
    return $this->checkType($entity, $schema, 'root');
  }
  
  /**
   * Checks data type of the entity.
   * The method returns TRUE if the entity has the required data type and FALSE otherwise.
   *
   * @param mixed $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkType($entity, \stdClass $schema, $path)
  {
    if (!$this->checkFormat($entity, $schema, $path)) return false;
    if (!$this->checkEnum($entity, $schema, $path)) return false;
    if (!$this->checkAllOf($entity, $schema, $path)) return false;
    if (!$this->checkAnyOf($entity, $schema, $path)) return false;
    if (!$this->checkOneOf($entity, $schema, $path)) return false;
    $types = isset($schema->type) ? strtolower($schema->type) : 'any';
    if (!is_array($types)) $types = array($types);
    $flag = false;
    foreach ($types as $type)
    {
      if ($type == 'string')
      {
        if (is_string($entity))
        {
          if (!$this->checkMinLength($entity, $schema, $path)) return false;
          if (!$this->checkMaxLength($entity, $schema, $path)) return false;
          if (!$this->checkPattern($entity, $schema, $path)) return false;
          $flag = true;
          break;
        }
      }
      else if ($type == 'number')
      {
        if (is_numeric($entity))
        {
          if (!$this->checkMinimum($entity, $schema, $path)) return false;
          if (!$this->checkMaximum($entity, $schema, $path)) return false;
          if (!$this->checkMultipleOf($entity, $schema, $path)) return false;
          $flag = true;
          break;
        }
      }
      else if ($type == 'integer')
      {
        if (is_int($entity))
        {
          if (!$this->checkMinimum($entity, $schema, $path)) return false;
          if (!$this->checkMaximum($entity, $schema, $path)) return false;
          if (!$this->checkMultipleOf($entity, $schema, $path)) return false;
          $flag = true;
          break;
        }
      }
      else if ($type == 'boolean')
      {
        if (is_bool($entity))
        {
          $flag = true;
          break;
        }
      }
      else if ($type == 'array')
      {
        if (is_array($entity))
        {
          if (!$this->checkMinItems($entity, $schema, $path)) return false;
          if (!$this->checkMaxItems($entity, $schema, $path)) return false;
          if (!$this->checkUniqueItems($entity, $schema, $path)) return false;
          if (!$this->checkItems($entity, $schema, $path)) return false;
          $flag = true;
          break;
        }
      }
      else if ($type == 'object')
      {
        if (is_object($entity))
        {
          $properties = get_object_vars($entity);
          if (!$this->checkMaxProperties($properties, $schema, $path)) return false;
          if (!$this->checkMinProperties($properties, $schema, $path)) return false;
          if (!$this->checkRequiredProperties($properties, $schema, $path)) return false;
          if (!$this->checkProperties($properties, $schema, $path)) return false;
          if (!$this->checkDependencies($entity, $schema, $path)) return false;
          $flag = true;
          break;
        }
      }
      else if ($type == 'null')
      {
        if ($entity === null)
        {
          $flag = true;
          break;
        } 
      }
      else if ($type == 'any')
      {
        $flag = true;
        break;
      }
      else
      {
        throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_4', $path, $type);
      }
    }
    if (!$flag) 
    {
      if (count($types) > 1) 
      {
        foreach ($types as &$type) $type = '"' . $type . '"';
        $this->reason['details'][] = 'Property "' . $path . '" should be one of the following types: ' . implode(', ', $types) . '.';
      }
      else
      {
        $this->reason['details'][] = 'Property "' . $path . '" should be ' . array_pop($types) . '.';
      }
      return false;
    }
    return true;
  }
  
  /**
   * Checks matching of the entity to the given regular expression pattern.
   * The method returns TRUE if the entity matches the required regular expression pattern and FALSE otherwise.
   *
   * @param string $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkPattern($entity, \stdClass $schema, $path)
  {
    if (!empty($schema->pattern))
    {
      if (!preg_match($schema->pattern, $entity))
      {
        $this->reason['details'][] = 'Property "' . $path . '" does not match pattern "' . $schema->pattern . '".';
        return false; 
      }
    }
    return true;
  }
  
  /**
   * Checks whether the length of the entity greater, or equal to, than the given minimum length.
   * The method returns TRUE if the entity length not less of the minimum length and FALSE otherwise.
   *
   * @param string $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkMinLength($entity, $schema, $path)
  {
    if (isset($schema->minLength))
    {
      if (strlen($entity) < $schema->minLength)
      {
        $this->reason['details'][] = 'Property "' . $path . '" should be at least ' . $schema->minLength . ' characters long.';
        return false;
      }
    }
    return true;
  }

  /**
   * Checks whether the length of the entity less, or equal to, than the given maximum length.
   * The method returns TRUE if the entity length not greater of the maximum length and FALSE otherwise.
   *
   * @param string $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkMaxLength($entity, \stdClass $schema, $path)
  {
    if (isset($schema->maxLength))
    {
      if (strlen($entity) > $schema->maxLength)
      {
        $this->reason['details'][] = 'Property "' . $path . '" should be at most ' . $schema->maxLength . ' characters long.';
        return false;
      }
    }
    return true;
  }
  
  /**
   * Checks whether the entity value has the minimum limit.
   * The method returns TRUE if the entity value matches its minimum limit and FALSE otherwise.
   *
   * @param float | integer $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkMinimum($entity, \stdClass $schema, $path)
  {
    if (isset($schema->minimum))
    {
      if (isset($schema->exclusiveMinimum) && $schema->exclusiveMinimum)
      {
        if ($entity <= $schema->minimum)
        {
          $this->reason['details'][] = 'Property "' . $path . '" should have a minimum value strictly greater than the value of ' . $schema->minimum;
          return false;
        }
      }
      else if ($entity < $schema->minimum)
      {
        $this->reason['details'][] = 'Property "' . $path . '" should have a minimum value greater than, or equal to, the value of ' . $schema->minimum;
        return false;
      }
    }
    else if (isset($schema->exclusiveMinimum))
    {
      throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_5', $path);
    }
    return true;
  }
  
  /**
   * Checks whether the entity value has the maximum limit.
   * The method returns TRUE if the entity value matches its maximum limit and FALSE otherwise.
   *
   * @param float | integer $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkMaximum($entity, \stdClass $schema, $path)
  {
    if (isset($schema->maximum))
    {
      if (isset($schema->exclusiveMaximum) && $schema->exclusiveMaximum)
      {
        if ($entity >= $schema->maximum)
        {
          $this->reason['details'][] = 'Property "' . $path . '" should have a maximum value strictly lower than the value of ' . $schema->maximum;
          return false;
        }
      }
      else if ($entity > $schema->maximum)
      {
        $this->reason['details'][] = 'Property "' . $path . '" should have a minimum value lower than, or equal to, the value of ' . $schema->maximum;
        return false;
      }
    }
    else if (isset($schema->exclusiveMaximum))
    {
      throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_6', $path);
    }
    return true;
  }
  
  /**
   * Checks whether the entity value is multiple of the given number.
   * The method returns TRUE if the entity value is multiple of the given number and FALSE otherwise.
   *
   * @param float | integer $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkMultipleOf($entity, \stdClass $schema, $path)
  {
    if (isset($schema->multipleOf))
    {
      if (!is_numeric($schema->multipleOf) || $schema->multipleOf == 0)
      {
        throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_7', $path);
      }
      if (fmod($entity, $schema->multipleOf) != 0)
      {
        $this->reason['details'][] = 'Property "' . $path . ' should be a multiple of ' . $schema->multipleOf;
        return false;
      }
    }
    return true;
  }
  
  /**
   * Checks whether the entity size greater, or equal to, than the given minimum value.
   * The method returns TRUE if the number of the entity items not less than the given minimum value and FALSE otherwise.
   *
   * @param array $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkMinItems(array $entity, \stdClass $schema, $path)
  {
    if (isset($schema->minItems))
    {
      if (count($entity) < $schema->minItems)
      {
        $this->reason['details'][] = 'Property "' . $path . '" should have minimum of ' . $schema->minItems . ' elements.';
        return false;
      }
    }
    return true;
  }
  
  /**
   * Checks whether the entity size less, or equal to, than the given maximum value.
   * The method returns TRUE if the number of the entity items not greater than the given maximum value and FALSE otherwise.
   *
   * @param array $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkMaxItems(array $entity, \stdClass $schema, $path)
  {
    if (isset($schema->maxItems))
    {
      if (count($entity) > $schema->maxItems)
      {
        $this->reason['details'][] = 'Property "' . $path . '" should have maximum of ' . $schema->maxItems . ' elements.';
        return false;
      }
    }
    return true;
  }
  
  /**
   * Checks whether the entity has only unique items.
   * The method returns TRUE if the entity has only unique items and FALSE otherwise.
   *
   * @param array $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkUniqueItems(array $entity, \stdClass $schema, $path)
  {
    if (isset($schema->uniqueItems) && $schema->uniqueItems)
    {
      if (count(array_unique($entity)) != count($entity))
      {
        $this->reason['details'][] = 'Property "' . $path . '" should have only unique items.';
        return false;
      }
    }
    return true;
  }

  /**
   * Checks whether the entity items match has the specified JSON schemes.
   * The method returns TRUE if the entity items match the specified JSON schemes and FALSE otherwise.
   *
   * @param array $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkItems($entity, \stdClass $schema, $path)
  {
    if (empty($schema->items)) return true;
    if (is_array($schema->items))
    {
      foreach ($entity as $key => $value)
      {
        if (array_key_exists($key, $schema->items)) 
        {
          if (!$this->checkType($value, $this->resolveRef($schema->items[$key]), $path . '[' . $key . ']')) return false;
        }
        else if (isset($schema->additionalItems))
        {
          if ($schema->additionalItems === false)
          {
            $this->reason['details'][] = 'Property "' . $path . '[' . $key . ']" is not defined and the definition does not allow additional items.';
            return false;
          }
          if (is_object($schema->additionalItems))
          {
            if (!$this->checkType($value, $this->resolveRef($schema->additionalItems), $path . '[' . $key . ']')) return false; 
          }
        }
      }
      return true;
    }
    else if (is_object($schema->items))
    {
      foreach ($entity as $key => $value)
      {
        if (!$this->checkType($value, $this->resolveRef($schema->items), $path . '[' . $key . ']')) return false;
      }
      return true;
    }
    throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_8', $path);
  }
  
  /**
   * Checks whether the number of the entity properties greater, or equal to, than the given minimum limit.
   * The method returns TRUE if the number of the entity items not less that the given minimum limit and FALSE otherwise.
   *
   * @param array $properties - the entity properties.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkMinProperties(array $properties, \stdClass $schema, $path)
  {
    if (isset($schema->minProperties))
    {
      if (count($properties) < $schema->minProperties)
      {
        $this->reason['details'][] = 'Object "' . $path . '" should have minimum of ' . $schema->minProperties . ' properties.';
        return false;
      }
    }
    return true; 
  }
  
  /**
   * Checks whether the number of the entity properties less, or equal to, than the given maximum limit.
   * The method returns TRUE if the number of the entity items not greater that the given maximum limit and FALSE otherwise.
   *
   * @param array $properties - the entity properties.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkMaxProperties(array $properties, \stdClass $schema, $path)
  {
    if (isset($schema->maxProperties))
    {
      if (count($properties) > $schema->maxProperties)
      {
        $this->reason['details'][] = 'Object "' . $path . '" should have maximum of ' . $schema->maxProperties . ' properties.';
        return false;
      }
    }
    return true; 
  }
  
  /**
   * Checks whether the entity has all of the mandatory properties.
   * The method returns TRUE if the entity has all required properties and FALSE otherwise.
   *
   * @param array $properties - the entity properties.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkRequiredProperties(array $properties, \stdClass $schema, $path)
  {
    if (isset($schema->required))
    {
      foreach ((array)$schema->required as $property)
      {
        if (!array_key_exists($property, $properties))
        {
          $this->reason['details'][] = 'Object "' . $path . '" does not have the required property "' . $property . '".';
          return false;
        }
      }
    }
    return true;
  }
  
  /**
   * Checks whether the entity properties match the specified JSON schemes.
   * The method returns TRUE if the entity properties match the specified JSON schemes and FALSE otherwise.
   *
   * @param array $properties - the entity properties.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkProperties(array $properties, \stdClass $schema, $path)
  {
    $validProperties = [];
    if (isset($schema->patternProperties))
    {
      foreach ((array)$schema->patternProperties as $pattern => $newSchema)
      {
        foreach ($properties as $property => $value)
        {
          if (preg_match($pattern, $property) && empty($validProperties[$property])) $validProperties[$property] = $newSchema;
        }
      }
    }
    foreach ($properties as $property => $value)
    {
      if (isset($schema->properties) && property_exists($schema->properties, $property)) 
      {
        if (!$this->checkType($value, $this->resolveRef($schema->properties->{$property}), $path . '.' . $property)) return false;
      }
      else if (isset($validProperties[$property]))
      {
        if (!$this->checkType($value, $this->resolveRef($validProperties[$property]), $path . '.' . $property)) return false;
      }
      else if (isset($schema->additionalProperties))
      {
        if ($schema->additionalProperties === false)
        {
          $this->reason['details'][] = 'Property "' . $path . '.' . $key . '" is not defined and the definition does not allow additional properties.';
          return false;
        }
        if (is_object($schema->additionalProperties))
        {
          if (!$this->checkType($value, $this->resolveRef($schema->additionalProperties), $path . '.' . $key)) return false; 
        }
      }
    }
    return true;
  }
  
  /**
   * Checks dependencies of the entity properties.
   * The method returns TRUE if the dependencies of the entity properties are successfully validated and FALSE otherwise.
   *
   * @param array $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkDependencies(\stdClass $entity, \stdClass $schema, $path)
  {
    if (isset($schema->dependencies))
    {
      foreach ($schema->dependencies as $property => $dependency)
      {
        if (!property_exists($entity, $property)) continue;
        if (is_object($dependency))
        {
          if (!$this->checkType($entity, $this->resolveRef($dependency), $path)) return false;
        }
        else if (is_array($dependency))
        {
          foreach ($dependency as $prop)
          {
            if (!property_exists($entity, $prop))
            {
              $this->reason['details'][] = 'Property "' . $path . '" depends on property "' . $property . '" and should have property "' . $prop . '".';
              return false;
            }
          }
        }
        else
        {
          throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_9', $path);
        }
      }
    }
    return true;
  }
  
  /**
   * Checks whether the entity value is one of the given enumeration values.
   * The method returns TRUE if the entity value is one of the enumeration values and FALSE otherwise.
   *
   * @param mixed $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkEnum($entity, \stdClass $schema, $path)
  {
    if (empty($schema->enum)) return true;
    if (is_object($entity))
    {
      foreach ((array)$schema->enum as $option)
      {
        if (is_object($option) && $entity == $option) return true;
      }
    }
    else
    {
      foreach ((array)$schema->enum as $option)
      {
        if ($entity === $option) return true;
      }
    }
    $this->reason['details'][] = 'Property "' . $path . '" has the value that does not match the enumeration options.';
    return false;
  }
  
  /**
   * Checks whether the entity matches all of the given JSON schemes.
   * The method returns TRUE if the entity matches all of the given JSON schemes and FALSE otherwise.
   *
   * @param mixed $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkAllOf($entity, \stdClass $schema, $path)
  {
    if (isset($schema->allOf))
    {
      foreach ((array)$schema->allOf as $newSchema)
      {
        if (!is_object($newSchema)) throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_10', $path, 'allOf');
        if (!$this->checkType($entity, $this->resolveRef($newSchema), $path)) return false;
      }
    }
    return true;
  }
  
  /**
   * Checks whether the entity matches any of the given JSON schemes.
   * The method returns TRUE if the entity matches any of the given JSON schemes and FALSE otherwise.
   *
   * @param mixed $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkAnyOf($entity, \stdClass $schema, $path)
  {
    if (isset($schema->anyOf))
    {
      foreach ((array)$schema->anyOf as $newSchema)
      {
        if (!is_object($newSchema)) throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_10', $path, 'anyOf');
        if (!$this->checkType($entity, $this->resolveRef($newSchema), $path)) continue;
      }
    }
    return true;
  }
  
  /**
   * Checks whether the entity matches exactly one of the given JSON schemes.
   * The method returns TRUE if the entity matches exactly one of the given JSON schemes and FALSE otherwise.
   *
   * @param mixed $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkOneOf($entity, \stdClass $schema, $path)
  {
    if (isset($schema->oneOf))
    {
      $flag = false;
      foreach ((array)$schema->oneOf as $newSchema)
      {
        if (!is_object($newSchema)) throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_10', $path, 'oneOf');
        if (!$this->checkType($entity, $this->resolveRef($newSchema), $path)) continue;   
        if ($flag)
        {
          $this->reason['details'][] = 'Property "' . $path . '" validates successfully more than one times.';
          return false;
        }
        $flag = true;
      }
      if (!$flag)
      {
        $this->reason['details'][] = 'Validation of the property "' . $path . '" is failed according to value of the keyword "oneOf".';
        return false;
      }
    }
    return true;
  }
  
  /**
   * Checks whether the entity does not match the given JSON schema.
   * The method returns TRUE if the entity does not match the given JSON schema and FALSE otherwise.
   *
   * @param mixed $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkNot($entity, \stdClass $schema, $path)
  {
    if (isset($schema->not))
    {
      if (!is_object($schema->not)) throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_10', $path, 'not');
      if ($this->checkType($entity, $this->resolveRef($schema->not), $path)) 
      {
        $this->reason['details'][] = 'Property "' . $path . '" should not validate successfully against the schema defined by keyword "not".';
        return false; 
      }
    }
    return true;
  }
  
  /**
   * Checks whether the entity value has the specified format.
   * The method returns TRUE if the entity value has the specified format and FALSE otherwise.
   *
   * @param string $entity - the entity to check.
   * @param \stdClass $schema - the required JSON schema.
   * @param string $path - the property name of the entity.
   * @return boolean
   * @access private
   */
  private function checkFormat($entity, \stdClass $schema, $path)
  {
    if (isset($schema->format))
    {
      switch (strtolower($schema->format))
      {
        case 'datetime':
        case 'date-time':
          if (!\DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $entity) &&
              !\DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $entity) &&
              !\DateTime::createFromFormat('Y-m-d\TH:i:sP', $entity) &&
              !\DateTime::createFromFormat('Y-m-d\TH:i:sO', $entity))
          {
            $this->reason['details'][] = 'Property "' . $path . '" has invalid date-time format and should have format YYYY-MM-DDThh:mm:ssZ or YYYY-MM-DDThh:mm:ss+hh:mm';
            return false;
          }
          break;
        case 'email':
          if (filter_var($entity, FILTER_VALIDATE_EMAIL) === false)
          {
            $this->reason['details'][] = 'Property "' . $path . '" has invalid email format.';
            return false;
          }
          break;
        case 'hostname':
        case 'host-name':
          if (!preg_match('/\A[_a-z]+\.([_a-z]+\.?)+\z/i', $entity))
          {
            $this->reason['details'][] = 'Property "' . $path . '" has invalid hostname format.';
            return false;
          }
          break;
        case 'ip':
        case 'ipv4':
          if (filter_var($entity, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false)
          {
            $this->reason['details'][] = 'Property "' . $path . '" has invalid ipv4 format.';
            return false;
          }
          break;
        case 'ipv6':
          if (filter_var($entity, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false)
          {
            $this->reason['details'][] = 'Property "' . $path . '" has invalid ipv6 format.';
            return false;
          }
          break;
        case 'uri':
          if (filter_var($entity, FILTER_VALIDATE_URL) === false)
          {
            $this->reason['details'][] = 'Property "' . $path . '" has invalid URI format.';
            return false;
          }
          break;
      }
    }
    return true;
  }
  
  /**
   * Retrieves (and returns) new JSON scheme from the $ref property of the given JSON scheme.
   * If the given JSON scheme does not have the $ref property the method will return the given JSON scheme.
   *
   * @param \stdClass $schema - the given JSON schema.
   * @return \stdClass
   * @access private
   */
  private function resolveRef(\stdClass $schema)
  {
    if (!property_exists($schema, '$ref') || strlen($schema->{'$ref'}) == 0) return $schema;
    $ref = $schema->{'$ref'};
    if ($ref[0] != '#')
    {
      $original = trim(file_get_contents($ref));
      $new = json_decode($original);
      if ($new === null && strtolower($original) != 'null') throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_12', $ref);
    }
    else
    {
      $ref = explode('/', $ref);
      array_shift($ref);
      $new = $this->rootSchema;
      foreach ($ref as $property) $new = $new->{$property};
    }
    return $new;
  }
}