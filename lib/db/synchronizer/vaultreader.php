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

namespace Aleph\DB\Sync;

/**
 * Class is used for reading database structure changes from the vault. 
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.db.sync
 * @abstract
 */
class VaultReader implements IReader
{
  /**
   * Path to the vault file.
   *
   * @var string $file
   * @access protected
   */
  protected $file = null;
  
  /**
   * Database structure received from the vault. 
   *
   * @var array $info
   * @access protected
   */
  protected $info = null;
  
  /**
   * Regular expression for detecting the tables, containing synchronizing data. 
   *
   * @var array $infoTablesPattern
   * @access protected
   */
  protected $infoTablesPattern = null;

  /**
   * Constructor.
   *
   * @param string $file - path to the vault file.
   * @access public
   */
  public function __construct($file)
  {
    $this->file = $file;
  }
  
  /**
   * Sets the regular expression for the names of the tables, containing synchronizing data.
   *
   * @param string $pattern - the regular expression.
   * @access public
   */
  public function setInfoTables($pattern)
  {
    $this->infoTablesPattern = $pattern;
  }
  
  /**
   * Returns the regular expression for detecting the tables, containing synchronizing data.
   * 
   * @return string
   * @access public
   */
  public function getInfoTables()
  {
    return $this->infoTablesPattern;
  }
  
  /**
   * Resets the received data of the database structure. 
   * Repetitive call of the "read" method will allow to receive up-to-date information concerning database structure.
   *
   * @return self
   * @access public
   */
  public function reset()
  {
    $this->info = null;
    return $this;
  }
  
  /**
   * Reads database structure.
   *
   * @return array - you can find the format of returned array in file /lib/db/synchronizer/structure_db.txt
   * @access public
   */
  public function read()
  {
    if ($this->info) return $this->info;
    if (!is_file($this->file)) $this->info = array();
    else $this->info = unserialize(gzuncompress(file_get_contents($this->file)));
    if ($this->infoTablesPattern) foreach ($this->info['data'] as $table => $data)
    {
      if (preg_match($this->infoTablesPattern, $table)) continue;
      unset($this->info['data'][$table]);
    }
    return $this->info;
  }
}