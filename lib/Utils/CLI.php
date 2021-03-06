<?php
/**
 * Copyright (c) 2013 - 2015 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2015 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Utils;

/**
 * This class contains some helpful methods to work with command line.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.1
 * @package aleph.utils
 */
class CLI
{
    /**
     * Available console colors and styles.
     *
     * @var array
     */
    private static $colors = [
        'background' => [
            'black' => 40, 
            'red' => 41, 
            'green' => 42, 
            'yellow' => 43, 
            'blue' => 44, 
            'magenta' => 45, 
            'cyan' => 46, 
            'white' => 47
        ],
        'foreground' => [
            'black' => 30, 
            'red' => 31, 
            'green' => 32, 
            'yellow' => 33, 
            'blue' => 34, 
            'magenta' => 35, 
            'cyan' => 36, 
            'white' => 37
        ],
        'styles' => [
            'bold' => 1,
            'underscore' => 4,
            'blink' => 5,
            'reverse' => 7,
            'conceal' => 8
        ]
    ];

    /**
     * Returns TRUE if the console supports colorization and FALSE otherwise.
     *
     * @return boolean
     */
    public static function hasColorSupport() : bool
    {
        if (DIRECTORY_SEPARATOR == '\\')
        {
            return version_compare('10.0.10586', PHP_WINDOWS_VERSION_MAJOR . '.' . PHP_WINDOWS_VERSION_MINOR . '.' . PHP_WINDOWS_VERSION_BUILD) <= 0 ||
                   getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON' || getenv('TERM') === 'xterm';
        }
        if (!function_exists('posix_isatty'))
        {
            return false;
        }
        $stream = fopen('php://output', 'w');
        $res = @posix_isatty($stream);
        fclose($stream);
        return $res;
    }
  
    /**
     * Returns colored version of the given text for console output.
     * If the given colors of foreground and background as well as styles don't exist it returns the same text.
     *
     * @param string $text - any text data.
     * @param integer|string - the foreground color index or name.
     * @param integer|string - the background color index or name.
     * @param integer|string|array $styles - the text style index(es) or name(s).
     * @return string
     */
    public static function highlight(string $text, $foreground = 'white', $background = 'black', $styles = null) : string
    {
        $tmp = [];
        if (is_int($foreground))
        {
            $tmp[] = $foreground;
        }
        else if (isset(self::$colors['foreground'][$foreground]))
        {
            $tmp[] = self::$colors['foreground'][$foreground];
        }
        if (is_int($background))
        {
            $tmp[] = $background;
        }
        else if (isset(self::$colors['background'][$background]))
        {
            $tmp[] = self::$colors['background'][$background];
        }
        if (is_int($styles))
        {
            $tmp[] = $styles;
        }
        else if ($styles !== null)
        {
            foreach ((array)$styles as $style)
            {
                if (isset(self::$colors['styles'][$style]))
                {
                    $tmp[] = self::$colors['styles'][$style];
                }
            }
        }
        return $tmp ? "\e[" . implode(';', $tmp) . 'm' . $text . "\e[0m" : $text;
    }
}