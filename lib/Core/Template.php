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
use Aleph\Core\Interfaces\ITemplate;
use Aleph\Core\Traits\{ArrayAccess, ObjectAccess};

/**
 * Implementation of the base template engine.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.1.0
 * @package aleph.core
 */
class Template implements \Countable, \IteratorAggregate, ITemplate
{
    use ArrayAccess, ObjectAccess;

    /**
     * Template variables.
     *
     * @var array
     */
    private $items = [];

    /**
     * Template string or path to a template file.
     *
     * @var string
     */
    private $template = '';

    /**
     * Constructor.
     *
     * @param string $template The template string or path to a template file.
     */
    public function __construct(string $template = '')
    {
        $this->template = $template;
    }

    /**
     * Returns template string.
     *
     * @return string
     */
    public function getTemplate() : string
    {
        return $this->template;
    }

    /**
     * Sets template.
     *
     * @param string $template Template string or path to a template file.
     * @return void
     */
    public function setTemplate(string $template)
    {
        $this->template = $template;
    }

    /**
     * Returns array of template variables.
     *
     * @return array
     */
    public function getVars() : array
    {
        return $this->items;
    }

    /**
     * Sets template variables.
     *
     * @param array $vars The template variables.
     * @param bool $merge Determines whether new variables should be merged with existing variables.
     * @return void
     */
    public function setVars(array $vars, bool $merge = false)
    {
        if ($merge) {
            $this->items = array_merge($this->items, $vars);
        } else {
            $this->items = $vars;
        }
    }

    /**
     * Returns number of the template variables.
     *
     * @return int
     */
    public function count() : int
    {
        return count($this->items);
    }

    /**
     * Returns an iterator instance to iterate over all template variables.
     *
     * @return \ArrayIterator
     */
    public function getIterator() : \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Returns the rendered template.
     *
     * @return string
     */
    public function render() : string
    {
        if (strlen($this->template) <= PHP_MAXPATHLEN && is_file($this->template)) {
            extract($this->items);
            ob_start();
            ob_implicit_flush(false);
            require($this->template);
            return ob_get_clean();
        }
        return Aleph::executeEmbeddedCode($this->template, $this->items);
    }

    /**
     * Outputs the rendered template to a browser.
     *
     * @return void
     */
    public function show()
    {
        echo $this->render();
    }

    /**
     * Converts the template object to a string.
     *
     * @return string
     */
    public function __toString() : string
    {
        try {
            return $this->render();
        } catch (\Throwable $e) {
            Aleph::exception($e);
        }
    }
}