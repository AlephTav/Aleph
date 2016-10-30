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

/**
 * INI config parser.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.scaffolding
 */
class INIParser extends Parser
{
    /**
     * Parses an ini config file and returns the config data.
     *
     * @return array
     */
    public function parse() : array
    {
        $data = parse_ini_file($this->path, true);
        $config = [];
        foreach ($data as $section => $properties) {
            if (is_array($properties)) {
                if (!isset($config[$section]) || !is_array($config[$section])) {
                    $config[$section] = [];
                }
                foreach ($properties as $k => $v) {
                    $config[$section][$k] = $this->parseValue($v);
                }
            } else {
                $config[$section] = $this->parseValue($properties);
            }
        }
        return $config;
    }

    /**
     * Parses value of a config parameter.
     *
     * @param string $value
     * @return mixed
     */
    private function parseValue(string $value)
    {
        if (is_array($value) || is_object($value)) {
            return $value;
        }
        $len = strlen($value) - 1;
        if ($len >= 0 && ($value[0] == '[' || $value[0] == '{') && ($value[$len] == ']' || $value[$len] == '}')) {
            $tmp = json_decode($value, true);
            $value = $tmp !== null ? $tmp : $value;
        }
        return $value;
    }
}