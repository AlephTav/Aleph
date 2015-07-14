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
 * @version 1.1.2
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
        if ($length == 0)
        {
            return '';
        }
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
            if ($sequence !== false && strlen($sequence) === $length)
            {
                return $sequence;
            }
        }
        if (is_readable('/dev/arandom'))
        {
            $fp = fopen('/dev/arandom', 'rb');
        }
        else if (is_readable('/dev/urandom'))
        {
            $fp = fopen('/dev/urandom', 'rb');
        }
        if (!empty($fp))
        {
            $streamset = stream_set_read_buffer($fp, 0);
            $sequence = fread($fp, $length);
            fclose($fp);
            if (strlen($sequence) === $length)
            {
                return $sequence;
            }
        }
        throw new \RuntimeException('Unable to get random sequence.');
    }

    /**
     * Generates a random alpha-numeric string.
     *
     * @param integer $length - the string length.
     * @param string $alphabet - the alphabet of the generated string. 
     * @return string
     * @access public
     * @static
     */
    public static function getRandomString($length = 32, $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $i = abs((int)$length);
        $max = strlen($alphabet);
        if ($max == 0 || $i == 0)
        {
            return '';
        }
        $max--;
        $str = '';
        while ($i--)
        {
            $str .= $alphabet[static::getRandomInt(0, $max)];
        }
        return $str;
    }
  
    /**
     * Generates a random strong password.
     *
     * @param integer $length - the password length.
     * @param array $alphabet - the array whose keys are the alphabets of the password characters, and the values are probabilities of the alphabet choice.
     * @return string
     * @access public
     * @static
     */
    public static function getRandomPassword($length = 12, array $alphabet = null)
    {
        $i = abs((int)$length);
        if ($i == 0)
        {
            return '';
        }
        if (!$alphabet)
        {
            $alphabet = [
                '0123456789'                         => 1/4,
                'abcdefghijklmnopqrstuvwxyz'         => 1/3,
                'ABCDEFGHIJKLMNOPQRSTUVWXYZ'         => 1/3,
                '~`!@#$%^&*()_-+=[]{}\\|/?.,<>:;"\'' => 1 - 2/3 - 1/4
            ];
        }
        $t = 0; $tmp = [];
        foreach ($alphabet as $set => $p)
        {
            $len = strlen($set);
            if ($len == 0)
            {
                throw new \InvalidArgumentException('Alphabet must not be an empty string.');
            }
            $t += $p;
            $tmp[] = [$t, $len - 1, $set];
        }
        $pass = '';
        while ($i--)
        {
            $p = static::getRandomFloat();
            foreach ($tmp as $v)
            {
                if ($p < $v[0])
                {
                    $pass .= $v[2][static::getRandomInt(0, $v[1])];
                    break;
                }
            }
        }
        return $pass;
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
        if ($max === $min)
        {
            return $max;
        }
        $range = $max - $min + 1;
        if (!is_int($range))
        {
            throw new \InvalidArgumentException('Integer overflow.');
        }
        if ($min > $max)
        {
            $tmp = $max;
            $max = $min;
            $min = $tmp;
        }
        $int = abs(current(unpack((PHP_INT_SIZE == 8 ? 'Q' : 'L') . '*', static::getRandomSequence(PHP_INT_SIZE))));
        return (int)floor($int / PHP_INT_MAX * $range) + $min;
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
            return random_int(0, PHP_INT_MAX) / PHP_INT_MAX;
        }
        return abs(current(unpack((PHP_INT_SIZE == 8 ? 'Q' : 'L') . '*', static::getRandomSequence(PHP_INT_SIZE)))) / PHP_INT_MAX;
    }
}