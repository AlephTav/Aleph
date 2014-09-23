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
 * Utility class that intended for generation of Active Record or model classes.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.orm
 */
class Generator
{
  /**
   * Error message templates.
   */
  const ERR_ORM_1 = 'Unable to create directory "[{var}]".';
  const ERR_ORM_2 = 'Directory "[{var}]" is not writable.';

  /**
   * These creation modes determine what need to do with already existing classes.
   * MODE_REPLACE_IF_EXISTS - always replaces already existing classes with new one.
   */
  const MODE_REPLACE_IF_EXISTS = 1;
  const MODE_IGNORE_IF_EXISTS = 2;
  const MODE_REPLACE_IMPORTANT = 3;
  const MODE_ADD_NEW = 4;
  
  public $tab = 2;

  protected $db = null;
  
  protected $alias = null;
  
  protected $basedir = null;
  
  protected $mode = null;
  
  protected $excludedTables = [];
  
  private $dbi = null;

  public function __construct($alias, $directory, $mode = self::MODE_REPLACE_IMPORTANT)
  {
    $this->basedir = \Aleph::dir($directory);
    $this->setMode($mode);
    $this->alias = $alias;
    $this->db = DB\DB::getConnection($alias);
  }
  
  public function getMode()
  {
    return $this->mode;
  }
  
  public function setMode($mode)
  {
    $this->mode = $mode;
  }
  
  public function getExcludedTables()
  {
    return $this->excludedTables;
  }
  
  public function setExcludedTables(array $tables)
  {
    $this->excludedTables = $tables;
  }
  
  public function xml($directory = null)
  {
    $info = $this->getInfoFromDB();
    
  }
  
  public function ar($namespace, $directory = null)
  {
    $dir = $this->extractDirectory($namespace, $directory);
    $info = $this->getDBInfo();
    $tpl = new Core\Template(\Aleph::dir('framework') . '/_templates/ar.tpl');
    $tpl->namespace = $namespace;
    $tpl->alias = $this->alias;
    foreach ($info as $table => $data)
    {
      if (in_array($table, $this->excludedTables)) continue;
      $file = $dir . '/' . $this->normalizeFile($table) . '.php';
      $exists = is_file($file);
      if ($exists && $this->mode == self::MODE_IGNORE_IF_EXISTS) continue;
      $properties = [];
      foreach ($data['columns'] as $column)
      {
        $properties[] = ' * @property ' . $column['phpType'] . ' $' . $column['column'];
      }
      $properties = implode(PHP_EOL, $properties) . PHP_EOL;
      $tpl->table = $table;
      $tpl->class = $this->normalizeClass($table);
      $tpl->properties = $properties;
      if (!$exists || $this->mode == self::MODE_REPLACE_IF_EXISTS)
      {
        file_put_contents($file, '<?php' . PHP_EOL . $tpl->render());
      }
      else
      {
        $orig = new PHP\InfoClass($namespace . '\\' . $tpl->class, $this->tab);
        $rnd = uniqid();
        $file .= $rnd;
        $tpl->class .= $rnd;
        file_put_contents($file, '<?php' . PHP_EOL . $tpl->render());
        require_once($file);
        $sample = new PHP\InfoClass($namespace . '\\' . $tpl->class, $this->tab);
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
  
  public function orm($namespace, $directory = null, $xml = null)
  {
    $dir = $this->extractDirectory($namespace, $directory);
    $info = $xml ? $this->getInfoFromXML($xml) : $this->getInfoFromDB();
    $tpl = new Core\Template(\Aleph::dir('framework') . '/_templates/model.tpl');
    $tpl->namespace = $namespace;
    $tpl->alias = PHP\Tools::php2str($this->alias);
    foreach ($info as $model => $data)
    {
      $file = $dir . '/' . $this->normalizeFile($model) . '.php';
      $exists = is_file($file);
      if ($exists && $this->mode == self::MODE_IGNORE_IF_EXISTS) continue;
      $properties = [];
      foreach ($data['properties'] as $property => $dta)
      {
        $properties[] = ' * @property ' . $data['columns'][$dta['column']]['phpType'] . ' $' . $property;
      }
      $tpl->class = $this->normalizeClass($model);
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
        $orig = new PHP\InfoClass($namespace . '\\' . $tpl->class, $this->tab);
        $rnd = uniqid();
        $file .= $rnd;
        $tpl->class .= $rnd;
        file_put_contents($file, '<?php' . PHP_EOL . $tpl->render());
        require_once($file);
        $sample = new PHP\InfoClass($namespace . '\\' . $tpl->class, $this->tab);
        if (empty($orig['comment']) || $this->mode == self::MODE_REPLACE_IMPORTANT) $orig['comment'] = $sample['comment'];
        foreach (['alias', 'ai', 'tables', 'columns', 'properties', 'relations'] as $property)
        {
          if (empty($orig['properties'][$property]) || $this->mode == self::MODE_REPLACE_IMPORTANT) 
          {
            $orig['properties'][$property] = $sample['properties'][$property];
          }
        }
        $orig->save();
        unlink($file);
      }
    }
  }
  
  protected function getInfoFromDB()
  {
    $dbal = [];
    $info = $this->getDBInfo();
    foreach ($info as $table => $data)
    {
      if (in_array($table, $this->excludedTables)) continue;
      $tbs = [$table];
      // Determining model inheritance.
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
      $info[$table]['tables'] = $tbs;
      $dbal[$table] = ['ai' => null, 'relations' => []];
      // Extracting common model properties: $tables, $columns, $properties.
      $table = end($tbs);
      foreach ($tbs as $tb)
      {
        $pk = $clist = $columns = [];
        foreach ($info[$tb]['columns'] as $column => $cinfo) 
        {
          $clist[] = $col = $this->db->wrap($tb . '.' . $column);
          $property = $column;
          if (isset($dbal[$table]['properties'][$column]))
          {
            $property = strtolower($this->singularize($tb)) . ucfirst($column);
          }
          $dbal[$table]['properties'][$property]['column'] = $col;
          if (empty($cinfo['isPrimaryKey'])) unset($cinfo['isPrimaryKey']);
          if (empty($cinfo['isNullable'])) unset($cinfo['isNullable']);
          if (empty($cinfo['isAutoincrement'])) unset($cinfo['isAutoincrement']);
          if (empty($cinfo['isUnsigned'])) unset($cinfo['isUnsigned']);
          if (empty($cinfo['set'])) unset($cinfo['set']);
          unset($cinfo['column']);
          $dbal[$table]['columns'][$col] = array_merge(['alias' => $property], $cinfo);
        }
        foreach ($info[$tb]['pk'] as $column) 
        {
          $pk[] = $col = $this->db->wrap($tb . '.' . $column);
          if (empty($dbal[$table]['ai']) && $info[$tb]['columns'][$column]['isAutoincrement'])
          {
            $dbal[$table]['ai'] = $dbal[$table]['columns'][$col]['alias'];
          }
        }
        $dbal[$table]['tables'][$this->db->wrap($tb, true)] = ['pk' => $pk, 'columns' => $clist];
      }
      // Creating related properties.
      if (count($tbs) > 1)
      {
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
      // Building RSQL.
      $dbal[$table]['RSQL'] = $this->getRSQL($dbal[$table]);
    }
    // Extracting relations.
    foreach ($info as $table => $data)
    {
      if (in_array($table, $this->excludedTables)) continue;
      foreach ($data['constraints'] as $cnst => $params)
      {
        $prms = $params['columns'];
        $tb = reset($prms)['table'];
        $this->extractRelation($table, $tb, $prms, $info, $dbal);
        $prms = [];
        foreach ($params['columns'] as $column => $rcolumn)
        {
          $prms[$rcolumn['column']] = ['schema' => $rcolumn['schema'], 'table' => $table, 'column' => $column];
        }
        $this->extractRelation($tb, $table, $prms, $info, $dbal);
      }
    }
    return $dbal;
  }
  
  /**
   * Extracts information about models from XML file.
   *
   * @param string $xml - the XML file.
   * @return array
   * @access protected
   */
  protected function getInfoFromXML($xml)
  {
  
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
  
  private function extractRelation($from, $to, array $params, array $info, array &$dbal)
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
    if ($type != 'one') $relation = $this->pluralize($to);
    else
    {
      $relation = $this->singularize($to);
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
    $dbal[$from]['relations'][$relation] = ['type' => $type, 'model' => $to, 'properties' => $properties, 'sql' => $dbal[$to]['RSQL']];
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
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) throw new Core\Exception($this, 'ERR_ORM_1', $dir);
    if (!is_writable($dir) && !chmod($dir, 0775)) throw new Core\Exception($this, 'ERR_ORM_2', $dir);
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
    $tmp = explode('_', $class);
    foreach ($tmp as $k => $v) 
    {
      if ($v = trim($v)) $tmp[$k] = ucfirst($v);
      else unset($tmp[$k]);
    }
    return implode('', $tmp);
  }
  
  /**
   * Normalizes a file name.
   *
   * @param string $file
   * @return string
   * @access private
   */
  private function normalizeFile($file)
  {
    return str_replace(['\\', '/', '<', '>', '"', '*', ':', '?', '|'], '', $file);
  }
}