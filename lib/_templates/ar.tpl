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

namespace <?=$namespace;?>;

/**
 * Active Record class for interaction with <?=$table;?> table.
 *
<?=$properties;?>
 */
class <?=$class;?> extends \Aleph\DB\AR
{
  public function __construct($where = null, $order = null, $metaInfoExpire = null)
  {
    parent::__construct('<?=$table;?>');
    $a = \Aleph::getInstance();
    $a = $a['<?=$dbalias;?>'];
    $this->init(\Aleph::get('db') ?: new DB($a['dsn'], isset($a['username']) ? $a['username'] : null, isset($a['password']) ? $a['password'] : null, isset($a['options']) ? $a['options'] : null), $metaInfoExpire);
    if ($where != '') $this->assign($where, $order); 
  }
}