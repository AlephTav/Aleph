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
   * Searches the occurrence of a php-code in other php-code.
   * Returns a numeric array of two elements. 
   * The first element is the number of the first token in the string to search. 
   * The second element is the number of the last token in the string to search.
   * 
   * @param string $needle
   * @param string $haystack  
   * @return array|boolean - returns FALSE if php-fragment is not found.
   * @access public
   * @static                          
   */                    
  public static function search($needle, $haystack)
  {
    $tokens = ['needle' => self::getTokens($needle), 'haystack' => self::getTokens($haystack)];
    $i = $j = 0; 
    $start = $end = -1;
    do
    {
         $needle = self::getNextToken($tokens['needle'], $j);
         if ($needle === false) break;
         $haystack = self::getNextToken($tokens['haystack'], $i);
         if ($haystack === false) break;
         if (self::isEqual($haystack, $needle))
         {
            if ($start < 0) $start = $i - 1;
            $end = $i - 1;
         }
         else 
         {
            $start = $end = -1; 
            $j = 0;
         }
         $k++;
    }
    while (1);
    if ($end > 0 && $needle === false) return [$start, $end];
    return false;
  }
   
  /**
   * Checks whether or not containing a php-code to other php-code.
   * 
   * @param string $needle
   * @param string $haystack
   * @return boolean
   * @static                    
   */       
  public static function in($needle, $haystack)
  {
    return (self::posInCode($needle, $haystack) !== false);
  }
   
   /**
    * Replaces a php-code an other php-code.
    * 
    * @param string $search
    * @param string $replace
    * @param string $subject 
    * @return string
    * @access public
    * @static                           
    */       
   public static function replaceCode($search, $replace, $subject)
   {
      if (($pos = self::posInCode($search, $subject)) === false) return $subject;       
      $tokens = self::getTokens($subject);
      for ($i = 0; $i < count($tokens); $i++)
      {
         if ($i == $pos[0])
         { 
            $i = $pos[1] + 1;
            $code .= $replace; 
         }
         $code .= (is_array($tokens[$i])) ? $tokens[$i][1] : $tokens[$i];
      }  
      return $code;
   }
   
   /**
    * Removes a php-fragment from a php-code string.
    * 
    * @param string $search
    * @param string $subject
    * @return string
    * @access public
    * @static                       
    */       
   public static function removeCode($search, $subject)
   {
      return self::replaceCode($search, '', $subject);
   }
   
   /**
    * Get all the class names from a file. $file can also be a link to the file
    *
    * @param string $file
    * @return array
    * @access public
    * @static                    
    */
   public static function getFullClassNames($file)
   {
      $tmp = array();
      $tokens = self::getTokens(is_file($file) ? file_get_contents($file) : $file);
      foreach ($tokens as $n => $token)
      {
         if ($token[0] == T_NAMESPACE) 
         {
            $i = $n;
            do
            {
               $tkn = $tokens[++$i];
               if ($tkn[0] == T_STRING || $tkn[0] == T_NS_SEPARATOR) $namespace .= $tkn[1];
            }
            while ($tkn != ';');
            $namespace .= '\\';
         }
         else if ($token[0] == T_CLASS || $token[0] == T_INTERFACE) 
         {
            do
            {
               $token = $tokens[++$n];
            }
            while ($token[0] != T_STRING);
            $tmp[] = '\\' . $namespace . $token[1];
         }
      }
      return $tmp;
   }
   
   /**
    * Returns the next token of token array.
    * 
    * @param array $tokens;
    * @param integer $pos
    * @return array|boolean returns FALSE if the current token is the last.
    * @access private
    * @static                         
    */       
   private static function getNextToken(array $tokens, &$pos)
   {
      for ($i = $pos; $i < count($tokens); $i++)
      {
         $token = $tokens[$i];
         if (in_array($token[0], array(T_DOC_COMMENT, T_LINE, T_COMMENT, T_WHITESPACE, T_OPEN_TAG, T_CLOSE_TAG))) continue;
         $pos = $i + 1;
         return $token;
      }
      return false;    
   }
   
  /**
   * Checks the equality of two tokens.
   * 
   * @param string|array $token1
   * @param string|array $token2
   * @return boolean
   * @access private
   * @static                        
   */       
  private static function isEqual($token1, $token2)
  {
    return (is_array($token1) && is_array($token2) && $token1[1] === $token2[1] || $token1 == $token2);  
  }  
}