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

use Aleph,
    Aleph\Core;

/**
 * The simple view class using PHP as template language.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.2
 * @package aleph.core
 */
class View
{
    /**
     * Error message templates.
     */
    const ERR_1 = 'View "%s" is not found.';
    const ERR_2 = 'No blocks have been started yet.';
    
    /**
     * Blocks of views.
     *
     * @var array
     */
    protected $blocks = [];
    
    /**
     * Contains in-progress blocks.
     *
     * @var array
     */
    protected $stack = [];
    
    /**
     * View data.
     *
     * @var array
     */
    protected $vars = [];
    
    /**
     * The number of rendering views.
     *
     * @var int
     */
    protected $level = 0;
    
    /**
     * Name of the parent view.
     *
     * @var string
     */
    protected $parentView = '';
    
    /**
     * Name of the parent block.
     * This block is used as container that contains all outputs of the current view.
     *
     * @var string
     */
    protected $parentBlock = '';
    
    /**
     * View string or path to the view file.
     *
     * @var string
     */
    protected $view = '';
    
    /**
     * Constructor.
     *
     * @param string $view A view string or path to a view file.
     * @return void
     */
    public function __construct(string $view = '')
    {
        $this->view = $view;
    }
    
    /**
     * Sets value of a view variable.
     *
     * @param string $name The variable name.
     * @param mixed $value The variable value.
     * @return void
     */
    public function __set(string $name, $value)
    {
        $this->vars[$name] = $value;
    }

    /**
     * Returns value of a view variable.
     *
     * @param string $name The variable name.
     * @return mixed
     */
    public function &__get(string $name)
    {
        if (!isset($this->vars[$name]))
        {
            $this->vars[$name] = null;
        }
        return $this->vars[$name];
    }

    /**
     * Checks whether or not a view variable exists and its value is not NULL.
     *
     * @param string $name The variable name.
     * @return bool
     */
    public function __isset($name) : bool
    {
        return isset($this->vars[$name]);
    }

    /**
     * Deletes a view variable.
     *
     * @param string $name The variable name.
     * @return void
     */
    public function __unset($name)
    {
        unset($this->vars[$name]);
    }
    
    /**
     * Returns array of view variables.
     *
     * @return array
     */
    public function getVars() : array
    {
        return $this->vars;
    }

    /**
     * Sets view variables.
     *
     * @param array $vars
     * @param bool $merge Determines whether new variables should be merged with existing variables.
     * @return void
     */
    public function setVars(array $vars, bool $merge = false)
    {
        if (!$merge)
        {
            $this->vars = $vars;
        }
        else
        {
            $this->vars = array_merge($this->vars, $vars);
        }
    }
    
    /**
     * Returns rendered view content.
     *
     * @param string $view The view name or path to the view file.
     * @return string
     */
    public function render(string $view = '') : string
    {
        $view = $view !== '' ? $view : $this->view;
        ${'(_._)'} = $this->findViewFile($view);
        unset($view);
        $this->level++;
        ob_start();
        ob_implicit_flush(false);
        extract($this->vars);
        require(${'(_._)'});
        while ($this->stack)
        {
            $this->endBlock();
        }
        $content = ob_get_clean();
        if ($this->parentView)
        {
            if (strlen($this->parentBlock))
            {
                $this->blocks[$this->parentBlock] = $content;
            }
            $view = $this->parentView;
            $this->parentView = null;
            $this->parentBlock = null;
            $content = $this->render($view);
        }
        $this->level--;
        if ($this->level == 0)
        {
            $this->blocks = [];
        }
        return $content;
    }
    
    /**
     * Outputs a rendered view to a browser.
     *
     * @param string $view The view name or path to the view file.
     * @return void
     */
    public function show(string $view = '')
    {
        echo $this->render($view);
    }
    
    /**
     * Searches view file by view name.
     *
     * @param string $view The name of a view file.
     * @return void
     * @throws \LogicException If the given view is not found.
     */
    protected function findViewFile(string $view)
    {
        if (is_file($view))
        {
            return $view;
        }
        foreach (Aleph::get('view.directories', []) as $path)
        {
            $path = Aleph::dir($path) . DIRECTORY_SEPARATOR . $view;
            if (is_file($path))
            {
                return $path;
            }
        }
        throw new \LogicException(sprintf(static::ERR_1, $view));
    }
    
    /**
     * Returns the block content.
     *
     * @param string $block The block name.
     * @param mixed $default The default block content.
     * @return string
     */
    protected function getBlock(string $block, $default = null) : string
    {
        return str_replace(md5($block), '', $this->blocks[$block] ?? $default);
    }
    
    /**
     * Sets block content.
     *
     * @param string $block The block name.
     * @param mixed $content The block content.
     * @return void
     */
    protected function setBlock(string $block, $content)
    {
        $this->blocks[$block] = $content;
    }
    
    /**
     * Starts the new block of a view.
     *
     * @param string $block The block name.
     * @param mixed $content The block content.
     * @return void
     */
    protected function startBlock(string $block, $content = null)
    {
        if ((string)$content === '')
        {
            $this->extendBlock($block, $content);
        }
        else
        {
            ob_start();
            ob_implicit_flush(false);
            $this->stack[] = $block;
        }
    }
    
    /**
     * Ends the block of a view.
     *
     * @param bool $overwrite Determines if the old block content should be overwritten.
     * @return string The block name.
     * @throws \BadMethodCallException If no blocks have been started yet.
     */
    protected function endBlock(bool $overwrite = false) : string
    {
        if (!$this->stack)
        {
            throw new \BadMethodCallException(static::ERR_2);
        }
        $block = array_pop($this->stack);
        if ($overwrite)
        {
            $this->blocks[$block] = ob_get_clean();
        }
        else
        {
            $this->extendBlock($block, ob_get_clean());
        }
        return $block;
    }
    
    /**
     * Extends the given block.
     *
     * @param string $block The block name.
     * @param mixed $content The inherited block content.
     * @return void
     */
    protected function extendBlock(string $block, $content)
    {
        if (isset($this->blocks[$block]))
        {
            $content = str_replace(md5($block), $content, $this->blocks[$block]);
        }
        $this->blocks[$block] = $content;
    }
    
    /**
     * Ends block and appends its content.
     *
     * @return string The block name.
     * @throws \BadMethodCallException If no blocks have been started yet.
     */
    protected function appendBlock() : string
    {
        if (!$this->stack)
        {
            throw new \BadMethodCallException(static::ERR_2);
        }
        $block = array_pop($this->stack);
        if (isset($this->blocks[$block]))
        {
            $this->blocks[$block] .= ob_get_clean();
        }
        else
        {
            $this->blocks[$block] = ob_get_clean();
        }
        return $block;
    }
    
    /**
     * Outputs the block content.
     *
     * @param string $block The block name. If it is not defined, the previously started block will be shown.
     * @return void
     */
    protected function showBlock(string $block = '')
    {
        echo $this->getBlock($block !== '' ? $block : $this->endBlock(false));
    }
    
    /**
     * Outputs the parent block content.
     *
     * @return void
     * @throws \BadMethodCallException If no blocks have been started yet.
     */
    protected function parentContent()
    {
        if (!$this->stack)
        {
            throw new \BadMethodCallException(static::ERR_2);
        }
        echo md5(end($this->stack));
    }
    
    /**
     * Extends the current view from the parent one.
     *
     * @param string $view The parent view name or path to the view file.
     * @param string $block The name of the parent view's block which the current view's content will be inserted to.
     * @return void
     */
    protected function inherit(string $view, string $block = '')
    {
        $this->parentView = $view;
        $this->parentBlock = $block;
    }
}