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
 
namespace <?php echo $namespace; ?>;

/**
 * Model class for interaction with the following table<?php echo (count($tables) > 1 ? 's' : ''); ?>: <?php echo $tableList; ?>.
 *
<?php echo $propertyList; ?>
 */
class <?=$class;?> extends <?php echo ($namespace == 'Aleph\DB\ORM' ? 'Model' : '\Aleph\DB\ORM\Model') . PHP_EOL; ?>
{
  /**
   * Database alias.
   *
   * @var string $alias
   * @access protected
   * @static
   */
  protected static $alias = <?php echo $alias; ?>;

  /**
   * Name of the autoincrement property.
   *
   * @var string $ai
   * @access protected
   * @static
   */
  protected static $ai = <?php echo $ai; ?>;
  
  /**
   * Tables, their primary keys and columns, which the model is mapped on.
   *
   * @var array $tables
   * @access protected
   * @static
   */
  protected static $tables = <?php echo $tables; ?>;
  
  /**
   * Meta information of the columns.
   *
   * @var array $columns
   * @access protected
   * @static
   */
  protected static $columns = <?php echo $columns; ?>;
  
  /**
   * SQL for initiation of the model.
   *
   * @var string $RSQL
   * @access protected
   * @static
   */
  protected static $RSQL = <?php echo $RSQL; ?>;
  
  /**
   * The model properties.
   *
   * @var array $properties
   * @access protected
   */
  protected $properties = <?php echo $properties; ?>;
  
  /**
   * The model relational data.
   *
   * @var array $relations
   * @access protected
   */
  protected $relations = <?php echo $relations; ?>;
}