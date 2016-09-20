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

namespace Aleph\Console\Input;

/**
 * Inteface implemented by all input classes.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.console
 */
interface InputInterface
{
    /**
     * Returns all command arguments.
     *
     * @return array
     */
    public function getArguments() : array;
    
    /**
     * Returns the first command argument.
     *
     * @return string|bool The value of the first argument or FALSE otherwise.
     */
    public function getFirstArgument();
    
    /**
     * Returns the argument value by name.
     *
     * @param string $name The argument name.
     * @return mixed The argument value.
     * @throws \InvalidArgumentException If the given argument doesn't exist.
     */
    public function getArgument(string $name);

    /**
     * Sets an argument value by name.
     *
     * @param string $name The argument name.
     * @param mixed $value The argument value.
     * @return void
     * @throws \InvalidArgumentException If the given argument doesn't exist.
     */
    public function setArgument(string $name, $value);

    /**
     * Returns TRUE if an argument with the given name exists and FALSE otherwise.
     *
     * @param string $name The argument name.
     * @return bool
     */
    public function hasArgument(string $name) : bool;
    
    /**
     * Returns all command options.
     *
     * @return array
     */
    public function getOptions() : array;

    /**
     * Returns the option value by name.
     *
     * @param string $name The option name.
     * @return mixed The option value.
     * @throws \InvalidArgumentException If the given option doesn't exist.
     */
    public function getOption(string $name);

    /**
     * Sets an option value by name.
     *
     * @param string $name The option name.
     * @param mixed $value The option value.
     * @return void
     * @throws \InvalidArgumentException If the given option doesn't exist.
     */
    public function setOption(string $name, $value);

    /**
     * Returns TRUE if an option with the given name exists and FALSE otherwise.
     *
     * @param string $name The option name.
     * @return bool
     */
    public function hasOption(string $name) : bool;
    
    /**
     * Returns an instance of input definition.
     *
     * @return \Aleph\Console\Input\InputDefinition
     */
    public function getDefinition() : InputDefinition;
    
    /**
     * Sets an input definition.
     *
     * @param \Aleph\Console\Input\InputDefinition $definition
     * @return void
     */
    public function setDefinition(InputDefinition $definition);
    
    /**
     * Validates the input according to the given input definition. 
     * Returns TRUE if command parameters match the given input definition, FALSE otherwise.
     *
     * @return bool
     */
    public function validate() : bool;
}