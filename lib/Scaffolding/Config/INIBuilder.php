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
 * INI config builder.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.scaffolding
 */
class INIBuilder extends Builder
{
    /**
     * Creates INI config file.
     *
     * @param string $path Path to the file where to write the config data.
     * @return void
     */
    public function build(string $path)
    {
        file_put_contents($path, $this->formINI($this->data), LOCK_EX);
    }
    
    /**
     * Returns content of building ini file.
     *
     * @param array $data The config data.
     * @return string
     */
    private function formINI(array $data) : string
    {
        $tmp1 = $tmp2 = [];
        foreach ($data as $k => $v)
        {
            if (is_array($v))
            {
                $tmp = ['[' . $k . ']'];
                foreach ($v as $kk => $vv)
                {
                    if (!is_numeric($vv))
                    {
                        if (is_array($vv))
                        {
                            $vv = '"' . str_replace(['"', '\\\\'], ['\"', '\\\\\\\\'], json_encode($vv)) . '"';
                        }
                        else if ($vv === null)
                        {
                            $vv = '""';
                        }
                        else if (is_bool($vv))
                        {
                            $vv = $vv ? 1 : 0;
                        }
                        else if (is_string($vv))
                        {
                            $vv = '"' . addcslashes($vv, '"') . '"';
                        }
                    }
                    $tmp[] = str_pad($kk, 30) . ' = ' . $vv;
                }
                $tmp2[] = PHP_EOL . implode(PHP_EOL, $tmp);
            }
            else
            {
                if (!is_numeric($v))
                {
                    if ($v === null)
                    {
                        $v = '""';
                    }
                    else if (is_bool($v))
                    {
                        $v = $v ? 1 : 0;
                    }
                    else if (is_string($v))
                    {
                        $v = '"' . addcslashes($v, '"') . '"';
                    }
                }
                $tmp1[] = str_pad($k, 30) . ' = ' . $v;
            }
        }
        return implode(PHP_EOL, array_merge($tmp1, $tmp2));
    }
}