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

namespace Aleph\Utils;

/**
 * Class for converting english words from the plural form to the singular form and vice versa.
 *
 * @author Sho Kuwamoto <sho@kuwamoto.org>
 * @version 1.0.0
 * @package aleph.utils
 */
class Inflect
{
  /**
   * Regular expressions for detecting and replacing the singular form of a word with the plural form.
   *
   * @var array $plural
   * @access private
   * @static
   */
  private static $plural = [
    '/(quiz)$/i'               => "$1zes",
    '/^(ox)$/i'                => "$1en",
    '/([m|l])ouse$/i'          => "$1ice",
    '/(matr|vert|ind)ix|ex$/i' => "$1ices",
    '/(x|ch|ss|sh)$/i'         => "$1es",
    '/([^aeiouy]|qu)y$/i'      => "$1ies",
    '/(hive)$/i'               => "$1s",
    '/(?:([^f])fe|([lr])f)$/i' => "$1$2ves",
    '/(shea|lea|loa|thie)f$/i' => "$1ves",
    '/sis$/i'                  => "ses",
    '/([ti])um$/i'             => "$1a",
    '/(tomat|potat|ech|her|vet)o$/i'=> "$1oes",
    '/(bu)s$/i'                => "$1ses",
    '/(alias)$/i'              => "$1es",
    '/(octop)us$/i'            => "$1i",
    '/(ax|test)is$/i'          => "$1es",
    '/(us)$/i'                 => "$1es",
    '/s$/i'                    => "s",
    '/$/'                      => "s"
  ];
    
  /**
   * Regular expressions for detecting and replacing the plural form of a word with the singular form.
   *
   * @var array $singular
   * @access private
   * @static
   */
  private static $singular = [
    '/(quiz)zes$/i'             => "$1",
    '/(matr)ices$/i'            => "$1ix",
    '/(vert|ind)ices$/i'        => "$1ex",
    '/^(ox)en$/i'               => "$1",
    '/(alias)es$/i'             => "$1",
    '/(octop|vir)i$/i'          => "$1us",
    '/(cris|ax|test)es$/i'      => "$1is",
    '/(shoe)s$/i'               => "$1",
    '/(o)es$/i'                 => "$1",
    '/(bus)es$/i'               => "$1",
    '/([m|l])ice$/i'            => "$1ouse",
    '/(x|ch|ss|sh)es$/i'        => "$1",
    '/(m)ovies$/i'              => "$1ovie",
    '/(s)eries$/i'              => "$1eries",
    '/([^aeiouy]|qu)ies$/i'     => "$1y",
    '/([lr])ves$/i'             => "$1f",
    '/(tive)s$/i'               => "$1",
    '/(hive)s$/i'               => "$1",
    '/(li|wi|kni)ves$/i'        => "$1fe",
    '/(shea|loa|lea|thie)ves$/i'=> "$1f",
    '/(^analy)ses$/i'           => "$1sis",
    '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => "$1$2sis",        
    '/([ti])a$/i'               => "$1um",
    '/(n)ews$/i'                => "$1ews",
    '/(h|bl)ouses$/i'           => "$1ouse",
    '/(corpse)s$/i'             => "$1",
    '/(us)es$/i'                => "$1",
    '/s$/i'                     => ""
  ];
    
  /**
   * List of singular and plural forms of the irregular nouns.
   *
   * @var array $irregular
   * @access private
   * @static
   */
  private static $irregular = [
    'move'   => 'moves',
    'foot'   => 'feet',
    'goose'  => 'geese',
    'sex'    => 'sexes',
    'child'  => 'children',
    'man'    => 'men',
    'tooth'  => 'teeth',
    'person' => 'people'
  ];
    
  /**
   * List of uncountable nouns.
   *
   * @var array $uncountable
   * @access private
   * @static
   */
  private static $uncountable = [ 
    'sheep', 
    'fish',
    'deer',
    'series',
    'species',
    'money',
    'rice',
    'information',
    'equipment'
  ];
    
  /**
   * Converts a word from the singular form to the plural form.
   *
   * @param string $str - a word.
   * @return string
   * @access public
   * @static
   */
  public static function pluralize($str) 
  {
    if (in_array(strtolower($str), self::$uncountable)) return $str;
    foreach (self::$irregular as $pattern => $result)
    {
      $pattern = '/' . $pattern . '$/i';
      if (preg_match($pattern, $str)) return preg_replace($pattern, $result, $str);
    }   
    foreach (self::$plural as $pattern => $result)
    {
      if (preg_match($pattern, $str)) return preg_replace($pattern, $result, $str);
    }    
    return $str;
  }
    
  /**
   * Converts a word from the plural form to the singular form.
   *
   * @param string $str - a word.
   * @return string
   * @access public
   * @static
   */
  public static function singularize($str)
  {
    if (in_array(strtolower($str), self::$uncountable)) return $str;
    foreach (self::$irregular as $result => $pattern)
    {
      $pattern = '/' . $pattern . '$/i';      
      if (preg_match($pattern, $str)) return preg_replace($pattern, $result, $str);
    }
    foreach (self::$singular as $pattern => $result)
    {
      if (preg_match($pattern, $str)) return preg_replace($pattern, $result, $str);
    }    
    return $str;
  }
}