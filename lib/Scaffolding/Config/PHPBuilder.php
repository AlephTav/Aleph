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
 * PHP config builder.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.scaffolding
 */
class PHPBuilder extends Builder
{
    /**
     * Error message templates.
     */
    const ERR_PHPBUILDER_1 = 'Variable of type %s% cannot be saved as a config variable.';

    /**
     * String representation of escape characters.
     *
     * @var array
     */
    private $reps = [
        '\\' => '\\\\',
        "\n" => '\n',
        "\r" => '\r',
        "\t" => '\t',
        "\v" => '\v',
        "\e" => '\e',
        "\f" => '\f'
    ];

    /**
     * Creates PHP config file.
     *
     * @param string $path Path to the file where to write the config data.
     * @return void
     */
    public function build(string $path)
    {
        $code = $this->formArray($this->data);
        $code = '<?php' . PHP_EOL . PHP_EOL . 'return ' . $code . ';';
        file_put_contents($path, $code, LOCK_EX);
    }

    /**
     * Returns PHP code representing an array.
     *
     * @param array $data The config data.
     * @param int $indent
     * @param int $tab
     * @return string
     */
    private function formArray(array $data, int $indent = 0, int $tab = 4) : string
    {
        $res = [];
        $indent += $tab;
        $isInteger = array_keys($data) === range(0, count($data) - 1);
        foreach ($data as $k => $v) {
            if (is_string($k)) {
                $k = $this->formString($k);
            }
            if (is_array($v)) {
                $v = $this->formArray($v, $indent, $tab);
            } else if (is_string($v)) {
                $v = $this->formString($v);
            } else if (is_bool($v)) {
                $v = $v ? 'true' : 'false';
            } else if (is_float($v)) {
                $v = str_replace(',', '.', $v);
            } else if ($v === null) {
                $v = 'null';
            } else {
                throw new \RuntimeException(sprintf(static::ERR_PHPBUILDER_1, gettype($v)));
            }
            $res[] = $isInteger ? $v : $k . ' => ' . $v;
        }
        if ($res) {
            $space = PHP_EOL . str_repeat(' ', $indent);
            return '[' . $space . implode(',' . $space, $res) . PHP_EOL . str_repeat(' ', $indent - $tab) . ']';
        }
        return '[]';
    }

    /**
     * Returns PHP code representing a string variable.
     *
     * @var string $value
     * @return string
     */
    private function formString(string $value) : string
    {
        $flag = false;
        $value = preg_replace_callback('/([^\x20-\x7e]|\\\\)/', function ($m) use (&$flag) {
            $m = $m[0];
            if ($m == '\\') {
                return '\\\\';
            }
            $flag = true;
            return $this->reps[$m] ?? '\x' . str_pad(dechex(ord($m)), 2, '0', STR_PAD_LEFT);
        }, $value);
        if ($flag) {
            return '"' . str_replace('"', '\"', $value) . '"';
        }
        return "'" . str_replace("'", "\\'", $value) . "'";
    }
}