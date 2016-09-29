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

namespace Aleph\Processes\Synchronization\Interfaces;

/**
 * Interface for all mutex classes.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.processes
 */
interface IMutex
{
    /**
     * Creates mutex object.
     * If $key is not specified, the application identifier will be used.
     *
     * @param string $key The unique identifier of a mutex.
     * @return void
     */
    public function __construct(string $key = null);
    
    /**
     * Attempt to lock the mutex for the caller.
     * An attempt to lock a mutex owned (locked) by another thread
     * will result in blocking.
     *
     * @return bool
     */
    public function lock() : bool;
    
    /**
     * Attempts to lock the mutex for the caller without blocking
     * if the mutex is owned (locked) by another thread.
     *
     * @return bool TRUE if the mutex was locked and FALSE otherwise. 
     */
    public function trylock() : bool;
    
    /**
     * Attempts to unlock the mutex for the caller, optionally destroying
     * the mutex handle. The calling thread should own the mutex at
     * the time of the call.
     *
     * @param bool $destroy
     * @return bool
     */
    public function unlock(bool $destroy = false) : bool;
    
    /**
     * Destroys the mutex.
     *
     * @return bool
     */
    public function destroy() : bool;
}