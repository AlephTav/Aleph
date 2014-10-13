<?php
/**
 * Copyright (c) 2014 Aleph Tav
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
 * @copyright Copyright &copy; 2014 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */
 
namespace Aleph\DB\ORM;

use Aleph\Core,
    Aleph\DB,
    Aleph\Utils,
    Aleph\Utils\PHP;

/**
 * Base class of a model that contains all ORM functionality.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.db.orm
 */
abstract class Model
{
  /**
   * Error message templates.
   */
  const ERR_MODEL_1 = 'Property "[{var}]" does not exist in model "[{var}]".';
  const ERR_MODEL_2 = 'The model instance "[{var}]" was marked as deleted, and now, you can use it only as a read-only object.';
  const ERR_MODEL_3 = 'Property "[{var}]" of model "[{var}]" cannot be NULL.';
  const ERR_MODEL_4 = 'Property "[{var}]" of model "[{var}]" cannot be an array or object (except for Aleph\DB\SQLExpression instance). It can only be a scalar value.';
  const ERR_MODEL_5 = 'Enumeration property "[{var}]" of the model "[{var}]" has invalid value.';
  const ERR_MODEL_6 = 'Maximum length of property "[{var}]" of model "[{var}]" cannot be more than [{var}].';
  const ERR_MODEL_7 = 'Primary key of model "[{var}]" is not filled yet. You can\'t [{var}] the row.';
  const ERR_MODEL_8 = 'Call to undefined method [{var}]';
  
  /**
   * Callback that will be called before saving (inserting or updating) data in the database.
   *
   * @var mixed $onBeforeSave
   * @access public
   * @static
   */
  public static $onBeforeSave = null;
  
  /**
   * Callback that will be called before inserting data in the database.
   *
   * @var mixed $onBeforeInsert
   * @access public
   * @static
   */
  public static $onBeforeInsert = null;
  
  /**
   * Callback that will be called before updating data in the database.
   *
   * @var mixed $onBeforeUpdate
   * @access public
   * @static
   */
  public static $onBeforeUpdate = null;
 
  /**
   * Callback that will be called before deleting data from the database.
   *
   * @var mixed $onBeforeDelete
   * @access public
   * @static
   */
  public static $onBeforeDelete = null;
  
  /**
   * Callback that will be called after saving (inserting or updating) data in the database.
   *
   * @var mixed $onAfterSave
   * @access public
   * @static
   */
  public static $onAfterSave = null;
  
  /**
   * Callback that will be called after inserting data in the database.
   *
   * @var mixed $onAfterInsert
   * @access public
   * @static
   */
  public static $onAfterInsert = null;
  
  /**
   * Callback that will be called after updating data in the database.
   *
   * @var mixed $onAfterUpdate
   * @access public
   * @static
   */
  public static $onAfterUpdate = null;
  
  /**
   * Callback that will be called after deleting data from the database.
   *
   * @var mixed $onAfterDelete
   * @access public
   * @static
   */
  public static $onAfterDelete = null;
  
  /**
   * Alias of the database that used in model queries.
   *
   * @var string $alias
   * @access protected
   */
  protected static $alias = null;
  
  /**
   * Name of the auto-increment column.
   *
   * @var string $ai
   * @access protected
   * @static
   */
  protected static $ai = null;
  
  /**
   * Information about model's tables (column list and primary key).
   *
   * @var array $tables
   * @access protected
   */
  protected static $tables = [];
  
  /**
   * Meta iInformation about model's columns.
   *
   * @var array $columns
   * @access protected
   */
  protected static $columns = [];

  /**
   * The instance of the database connection class.
   *
   * @var Aleph\DB\DB $db
   * @access protected
   */
  protected $db = null;

  /**
   * Information about model's relations.
   *
   * @var array $relations
   * @access protected
   */
  protected $relations = [];
  
  /**
   * Information about model's properties.
   *
   * @var array $properties
   * @access protected
   */
  protected $properties = [];
  
  /**
   * Contains values of the table columns.
   *
   * @var array $values
   * @access private
   */
  private $values = [];
  
  /**
   * Active relation objects.
   *
   * @var array $rels
   * @access private
   */
  private $rels = [];
  
  /**
   * Determines whether a model is initiated from database.
   *
   * @var boolean $assigned
   * @access private
   */
  private $assigned = false;
  
  /**
   * Determines whether at least one property value is changed.
   *
   * @var boolean $changed
   * @access private
   */
  private $changed = false;
  
  /**
   * Determines whether the model is marked as deleted.
   *
   * @var boolean $deleted
   * @access private
   */
  private $deleted = false;
  
  /**
   * Returns information about model structure.
   *
   * @return array
   * @access public
   * @static
   */
  public static function getInfo()
  {
    return ['alias' => static::$alias, 'ai' => static::$ai, 'tables' => static::$tables, 'columns' => static::$columns];
  }
  
  /**
   * Returns database connection object that used by the model.
   *
   * @return Aleph\DB\DB
   * @access public
   * @static
   */
  public static function getDB()
  {
    return DB\DB::getConnection(static::$alias);
  }
  
  /**
   * Returns SQLBuilder instance associated with the current database.
   *
   * @return Aleph\DB\SQLBuilder
   * @access public
   * @static
   */
  public static function getSQL()
  {
    return static::getDB()->sql;
  }
  
  /**
   * Returns Relation object to iterate rows of the received model dataset.
   *
   * @return Aleph\DB\ORM\Relation
   * @access public
   * @static
   */
  public static function find()
  {
    return new Relation(static::getDB(), get_called_class(), static::$RSQL);
  }
  
  /**
   * Converts the given string into datetime object.
   *
   * @param mixed $value - date string or DateTime object.
   * @param array $options - option array that can contain timezone of the given date.
   * @return Aleph\Utils\DT
   * @access public
   * @static   
   */
  public static function str2date($value, array $options = null)
  {
    return new Utils\DT($value, null, isset($options['timezone']) ? $options['timezone'] : null);
  }
  
  /**
   * Converts datatime object to the formated date string.
   *
   * @param mixed $value - the datatime object.
   * @param array $options - option array that can contain output date format value.
   * @return string
   * @access public
   * @static
   */
  public static function date2str($value, array $options = null)
  {
    return $value instanceof \DateTimeInterface ? $value->format(isset($options['format']) ? $options['format'] : 'Y-m-d H:i:s') : $value;
  }

  /**
   * Constructor. Initiates the model.
   *
   * @param mixed $values - the property values of the model.
   * @param mixed $order - the ORDER BY clause condition.
   * @access public
   */
  public function __construct($values = null, $order = null)
  {
    $this->db = static::getDB();
    if ($values !== null) $this->assign($values, $order);
    else $this->reset();
  }
  
  /**
   * Returns the database connection object.
   *
   * @return Aleph\DB\DB
   * @access public
   */
  public function getConnection()
  {
    return $this->db;
  }
  
  /**
   * Sets the database connection object.
   *
   * @param Aleph\DB\DB $db - the database connection object.
   * @access public
   */
  public function setConnection(DB\DB $db)
  {
    $this->db = $db;
  }
  
  /**
   * Finds record in the model's tables by the given property values and assigns column values to the properties of the model instance.
   *
   * @param mixed $values - the WHERE clause condition.
   * @param mixed $order - the ORDER BY clause condition.
   * @return self
   * @access public
   */
  public function assign($values, $order = null)
  {
    $tmp = [];
    if (is_scalar($values))
    {
      foreach (reset(static::$tables)['pk'] as $column) $tmp[$column] = $values;
    }
    else
    {
      foreach ($values as $property => $value) 
      {
        if (empty($this->properties[$property])) throw new Core\Exception($this, 'ERR_MODEL_1', $property, get_class($this));
        $tmp[$this->properties[$property]['column']] = $value;
      }
    }
    return $this->assignFromCondition($tmp, $order);
  }
  
  /**
   * Finds record in the model's tables by the given criteria and assigns column values to the properties of the model instance.
   *
   * @param mixed $where - the WHERE clause condition.
   * @param mixed $order - the ORDER BY clause condition.
   * @return self
   * @access public
   */
  public function assignFromCondition($where, $order = null)
  {
    $this->values = $this->db->row($this->db->sql->start(static::$RSQL)->where($where)->order($order)->limit(1)->build($tmp), $tmp);
    if ($this->values)
    {
      $this->assigned = true;
      $this->changed = false;
      $this->deleted = false;
      return $this;
    }
    return $this->reset();
  }
  
  /**
   * Initializes the model object by array values.
   *
   * @param array $values - the values of properties.
   * @return self
   * @access public
   */
  public function assignFromArray(array $values)
  {
    return $this->reset()->setValues($values, true);
  }
  
  /**
   * Resets the model object to the initial state.
   *
   * @return self
   * @access public
   */
  public function reset()
  {
    $this->assigned = false;
    $this->changed = false;
    $this->deleted = false;
    $this->values = [];
    foreach ($this->properties as $property => $data) 
    {
      $this->values[$property] = static::$columns[$data['column']]['default'];
    }
    return $this;
  }
  
  /**
   * Returns values of properties.
   *
   * @param boolean $returnRawValues - determines whether the raw values of properties will be returned. 
   * @return array
   * @access public
   */
  public function getValues($returnRawValues = true)
  {
    if ($returnRawValues) return $this->values;
    $tmp = $this->values;
    foreach ($tmp as $property => &$value) $value = $this->__get($property);
    return $tmp;
  }
  
  /**
   * Sets values of the model properties.
   *
   * @param array $values - new values of properties.
   * @param boolean $ignoreNonExistingProperties - determines whether non-existing properties should be ignored when new values are assigned.
   * @return self
   * @access public
   */
  public function setValues(array $values, $ignoreNonExistingProperties = true)
  {
    if ($ignoreNonExistingProperties)
    {
      foreach ($values as $property => $value) 
      {
        if (isset($this->properties[$property])) $this->__set($property, $value);
      }
    }
    else
    {
      foreach ($values as $property => $value) $this->__set($property, $value);
    }
    return $this;
  }
  
  /**
   * Returns TRUE if primary key properties are filled and FALSE otherwise.
   *
   * @param boolean $insert - if TRUE the autoincrement primary key property will be ignored.
   * @return boolean
   * @access public
   */
  public function isPrimaryKeyFilled($insert = false)
  {
    foreach (reset(static::$tables)['pk'] as $column)
    {
      $column = static::$columns[$column];
      if ($insert && $column['alias'] == static::$ai) continue;
      $type = $column['phpType'];
      if (($type == 'int' || $type == 'float') && strlen($this->values[$column['alias']]) == 0 && strlen($column['default']) == 0) 
      {
        if (!$insert || empty(static::$ai)) return false;
      }
    }
    return true;
  }
  
  /**
   * Returns TRUE if the model object was initiated from the database and FALSE otherwise.
   *
   * @return boolean
   * @access public
   */
  public function isAssigned()
  {
    return $this->assigned;
  }
  
  /**
   * Returns TRUE if at least one property value was changed and FALSE otherwise.
   *
   * @return boolean
   * @access public
   */
  public function isChanged()
  {
    return $this->changed;
  }
  
  /**
   * Returns TRUE if the current model instance was marked as deleted and FALSE otherwise.
   *
   * @return boolean
   * @access public
   */
  public function isDeleted()
  {
    return $this->deleted;
  }
  
  /**
   * Returns meta-information about the property.
   * It returns FALSE if metadata for the given entity does not exist or if the given property does not exist.
   *
   * @param string $property - the property name.
   * @param string $entity - determines the type of needed metadata. If this parameter is null the method returns all metadata.
   * @return mixed
   * @access public
   */
  public function getPropertyInfo($property, $entity = null)
  {
    if (empty($this->properties[$property])) return false;
    $info = $this->properties[$property];
    $info = static::$columns[$info['column']];
    if ($entity === null) return $info;
    return isset($info[$entity]) ? $info[$entity] : false;
  }

  /**
   * Returns TRUE if the given property is a primary key and FALSE otherwise.
   *
   * @param string $property - the property name.
   * @return boolean
   * @access public
   */
  public function isPrimaryKey($property)
  {
    return $this->getPropertyInfo($property, 'isPrimaryKey');
  }
  
  /**
   * Returns TRUE if the given property is an autoincrement property and FALSE otherwise.
   *
   * @param string $property - the property name.
   * @return boolean
   * @access public
   */
  public function isAutoincrement($property)
  {
    return $this->getPropertyInfo($property, 'isAutoincrement');
  }
  
  /**
   * Returns TRUE if the given property is nullable and FALSE otherwise.
   *
   * @param string $property - the property name.
   * @return boolean
   * @access public
   */
  public function isNullable($property)
  {
    return $this->getPropertyInfo($property, 'isNullable');
  }
  
  /**
   * Returns TRUE if the given property is unsigned and FALSE otherwise.
   *
   * @param string $property - the property name.
   * @return boolean
   * @access public
   */
  public function isUnsigned($property)
  {
    return $this->getPropertyInfo($property, 'isUnsigned');
  }
  
  /**
   * Returns TRUE if the given property is a numeric one. Otherwise, it returns FALSE. 
   *
   * @param string $property - the property name.
   * @return boolean
   * @access public
   */
  public function isNumeric($property)
  {
    $type = $this->getPropertyPHPType($property);
    return $type == 'int' || $type == 'float';
  }

  /**
   * Returns TRUE if the given property is a text one. Otherwise, it returns FALSE. 
   *
   * @param string $column - the property name.
   * @return boolean
   * @access public
   */
  public function isText($property)
  {
    return $this->getPropertyPHPType($property) == 'string';
  }

  /**
   * Returns TRUE if the given property has one of date or time property types. Otherwise, it returns FALSE. 
   *
   * @param string $property - the property name.
   * @return boolean
   * @access public
   */
  public function isDateTime($property)
  {
    switch ($this->getPropertyType($property))
    {
      case 'datetime':
      case 'timestamp':
      case 'date':
      case 'time':
      case 'year':
        return true;
    }
    return false;
  }
  
  /**
   * Returns DBMS data type of the given property.
   *
   * @param string $property - the property name.
   * @return string
   * @access public
   */
  public function getPropertyType($property)
  {
    return $this->getPropertyInfo($property, 'type');
  }
  
  /**
   * Returns PHP data type of the given property.
   *
   * @param string $property - the property name.
   * @return string
   * @access public
   */
  public function getPropertyPHPType($property)
  {
    return $this->getPropertyInfo($property, 'phpType');
  }
  
  /**
   * Returns default value of the given property.
   *
   * @param string $property - the property name.
   * @return mixed
   * @access public
   */
  public function getPropertyDefaultValue($property)
  {
    return $this->getPropertyInfo($property, 'default');
  }
  
  /**
   * Returns maximum length of the given property.
   *
   * @param string $property - the property name.
   * @return integer
   * @access public
   */
  public function getPropertyMaxLength($property)
  {
    return $this->getPropertyInfo($property, 'maxLength');
  }
  
  /**
   * Returns precision of the given property.
   *
   * @param string $property - the property name.
   * @return integer
   * @access public
   */
  public function getPropertyPrecision($property)
  {
    return $this->getPropertyInfo($property, 'precision');
  }
  
  /**
   * Returns enumeration values of the given property.
   *
   * @param string $property - the property name.
   * @return array
   * @access public
   */
  public function getPropertyEnumeration($property)
  {
    return $this->getPropertyInfo($property, 'set');
  }
  
  /**
   * Returns TRUE if a property with the given name exists and FALSE if it doesn't.
   *
   * @param string $property - the property name.
   * @return boolean
   * @access public
   */
  public function __isset($property)
  {
    return isset($this->properties[$property]) || isset($this->relations[$property]);
  }
  
  /**
   * Returns property value.
   *
   * @param string $property - the property name.
   * @return mixed
   * @access public
   */
  public function __get($property)
  {
    if (empty($this->properties[$property])) 
    {
      if (empty($this->relations[$property])) throw new Core\Exception($this, 'ERR_MODEL_1', $property, get_class($this));
      if (empty($this->rels[$property]))
      {
        if ($this->relations[$property]['type'] == 'one')
        {
          $this->rels[$property] = $this->getRelationModel($property);
        }
        else
        {
          $info = $this->relations[$property];
          $this->rels[$property] = new Relation($this, $info['model'], $info['sql'], $info['properties']);
        }
      }
      return $this->rels[$property];
    }
    $prop = $this->properties[$property];
    if (empty($prop['getter'])) return $this->values[$property];
    $ops = isset($prop['options']) ? (array)$prop['options'] : [];
    $ops['property'] = $property;
    return \Aleph::delegate($this->properties[$property]['getter'], $this->values[$property], $ops);
  }
  
  /**
   * Sets property value.
   *
   * @param string $property - the property name.
   * @param mixed $value - the property value.
   * @access public
   */
  public function __set($property, $value)
  {
    static $lock = false;
    if (!$lock)
    {
      if ($this->deleted) throw new Core\Exception($this, 'ERR_MODEL_2', get_class($this));
      if (empty($this->properties[$property])) throw new Core\Exception($this, 'ERR_MODEL_1', $property, get_class($this));
    }
    if ($value instanceof SQLExpression)
    {
      if ((string)$value === (string)$this->values[$property]) return;
    }
    else
    {
      $prop = $this->properties[$property];
      if (isset($prop['setter'])) 
      {
        $ops = isset($prop['options']) ? (array)$prop['options'] : [];
        $ops['property'] = $property;
        $value = \Aleph::delegate($prop['setter'], $value, $ops);
      }
      if ($value === null && !$this->isNullable($property)) throw new Core\Exception($this, 'ERR_MODEL_3', $property, get_class($this)); 
      if (is_array($value) || is_object($value)) throw new Core\Exception($this, 'ERR_MODEL_4', $property, get_class($this));
      if ($value !== null) settype($value, $this->getPropertyPHPType($property));
      if ($value === $this->values[$property]) return;
      $type = $this->getPropertyType($property);
      if ($type == 'enum' && !in_array($value, $this->getPropertyEnumeration($property))) throw new Core\Exception($this, 'ERR_MODEL_5', $property, get_class($this));
      if (($this->isText($property) && !$this->isDateTime($property) || $type == 'bit') && ($max = $this->getPropertyMaxLength($property)) > 0)
      {
        $length = $type == 'bit' ? strlen(decbin($value)) : strlen($value);
        if ($length > $max) throw new Core\Exception($this, 'ERR_MODEL_6', $property, get_class($this), $max);
      }
    }
    $this->values[$property] = $value;
    if (isset($prop['relations']))
    {
      foreach ($prop['relations'] as $relation)
      {
        if (isset($this->rels[$relation])) $this->rels[$relation] = $this->getRelationModel($relation);
      }
    }
    if (!$lock && isset($prop['related']))
    {
      $lock = true;
      foreach ($prop['related'] as $p)
      {
        $this->__set($p, $value);
      }
      $lock = false;
    }
    $this->changed = true;
  }
  
  /**
   * Returns Relation object by its name.
   *
   * @param string $method - the name of relation.
   * @param array $args - the relation parameters: $limit, $offset, $asArray.
   * @return Aleph\DB\ORM\Relation
   * @access public   
   */
  public function __call($method, array $args)
  {
    if (empty($this->relations[$method])) throw new Core\Exception($this, 'ERR_MODEL_8', get_class($this) . '::' . $method . '()');
    $rel = $this->__get($method);
    return $rel(isset($args[0]) ? $args[0] : null, isset($args[1]) ? $args[1] : null, isset($args[2]) ? $args[2] : false);
  }
  
  /**
   * Updates model records in the database table(s) if this records exist or inserts new records otherwise.
   * It returns numbers of affected rows.
   *
   * @param array $options - contains additional parameters (for example, updateOnKeyDuplicate or sequenceName) required by some DBMS for row insertion.
   * @return integer
   * @access public
   */
  public function save(array $options = null)
  {
    if (static::$onBeforeSave) \Aleph::delegate(static::$onBeforeSave, $this);
    $res = $this->assigned ? $this->update() : $this->insert($options);
    if (static::$onAfterSave) \Aleph::delegate(static::$onAfterSave, $this, $res);
    return $res;
  }
  
  /**
   * Inserts new model row(s) to the database table(s).
   * The method returns the ID of the last inserted row, or the last value from a sequence object, depending on the underlying driver.
   * If a database table doesn't have the auto-incremental column or the sequence name is not passed as a parameter, the method returns NULL.
   *
   * @param array $options - contains additional parameters (for example, updateOnKeyDuplicate or sequenceName) required by some DBMS.
   * @return integer
   * @access public
   */
  public function insert(array $options = null)
  {
    if ($this->deleted) throw new Core\Exception($this, 'ERR_MODEL_2', get_class($this));
    if (!$this->isPrimaryKeyFilled(true)) throw new Core\Exception($this, 'ERR_MODEL_7', get_class($this), 'insert');
    if (static::$onBeforeInsert) \Aleph::delegate(static::$onBeforeInsert, $this);
    $res = $this->doAction('insert', $options);
    $this->changed = false;
    $this->assigned = true;
    if (static::$onAfterInsert) \Aleph::delegate(static::$onAfterInsert, $this, $res);
    return $res;
  }
  
  /**
   * Updates existing model row (or rows) in the database table(s).
   * It returns the number of affected rows.
   *
   * @return integer
   * @access public
   */
  public function update()
  {
    if ($this->deleted) throw new Core\Exception($this, 'ERR_MODEL_2', get_class($this));
    if (!$this->changed) return 0;
    if (!$this->isPrimaryKeyFilled()) throw new Core\Exception($this, 'ERR_MODEL_7', get_class($this), 'update');
    if (static::$onBeforeUpdate) \Aleph::delegate(static::$onBeforeUpdate, $this);
    $res = $this->doAction('update');
    $this->changed = false;
    if (static::$onAfterUpdate) \Aleph::delegate(static::$onAfterUpdate, $this, $res);
    return $res;
  }
  
  /**
   * Deletes existing model row (or rows) from the database table(s).
   * The method returns numbers of affected rows.
   *
   * @return integer
   * @access public
   */
  public function delete()
  {
    if ($this->deleted) throw new Core\Exception($this, 'ERR_MODEL_2', get_class($this));
    if (!$this->isPrimaryKeyFilled()) throw new Core\Exception($this, 'ERR_MODEL_7', get_class($this), 'delete');
    if (static::$onBeforeDelete) \Aleph::delegate(static::$onBeforeDelete, $this);
    $res = $this->doAction('delete');
    $this->deleted = true;
    if (static::$onAfterDelete) \Aleph::delegate(static::$onAfterDelete, $this, $res);
    return $res;
  }
  
  /**
   * Fixes inconsistency between inherited tables.
   * It returns FALSE if the model is deleted or the primary key is not filled. Otherwise, it returns TRUE.
   *
   * @return boolean
   * @access public
   */
  public function fix()
  {
    if ($this->deleted || !$this->isPrimaryKeyFilled()) return false;
    return $this->doAction('fix');
  }
  
  /**
   * Sets setter callback for the given model property.
   *
   * @param string $property - the property name.
   * @param mixed $setter - the callback that will be automatically invoked when property gets new value.
   * @param array $options - the setter additional arguments.
   * @return self
   * @access protected
   */
  protected function setter($property, $setter, array $options = null)
  {
    if ($setter === false) 
    {
      unset($this->properties[$property]['setter']);
      if (empty($this->properties[$property]['getter'])) unset($this->properties[$property]['options']);
      return $this;
    }
    $options = array_merge(isset($this->properties[$property]['options']) ? (array)$this->properties[$property]['options'] : [], $options ?: []);
    $this->properties[$property]['setter'] = ['setter' => $setter, 'options' => $options];
    return $this;
  }
  
  /**
   * Sets getter callback for the given model property.
   *
   * @param string $property - the property name.
   * @param mixed $setter - the callback that will be automatically invoked when property value is returned.
   * @param array $options - the getter additional arguments.
   * @return self
   * @access protected
   */
  protected function getter($property, $getter, array $options = null)
  {
    if ($getter === false) 
    {
      unset($this->properties[$property]['getter']);
      if (empty($this->properties[$property]['setter'])) unset($this->properties[$property]['options']);
      return $this;
    }
    $options = array_merge(isset($this->properties[$property]['options']) ? (array)$this->properties[$property]['options'] : [], $options ?: []);
    $this->properties[$property]['getter'] = ['getter' => $getter, 'options' => $options];
    return $this;
  }
  
  /**
   * Sets new model relation.
   *
   * @param string $name - the relation name.
   * @param string $type - the relation type. Valid values are "one" (one to one relation) and "many" (one to many relation).
   * @param string $model - the class name of the related model.
   * @param array $properties - mapping between the current model properties and related model's columns.
   * @param string $sql - the SQL that determines relation structure.
   * @return self
   * @access protected   
   */
  protected function relation($name, $type, $model, array $properties, $sql)
  {
    if ($type === false) 
    {
      unset($this->relations[$name]);
      return $this;
    }
    $this->relations[$name] = ['type' => $type, 'model' => $model, 'properties' => $properties, 'sql' => $sql];
    foreach ($properties as $property => $foo)
    {
      $rels = isset($this->properties[$property]['relations']) ? (array)$this->properties[$property]['relations'] : [];
      $rels[] = $name;
      $this->properties[$property]['relations'] = array_unique($rels);
    }
    return $this;
  }
  
  /**
   * Performs some SQL queries.
   *
   * @param string $action - type of queries.
   * @param array $options - additional options.
   * @return mixed
   * @access private
   */
  private function doAction($action, $options = null)
  {
    if (count(static::$tables) <= 1 || $this->db->inTransaction())
    {
      $res = $this->{'do' . $action}($options);
    }
    else
    {
      $this->db->beginTransaction();
      try
      {
        $res = $this->{'do' . $action}($options);
        $this->db->commit();
      }
      catch (\Exception $e)
      {
        $this->db->rollBack();
        throw $e;
      }
    }
    return $res;
  }
  
  /**
   * Inserts row(s) in the database.
   *
   * @param array $options - additional options for insert operation.
   * @return mixed
   * @access private   
   */
  private function doInsert(array $options = null)
  {
    $n = 0;
    if (empty($options['sequenceName']) && !empty(static::$ai) && empty($this->properties[static::$ai])) $options['sequenceName'] = static::$ai;
    foreach (static::$tables as $table => $data)
    {
      if ($n)
      {
        foreach ($data['pk'] as $k => $column)
        {
          $this->values[static::$columns[$column]['alias']] = $this->values[static::$columns[$pdata['pk'][$k]]['alias']];
        }
      }
      $tmp = [];
      foreach ($data['columns'] as $column)
      {
        $info = static::$columns[$column];
        $value = $this->values[$info['alias']];
        if ($n == 0 && $info['alias'] == static::$ai && !is_object($value) && strlen($value) == 0) continue;
        if (!empty($info['isNullable']) && $value === null) continue;
        $tmp[$this->db->wrap($info['column'])] = $value;
      }
      $res = $this->db->insert($table, $tmp, $options);
      if ($n == 0 && !empty(static::$ai)) 
      {
        if (isset($this->values[static::$ai]))
        {
          $this->values[static::$ai] = $res;
        }
        else
        {
          $sv = $res;
          foreach ($data['pk'] as $column)
          {
            $column = static::$columns[$column];
            if ($column['phpType'] == 'int' || $column['phpType'] == 'float') $this->values[$column['alias']] = $res;
          }
        }
      }
      $pdata = $data;
      $n++;
    }
    if (empty(static::$ai)) return null;
    return isset($this->values[static::$ai]) ? $this->values[static::$ai] : $sv;
  }
  
  /**
   * Updates model row(s) in the database.
   *
   * @return integer
   * @access private
   */
  private function doUpdate()
  {
    $res = 0;
    foreach (static::$tables as $table => $data)
    {
      $columns = $where = [];
      foreach ($data['columns'] as $column) 
      {
        $column = static::$columns[$column];
        $columns[$this->db->wrap($column['column'])] = $this->values[$column['alias']];
      }
      foreach ($data['pk'] as $column) 
      {
        $column = static::$columns[$column];
        $where[$this->db->wrap($column['column'])] = $this->values[$column['alias']];
      }
      $res += $this->db->update($table, $columns, $where);
    }
    return $res;
  }
  
  /**
   * Deletes model row(s) from the database.
   *
   * @return integer
   * @access private
   */
  private function doDelete()
  {
    $res = 0;
    foreach (array_reverse(static::$tables) as $table => $data)
    {
      $where = [];
      foreach ($data['pk'] as $column) $where[$column] = $this->values[static::$columns[$column]['alias']];
      $res += $this->db->delete($table, $where);
    }
    return $res;
  }
  
  /**
   * Inserts missed row(s) of the model in the database.
   *
   * @return integer
   * @access private
   */
  private function doFix()
  {
    $tbs = static::$tables;
    $pk = array_shift(static::$tables)['pk'];
    foreach (static::$tables as $table => $data)
    {
      foreach ($data['pk'] as $k => $column)
      {
        $this->values[static::$columns[$column]['alias']] = $this->values[static::$columns[$pk[$k]]['alias']];
      }
    }
    $res = 0;
    foreach (static::$tables as $table => $data)
    {
      $columns = $where = [];
      foreach ($data['pk'] as $column) 
      {
        $column = static::$columns[$column];
        $where[$this->db->wrap($column['column'])] = $this->values[$column['alias']];
      }
      if (!$this->db->cell($this->db->sql->select($table, new DB\SQLExpression('COUNT(*)'))->where($where)->build($tmp), $tmp))
      {
        foreach ($data['columns'] as $column)
        {
          $column = static::$columns[$column];
          $value = $this->values[$column['alias']];
          if (!empty($column['isNullable']) && $value === null) continue;
          $columns[$this->db->wrap($column['column'])] = $value;
        }
        $res += $this->db->insert($table, $columns);
      }
    }
    return $res;
  }
  
  /**
   * Returns related model object by the given relation name.
   *
   * @param string $relation - the relation name.
   * @return Aleph\DB\ORM
   */
  private function getRelationModel($relation)
  {
    $data = $this->relations[$relation];
    $sql = $this->db->sql->start($this->data['sql']);
    foreach ($this->data['properties'] as $property => $column) $where[$column] = $this->__get($property);
    return (new $data['model'])->setValues($this->db->row($sql->where($where)->limit(1)->build($tmp), $tmp));
  }
}