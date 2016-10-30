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

use Aleph\Scaffolding\Config\Interfaces\IBuilder;

/**
 * Config builder factory.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.scaffolding
 */
abstract class Builder implements IBuilder
{
    /**
     * Error message templates.
     */
    const ERR_BUILDER_1 = 'Config builder of type "%s" is not supported.';

    /**
     * Available types of config files.
     */
    const TYPE_PHP = 'php';
    const TYPE_INI = 'ini';

    /**
     * The configuration data.
     *
     * @var array
     */
    protected $data = null;

    /**
     * Creates an instance of the specified config builder.
     *
     * @param array $data The config data.
     * @param string $type The builder type.
     * @return \Aleph\Scaffolding\Config\Interfaces\IBuilder
     */
    public static function create(array $data, string $type = self::TYPE_PHP) : IBuilder
    {
        switch ($type) {
            case self::TYPE_PHP:
                return new PHPBuilder($data);
            case self::TYPE_INI:
                return new INIBuilder($data);
        }
        throw new \UnexpectedValueException(sprintf(static::ERR_BUILDER_1, $type));
    }

    /**
     * Constructor.
     *
     * @param array $data The config data.
     */
    public function __construct(array $data)
    {
        $this->setData($data);
    }

    /**
     * Returns the given config data.
     *
     * @return array
     */
    public function getData() : array
    {
        return $this->data;
    }

    /**
     * Sets the config data.
     *
     * @param array $data
     * @return void
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Creates config file of the given type.
     *
     * @param string $path Path to the file where to write the config data.
     * @return void
     */
    abstract public function build(string $path);
}