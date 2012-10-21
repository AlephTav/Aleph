<?php
/**
 * Copyright (c) 2012 Aleph Tav
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
 * @copyright Copyright &copy; 2012 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Web\UI\POM;

use Aleph\Core,
    Aleph\MVC,
    Aleph\Web,
    Aleph\Web\UI\Tags;

interface IValidator
{

}

abstract class Validator extends Control implements IValidator
{
  const ERR_VAL_1 = 'Validating control with UniqueID = "[{var}]" is not found.';

  public function __construct($ctrl, $id, $message = null)
  {
    parent::__construct($ctrl, $id);
    $this->properties['controls'] = array();
    $this->properties['groups'] = array();
    $this->properties['text'] = $message;
    $this->properties['mode'] = 'AND';
    $this->properties['client'] = false;
    $this->properties['action'] = null;
    $this->properties['unaction'] = null;
    $this->properties['isValid'] = true;
    $this->properties['order'] = 0;
    $this->properties['tag'] = 'span';
    $this->properties['hiding'] = false;
  }

  abstract public function validate();

  abstract public function check($value);

  public function &__get($param)
  {
    if ($param == 'groups') return $this->properties['groups'];
    if ($param == 'controls') return $this->properties['controls'];
    $v = parent::__get($param);
    return $v;
  }

  public function __set($param, $value)
  {
    if ($param == 'controls')
    {
      if (is_array($value)) $this->properties['controls'] = $value;
      else
      {
        $this->properties['controls'] = array();
        foreach (explode(',', $value) as $id)
        {
          $id = trim($id);
          if (strlen($id) > 0) $this->properties['controls'][] = $id;        
        }
      }
      return;
    }
    if ($param == 'groups')
    {
      if (is_array($value)) $this->properties['groups'] = $value;
      else
      {
        $this->properties['groups'] = array();
        foreach (explode(',', $value) as $group)
        {
          $group = trim($group);
          if (strlen($group) > 0) $this->properties['groups'][] = $group;
        }
      }
      return;
    }
    parent::__set($param, $value);
  }

   public function JS()
   {
      if (!$this->properties['client'] || !$this->properties['visible']) return $this;
      $this->js->addTool('validators');
      $script = new Helpers\Script(null, $this->getScriptString());
      if (!Web\Ajax::isAction()) 
	  {
	     if (!$this->page->validatorIsLocked($this->attributes['uniqueID'])) $this->js->add($script, 'foot');
	  }
      else
	  {
	     if ($this->page->validatorIsLocked($this->attributes['uniqueID'])) $this->ajax->script('validators.remove(\'' . $this->attributes['uniqueID'] . '\')', 0, true);
		 else $this->ajax->script($script->text, 0, true);
	  }
      return $this;
   }

  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    if (!$this->properties['hiding'])
    {
      return '<' . $this->properties['tag'] . $this->getParams() . '>' . ((!$this->properties['isValid']) ? $this->properties['text'] : '') . '</' . $this->properties['tag'] . '>';
    }
    else
    {
      if ($this->properties['isValid']) $this->addStyle('display', 'none');
      else $this->addStyle('display', '');
      return '<' . $this->properties['tag'] . $this->getParams() . '>' . $this->properties['text'] . '</' . $this->properties['tag'] . '>';
    }
  }

   protected function doAction()
   {
      if ($this->properties['action'] && !$this->properties['isValid']) $this->ajax->script('if (document.getElementById(\'' . $this->attributes['uniqueID'] . '\')) {' . $this->properties['action'] . ';}', 0, true);
      if ($this->properties['unaction'] && $this->properties['isValid']) $this->ajax->script('if (document.getElementById(\'' . $this->attributes['uniqueID'] . '\')) {' . $this->properties['unaction'] . ';}', 0, true);
   }

  protected function validateControl($uniqueID)
  {
    $ctrl = Control::getByUniqueID($uniqueID);
    if ($ctrl === false) throw new Core\Exception('ERR_VAL_1', $uniqueID);
    return $ctrl->validate($this->type, new Core\Delegate('@' . $this->properties['uniqueID'] . '->check'));
  }

   protected function getScriptString(array $params = null)
   {
      if ($this->ajax->isSubmit()) $p = 'parent.';
	  $params['hiding'] = (int)$this->properties['hiding'];
	  foreach ($params as $key => &$param) $param = "'" . $key . "': " . $param;
      return $p . 'validators.add(\'' . $this->attributes['uniqueID'] . '\', {\'groups\': \'' . addslashes(implode(',', $this->groups)) . '\', \'cids\': \'' . implode(',', $this->controls) . '\', \'type\': \'' . $this->type . '\', \'order\': \'' . (int)$this->properties['order'] . '\', \'mode\': \'' . addslashes($this->properties['mode']) . '\', \'message\': \'' . addslashes($this->properties['text']) . '\', \'action\': \'' . addslashes($this->properties['action']) . '\', \'unaction\': \'' . addslashes($this->properties['unaction']) . '\', \'exparam\': {' . implode(', ', $params) . '}});';
   }

   protected function repaint($time = 0)
   {
      parent::repaint($time);
      if (!$this->properties['visible']) return;
      if ($this->properties['client'])
      {
         $this->js->addTool('validators');
         $this->ajax->script('validators.remove(\'' . $this->attributes['uniqueID'] . '\')', $time, true);
         $this->ajax->script($this->getScriptString(), $time, true);
      }
   }

   public function remove($time = 0)
   {
      parent::remove($time);
      if ($this->properties['client']) $this->ajax->script('validators.remove(\'' . $this->attributes['uniqueID'] . '\')', $time, true);
   }

   protected function getXHTMLParams()
   {
      $controls = $this->properties['controls'];
      $tmp = array();
      foreach ($controls as $uniqueID)
      {
         $tmp[] = $this->page->getByUniqueID($uniqueID)->getFullID();
      }
      $this->properties['controls'] = implode(', ', $tmp);
      $params = parent::getXHTMLParams();
      $this->properties['controls'] = $controls;
      return $params;
   }
}