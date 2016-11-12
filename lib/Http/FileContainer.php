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
 * @copyright Copyright &copy; 2013 - 2016 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Http;

use Aleph\Data\Structures\Container;
use Aleph\Utils\Arr;

/**
 * The simple container container for uploaded files.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.2
 * @package aleph.http
 */
class FileContainer extends Container
{
    /**
     * Error message templates.
     */
    const ERR_FILEBAG_1 = 'An uploaded file must be an array of the proper format or an instance of Aleph\Utils\UploadedFile.';

    /**
     * Structure of the uploaded file array.
     *
     * @var array
     */
    private static $fileKeys = [
        'error',
        'name',
        'size',
        'tmp_name',
        'type'
    ];

    /**
     * Constructor.
     *
     * @param array $files An array of key/value pairs.
     * @param string $delimiter The default key delimiter in composite keys.
     */
    public function __construct(array $files = [], string $delimiter = Arr::DEFAULT_KEY_DELIMITER)
    {
        parent::__construct($this->convertFiles($files), $delimiter);
    }

    /**
     * Replaces the current file set by a new one.
     *
     * @param array $files
     * @return \Aleph\Data\Structures\Container
     */
    public function replace(array $files = []) : Container
    {
        return parent::replace($this->convertFiles($files));
    }

    /**
     * Adds new files to the current set.
     *
     * @param array $files
     * @return \Aleph\Data\Structures\Container
     */
    public function add(array $files = []) : Container
    {
        return parent::add($this->convertFiles($files));
    }

    /**
     * Merge existing files with new set.
     *
     * @param array $files
     * @return \Aleph\Data\Structures\Container
     */
    public function merge(array $files = []) : Container
    {
        return parent::merge($this->convertFiles($files));
    }

    /**
     * Sets uploaded file.
     *
     * @param string $name The uploaded file key.
     * @param mixed $value The uploaded file information.
     * @param bool $merge Determines whether the old element value should be merged with new one.
     * @param bool $compositeKey Determines whether the key is a compound key.
     * @param string $delimiter The key delimiter in compound keys.
     * @return \Aleph\Data\Structures\Container
     */
    public function set($name, $value, bool $merge = false,
                        bool $compositeKey = false, string $delimiter = '') : Container
    {
        return parent::set($name, $this->convertFile($value), $merge, $compositeKey, $delimiter);
    }

    /**
     * Converts uploaded files to an array of Aleph\Http\UploadedFile instances.
     *
     * @param array $files A multi-dimensional array with information about uploaded files.
     * @return \Aleph\Http\UploadedFile[]
     */
    protected function convertFiles(array $files) : array
    {
        foreach ($files as &$file) {
            $file = $this->convertFile($file);
        }
        return $files;
    }

    /**
     * Converts information about an uploaded file to \Aleph\Http\UploadedFile instance.
     *
     * @param mixed $file Information about an uploaded file or instance of UploadedFile.
     * @return \Aleph\Http\UploadedFile[]|\Aleph\Http\UploadedFile
     * @throws \InvalidArgumentException
     */
    protected function convertFile($file)
    {
        if ($file instanceof UploadedFile) {
            return $file;
        }
        if (!is_array($file) || !array_intersect(array_keys($file), self::$fileKeys)) {
            throw new \InvalidArgumentException(static::ERR_FILEBAG_1);
        }
        if (!is_array($file['name'])) {
            return new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error']);
        }
        $files = [];
        foreach ($file['name'] as $key => $name) {
            $files[] = new UploadedFile([
                'error' => $file['error'][$key],
                'name' => $name,
                'type' => $file['type'][$key],
                'tmp_name' => $file['tmp_name'][$key],
                'size' => $file['size'][$key],
            ]);
        }
        return count($files) == 1 ? $files[0] : $files;
    }
}