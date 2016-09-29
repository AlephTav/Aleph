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

namespace Aleph\Processes\Synchronization;

use Aleph;
use Aleph\Processes\Synchronization\Interfaces\IMutex;

/**
 * Files-based mutex.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.processes
 */
class FileMutex implements IMutex
{
    /**
     * The unique identifier associated with a mutex object.
     *
     * @var string
     */
    private $key = '';
    
    /**
     * The mutex file handler.
     *
     * @var resource
     */
    private $fh = null;
    
    /**
     * The directory in which mutex files will be stored.
     *
     * @var string $dir
     */
    private $dir = null;
    
    /**
     * Permissions for newly created mutex directory.
     *
     * @var int
     */
    private $directoryMode = 0711;
    
    /**
     * Creates mutex object.
     * If $key is not specified, the application identifier will be used.
     *
     * @param string $key The unique identifier of a mutex.
     * @return void
     */
    public function __construct(string $key = null)
    {
        $this->key = md5($key !== null ? $key : Aleph::getAppUniqueID());
    }
    
    /**
     * Returns the current mutex directory.
     *
     * @return string
     */
    public function getDirectory() : string
    {
        return $this->dir;
    }
   
    /**
     * Sets new directory for mutex files.
     * If this directory doesn't exist, it will be automatically created.
     *
     * @param string $path
     * @param int $mode The directory permissions.
     * @return void
     * @throws \RuntimeException If the directory is not writable.
     */
    public function setDirectory(string $path = '', int $mode = null)
    {
        $dir = Aleph::dir($path ?: '@mutex');
        if ($mode !== null)
        {
            $this->directoryMode = $mode;
        }
        if (!is_dir($dir))
        {
            mkdir($dir, $this->directoryMode, true);
        }
        if (!is_writable($dir) && !chmod($dir, $this->directoryMode))
        {
            throw new \RuntimeException(sprintf(static::ERR_CACHE_FILE_1, $dir));
        }
        $this->dir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
    }
    
    /**
     * Returns permissions of the mutex directory.
     *
     * @return int
     */
    public function getDirectoryMode() : int
    {
        return $this->directoryMode;
    }
    
    /**
     * Sets permissions of the mutex directory.
     *
     * @param int $mode The directory permissions.
     * @return bool TRUE on success or FALSE on failure.
     */
    public function setDirectoryMode(int $mode) : bool
    {
        if (is_dir($this->dir))
        {
            if (chmod($this->dir, $mode))
            {
                $this->directoryMode = $mode;
                return true;
            }
        }
        return false;
    }
    
    /**
     * Attempt to lock the mutex for the caller.
     * An attempt to lock a mutex owned (locked) by another thread
     * will result in blocking.
     *
     * @return bool
     */
    public function lock() : bool
    {
        while (!$this->createFile())
        {
            usleep(rand(1000, 50000));
        }
        return flock($this->fh, LOCK_EX);;
    }
    
    /**
     * Attempts to lock the mutex for the caller without blocking
     * if the mutex is owned (locked) by another thread.
     *
     * @return bool TRUE if the mutex was locked and FALSE otherwise. 
     */
    public function trylock() : bool
    {
        if ($this->createFile())
        {
            return flock($this->fh, LOCK_EX | LOCK_NB);
        }
        return false;
    }
    
    /**
     * Attempts to unlock the mutex for the caller, optionally destroying
     * the mutex handle. The calling thread should own the mutex at
     * the time of the call.
     *
     * @param bool $destroy
     * @return bool
     */
    public function unlock(bool $destroy = false) : bool
    {
        if (flock($this->fh, LOCK_UN) && fclose($this->fh))
        {
            if ($destroy)
            {
                return $this->destroy();
            }
            return true;
        }
        return false;
    }
    
    /**
     * Destroys the mutex.
     *
     * @return bool
     */
    public function destroy() : bool
    {
        return @unlink($this->dir . $this->key);
    }
    
    /**
     * Destroys the mutex object.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->destroy();
    }
    
    /**
     * Creates mutex file.
     *
     * @return resource
     */
    private function createFile()
    {
        return $this->fh = fopen($this->dir . $this->key, 'c');
    }
}