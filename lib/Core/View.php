<?php
/**
 * Copyright (c) 2013 - 2015 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2015 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Core;

use Aleph\Core;

/**
 * The simple view class using PHP as template language.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.core
 */
class View
{
    /**
     * Error message templates.
     */
    const ERR_VIEW_1 = 'View "%s" not found.';
    const ERR_VIEW_2 = 'No blocks have been started yet.';
    
    /**
     * Blocks of views.
     *
     * @var array $blocks
     * @access protected
     */
    protected $blocks = [];
    
    /**
     * Contains in-progress blocks.
     *
     * @var array $stack
     * @access protected
     */
    protected $stack = [];
    
    /**
	 * View data.
	 *
	 * @var array $vars
     * @access protected
	 */
    protected $vars = [];
    
    /**
	 * The number of rendering views.
	 *
	 * @var integer $level
     * @access protected
	 */
    protected $level = 0;
    
    /**
     * Name of the parent view.
     *
     * @var string $parentView
     * @access protected
     */
    protected $parentView = null;
    
    /**
     * Name of the parent block.
     * This block is used as container that contains all outputs of the current view.
     *
     * @var string $parentBlock
     * @access protected
     */
    protected $parentBlock = null;
    
    /**
     * Sets value of a view variable.
     *
     * @param string $name - the variable name.
     * @param mixed $value - the variable value.
     * @access public
     */
    public function __set($name, $value)
    {
        $this->vars[$name] = $value;
    }

    /**
     * Returns value of a view variable.
     *
     * @param string $name - the variable name.
     * @return mixed
     * @access public
     */
    public function &__get($name)
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
     * @param string $name - the variable name.
     * @return boolean
     * @access public
     */
    public function __isset($name)
    {
        return isset($this->vars[$name]);
    }

    /**
     * Deletes a view variable.
     *
     * @param string $name - the variable name.
     * @access public
     */
    public function __unset($name)
    {
        unset($this->vars[$name]);
    }
    
    /**
     * Returns array of view variables.
     *
     * @return array
     * @access public
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * Sets view variables.
     *
     * @param array $vars
     * @param boolean $merge - determines whether new variables should be merged with existing variables.
     * @access public
     */
    public function setVars(array $vars, $merge = false)
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
     * @param string $view - the view name or path to the view file.
     * @return string
     * @access public
     */
    public function render($view)
    {
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
     * @param string $view - the view name or path to the view file.
     * @access public
     */
    public function show($view = null)
    {
        echo $this->render($view);
    }
    
    /**
     * Searches view file by view name.
     *
     * @param string $view - the name of a view file.
     * @access protected
     */
    protected function findViewFile($view)
    {
        if (is_file($view))
        {
            return $view;
        }
        foreach (\Aleph::get('view.directories', []) as $path)
        {
            $path = \Aleph::dir($path) . DIRECTORY_SEPARATOR . $view;
            if (is_file($path))
            {
                return $path;
            }
        }
        throw new Core\Exception([$this, 'ERR_VIEW_1'], $view);
    }
    
    /**
     * Returns the block content.
     *
     * @param string $block - the block name.
     * @param mixed $default - the default block content.
     * @return mixed
     * @access protected
     */
    protected function getBlock($block, $default = null)
    {
        $content = isset($this->blocks[$block]) ? $this->blocks[$block] : $default;
        return str_replace(md5($block), '', $content);
    }
    
    /**
     * Sets block content.
     *
     * @param string $block - the block name.
     * @param mixed $content - the block content.
     * @access protected
     */
    protected function setBlock($block, $content)
    {
        $this->blocks[$block] = $content;
    }
    
    /**
     * Starts the new block of a view.
     *
     * @param string $block - the block name.
     * @access protected
     */
    protected function startBlock($block, $content = null)
    {
        if (strlen($content))
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
     * @return string - the block name.
     * @access protected
     */
    protected function endBlock($overwrite = false)
    {
        if (!$this->stack)
        {
            throw new Core\Exception([$this, 'ERR_VIEW_2']);
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
     * @param string $block - the block name.
     * @param mixed $content - the inherited block content.
     * @access protected
     */
    protected function extendBlock($block, $content)
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
	 * @return string - the block name.
     * @access protected
	 */
	protected function appendBlock()
	{
        if (!$this->stack)
        {
            throw new Core\Exception([$this, 'ERR_VIEW_2']);
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
     * @param string $block - the block name. If it is not defined, the previously started block will be shown.
     * @access protected
     */
    protected function showBlock($block = null)
    {
        echo $this->getBlock($block !== null ? $block : $this->endBlock(false));
    }
    
    /**
     * Outputs the parent block content.
     *
     * @access protected
     */
    protected function parentContent()
    {
        if (!$this->stack)
        {
            throw new Core\Exception([$this, 'ERR_VIEW_2']);
        }
        echo md5(end($this->stack));
    }
    
    /**
     * Extends the current view from the parent one.
     *
     * @param string $view - the parent view name or path to the view file.
     * @param string $block - the name of the parent view's block which the current view's content will be inserted to.
     * @access protected
     */
    protected function inherit($view, $block = null)
    {
        $this->parentView = $view;
        $this->parentBlock = $block;
    }
}