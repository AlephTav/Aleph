<?php
/**
 * Copyright (c) 2013 - 2016 Aleph Tav
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

use Aleph\Core\Interfaces\IHashable;

/**
 * Contains methods for simplifying the work with strings.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.1
 * @package aleph.utils
 */
class Str
{
    /**
     * Returns the hash of an item.
     *
     * @param mixed $item Any data to be hashed.
     * @param string $algo Name of selected hashing algorithm (e.g. "md5", "sha256", "haval160,4", etc..)
     * @param bool $rawOutput When set to TRUE, outputs raw binary data. FALSE outputs lowercase hexits.
     * @return string
     */
    public static function hash($item, string $algo = 'md5', bool $rawOutput = true) : string
    {
        if (is_object($item)) {
            if ($item instanceof IHashable) {
                return $item->hash();
            }
            return hash($algo, spl_object_hash($item), $rawOutput);
        }
        if (is_array($item)) {
            $hash = '';
            foreach ($item as $v) {
                $hash .= self::hash($v);
            }
            return hash($algo, $hash, $rawOutput);
        }
        return hash($algo, $item, $rawOutput);
    }

    /**
     * Cuts a large text.
     *
     * @param string $str The large text.
     * @param integer $length Length of the shortened text.
     * @param bool $word Determines need to reduce the given string to the nearest word to the right.
     * @param bool $stripTags Determines whether HTML and PHP tags will be deleted or not.
     * @param string $allowableTags Specifies tags which should not be stripped.
     * @return string The shortened text.
     */
    public static function cut(string $str, int $length, bool $word = true,
                               bool $stripTags = false, string $allowableTags = null)
    {
        if ($stripTags) {
            $str = strip_tags($str, $allowableTags);
        }
        if ($length < 4 || $str === '' || strlen($str) <= $length) {
            return $str;
        }
        $lastSpacePos = strrpos($str, ' ', -1);
        if ($word) {
            if ($lastSpacePos === false) {
                $shortText = $str;
            } else {
                $shortText = substr($str, 0, $lastSpacePos) . '...';
            }
        } else {
            if ($lastSpacePos > $length || $lastSpacePos === false) {
                $shortText = trim(substr($str, 0, $length - 3)) . '...';
            } else {
                $shortText = substr($str, 0, $lastSpacePos) . '...';
            }
        }
        return $shortText;
    }
}