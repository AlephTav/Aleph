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

namespace Aleph\Console;

use Aleph,
    Aleph\Core\Traits,
    Aleph\Console\Exceptions,
    Aleph\Console\Commands,
    Aleph\Console\Output;

/**
 * General class for executing console commands.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.console
 */
class Controller
{
    use Traits\Singleton;
    
    /**
     * Error message templates.
     */
    const ERR_CTRL_1 = 'Command "%s" does not exist.';
    
    /**
     * The available commands.
     *
     * @var array
     */
    protected $commands = [];
    
    /**
     * The name of a default command.
     *
     * @var string
     */
    protected $defaultCommand = 'list';
    
    /**
     * The default command options.
     *
     * @var array
     */
    protected $defaultCommandOptions = [];
    
    /**
     * The command running at the moment.
     */
    protected $runningCommand = null;
    
    /**
     * Returns TRUE if the current interface type is interactive console and FALSE otherwise.
     *
     * @return bool
     */
    public static function isConsoleAvailable() : bool
    {
        return PHP_SAPI === 'cli';
    }
    
    /**
     * Run a console command by name.
     *
     * @param string $command The command name.
     * @param array $ptions The command options.
     * @param mixed $out If specified it will contain the command output.
     * @return int Command exit status.
     */
    public static function call(string $command, array $options = [], &$out = null)
    {
        array_unshift($options, $command);
        $instance = static::getInstance();
        $input = new Input\ArrayInput($options);
        if ($out === null)
        {
            $output = new Output\NullOutput();
            return $istance->run($input, $output);
        }
        $output = new Output\ConsoleOutput();
        ob_start();
        $exitCode = $istance->run($input, $output);
        $out = ob_get_clean();
        return $exitCode;
    }
    
    /**
     * Constructor.
     *
     * @return void
     */
    private function __construct()
    {
        if (static::isConsoleAvailable())
        {
            Aleph::setErrorHandler(function(\Throwable $e, array $info) {return $this->errorHandler($e, $info);});
        }
        $this->loadCommandsFromConfig();
    }
    
    /**
     * Executes a console command.
     *
     * @param \Aleph\Console\Input\InputInterface $input
     * @param \Aleph\Console\Output\OutputInterface $output
     * @return int Command exit status. 
     * @throws \RuntimeException
     */
    public function run(Input\InputInterface $input = null, Output\OutputInterface $output = null)
    {
        if (!$input)
        {
            $input = new Input\ArgvInput();
        }
        if (!$output)
        {
            $output = new Output\ConsoleOutput();
        }
        $command = $input->getFirstArgument(); 
        if (!$command)
        {
            $command = $this->defaultCommand;
            $options = $this->defaultCommandOptions;
            array_unshift($options, $command);
            $input = new Input\ArrayInput($options);
        }
        $command = $this->get($command);
        $this->runningCommand = $command;
        $exitCode = $command->run($input, $output);
        $this->runningCommand = null;
        return $exitCode;
    }
    
    /**
     * Loads commands from the application config.
     *
     * @param bool $replace Determines whether the previous commands should be removed from the controller.
     * @return void
     */
    public function loadCommandsFromConfig(bool $replace = false)
    {
        if ($replace)
        {
            $this->commands = [];
        }
        foreach (Aleph::get('console.commands', []) as $command)
        {
            $this->addCommand(new $command);
        }
    }
    
    /**
     * Adds an array of command objects.
     *
     * @param \Aleph\Console\Commands\Command[] $commands An array of commands.
     * @return void
     */
    public function addCommands(array $commands)
    {
        foreach ($commands as $command)
        {
            $this->add($command);
        }
    }
    
    /**
     * Adds a command instance.
     *
     * @param \Aleph\Console\Commands\Command $command A command instance.
     * @return void
     */
    public function add(Commands\Command $command)
    {
        $command->setController($this);
        $this->commands[$command->getName()] = $command;
    }
    
    /**
     * Returns a registered command by name.
     *
     * @param string $command The command name.
     * @return \Aleph\Console\Commands\Command A command object.
     * @throws \OutOfBoundsException If the given command name does not exist.
     */
    public function get(string $command) : Commands\Command
    {
        $this->has($command, true);
        return is_object($command) ? $this->commands[$command] : $this->commands[$command] = new $command();
    }
    
    /**
     * Returns TRUE if a command exists and FALSE otherwise.
     *
     * @param string $command The command name.
     * @return bool
     */
    public function has(string $command, bool $throwException = false) : bool
    {
        if (false === $flag = ($command !== '' && isset($this->commands[$command])) && $throwException)
        {
            throw new Exceptions\CommandNotFoundException(sprintf(static::ERR_CTRL_1, $command));
        }
        return $flag;
    }
    
    /**
     * Returns name and|or options of the default command.
     *
     * @param bool $withOptions Determines whether to return command options along with command name.
     * @return string|array
     */
    public function getDefaultCommand(bool $withOptions = true)
    {
        return $withOptions ? [$this->defaultCommand, $this->defaultCommandOptions] : $this->defaultCommand;
    }
    
    /**
     * Sets the default command.
     *
     * @param string $command The default command name.
     * @param array $options The default command options.
     * @return void
     */
    public function setDefaultCommand(string $command, array $options = [])
    {
        $this->has($command, true);
        $this->defaultCommand = $command;
        $this->defaultCommandOptions = $options;
    }
    
    /**
     * Sets the debug output for an exception.
     *
     * @param \Throwable $e
     * @return void
     */
    protected function errorHandler(\Throwable $e, array $info)
    {
        return true;
    }
}