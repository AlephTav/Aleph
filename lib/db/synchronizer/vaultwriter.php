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

namespace Aleph\DB\Sync;

/**
 * Class for recording database structure changes to the vault.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.db.sync
 * @abstract
 */
class VaultWriter implements IWriter
{
  /**
   * Path to the vault file.
   *
   * @var string $file
   * @access protected
   */
  protected $file = null;

  /**
   * Constructor.
   *
   * @params string $file - path to the vault file.
   * @access public
   */
  public function __construct($file)
  {
    $this->file = $file;
  }
  
  /**
   * Makes changes in database structure and information tables data.
   * If changes were made in db structure then method returns the array of executed SQL queries.
   *
   * @param array $info - the array returned by method Synchronizer::compare.
   * @return array | NULL
   * @access public
   */
  public function write(array $info)
  {
    if (!is_file($this->file)) $data = array();
    else $data = unserialize(gzuncompress(file_get_contents($this->file)));
    $entities = array('tables' => false, 'columns' => true, 'indexes' => true, 'constraints' => true, 'triggers' => true, 'procedures' => false, 'events' => false, 'views' => false);
    foreach ($info['delete'] as $entity => $dta)
    {
      if ($entities[$entity])
      {
        foreach ($dta as $table => $values)
        {
          foreach ($values as $name => $v) unset($data['tables'][$table][$entity][$name]);
        }
      }
      else
      {
        foreach ($dta as $name => $v) unset($data[$entity][$name]);
      }
    }
    foreach ($info['insert'] as $entity => $dta)
    {
      if ($entities[$entity])
      {
        foreach ($dta as $table => $values)
        {
          foreach ($values as $name => $v) $data['tables'][$table][$entity][$name] = $v;
        }
      }
      else
      {
        foreach ($dta as $name => $v) $data[$entity][$name] = $v;
      }
    }
    foreach ($info['update'] as $entity => $dta)
    {
      if ($entity == 'tables')
      {
        foreach ($dta as $table => $values)
        {
          foreach ($values as $ent => $vls)
          {
            if ($ent == 'meta') $data['tables'][$table]['meta'] = $vls;
            else 
            {
              if (is_array($vls)) 
              {
                if (!isset($data['tables'][$table][$ent])) $data['tables'][$table][$ent] = array();
                foreach ($vls as $name => $v) $data['tables'][$table][$ent][$name] = $v;
              }
              else $data['tables'][$table][$ent] = $vls;
            }
          }
        }
      }
      else
      {
        $data[$entity] = array();
        foreach ($dta as $name => $v) $data[$entity][$name] = $v;
      }
    }
    $data['data'] = isset($info['data']) ? $info['data'] : null;
    file_put_contents($this->file, gzcompress(serialize($data), 9));
  }
}