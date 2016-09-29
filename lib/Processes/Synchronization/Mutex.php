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

use Aleph\Processes\Synchronization\Interfaces\IMutex;

/**
 * The factory for mutex objects.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.processes
 */
final class Mutex
{
    /**
     * The error message templates.
     */
    const ERR_MUTEX_1 = 'The mutex of type "%s" is not supported.';
    
    /**
     * The mutex types.
     */
    const TYPE_FILE = 'file';
    
    /**
     * Creates, and optionally locks a new Mutex for the caller.
     *
     * @param string $type The type of mutex engine.
     * @param bool $lock Determines whether the newly created mutex should be locked.
     * @param array $params The additional mutex parameters, specific for particular mutex type.
     * @return Aleph\Processes\Synchronization\IMutex
     */
    public static function create(string $type = self::TYPE_FILE, bool $lock = false, array $params = []) : IMutex
    {
        switch ($type)
        {
            case self::TYPE_FILE:
                $mutex = new FileMutex($params['key'] ?? null);
                $mutex->setDirectory($params['directory'] ?? '', $params['directoryMode'] ?? null);
                break;
            default:
                throw new \UnexpectedValueException(sprintf(static::ERR_MUTEX_1, $type));
        }
        if ($lock)
        {
            $mutex->lock();
        }
        return $mutex;
    }
}