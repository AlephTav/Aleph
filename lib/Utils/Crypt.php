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
 * This class contains some helpful methods that can be used in cryptography purposes.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.1.0
 * @package aleph.utils
 */
class Crypt
{
    /**
     * Generates a pseudo-random sequence of bytes.
     *
     * @param integer $length - the sequence length.
     * @return string
     * @access public
     * @static
     */
    public static function getRandomSequence($length = 32)
    {
        $length = abs((int)$length);
        if (PHP_MAJOR_VERSION >= 7)
        {
            return random_bytes($length);
        }
        if (function_exists('openssl_random_pseudo_bytes'))
        {
            $sequence = openssl_random_pseudo_bytes($length, $strong);
            if ($sequence !== false && $strong === true)
            {
                return $sequence;
            }
        }
        if (function_exists('mcrypt_create_iv'))
        {
            $sequence = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
            if ($sequence !== false)
            {
                return $sequence;
            }
        }
        $sha = uniqid(mt_rand(), true);
        if (file_exists('/dev/urandom'))
        {
            $fp = fopen('/dev/urandom', 'rb');
            if ($fp)
            {
                if (function_exists('stream_set_read_buffer'))
                {
                    stream_set_read_buffer($fp, 0);
                }
                $sha = fread($fp, $length);
                fclose($fp);
            }
        }
        $sequence = '';
        for ($i = 0; $i < $length; $i++)
        {
            $sha = hash('sha256', $sha . $i . uniqid(mt_rand(), true));
            $char = mt_rand(0, 62);
            $sequence .= chr(hexdec($sha[$char] . $sha[$char + 1]));
        }
        return $sequence;
    }

    /**
     * Generates a random alpha-numeric string.
     *
     * @param integer $length - the string length.
     * @return string
     * @access public
     * @static
     */
    public static function getRandomString($length = 32)
    {
        $length = abs((int)$length);
        $sequence = static::getRandomSequence($length << 1);
        return substr(str_replace(['=', '+', '/'], '', base64_encode($sequence)), 0, $length);
    }
  
    /**
     * Generates a random strong password.
     *
     * @param integer $length - the password length.
     * @return string
     * @access public
     * @static
     */
    public static function getRandomPassword($length = 12)
    {
        $length = abs((int)$length);
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ~`!@#$%^&*()_-+=[]{}\\|/?.,<>:;"\'';
        return substr(str_shuffle(str_repeat($chars, $length)), 0, $length);
    }
    
    /**
     * Generates cryptographically secure pseudo-random integer value between $min and $max.
     *
     * @param $min - the minimum value.
     * @param $max - the maximum value.
     * @return integer
     * @access public
     * @static
     */
    public static function getRandomInt($min, $max)
    {
        if (PHP_MAJOR_VERSION >= 7)
        {
            return random_int($min, $max);
        }
        $max = (int)$max;
        $min = (int)$min;
        if ($max == $min)
        {
            return $max;
        }
        $int = abs(current(unpack((PHP_INT_SIZE == 8 ? 'Q' : 'L') . '*', static::getRandomSequence(PHP_INT_SIZE))));
        return floor($int / PHP_INT_MAX * ($max - $min + 1)) + $min;
    }
    
    /**
     * Generates cryptographically secure pseudo-random float value between 0 and 1.
     *
     * @return float
     * @access public
     * @static
     */
    public static function getRandomFloat()
    {
        if (PHP_MAJOR_VERSION >= 7)
        {
            return (random_int(PHP_INT_MIN, PHP_INT_MAX) - PHP_INT_MIN) / (PHP_INT_MAX - PHP_INT_MIN);
        }
        return abs(current(unpack((PHP_INT_SIZE == 8 ? 'Q' : 'L') . '*', static::getRandomSequence(PHP_INT_SIZE)))) / PHP_INT_MAX;
    }
}