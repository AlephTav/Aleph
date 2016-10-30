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

namespace Aleph\Scaffolding\Config;

use Aleph\Scaffolding\Config\Interfaces\IParser;

/**
 * Config parser factory.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.scaffolding
 */
abstract class Parser implements IParser
{
    /**
     * Error message templates.
     */
    const ERR_PARSER_1 = 'Config parser of type "%s" is not supported.';

    /**
     * Available types of config files.
     */
    const TYPE_PHP = 'php';
    const TYPE_INI = 'ini';

    /**
     * Path to the configuration file.
     *
     * @var array
     */
    protected $path = null;

    /**
     * Creates an instance of the specified config parser.
     *
     * @param string $path The path to the config file.
     * @param string $type The parser type.
     * @return \Aleph\Scaffolding\Config\Interfaces\IParser
     */
    public static function create(string $path, string $type = self::TYPE_PHP) : IParser
    {
        switch ($type) {
            case self::TYPE_PHP:
                return new PHPParser($path);
            case self::TYPE_INI:
                return new INIParser($path);
        }
        throw new \UnexpectedValueException(sprintf(static::ERR_PARSER_1, $type));
    }

    /**
     * Constructor.
     *
     * @param string $path Path to the config file.
     */
    public function __construct(string $path)
    {
        $this->setConfigFile($path);
    }

    /**
     * Returns path to the config file.
     *
     * @return string
     */
    public function getConfigFile() : string
    {
        return $this->path;
    }

    /**
     * Sets path to the config file.
     *
     * @param string $path
     * @return void
     */
    public function setConfigFile(string $path)
    {
        $this->path = $path;
    }

    /**
     * Parses a config file of the given type and
     * returns the config data.
     *
     * @return array
     */
    abstract public function parse() : array;
}