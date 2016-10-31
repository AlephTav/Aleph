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

namespace Aleph\Core;

use Aleph;

/**
 * Exception allows to generate user exceptions with some additional information about exception.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 2.0.1
 * @package aleph.core
 */
class Exception extends \Exception
{
    /**
     * Additional information about the exception.
     *
     * @var mixed
     */
    private $data = null;

    /**
     * Constructor.
     *
     * @param string $message The exception message to throw.
     * @param mixed $data Some additional information about the exception.
     * @param int $code The exception code.
     * @param \Throwable $previous The previous exception used for the exception chaining.
     */
    public function __construct(string $message = '', $data = null, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }

    /**
     * Returns the data associated with the exception, otherwise it returns the exception message.
     *
     * @return mixed
     */
    public function getDataOrMessage()
    {
        return $this->data !== null ? $this->data : $this->getMessage();
    }

    /**
     * Returns additional information about the exception.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Sets additional information about the exception.
     *
     * @param mixed $data
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Returns full information about the current exception.
     *
     * @return array
     */
    public function getInfo() : array
    {
        return Aleph::analyzeException($this);
    }
}