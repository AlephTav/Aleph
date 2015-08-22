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

namespace Aleph\Net;

use Aleph\Utils;

/**
 * The simple container container for uploaded files.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.1
 * @package aleph.net
 */
class FileBag extends Utils\Bag
{
    /**
     * Error message templates.
     */
    const ERR_FILEBAG_1 = 'An uploaded file must be an array or an instance of Aleph\Utils\UploadedFile.';

    /**
     * Structure of the uploaded file array.
     *
     * @var array $fileKeys
     * @access private
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
     * The most of the code of this method is taken from the Symfony framework (see Symfony\Component\HttpFoundation\ServerBag::getHeaders()).
     *
     * @param array $arr - an array of key/value pairs.
     * @param string $delimiter - the default key delimiter in composite keys.
     * @access public
     */
    public function __construct(array $arr = [], $delimiter = Utils\Arr::DEFAULT_KEY_DELIMITER)
    {
        parent::__construct($this->convertFiles($arr), $delimiter);
    }
    
    /**
     * Replaces the current file set by a new one.
     *
     * @param array $files
     * @return static
     * @access public
     */
    public function replace(array $files = [])
    {
        return parent::replace($this->convertFiles($files));
    }
    
    /**
     * Adds new files to the current set.
     *
     * @param array $files
     * @return static
     * @access public
     */
    public function add(array $files = [])
    {
        return parent::add($this->convertFiles($files));
    }
    
    /**
     * Merge existing files with new set.
     *
     * @param array $files
     * @return static
     * @access public
     */
    public function merge(array $files = [])
    {
        return parent::merge($this->convertFiles($files));
    }
  
    /**
     * Sets uploaded file.
     *
     * @param string $name - the uploaded file key.
     * @param mixed $value - the uploaded file information.
     * @param boolean $merge - determines whether the old element value should be merged with new one.
     * @param boolean $compositeKey - determines whether the key is compound key.
     * @param string $delimiter - the key delimiter in composite keys.
     * @return static
     * @access public
     */
    public function set($name, $value, $merge = false, $compositeKey = false, $delimiter = null)
    {
        return parent::set($name, $this->convertFile($value), $merge, $compositeKey, $delimiter);
    }
    
    /**
     * Converts uploaded files to Aleph\Utils\UploadedFile instances.
     *
     * @param array|Aleph\Utils\UploadedFile $file - a (multi-dimensional) array of uploaded file information.
     * @return array - a (multi-dimensional) array of UploadedFile instances.
     * @access protected
     */
    protected function convertFiles(array $files)
    {
        foreach ($files as &$file)
        {
            $file = $this->convertFile($file);
        }
        return $files;
    }
    
    /**
     * Converts uploaded file to Aleph\Utils\UploadedFile instance.
     *
     * @param array|Aleph\Utils\UploadedFile $file - a (multi-dimensional) array of uploaded file information.
     * @return array - a (multi-dimensional) array of UploadedFile instances.
     * @access protected
     */
    protected function convertFile($file)
    {
        if ($file instanceof Utils\UploadedFile)
        {
            return $file;
        }
        if (!is_array($file))
        {
            throw new \InvalidArgumentException(static::ERR_FILEBAG_1);
        }        
        $file = $this->fixArray($file);
        if (is_array($file))
        {
            $keys = array_keys($file);
            sort($keys);
            if ($keys == self::$fileKeys)
            {
                $file = new Utils\UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error']);
            }
            else
            {
                $file = array_map([$this, 'convertFile'], $file);
            }
        }
        return $file;
    }
    
    /**
     * Fixes a malformed PHP $_FILES array.
     * PHP has a bug that the format of the $_FILES array differs, depending on
     * whether the uploaded file fields had normal field names or array-like
     * field names ("normal" vs. "parent[child]").
     * This method fixes the array to look like the "normal" $_FILES array.
     * It's safe to pass an already converted array, in which case this method
     * just returns the original array unmodified.
     *
     * @param array $data
     * @return array
     */
    protected function fixArray($data)
    {
        if (!is_array($data))
        {
            return $data;
        }
        $keys = array_keys($data);
        sort($keys);
        if (self::$fileKeys != $keys || !isset($data['name']) || !is_array($data['name']))
        {
            return $data;
        }
        $files = $data;
        foreach (self::$fileKeys as $k)
        {
            unset($files[$k]);
        }
        foreach ($data['name'] as $key => $name)
        {
            $files[$key] = $this->fixArray([
                'error' => $data['error'][$key],
                'name' => $name,
                'type' => $data['type'][$key],
                'tmp_name' => $data['tmp_name'][$key],
                'size' => $data['size'][$key],
            ]);
        }
        return $files;
    }
}