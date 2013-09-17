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
    $entity = json_decode($entity, true);
    if ($entity === null && $original !== null) throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_1');
    if ($this->schema !== null)
    {
      $schema = json_decode(is_file($this->schema) ? file_get_contents($this->schema) : $this->schema);
      if ($schema === null) throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_2');
      if (!$this->checkSchema(json_decode($entity), $schema)) return false;
    }
    if ($this->json === null) return $this->reason = true;
    $original = is_file($this->json) ? file_get_contents($this->json) : $this->json;
    $json = json_decode($original, true);
    if ($json === null && $original !== null) throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_3');
    if ($entity === $json) return $this->reason = true;
    $this->reason = ['code' => 1, 'reason' => 'JSON are not equal'];
    return false;
  }
  
  /**
   * Checks JSON schema of the given JSON entity.
   *
   * @return boolean
   * @access protected
   */
  protected function checkSchema($entity, $schema)
  {
    $this->reason = ['code' => 0, 'reason' => 'invalid schema', 'details' => null];
    return $this->checkType($entity, $schema, 'root');
  }
  
  private function checkType($entity, $schema, $path)
  {
    if (!$this->checkEnum($entity, $schema, $path)) return false;
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
          if (!$this->checkObject($entity, $schema, $path)) return false;
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
      $this->reason['details'] = 'Property "' . $path . '" should be one of the following types: "string", "number", "integer", "boolean", "array", "object", "null" or "any".';
      return false;
    }
    return true;
  }
  
  private function checkPattern($entity, $schema, $path)
  {
    if (!empty($schema->pattern))
    {
      if (!preg_match($schema->pattern, $entity))
      {
        $this->reason['details'] = 'Property "' . $path . '" does not match pattern "' . $schema->pattern . '".';
        return false; 
      }
    }
    return true;
  }
  
  private function checkMinLength($entity, $schema, $path)
  {
    if (isset($schema->minLength))
    {
      if (strlen($entity) < $schema->minLength)
      {
        $this->reason['details'] = 'Property "' . $path . '" should be at least ' . $schema->minLength . ' characters long.';
        return false;
      }
    }
    return true;
  }

  private function checkMaxLength($entity, $schema, $path)
  {
    if (isset($schema->maxLength))
    {
      if (strlen($entity) > $schema->maxLength)
      {
        $this->reason['details'] = 'Property "' . $path . '" should be at most ' . $schema->maxLength . ' characters long.';
        return false;
      }
    }
    return true;
  }
  
  private function checkMinimum($entity, $schema, $path)
  {
    if (isset($schema->minimum))
    {
      if (isset($schema->exclusiveMinimum) && $schema->exclusiveMinimum)
      {
        if ($entity <= $schema->minimum)
        {
          $this->reason['details'] = 'Property "' . $path . '" should have a minimum value strictly greater than the value of ' . $schema->minimum;
          return false;
        }
      }
      else if ($entity < $schema->minimum)
      {
        $this->reason['details'] = 'Property "' . $path . '" should have a minimum value greater than, or equal to, the value of ' . $schema->minimum;
        return false;
      }
    }
    else if (isset($schema->exclusiveMinimum))
    {
      throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_5', $path);
    }
    return true;
  }
  
  private function checkMaximum($entity, $schema, $path)
  {
    if (isset($schema->maximum))
    {
      if (isset($schema->exclusiveMaximum) && $schema->exclusiveMaximum)
      {
        if ($entity >= $schema->maximum)
        {
          $this->reason['details'] = 'Property "' . $path . '" should have a maximum value strictly lower than the value of ' . $schema->maximum;
          return false;
        }
      }
      else if ($entity > $schema->maximum)
      {
        $this->reason['details'] = 'Property "' . $path . '" should have a minimum value lower than, or equal to, the value of ' . $schema->maximum;
        return false;
      }
    }
    else if (isset($schema->exclusiveMaximum))
    {
      throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_6', $path);
    }
    return true;
  }
  
  private function checkMultipleOf($entity, $schema, $path)
  {
    if (isset($schema->multipleOf))
    {
      if (!is_numeric($schema->multipleOf) || $schema->multipleOf == 0)
      {
        throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_7', $path);
      }
      if (fmod($entity, $schema->multipleOf) != 0)
      {
        $this->reason['details'] = 'Property "' . $path . ' should be a multiple of ' . $schema->multipleOf;
        return false;
      }
    }
    return true;
  }
  
  private function checkMinItems($entity, $schema, $path)
  {
    if (isset($schema->minItems))
    {
      if (count($entity) < $schema->minItems)
      {
        $this->reason['details'] = 'Property "' . $path . '" should have minimum of ' . $schema->minItems . ' elements.'.
        return false;
      }
    }
    return true;
  }
  
  private function checkMaxItems($entity, $schema, $path)
  {
    if (isset($schema->maxItems))
    {
      if (count($entity) > $schema->maxItems)
      {
        $this->reason['details'] = 'Property "' . $path . '" should have maximum of ' . $schema->maxItems . ' elements.'.
        return false;
      }
    }
    return true;
  }
  
  private function checkUniqueItems($entity, $schema, $path)
  {
    if (isset($schema->uniqueItems) && $schema->uniqueItems)
    {
      if (count(array_unique($entity)) != count($entity))
      {
        $this->reason['details'] = 'Property "' . $path . '" should have only unique items.';
        return false;
      }
    }
    return true;
  }

  private function checkItems($entity, $schema, $path)
  {
    if (empty($schema->items)) return true;
    if (is_array($schema->items))
    {
    
    }
    else if (is_object($schema->items))
    {
      foreach ($entity as $key => $value)
      {
        if (!$this->checkType($value, $schema->items, $path . '[' . $key . ']')) return false;
      }
    }
    throw new Core\Exception($this, 'ERR_VALIDATOR_JSON_8', $path);
  }
  
  private function checkEnum($entity, $schema, $path)
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
    $this->reason['details'] = 'Property "' . $path . '" has the value that does not match the enumeration options.';
    return false;
  }
}