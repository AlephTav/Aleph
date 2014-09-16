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
 * DBAL class for interaction with table(s): <?php echo $tableList; ?>.
 *
<?php echo $properties; ?>
 */
class <?=$class;?>Model extends \Aleph\DB\ORM\Model
{
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
  protected static $RSQL = '<?php echo $RSQL; ?>';
  
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
  
  /**
   * Constructor.
   * Tries to map a row of the database table to the model properties 
   * or initializes the model properties with the default values.
   *
   * @param mixed $values - values of the model properties for row searching.
   * @param mixed $order - the ORDER BY clause condition.
   * @access public
   */
  public function __construct($values = null, $order = null)
  {
    parent::__construct('<?php echo $alias; ?>', $values, $order);
  }
}