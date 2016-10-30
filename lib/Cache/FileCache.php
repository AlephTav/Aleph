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

/**
 * The class is intended for caching of different data using the file system.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.3.0
 * @package aleph.cache
 */
class FileCache extends Cache
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
    private $directoryMode = 0711;

    /**
     * Permissions for newly created cache files.
     *
     * @var int
     */
    private $fileMode = 0644;

    /**
     * The directory in which cache files will be stored.
     *
     * @var string $dir
     */
    private $dir = null;

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
        if (is_dir($this->dir)) {
            if (chmod($this->dir, $mode)) {
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
        if ($change) {
            if (!is_dir($this->dir)) {
                return false;
            }
            foreach (scandir($this->dir) as $file) {
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
     * @param int $mode The directory permissions.
     * @return void
     * @throws \RuntimeException If the directory is not writable.
     */
    public function setDirectory(string $path = '', int $mode = null)
    {
        if ($mode !== null) {
            $this->directoryMode = $mode;
        }
        if (!is_dir($path)) {
            mkdir($path, $this->directoryMode, true);
        }
        if (!is_writable($path) && !chmod($path, $this->directoryMode)) {
            throw new \RuntimeException(sprintf(static::ERR_CACHE_FILE_1, $path));
        }
        $this->dir = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Checks whether cache lifetime is expired or not.
     *
     * @param mixed $key The data key.
     * @return bool
     */
    public function isExpired($key) : bool
    {
        $file = $this->dir . $this->normalizeKey($key);
        clearstatcache(true, $file);
        return @filemtime($file) <= time();
    }

    /**
     * Removes all previously stored data from the cache.
     *
     * @return void
     */
    public function clean()
    {
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            exec('del /Q /F ' . escapeshellarg($this->dir));
        } else {
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
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            exec('forfiles /P ' . escapeshellarg(rtrim($this->dir, '/\\')) . ' /D -0 /C "cmd /c IF @ftime LEQ %TIME:~0,-3% del @file"');
        } else {
            exec('find ' . escapeshellarg($this->dir) . ' -type f -mmin +0 -delete');
        }
        parent::normalizeVault();
    }

    /**
     * Retrieves data from the cache.
     *
     * @param string $key The normalized data key.
     * @param mixed $success Set to TRUE in success and FALSE in failure.
     * @return mixed
     */
    protected function fetch(string $key, &$success = null)
    {
        $file = $this->dir . $key;
        $success = false;
        clearstatcache(true, $file);
        if (@filemtime($file) > time()) {
            if (false !== $fp = @fopen($file, 'rb')) {
                if (@flock($fp, LOCK_SH)) {
                    if (false !== $data = @stream_get_contents($fp)) {
                        $data = @unserialize($data);
                        flock($fp, LOCK_UN);
                        fclose($fp);
                        $success = true;
                        return $data;
                    } else {
                        flock($fp, LOCK_UN);
                        fclose($fp);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Stores data in the cache.
     *
     * @param string $key The normalized data key.
     * @param mixed $content The serializable data.
     * @param int $expire The normalized cache expiration time.
     * @return bool TRUE on success and FALSE on failure.
     * @throws \RuntimeException If unable to create a cache file.
     */
    protected function store(string $key, $content, int $expire) : bool
    {
        $file = $this->dir . $key;
        if (false !== @file_put_contents($file, serialize($content), LOCK_EX)) {
            return @chmod($file, $this->fileMode) && @touch($file, $expire + time());
        }
        throw new \RuntimeException(sprintf(static::ERR_CACHE_FILE_2, $file));
    }

    /**
     * Sets a new expiration on an cached data.
     * Returns TRUE on success or FALSE on failure.
     *
     * @param string $key The normalized data key.
     * @param int $expire The new expiration time (in seconds).
     * @return bool
     */
    protected function expire(string $key, int $expire) : bool
    {
        return @touch($this->dir . $key, $expire + time());
    }

    /**
     * Removes a stored data from the cache.
     *
     * @param string $key The normalized data key.
     * @return bool TRUE on success and FALSE on failure.
     */
    protected function delete(string $key) : bool
    {
        return @unlink($this->dir . $key);
    }
}