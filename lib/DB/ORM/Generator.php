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

namespace Aleph\DB\ORM;

use Aleph\Core,
    Aleph\DB,
    Aleph\Utils,
    Aleph\Utils\PHP;

/**
 * Utility class that intended for generation of Active Record or model classes.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.db.orm
 */
class Generator
{
  /**
   * Error message templates.
   */
  const ERR_GENERATOR_1 = 'Unable to create directory "%s".';
  const ERR_GENERATOR_2 = 'Directory "%s" is not writable.';
  const ERR_GENERATOR_3 = 'Database of XML file does not match the given database.';
  const ERR_GENERATOR_4 = 'Table "%s" does not exist in database "%s". Model: %s.';
  const ERR_GENERATOR_5 = 'Column "%s" does not exist in table "%s". Model: %s.';
  const ERR_GENERATOR_6 = 'Model %s has namespace that does not match the given namespace of models.';
  const ERR_GENERATOR_7 = 'Number of columns for FROM table should equal to the number of columns for TO table. Relation: %s. Model: %s.';
  const ERR_GENERATOR_8 = 'Invalid creation mode.';

  /**
   * These creation modes determine what need to do with already existing classes.
   * MODE_REPLACE_IF_EXISTS - always replaces already existing class with new one.
   * MODE_IGNORE_IF_EXISTS - always ignores already existing class.
   * MODE_REPLACE_IMPORTANT - replaces only important data.
   * MODE_ADD_NEW - adds only new data.
   */
  const MODE_REPLACE_IF_EXISTS = 1;
  const MODE_IGNORE_IF_EXISTS = 2;
  const MODE_REPLACE_IMPORTANT = 3;
  const MODE_ADD_NEW = 4;
  
  /**
   * Number of spaces of indentation in files of classes.
   *
   * @var integer $tab
   * @access public
   */
  public $tab = 2;
  
  /**
   * Permissions for newly created directories.
   *
   * @var integer $directoryMode
   * @access public
   */
  public $directoryMode = 0777;
  
  /**
   * Permissions for newly created files.
   *
   * @var integer $fileMode
   * @access public
   */
  public $fileMode = 0666;
  
  /**
   * Determines whether the transformation of some data types should be used.
   *
   * @var boolean $useTransformation
   * @access public
   */
  public $useTransformation = true;
  
  /**
   * Determines whether the auto inheritance of models should be applied. 
   *
   * @var boolean $useInheritance
   * @access public
   */
  public $useInheritance = true;
  
  /**
   * Determines whether each class name should be normalizes.
   *
   * @var boolean $usePrettyClassName
   * @access public
   */
  public $usePrettyClassName = false;
  
  /**
   * Determines whether each property name should be normalizes.
   *
   * @var boolean $usePrettyPropertyName
   * @access public
   */
  public $usePrettyPropertyName = false;

  /**
   * Database connection object.
   *
   * @var Aleph\DB\DB $db
   * @access protected
   */
  protected $db = null;
  
  /**
   * Database alias.
   *
   * @var string $alias
   * @access protected
   */
  protected $alias = null;
  
  /**
   * Base directory for all ORM and AR classes.
   *
   * @var string $basedir
   * @access protected
   */
  protected $basedir = null;
  
  /**
   * Class creation mode.
   *
   * @var integer $mode
   * @access protected
   */
  protected $mode = null;
  
  /**
   * Tables that should be excluded from processing.
   *
   * @var array $excludedTables
   * @access protected
   */
  protected $excludedTables = [];
  
  /**
   * Information about database structure.
   *
   * @var array $dbi
   * @access private
   */
  private $dbi = null;

  /**
   * Constructor. Initializes the class properties.
   *
   * @param string $alias - the database alias.
   * @param string $directory - the base directory for all generated classes.
   * @param integer $mode - the class creation mode.
   * @access public
   */
  public function __construct($alias, $directory, $mode = self::MODE_REPLACE_IMPORTANT)
  {
    $this->basedir = \Aleph::dir($directory);
    $this->setMode($mode);
    $this->alias = $alias;
    $this->db = DB\DB::getConnection($alias);
  }
  
  /**
   * Returns the class creation mode.
   *
   * @return integer
   * @access public
   */
  public function getMode()
  {
    return $this->mode;
  }
  
  /**
   * Sets new class creation mode.
   *
   * @param integer $mode
   * @access public
   */
  public function setMode($mode)
  {
    $mode = (int)$mode;
    if ($mode < 1 || $mode > 4) throw new Core\Exception($this, 'ERR_GENERATOR_8');
    $this->mode = $mode;
  }
  
  /**
   * Returns list of tables that excluded from processing.
   *
   * @return array
   * @access public
   */
  public function getExcludedTables()
  {
    return $this->excludedTables;
  }
  
  /**
   * Sets the tables that should be excluded from processing.
   *
   * @param array $tables
   * @access public
   */
  public function setExcludedTables(array $tables)
  {
    $this->excludedTables = $tables;
  }
  
  /**
   * Creates XML file containing description of database model.
   *
   * @param string $namespace - the namespace of all model or AR classes.
   * @param string $directory - a directory of XML file. If this parameter is not defined, the base directory is used.
   * @access public
   */
  public function xml($namespace, $directory = null)
  {
    $namespace = trim($namespace, '\\');
    $dir = $directory ? $directory : \Aleph::dir($this->basedir);
    $this->createDirectory($dir);
    $info = $this->getInfoFromDB($namespace);
    $file = $dir . '/' . $this->alias . '.xml';
    if (!file_exists($file))
    {
      $this->createXML($file, $namespace, $info);
    }
    else
    {
      $xinfo = $this->getInfoFromXML($file);
      foreach ($info as $class => $data)
      {
        $class = $this->normalizeClass($class);
        if (empty($xinfo[$class]) || $this->mode == self::MODE_REPLACE_IF_EXISTS) $xinfo[$class] = $data;
        else if ($this->mode == self::MODE_IGNORE_IF_EXISTS) continue;
        else
        {
          foreach (['ai', 'tables', 'columns'] as $property)
          {
            $value = $data[$property];
            if (!isset($xinfo[$class][$property]) || $this->mode == self::MODE_REPLACE_IMPORTANT) 
            {
              $xinfo[$class][$property] = $value;
            }
            else if (is_array($value))
            {
              $xinfo[$class][$property] += $value;
            }
          }
          if (empty($xinfo[$class]['properties']) || $this->mode == self::MODE_REPLACE_IMPORTANT) 
          {
            $xinfo[$class]['properties'] = $data['properties'];
          }
          else
          {
            $new = $xinfo[$class]['properties'];
            foreach ($data['properties'] as $k1 => $v1)
            {
              $flag = true;
              foreach ($new as $v2)
              {
                if ($v2['column'] == $v1['column']) 
                {
                  $flag = false;
                  break;
                }
              }
              if ($flag && empty($new[$k1])) $new[$k1] = $v1;
            }
            $xinfo[$class]['properties'] = $new;
          }
          if (empty($xinfo[$class]['relations']) || $this->mode == self::MODE_REPLACE_IMPORTANT) 
          {
            $xinfo[$class]['relations'] = $xinfo[$class]['relations'];
          }
          else
          {
            $new = $xinfo[$class]['relations'];
            foreach ($data['relations'] as $k => $v)
            { 
              if (!in_array($v, $new)) $new[$k] = $v;
            }
            $xinfo[$class]['relations'] = $new;
          }
        }
      }
      $this->createXML($file, $namespace, $xinfo);
    }
  }
  
  /**
   * Creates AR classes.
   *
   * @param string $namespace - the namespace of all AR classes.
   * @param string $directory - the directory of AR classes. If this parameter is not defined, the base directory is used.
   * @access public
   */
  public function ar($namespace, $directory = null)
  {
    $namespace = trim($namespace, '\\');
    $dir = $this->extractDirectory($namespace, $directory);
    $info = $this->getDBInfo();
    $tpl = new Core\Template(\Aleph::dir('framework') . '/_templates/ar.tpl');
    $tpl->namespace = $namespace;
    $tpl->alias = $this->alias;
    foreach ($info as $table => $data)
    {
      if (in_array($table, $this->excludedTables)) continue;
      $class = $this->normalizeClass($table);
      $file = $dir . '/' . $class . '.php';
      $exists = is_file($file);
      if ($exists && $this->mode == self::MODE_IGNORE_IF_EXISTS) continue;
      $properties = [];
      foreach ($data['columns'] as $column)
      {
        $properties[] = ' * @property ' . $column['phpType'] . ' $' . $column['column'];
      }
      $properties = implode(PHP_EOL, $properties) . PHP_EOL;
      $tpl->table = $table;
      $tpl->class = $class;
      $tpl->properties = $properties;
      if (!$exists || $this->mode == self::MODE_REPLACE_IF_EXISTS)
      {
        file_put_contents($file, '<?php' . PHP_EOL . $tpl->render());
      }
      else
      {
        $orig = new PHP\InfoClass($namespace . '\\' . $tpl->class, $this->tab, $this->fileMode);
        $rnd = uniqid();
        $file .= $rnd;
        $tpl->class .= $rnd;
        file_put_contents($file, '<?php' . PHP_EOL . $tpl->render());
        require_once($file);
        $sample = new PHP\InfoClass($namespace . '\\' . $tpl->class, $this->tab, $this->fileMode);
        if (empty($orig['comment']) || $this->mode == self::MODE_REPLACE_IMPORTANT) $orig['comment'] = $sample['comment'];
        if (empty($orig['methods']['__construct'])) $orig['methods']['__construct'] = $sample['methods']['__construct'];
        else if ($this->mode == self::MODE_REPLACE_IMPORTANT)
        {
          $code = $orig['methods']['__construct']['code'];
          $lines = array_values(array_filter(explode("\n", $sample['methods']['__construct']['code']), function($v){return strlen(trim($v));}));
          $space = str_repeat(' ', $orig->tab);
          if (!PHP\Tools::in($lines[0], $code)) $code = PHP_EOL . rtrim($lines[0], "\r\n ") . $code;
          if (!PHP\Tools::in($lines[1], $code)) $code = rtrim($code, "\r\n ") . PHP_EOL  . rtrim($lines[1], "\r\n ") . PHP_EOL . $space;
          foreach ($sample['methods']['__construct']['arguments'] as $arg => $data)
          {
            if (empty($orig['methods']['__construct']['arguments'][$arg])) 
            {
              $orig['methods']['__construct']['arguments'][$arg] = $data;
            }
          }
          $orig['methods']['__construct']['code'] = $code;
        }
        $orig->save();
        unlink($file);
      }
    }
  }
  
  /**
   * Creates model classes.
   *
   * @param string $namespace - the namespace of all model classes.
   * @param string $directory - the directory of model classes. If this parameter is not defined, the base directory is used.
   * @param string $xml - the path to the XML file that contained information about models.
   * @access public
   */
  public function orm($namespace, $directory = null, $xml = null)
  {
    $namespace = trim($namespace, '\\');
    $dir = $this->extractDirectory($namespace, $directory);
    $info = $xml ? $this->getInfoFromXML($xml, $namespace) : $this->getInfoFromDB($namespace);
    $tpl = new Core\Template(\Aleph::dir('framework') . '/_templates/model.tpl');
    $tpl->namespace = $namespace;
    $tpl->alias = PHP\Tools::php2str($this->alias);
    foreach ($info as $model => $data)
    {
      if (!$xml && in_array($model, $this->excludedTables)) continue;
      $model = $this->normalizeClass($model);
      $file = $dir . '/' . $model . '.php';
      $exists = is_file($file);
      if ($exists && $this->mode == self::MODE_IGNORE_IF_EXISTS) continue;
      $properties = [];
      foreach ($data['properties'] as $property => $dta)
      {
        $properties[] = ' * @property ' . $data['columns'][$dta['column']]['phpType'] . ' $' . $property;
      }
      $tpl->class = $model;
      $tpl->tableList = implode(', ', array_keys($data['tables']));
      $tpl->propertyList = implode(PHP_EOL, $properties) . PHP_EOL;
      $tpl->ai = PHP\Tools::php2str($data['ai']);
      $tpl->tables = PHP\Tools::php2str($data['tables'], true, $this->tab, $this->tab);
      $tpl->columns = PHP\Tools::php2str($data['columns'], true, $this->tab, $this->tab);
      $tpl->RSQL = PHP\Tools::php2str($data['RSQL']);
      $tpl->properties = PHP\Tools::php2str($data['properties'], true, $this->tab, $this->tab);
      $tpl->relations = PHP\Tools::php2str($data['relations'], true, $this->tab, $this->tab);
      if (!$exists || $this->mode == self::MODE_REPLACE_IF_EXISTS)
      {
        file_put_contents($file, '<?php' . PHP_EOL . $tpl->render());
      }
      else
      {
        $orig = new PHP\InfoClass($namespace . '\\' . $tpl->class, $this->tab, $this->fileMode);
        $rnd = uniqid();
        $file .= $rnd;
        $tpl->class .= $rnd;
        file_put_contents($file, '<?php' . PHP_EOL . $tpl->render());
        require_once($file);
        $sample = new PHP\InfoClass($namespace . '\\' . $tpl->class, $this->tab, $this->fileMode);
        if (empty($orig['comment']) || $this->mode == self::MODE_REPLACE_IMPORTANT) $orig['comment'] = $sample['comment'];
        if (empty($orig['properties']['alias']) || $this->mode == self::MODE_REPLACE_IMPORTANT) $orig['properties']['alias'] = $sample['properties']['alias'];
        if (empty($orig['properties']['ai']) || $this->mode == self::MODE_REPLACE_IMPORTANT) $orig['properties']['ai'] = $sample['properties']['ai'];
        foreach (['tables', 'columns'] as $property)
        {
          if (empty($orig['properties'][$property]) || $this->mode == self::MODE_REPLACE_IMPORTANT) 
          {
            $orig['properties'][$property] = $sample['properties'][$property];
          }
          else
          {
            $orig['properties'][$property]['defaultValue'] += $sample['properties'][$property]['defaultValue'];
          }
        }
        if (empty($orig['properties']['properties']) || $this->mode == self::MODE_REPLACE_IMPORTANT) 
        {
          $orig['properties']['properties'] = $sample['properties']['properties'];
        }
        else
        {
          $new = $orig['properties']['properties']['defaultValue'];
          $old = $sample['properties']['properties']['defaultValue'];
          foreach ($old as $k1 => $v1)
          {
            $flag = true;
            foreach ($new as $v2)
            {
              if ($v2['column'] == $v1['column']) 
              {
                $flag = false;
                break;
              }
            }
            if ($flag && empty($new[$k1])) $new[$k1] = $v1;
          }
        }
        if (empty($orig['properties']['relations']) || $this->mode == self::MODE_REPLACE_IMPORTANT) 
        {
          $orig['properties']['relations'] = $sample['properties']['relations'];
        }
        else
        {
          $new = $orig['properties']['relations']['defaultValue'];
          $old = $sample['properties']['relations']['defaultValue'];
          foreach ($old as $k => $v)
          { 
            if (!in_array($v, $new)) $new[$k] = $v;
          }
        }
        $orig->save();
        unlink($file);
      }
    }
  }
  
  /**
   * Creates model according to the database structure.
   *
   * @param string $namespace - the models namespace.
   * @return array
   * @access protected
   */
  protected function getInfoFromDB($namespace)
  {
    $dbal = [];
    $info = $this->getDBInfo();
    foreach ($info as $table => $data)
    {
      $tbs = [$table];
      // Determining model inheritance.
      if ($this->useInheritance)
      {
        do
        {
          $flag = false;
          foreach ($data['constraints'] as $cnst => $params)
          {
            if ($data['pk'] == array_keys($params['columns']))
            {
              $tb = reset($params['columns'])['table'];
              if ($info[$tb]['pk'] == array_column($params['columns'], 'column'))
              {
                array_unshift($tbs, $tb);
                $data = $info[$tb];
                $flag = true;
                break;
              }
            }
          }
        }
        while ($flag);
      }
      $info[$table]['tables'] = $tbs;
      $dbal[$table] = ['ai' => null, 'relations' => []];
      // Extracting common model properties: $tables, $columns, $properties.
      $table = end($tbs);
      foreach ($tbs as $tb)
      {
        $pk = $clist = $columns = [];
        // Determining the autoincrement field or sequence name.
        if ($info[$tb]['ai'] && !$dbal[$table]['ai'])
        {
          $ai = $this->db->wrap($tb) . '.' . $this->db->wrap($info[$tb]['ai']);
          if (isset($dbal[$table]['columns'][$ai]))
          {
            $dbal[$table]['ai'] = $dbal[$table]['columns'][$ai]['alias'];
          }
          else
          {
            $dbal[$table]['ai'] = $info[$tb]['ai'];
          }
        }
        // Extracting columns info.
        foreach ($info[$tb]['columns'] as $column => $cinfo) 
        {
          $clist[] = $col = $this->db->wrap($tb, true) . '.' . $this->db->wrap($column);
          $property = $this->normalizeProperty($column);
          if (isset($dbal[$table]['properties'][$property]))
          {
            $property = $this->singularize($this->normalizeProperty($tb)) . ucfirst($property);
          }
          if (empty($cinfo['isPrimaryKey'])) unset($cinfo['isPrimaryKey']);
          if (empty($cinfo['isNullable'])) unset($cinfo['isNullable']);
          if (empty($cinfo['isAutoincrement'])) unset($cinfo['isAutoincrement']);
          if (empty($cinfo['isUnsigned'])) unset($cinfo['isUnsigned']);
          if (empty($cinfo['set'])) unset($cinfo['set']);
          $dbal[$table]['columns'][$col] = array_merge(['alias' => $property, 'table' => $tb], $cinfo);
          if ($this->useTransformation && $cinfo['type'] == 'datetime')
          {
            $callback = $namespace . '\\' . $table;
            $dbal[$table]['properties'][$property] = ['column' => $col, 
                                                      'options' => ['format' => 'Y-m-d H:i:s', 'timezone' => date_default_timezone_get()],
                                                      'setter' => $callback . '::date2str', 
                                                      'getter' => $callback . '::str2date'];
          }
          else
          {
            $dbal[$table]['properties'][$property]['column'] = $col;
          }
        }
        // Extracting primary key columns.
        foreach ($info[$tb]['pk'] as $column) 
        {
          $pk[] = $col = $this->db->wrap($tb, true) . '.' . $this->db->wrap($column);
        }
        $dbal[$table]['tables'][$this->db->wrap($tb, true)] = ['pk' => $pk, 'columns' => $clist];
      }
      // Creating related properties.
      $this->extractRelatedProperties($table, $tbs, $dbal);
      // Building RSQL.
      $dbal[$table]['RSQL'] = $this->getRSQL($dbal[$table]);
    }
    // Extracting relations.
    foreach ($info as $table => $data)
    {
      foreach ($data['constraints'] as $cnst => $params)
      {
        $prms = $params['columns'];
        $tb = reset($prms)['table'];
        $this->extractRelation($table, $tb, $prms, $info, $dbal, $namespace);
        $prms = [];
        foreach ($params['columns'] as $column => $rcolumn)
        {
          $prms[$rcolumn['column']] = ['schema' => $rcolumn['schema'], 'table' => $table, 'column' => $column];
        }
        $this->extractRelation($tb, $table, $prms, $info, $dbal, $namespace);
      }
    }
    return $dbal;
  }
  
  /**
   * Extracts information about models from XML file.
   *
   * @param string $xml - path to the XML file.
   * @param mixed $namespace - the models namespace that defined in XML file.
   * @return array
   * @access protected
   */
  protected function getInfoFromXML($xml, &$namespace = null)
  {
    $info = $this->getDBInfo();
    $dom = new \DOMDocument('1.0', 'utf-8');
    $dom->load($xml);
    $dom->schemaValidate(\Aleph::dir('framework') . '/_templates/db.xsd');
    $dom = simplexml_import_dom($dom);
    if ((string)$dom['Alias'] != $this->alias) throw new Core\Exception($this, 'ERR_GENERATOR_3');
    $namespace = trim((string)$dom['Namespace'], '\\');
    $dbal = [];
    foreach ($dom->Model as $model)
    {
      $class = (string)$model['Class'];
      $tables = $tbs = $columns = $tmp = []; $ai = null;
      foreach ($model->Mapping->Table as $tb)
      {
        $table = (string)$tb['Name'];
        $tables[] = $table;
        $wtb = $this->db->wrap($table, true);
        $pk = $cols = [];
        if (empty($info[$table])) throw new Core\Exception($this, 'ERR_GENERATOR_4', $table, $this->alias, $class);
        foreach ($info[$table]['pk'] as $column) $pk[] = $wtb . '.' . $this->db->wrap($column);
        foreach ($info[$table]['columns'] as $column => $cinfo)
        {
          $col = $wtb . '.' . $this->db->wrap($column);
          $cols[] = $col;
          if (empty($cinfo['isPrimaryKey'])) unset($cinfo['isPrimaryKey']);
          if (empty($cinfo['isNullable'])) unset($cinfo['isNullable']);
          if (empty($cinfo['isAutoincrement'])) unset($cinfo['isAutoincrement']);
          if (empty($cinfo['isUnsigned'])) unset($cinfo['isUnsigned']);
          if (empty($cinfo['set'])) unset($cinfo['set']);
          $columns[$col] = array_merge(['alias' => null, 'table' => $table], $cinfo);
          $tmp[$column] = $column;
        }
        $tbs[$wtb] = ['pk' => $pk, 'columns' => $cols];
        if (isset($info[$table]['ai'])) $ai = $wtb . '.' . $this->db->wrap($info[$table]['ai']);
      }
      $properties = [];
      foreach ($model->Properties->Property as $prop)
      {
        $property = (string)$prop['Name'];
        $table = (string)$prop['Table'];
        $column = (string)$prop['Column'];
        if (empty($info[$table])) throw new Core\Exception($this, 'ERR_GENERATOR_4', $table, $this->alias, $class);
        if (empty($info[$table]['columns'][$column])) throw new Core\Exception($this, 'ERR_GENERATOR_5', $column, $table, $class);
        $col = $this->db->wrap($table, true) . '.' . $this->db->wrap($column);
        $columns[$col]['alias'] = $property;
        if ($this->useTransformation && isset($prop->Transformation) && (string)$prop->Transformation['Type'] == 'datetime')
        {
          $options = [];
          foreach ($prop->Transformation->Options->Option as $option)
          {
            $options[(string)$option['Name']] = (string)$option['Value'];
          }
          $callback = $namespace . '\\' . $class . '::';           
          $properties[$property] = ['column' => $col, 'options' => $options, 'setter' => $callback . 'date2str', 'getter' => $callback . 'str2date'];
        }
        else 
        {
          $properties[$property] = ['column' => $col];
        }
      }
      if ($ai) $ai = $columns[$ai]['alias'];
      $dbal[$class] = ['ai' => $ai, 'relations' => [], 'properties' => $properties, 'columns' => $columns, 'tables' => $tbs];
      $dbal[$class]['RSQL'] = $this->getRSQL($dbal[$class]);
      $this->extractRelatedProperties($class, $tables, $dbal);
    }
    foreach ($dom->Model as $model)
    {
      $class = (string)$model['Class'];
      if (isset($model->Relations))
      {
        foreach ($model->Relations->Relation as $rel)
        {
          $relation = (string)$rel['Name'];
          $rmodel = (string)$rel['Model'];
          if (PHP\Tools::getNamespace($rmodel) != $namespace) throw new Core\Exception($this, 'ERR_GENERATOR_6', $rmodel);
          $rclass = $this->normalizeClass(PHP\Tools::getClassName($rmodel));
          $type = (string)$rel['Type'];
          $len = count($rel->Join) - 1;
          $table = (string)$rel->Join[0]->From['Table'];
          if (empty($info[$table])) throw new Core\Exception($this, 'ERR_GENERATOR_4', $table, $this->alias, $class);
          $sql = $dbal[$rclass]['RSQL'];
          $sql = $this->db->sql->start(substr($sql, 0, strpos($sql, ' FROM ') + 6) . $this->db->wrap($table, true));
          for ($n = 0; $n <= $len; $n++)
          {
            $join = $rel->Join[$n];
            $fromTable = (string)$join->From['Table'];
            if (empty($info[$fromTable])) throw new Core\Exception($this, 'ERR_GENERATOR_4', $fromTable, $this->alias, $class);
            $toTable = (string)$join->To['Table'];
            if (empty($info[$toTable])) throw new Core\Exception($this, 'ERR_GENERATOR_4', $toTable, $this->alias, $class);
            if (count($join->From->Column) != count($join->To->Column)) throw new Core\Exception($this, 'ERR_GENERATOR_7', $relation, $class);
            $columns = $properties = []; $k = 0;
            foreach ($join->To->Column as $column)
            {
              $fromColumn = (string)$join->From->Column[$k]['Name'];
              if (empty($info[$fromTable]['columns'][$fromColumn])) throw new Core\Exception($this, 'ERR_GENERATOR_5', $fromColumn, $fromTable, $class);
              $toColumn = (string)$column['Name'];
              if (empty($info[$toTable]['columns'][$toColumn])) throw new Core\Exception($this, 'ERR_GENERATOR_5', $toColumn, $toTable, $class);
              if ($n == $len)
              {
                $toColumn = $this->db->wrap($toTable) . '.' . $this->db->wrap($toColumn);
                $property = $dbal[$class]['columns'][$toColumn]['alias'];
                if ($type == 'one') $dbal[$class]['properties'][$property]['relations'][] = $relation;
                $properties[$property] = $this->db->wrap($fromTable) . '.' . $this->db->wrap($fromColumn);
              }
              else
              {
                $columns[] = $this->db->wrap($toTable) . '.' . $this->db->wrap($toColumn) . ' = ' . $this->db->wrap($fromTable) . '.' . $this->db->wrap($fromColumn);
              }
              $k++;
            }
            if ($n < $len) $sql->join($toTable, new DB\SQLExpression(implode(' AND ', $columns)), 'LEFT');
          }
          $dbal[$class]['relations'][$relation] = ['type' => $type, 'model' => $rmodel, 'properties' => $properties, 'sql' => $sql->build()];
        }
      }
    }
    return $dbal;
  }
  
  /**
   * Creates XML file.
   *
   * @param string $file - the path to the XML file.
   * @param string $namespace - the namespace of model classes.
   * @param array $info - information about database model. 
   * @access private
   */
  private function createXML($file, $namespace, array $info)
  {
    $dom = new \DOMDocument('1.0', 'utf-8');
    $dom->formatOutput = true;
    $root = $dom->createElement('Database');
    $root->setAttribute('Alias', $this->alias);
    $root->setAttribute('Namespace', $namespace);
    foreach ($info as $class => $data)
    {
      if (in_array($class, $this->excludedTables)) continue;
      $model = $dom->createElement('Model');
      $model->setAttribute('Class', $this->normalizeClass($class));
      $mapping = $dom->createElement('Mapping');
      foreach ($data['tables'] as $table => $details)
      {
        $tb = $dom->createElement('Table');
        $tb->setAttribute('Name', $this->db->unwrap($table, true));
        $mapping->appendChild($tb);
      }
      $properties = $dom->createElement('Properties');
      foreach ($data['properties'] as $property => $details)
      {
        $col = $data['columns'][$details['column']];
        $prop = $dom->createElement('Property');
        $prop->setAttribute('Name', $property);
        $prop->setAttribute('Table', $col['table']);
        $prop->setAttribute('Column', $col['column']);
        if ($col['type'] == 'datetime')
        {
          $tr = $dom->createElement('Transformation');
          $tr->setAttribute('Type', 'datetime');
          $ops = $dom->createElement('Options');
          $op = $dom->createElement('Option');
          $op->setAttribute('Name', 'format');
          $op->setAttribute('Value', 'Y-m-d H:i:s');
          $ops->appendChild($op);
          $op = $dom->createElement('Option');
          $op->setAttribute('Name', 'timezone');
          $op->setAttribute('Value', date_default_timezone_get());
          $ops->appendChild($op);
          $tr->appendChild($ops);
          $prop->appendChild($tr);
        }
        $properties->appendChild($prop);
      }
      $relations = $dom->createElement('Relations');
      foreach ($data['relations'] as $relation => $details)
      {
        $rel = $dom->createElement('Relation');
        $rel->setAttribute('Name', $relation);
        $rel->setAttribute('Model', $details['model']);
        $rel->setAttribute('Type', $details['type']);
        $tables = [];
        $rmodel = $info[substr($details['model'], strlen($namespace) + 1)];
        if (count($rmodel['tables']) > 1)
        {
          $tables = array_keys($rmodel['tables']);
          foreach ($tables as &$tb) 
          {
            $columns = [];
            foreach ($rmodel['tables'][$tb]['pk'] as $column)
            {
              $columns[] = $rmodel['columns'][$column]['column'];
            }
            $tb = ['table' => $this->db->unwrap($tb, true), 'columns' => $columns];
          }
          unset($tb);
        }
        $columns = $rcolumns = [];
        $rtable = $rmodel['columns'][reset($details['properties'])]['table'];
        $table = $data['columns'][$data['properties'][key($details['properties'])]['column']]['table'];
        foreach ($details['properties'] as $property => $column)
        {
          $rcolumns[] = $rmodel['columns'][$column]['column'];
          $column = $data['properties'][$property]['column'];
          $columns[] = $data['columns'][$column]['column'];
        }
        $tables[] = ['table' => $rtable, 'columns' => $rcolumns];
        $tables[] = ['table' => $table, 'columns' => $columns];
        for ($n = 0, $len = count($tables) - 1; $n < $len; $n += 2)
        {
          $tb = $tables[$n];
          $join = $dom->createElement('Join');
          $from = $dom->createElement('From');
          $from->setAttribute('Table', $tb['table']);
          foreach ($tb['columns'] as $column)
          {
            $col = $dom->createElement('Column');
            $col->setAttribute('Name', $column);
            $from->appendChild($col);
          }
          $tb = $tables[$n + 1];
          $to = $dom->createElement('To');
          $to->setAttribute('Table', $tb['table']);
          foreach ($tb['columns'] as $column)
          {
            $col = $dom->createElement('Column');
            $col->setAttribute('Name', $column);
            $to->appendChild($col);
          }
          $join->appendChild($from);
          $join->appendChild($to);
          $rel->appendChild($join);
        }
        $relations->appendChild($rel);
      }
      $model->appendChild($mapping);
      $model->appendChild($properties);
      if ($data['relations']) $model->appendChild($relations);
      $root->appendChild($model);
    }
    $dom->appendChild($root);
    file_put_contents($file, $dom->saveXML());
    chmod($file, $this->fileMode);
  }
  
  /**
   * Forms and returns SQL for initiating a model instance.
   *
   * @param array $info - the model information.
   * @return string
   * @access private
   */
  private function getRSQL(array $info)
  {    
    $columns = array_combine(array_column($info['properties'], 'column'), array_keys($info['properties']));
    if (count($info['tables']) > 1)
    {
      $tb = each($info['tables'])[0];
      $sql = $this->db->sql->select($tb, $columns);
      while (list($table, $data) = each($info['tables']))
      {
        $tmp = [];
        $data = $data['pk'];
        foreach ($info['tables'][$tb]['pk'] as $n => $column)
        {
          $tmp[] = ['=', $data[$n], $sql->exp($column)];
        }
        $sql->join($table, $tmp, 'LEFT');
        $tb = $table;
      }
      return $sql->build();
    }
    return $this->db->sql->select(key($info['tables']), $columns)->build();
  }
  
  /**
   * Extracts related properties from the database info.
   *
   * @param string $table - the current table name.
   * @param array $tbs - the tables of a model.
   * @param array $dbal - the database model.
   * @access private   
   */
  private function extractRelatedProperties($table, array $tbs, array &$dbal)
  {
    if (count($tbs) <= 1) return;
    foreach ($tbs as $t1)
    {
      $pk = $dbal[$table]['tables'][$this->db->wrap($t1, true)]['pk'];
      foreach ($pk as $n => $c1)
      {
        $p1 = $dbal[$table]['columns'][$c1]['alias'];
        foreach ($tbs as $t2)
        {
          if ($t2 == $t1) continue;
          $c2 = $dbal[$table]['tables'][$this->db->wrap($t2, true)]['pk'][$n];
          $p2 = $dbal[$table]['columns'][$c2]['alias'];
          $dbal[$table]['properties'][$p2]['related'][] = $p1;
        }
      }
    }
  }
  
  /**
   * Extracts information about relation.
   *
   * @param string $from - the parent table name.
   * @param string $to - the child (related) table name.
   * @param array $params - information about relation structure.
   * @param array $info - information about database structure.
   * @param array $dbal - information about database model.
   * @param array $namespace - the models namespace.
   * @return array
   * @access private
   */
  private function extractRelation($from, $to, array $params, array $info, array &$dbal, $namespace)
  {
    $columns = array_column($params, 'column');
    $type = 'many';
    foreach ($info[$to]['tables'] as $t)
    {
      if ($info[$t]['pk'] == $columns)
      {
        $type = 'one';
        break;
      }
    }
    if ($type == 'many')
    {
      foreach ($info[$to]['keys'] as $key => $data)
      {
        if (!$data['isUnique']) continue;
        if ($columns == $data['columns'])
        {
          $type = 'one';
          break;
        }
      }
    }
    $properties = [];
    foreach ($params as $column => $data)
    {
      $property = $dbal[$from]['columns'][$this->db->wrap($from . '.' . $column)]['alias'];
      $properties[$property] = $this->db->wrap($data['table'] . '.' . $data['column']);
    }
    if ($type != 'one') $relation = $this->pluralize($this->normalizeProperty($to));
    else
    {
      $relation = $this->singularize($this->normalizeProperty($to));
      if ($to == $from) $relation = 'parent' . ucfirst($relation);
    }
    $relation = isset($dbal[$from]['relations']) ? $this->prefix($relation, [$dbal[$from]['relations'], $dbal[$from]['properties']]) : $relation;
    if ($type == 'one')
    {
      foreach ($properties as $property => $foo) 
      {
        $dbal[$from]['properties'][$property]['relations'][] = $relation;
      }
    }
    $dbal[$from]['relations'][$relation] = ['type' => $type, 'model' => $namespace . '\\' . $to, 'properties' => $properties, 'sql' => $dbal[$to]['RSQL']];
  }
  
  /**
   * Returns information about the database structure.
   *
   * @return array
   * @access private
   */
  private function getDBInfo()
  {
    if (!$this->dbi)
    {
      foreach ($this->db->getTableList() as $table) $this->dbi[$table] = $this->db->getTableInfo($table);
    }
    return $this->dbi;
  }
  
  /**
   * Converts a word from the singular form to the plural form.
   *
   * @param string $str - a word to convert.
   * @return string
   * @access private
   */
  private function pluralize($str)
  {
    if (strtolower(substr($str, 0, 6)) == 'lookup') $str = substr($str, 6);
    if (!ctype_upper(substr($str, 1, 1))) $str = lcfirst($str);
    return Utils\Inflect::pluralize($str);
  }
  
  /**
   * Converts a word from the plural form to the singular form.
   *
   * @param string $str - a word to convert.
   * @return string
   * @access private
   */
  private function singularize($str)
  {
    if (strtolower(substr($str, 0, 6)) == 'lookup') $str = substr($str, 6);
    if (!ctype_upper(substr($str, 1, 1))) $str = lcfirst($str);
    return Utils\Inflect::singularize($str);
  }
  
  /**
   * Adds numeric prefix to the identical element key of the given arrays.
   *
   * @param string $key - key of an array element.
   * @param array $arrays - arrays for checking.
   * @return string
   * @access private
   */
  private function prefix($key, array $arrays)
  {
    $n = ''; $k = $key;
    do
    {
      $flag = false;
      $k = $key . $n;
      foreach ($arrays as $a)
      {
        if (isset($a[$k]))
        {
          $flag = true;
          break;
        }
      }
      $n++;
    }
    while ($flag);
    return $k;
  }
  
  /**
   * Creates directory by the given namespace.
   *
   * @param string $namespace - the given namespace.
   * @param string $dir - the directory to create.
   * @return string
   * @access private
   */
  private function extractDirectory($namespace, $dir = null)
  {
    if ($dir === null)
    {
      $namespace = trim($namespace, '\\');
      $dir = $this->basedir;
      foreach (['Aleph\DB', 'Aleph'] as $ns)
      {
        if (strpos($namespace, $ns) === 0)
        {
          $dir .= substr($namespace, strlen($ns));
          break;
        }
      }
    }
    $dir = \Aleph::dir($dir);
    $this->createDirectory($dir);
    return $dir;
  }
  
  /**
   * Creates directory.
   *
   * @param string $dir - directory to create.
   * @access private
   */
  private function createDirectory($dir)
  {
    if (!is_dir($dir) && !mkdir($dir, $this->directoryMode, true)) throw new Core\Exception($this, 'ERR_GENERATOR_1', $dir);
    if (!is_writable($dir) && !chmod($dir, $this->directoryMode)) throw new Core\Exception($this, 'ERR_GENERATOR_2', $dir);
  }
  
  /**
   * Normalizes a class name.
   *
   * @param string $class - a class name to normalize.
   * @return string
   * @access private
   */
  private function normalizeClass($class)
  {
    $class = str_replace(['\\', '/', '<', '>', '"', '*', ':', '?', '|'], '', $class);
    if (!$this->usePrettyClassName) return $class;
    $tmp = explode('_', $class);
    if (count($tmp) == 1) return strtoupper($class) == $class ? ucfirst(strtolower($class)) : ucfirst($class); 
    foreach ($tmp as $k => $v) 
    {
      if (trim($v)) $tmp[$k] = ucfirst(strtolower(trim($v)));
      else unset($tmp[$k]);
    }
    return implode('', $tmp);
  }

  /**
   * Normalizes a property name.
   *
   * @param string $property - a property name to normalize.
   * @return string
   * @access private
   */
  private function normalizeProperty($property)
  {
    if (!$this->usePrettyPropertyName) return $property;
    $tmp = explode('_', $property);
    if (count($tmp) == 1) return strtoupper($property) == $property ? lcfirst(strtolower($property)) : lcfirst($property);
    foreach ($tmp as $k => $v) 
    {
      if (trim($v)) $tmp[$k] = ucfirst(strtolower(trim($v)));
      else unset($tmp[$k]);
    }
    return lcfirst(implode('', $tmp));
  }
}