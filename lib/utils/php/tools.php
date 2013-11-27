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

namespace Aleph\Utils\PHP;

/**
 * Contains useful methods for variety manipulations with PHP code.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.utils.php                                                           
 */
class Tools
{
  /**
   * Splits full class name into array containing two elements of the following structure: [class namespace, own class name].
   * 
   * @param string|object $class
   * @return array
   * @access static
   */
  public static function splitClassName($class)
  {
    if (is_object($class)) $class = get_class($class);
    $k = strrpos($class, '\\');
    if ($k === false) return ['\\', $class];
    return [substr($class, 0, $k), substr($class, $k + 1)];
  }
  
  /**
   * Returns the namespace of the full class name.
   * 
   * @param string|object $class   
   * @return string      
   * @access static   
   */       
  public static function getNamespace($class)
  {
    return static::splitClassName($class)[0];
  }
  
  /**
   * Returns the class name of the full class name (class name with namespace).
   * 
   * @param string|object $class   
   * @return string      
   * @access static   
   */       
  public static function getClassName($class)
  {
    return static::splitClassName($class)[1];
  }
          
  /**
   * Searches the first occurrence or all occurrences of the given PHP code in another PHP code.
   * Returns a numeric array of two elements or FALSE if the PHP fragment is not found.
   * The first element is the number of the first token in the haystack. 
   * The second element is the number of the last token in the haystack.
   * 
   * @param string $needle - the PHP code which we want to find.
   * @param string $haystack  - the PHP code in which we want to find our PHP fragment.
   * @param boolean $all - determines whether all occurrences of the PHP fragment will be found.
   * @return array|boolean
   * @access public
   * @static                          
   */                    
  public static function search($needle, $haystack, $all = false)
  {
    $x = [];
    foreach (Tokenizer::parse($needle) as $token) if (Tokenizer::isSemanticToken($token)) $x[] = $token;
    $m = count($x);
    if ($m == 0) return false;
    $y = Tokenizer::parse($haystack);
    $n = count($y) - $m;
    if ($n < 0) return false;
    $res = [];
    for ($i = 0, $k = 0; $i < $n; $i++)
    {
      $token = $y[$i];
      if (!Tokenizer::isSemanticToken($token)) continue;
      if (Tokenizer::isEqual($x[$k], $token)) 
      {
        $k++;
        if ($k == 1) $start = $i;
        if ($k == $m) 
        {
          if (!$all) return [$start, $i];
          else 
          {
            $res[] = [$start, $i];
            $k = 0;
          }
        }
      }
      else $k = 0;
    }
    return $res ?: false;
  }
   
  /**
   * Checks whether the given PHP code is contained in other one.
   * 
   * @param string $needle
   * @param string $haystack
   * @return boolean
   * @static                    
   */       
  public static function in($needle, $haystack)
  {
    return static::search($needle, $haystack) !== false;
  }
   
  /**
   * Replaces all occurrences of the PHP code fragment in the given PHP code string with another PHP fragment.
   * 
   * @param string $search - the PHP fragment being searched for.
   * @param string $replace - the replacement PHP code that replaces found $search values.
   * @param string $subject - the PHP code string being searched and replaced on.
   * @return string
   * @access public
   * @static                           
   */       
  public static function replace($search, $replace, $subject)
  {
    
  }
   
  /**
   * Removes the given PHP code fragment from the PHP code string.
   * 
   * @param string $search - the PHP fragment being searched for.
   * @param string $subject - the PHP code string being searched and removed from.
   * @return string
   * @access public
   * @static                       
   */       
  public static function remove($search, $subject)
  {
    return static::replace($search, '', $subject);
  }
}