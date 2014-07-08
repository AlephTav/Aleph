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
    Aleph\Utils;

/**
 * The class is designed for generation of all ORM classes.
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
  const ERR_GENERATOR_1 = 'Database alias "[{var}]" doesn\'t exist in configuration file.';
  const ERR_GENERATOR_2 = 'Directory "[{var}]" is not writable.';

  protected $db = null;
  
  protected $dbalias = null;

  public function __construct($dbalias)
  {
    $a = \Aleph::getInstance();
    $a = $a[$dbalias];
    if ($a === null) throw new Core\Exception($this, 'ERR_GENERATOR_1', $dbalias);
    $this->db = new DB\DB($a['dsn'], isset($a['username']) ? $a['username'] : null, isset($a['password']) ? $a['password'] : null, isset($a['options']) ? $a['options'] : null);
    $this->dbalias = $dbalias;
  }

  public function createARClasses($namespace)
  {
    $a = \Aleph::getInstance();
    $dir = $this->createDirectory(\Aleph::dir($a['orm']['arDirectory']) . '/' . $this->normalizeFile($this->dbalias));
    $tpl = new Core\Template(\Aleph::dir($a['orm']['arTemplate']));
    $tpl->namespace = $namespace;
    $tpl->dbalias = $this->dbalias;
    foreach ($this->db->getTableList() as $table)
    {
      $file = $dir . '/' . $this->normalizeFile($table) . '.php';
      $properties = array();
      foreach ($this->db->getColumnsInfo($table) as $column)
      {
        $properties[] = ' * @property ' . $column['phpType'] . ' $' . $column['column'];
      }
      $properties = implode(PHP_EOL, $properties) . PHP_EOL;
      if (!is_file($file))
      {
        $tpl->table = $table;
        $tpl->class = $this->normalizeClass($table);
        $tpl->properties = $properties;
        file_put_contents($file, '<?php' . PHP_EOL . $tpl->render());
      }
      else
      {
        $tpl->table = $table;
        $tpl->class = $this->normalizeClass($table);
        $tpl->properties = $properties;
        $class1 = new Utils\InfoClass($namespace . '\\' . $tpl->class);
        $rnd = uniqid();
        $file .= $rnd;
        $tpl->class .= $rnd;
        file_put_contents($file, '<?php' . PHP_EOL . $tpl->render());
        require_once($file);
        $class2 = new Utils\InfoClass($namespace . '\\' . $tpl->class);
        $class1['comment'] = $class2['comment'];
        $class1->save();
        unlink($file);
      }
    }
  }
  
  protected function normalizeClass($class)
  {
    $tmp = explode('_', $class);
    foreach ($tmp as $k => $v) 
    {
      if ($v = trim($v)) $tmp[$k] = ucfirst($v);
      else unset($tmp[$k]);
    }
    return implode('', $tmp);
  }
  
  protected function normalizeFile($file)
  {
    return str_replace(array('\\', '/', '<', '>', '"', '*', ':', '?', '|'), '', $file);
  }
  
  protected function createDirectory($directory)
  {
    $dir = \Aleph::dir($directory);
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    if (!is_writable($dir) && !chmod($dir, 0775)) throw new Core\Exception($this, 'ERR_GENERATOR_2', $dir);
    return $dir;
  }
}