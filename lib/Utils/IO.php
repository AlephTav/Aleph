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
 * Contains helpful methods for working with file systems.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.1
 * @package aleph.utils
 */
class IO
{
    /**
     * Error message templates.
     */
    const ERR_IO_1 = 'File or directory "%s" does not exist.';
    const ERR_IO_2 = '%s is not a directory.';
    const ERR_IO_3 = 'ZIP extension is not loaded.';
    const ERR_IO_4 = 'Unable to open ZIP archive. Error code: %s.';
    
    /**
     * Removes the given directory with all subdirectories and files.
     *
     * @param string $dir - the given directory.
     * @throws RuntimeException
     * @access public
     * @static
     */
    public static function removeDirectory($dir)
    {
        if (!is_dir($dir))
        {
            throw new \RuntimeException(printf(static::ERR_IO_2, $dir));
        }
        if (file_exists($dir))
        {
            $iterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $item)
            {
                if ($item->isDir()) 
                {
                    rmdir($item->getPathname());
                }
                else
                {
                    unlink($item->getPathname());
                }
            }
            rmdir($dir);
        }
    }
  
    /**
     * Removes files in the given directory.
     *
     * @param string $dir - the given directory.
     * @param string $mask - the PCRE compatible regular expression to match with files to be deleted. If $mask started with "i" that only files which don't match the $mask will be deleted.
     * @param boolean $removeRecursively - determines whether files should also be deleted from subdirectories or not.
     * @param array $acceptedMimeTypes - if specified, determines mime types of files that shouldn't be deleted.
     * @return boolean - TRUE on success and FALSE on failure.
     * @access public
     * @static
     */
    public static function removeFiles($dir, $mask = '/.*/', $removeRecursively = false, array $acceptedMimeTypes = null)
    {
        if (!is_dir($dir))
        {
            throw new \RuntimeException(printf(static::ERR_IO_2, $dir));
        }
        if (file_exists($dir))
        {
            if ($removeRecursively)
            {
                $iterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
                $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);
            }
            else
            {
                $iterator = new \DirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
            }
            $mask = strlen($mask) ? $mask : '/.*/';
            if ($mask[0] == 'i')
            {
                $res = 0;
                $mask = substr($mask, 1);
            }
            else
            {
                $res = 1;
            }
            foreach ($iterator as $item)
            {
                if (!$item->isDir())
                {
                    if (preg_match($mask, $item->getFilename()) == $res)
                    {
                        if (!$acceptedMimeTypes || !in_array(mime_content_type($item->getPathname()), $acceptedMimeTypes))
                        {
                            unlink($item->getPathname());
                        }
                    }
                }
            }
        }
    }
  
    /**
     * Creates ZIP archive of the given directory or file.
     *
     * @param string $src - the directory ot file to be zipped.
     * @param string $dest - the desired path to the ZIP archive.
     * @param boolean $includeMainDirectory - determines whether all files will be added under the main directory rather than directly in the $dest folder.
     * @return boolean - TRUE on success and FALSE on failure.
     * @throws RuntimeException
     * @access public
     * @static
     */
    public static function zip($src, $dest, $includeMainDirectory = false)
    {
        if (!extension_loaded('zip'))
        {
            throw new \RuntimeException(static::ERR_IO_3);
        }
        if (!file_exists($src))
        {
            throw new \RuntimeException(sprintf(static::ERR_IO_1, $src));
        }
        $zip = new \ZipArchive();
        if (true !== $err = $zip->open($dest, is_file($dest) ? \ZipArchive::OVERWRITE : \ZipArchive::CREATE))
        {
            throw new \RuntimeException(sprintf(static::ERR_IO_4, $err));
        }
        if (is_dir($src))
        {
            if ($includeMainDirectory)
            {
                $src = pathinfo($src);
                $main = $src['basename'];
                $src = $src['dirname'];
                $zip->addEmptyDir($main);
            }
            $src = realpath($src);
            $len = strlen($src);
            $iterator = new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($iterator as $item)
            {
                if (is_dir($item))
                {
                    $zip->addEmptyDir(substr($item->getRealPath(), $len));
                }
                else if (is_file($item))
                {
                    $zip->addFromString(substr($item->getRealPath(), $len), file_get_contents($item->getPathname()));
                }
            }
        }
        else if (is_file($src))
        {
            $zip->addFromString(basename($src), file_get_contents($src));
        }
        return $zip->close();
    }
}