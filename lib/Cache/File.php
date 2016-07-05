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

namespace Aleph\Cache;

use Aleph,
    Aleph\Core;

/**
 * The class is intended for caching of different data using the file system.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.2.2
 * @package aleph.cache
 */
class File extends Cache
{  
    /**
     * Error message templates.
     */
    const ERR_CACHE_FILE_1 = 'Cache directory "%s" is not writable.';
    const ERR_CACHE_FILE_2 = 'Unable to create cache file "%s".';
  
    /**
     * Permissions for newly created cache directory.
     *
     * @var int
     */
    protected $directoryMode = 0711;
  
    /**
     * Permissions for newly created cache files.
     *
     * @var int
     */
    protected $fileMode = 0644;

    /**
     * The directory in which cache files will be stored.
     *
     * @var string $dir
     */
    protected $dir = null;
    
    /**
     * Returns permissions of the cache directory.
     *
     * @return int
     */
    public function getDirectoryMode() : int
    {
        return $this->directoryMode;
    }
    
    /**
     * Sets permissions of the cache directory.
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
     * Returns file permissions.
     *
     * @return int
     */
    public function getFileMode() : int
    {
        return $this->fileMode;
    }
    
    /**
     * Sets permissions of cache files.
     *
     * @param int $mode The cache file permissions.
     * @param bool $change Determines whether permissions of all cache files should set to $mode.  
     * @return bool TRUE on success or FALSE on failure.
     */
    public function setFileMode(int $mode, bool $change = false)
    {
        $this->fileMode = $mode;
        if ($change)
        {
            if (!is_dir($this->dir))
            {
                return false;
            }
            foreach (scandir($this->dir) as $file)
            {
                chmod($this->dir . $file, $this->fileMode);
            }
        }
        return true;
    }
    
    /**
     * Returns the current cache directory.
     *
     * @return string
     */
    public function getDirectory() : string
    {
        return $this->dir;
    }
   
    /**
     * Sets new directory for storing of cache files.
     * If this directory doesn't exist it will be created.
     *
     * @param string $path
     * @return void
     * @throws \RuntimeException If the directory is not writable.
     */
    public function setDirectory(string $path = '')
    {
        $dir = Aleph::dir($path ?: '@cache');
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
     * Returns meta information (expiration time and tags) of the cached data.
     * It returns empty array if the meta data does not exist.
     *
     * @param mixed $key The data key.
     * @return array
     * @access public
     */
    public function getMeta($key) : array
    {
        $meta = $this->getValue(static::META_PREFIX . $this->normalizeKey($key));
        return $meta !== false ? unserialize($meta) : [];
    }
    
    /**
     * Returns some data previously stored in the cache.
     *
     * @param mixed $key The data key.
     * @param mixed $isExpired It will be set to TRUE if the given cache is expired and FALSE otherwise.
     * @return mixed
     */
    public function get($key, &$isExpired = null)
    {                     
        $key = $this->normalizeKey($key);
        $file = $this->dir . $key;
        $isExpired = @filemtime($file) <= time();
        if (!$isExpired)
        {
            $content = $this->getValue($key);
            return $content === false ? null : unserialize($content);
        }
    }

    /**
     * Stores some data identified by a key in the cache.
     *
     * @param mixed $key The data key.
     * @param mixed $content The cached data.
     * @param int $expire The cache lifetime (in seconds). If it is FALSE or zero the cache life time is used.
     * @param string[] $tags An array of tags associated with the data.
     * @return void
     */
    public function set($key, $content, int $expire = 0, array $tags = [])
    {
        $k = $this->normalizeKey($key);
        $expire = $this->normalizeExpire($expire);
        $this->setValue($k, serialize($content), $expire);
        $this->setValue(static::META_PREFIX . $k, serialize([$expire, $tags]), $expire);
        $this->saveKeyToVault($key, $expire, $tags);
    }
    
    /**
     * Updates the previously stored data with new data.
     *
     * @param mixed $key The key of the data being updated.
     * @param mixed $content The new data.
     * @return bool TRUE on success and FALSE on failure (if cache does not exist or expired).
     */
    public function update($key, $content) : bool
    {
        $meta = $this->getMeta($key);
        return $meta ? $this->setValue($this->normalizeKey($key), serialize($content), $meta[0]) : false;
    }
    
    /**
     * Sets a new expiration on an cached data.
     * Returns TRUE on success or FALSE on failure. 
     *
     * @param mixed $key The data key.
     * @param int $expire The new expiration time.
     * @return bool
     */
    public function touch($key, int $expire = 0) : bool
    {
        $meta = $this->getMeta($key);
        if ($meta)
        {
            $expire = $this->normalizeExpire($expire);
            $meta[0] = $expire;
            $key = $this->normalizeKey($key);
            $this->setValue(static::META_PREFIX . $key, serialize($meta), $expire);
            return @touch($this->dir . $key, $expire + time());
        }
        return false;
    }
    
    /**
     * Removes some data identified by a key from the cache.
     *
     * @param mixed $key The data key.
     * @return void
     */
    public function remove($key)
    {
        $key = $this->normalizeKey($key);
        @unlink($this->dir . $key);
        @unlink($this->dir . static::META_PREFIX . $key);
    }

    /**
     * Checks whether cache lifetime is expired or not.
     *
     * @param mixed $key The data key.
     * @return bool
     */
    public function isExpired($key) : bool
    {                      
        return @filemtime($this->dir . $this->normalizeKey($key)) <= time();
    }
  
    /**
     * Removes all previously stored data from the cache.
     *
     * @return void
     */
    public function clean()
    {
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win')
        {
            exec('del /Q /F ' . escapeshellarg($this->dir));
        }
        else
        {
            exec('find ' . escapeshellarg($this->dir) . ' -maxdepth 1 -type f -delete');
        }
    }
  
    /**
     * Removes keys of the expired data from the key vault.
     *
     * @return void
     */
    protected function normalizeVault()
    {
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win')
        {
            exec('forfiles /P ' . escapeshellarg(rtrim($this->dir, '/\\')) . ' /D -0 /C "cmd /c IF @ftime LEQ %TIME:~0,-3% del @file"');
        }
        else
        {
            exec('find ' . escapeshellarg($this->dir) . ' -type f -mmin +0 -delete');
        }
        parent::normalizeVault();
    }
  
    /**
     * Retrieves data from the cache.
     * Returns FALSE on failure.
     *
     * @param string $key - the normalized data key.
     * @return string|bool
     */
    protected function getValue($key) : string
    {
        $fp = @fopen($this->dir . $key, 'rb');
        if ($fp !== false)
        {
            @flock($fp, LOCK_SH);
            $meta = @stream_get_contents($fp);
            @flock($fp, LOCK_UN);
            @fclose($fp);
            return $meta;
        }
        return false;
    }
    
    /**
     * Stores data in the cache.
     *
     * @param string $key The normalized data key.
     * @param string $content The serialized data.
     * @param int $expire The normalized cache expiration time.
     * @return bool TRUE on success and FALSE on failure.
     * @throws \RuntimeException If unable to create a cache file.
     */
    protected function setValue($key, $content, int $expire)
    {
        $file = $this->dir . $key;
        if (@file_put_contents($file, $content, LOCK_EX) !== false)
        {
            @chmod($file, $this->fileMode);
            return @touch($file, $expire + time()); 
        }
        throw new \RuntimeException(sprintf(static::ERR_CACHE_FILE_2, $file));
    }
}