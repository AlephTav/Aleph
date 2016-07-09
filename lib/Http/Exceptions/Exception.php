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

namespace Aleph\Http\Exceptions;

use Aleph\Core;

/**
 * The base class for all HTTP exceptions.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.http
 */
class Exception extends Core\Exception
{
    /**
     * An HTTP response status code.
     *
     * @var int
     */
    protected $statusCode;
    
    /**
     * Constructor.
     *
     * @param string $message The exception message to throw.
     * @param int $statusCode An HTTP response status code.
     * @param int $code The exception code.
     * @param \Throwable $previous The previous exception used for the exception chaining.
     * @return void
     */
    public function __construct(string $message = '', int $statusCode = 200, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, null, $code, $previous);
        $this->statusCode = $statusCode;
    }
    
    /**
     * Returns an HTTP response status code.
     *
     * @return int
     */
    public function getStatusCode() : int
    {
        return $this->statusCode;
    }
}