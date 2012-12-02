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
    Aleph\Web\UI\Tags,
    Aleph\Utils;

interface IControl
{
}

abstract class Control extends Tags\Tag implements IControl
{
  const ID_REG_EXP = '/^[0-9a-zA-Z_]+$/';
  
  const ERR_CTRL_1 = 'ID of [{var}] should match /^[0-9a-zA-Z_]+$/ pattern, "[{var}]" was given.';
  const ERR_CTRL_2 = 'You cannot change uniqueID of [{var}] with fullID = "[{var}]".';
  const ERR_CTRL_3 = 'You cannot remove control [{var}] because it is not in POM.';
  const ERR_CTRL_4 = 'You cannot change parentUniqueID of [{var}] with fullID = "[{var}]". To change parentUniqueID of some web control you should use setParent or setParentByUniqueID methods of web control object.';
  const ERR_CTRL_5 = '[{var}] with fullID = "[{var}]" exists already in the Panel with fullID = "[{var}]".';
  const ERR_CTRL_7 = 'Web control with uniqueID = "[{var}]" does not exist.';
  
  public static $tags = array('PANEL' => true, 'TEXTBOX' => true, 'VALIDATORREQUIRED' => true);
  
  private static $vs = array();
  private static $vsTimestamp = 0;
  private static $controls = array();
  
  public static function vs($uniqueID)
  {
    if (empty(self::$vs[$uniqueID])) return false;
    if (isset(self::$controls[$uniqueID])) return self::$controls[$uniqueID]->getVS();
    return self::$vs[$uniqueID];
  }

  public static function vsGet($uniqueID, $getFromPool = true, $putToPool = true)
  {
    if (empty(self::$vs[$uniqueID])) return false;
    if ($getFromPool)
    {
      if (isset(self::$controls[$uniqueID])) return self::$controls[$uniqueID];
      $vs = self::$vs[$uniqueID];
      $ctrl = new $vs['class']($vs['class'] == 'Aleph\Web\UI\POM\Body' ? MVC\Page::$page->getPageID() : $vs['parameters'][0]['data-id']);
      $ctrl->setVS($vs);
      if ($putToPool) self::$controls[$ctrl->uniqueID] = $ctrl;
      return $ctrl;
    }
    return self::$vs[$uniqueID];
  }
  
  public static function vsSet(IControl $ctrl, $putToPool = true, $isRecursively = false)
  {
    self::$vs[$ctrl->uniqueID] = $ctrl->getVS();
    if ($isRecursively && $ctrl instanceof IPanel) foreach ($ctrl as $ct) self::vsSet($ct, $putToPool, $isRecursively);
    //if ($ctrl instanceof POM\IValidator && !isset($this->vs['validators'][$uniqueID])) $this->vs['validators'][$uniqueID] = true;
    if ($putToPool) self::$controls[$ctrl->uniqueID] = $ctrl;
  }
  
  public static function vsPull($init = false)
  {
    self::$vs = MVC\Page::$page->cache->get(MVC\Page::$page->getPageID() . ($init ? '_init_vs' : session_id() . '_vs'));
    self::$vsTimestamp = self::$vs['timestamp'];
    Core\Template::setGlobals(self::$vs['globals']);
    unset(self::$vs['globals']);
    unset(self::$vs['timestamp']);
  }
  
  public static function vsPush($init = false)
  {
    $vs = self::$vs + array('globals' => Core\Template::getGlobals(), 'timestamp' => $init ? 0 : foo(new Utils\DT('now', null, 'UTC'))->getTimestamp());
    $cache = MVC\Page::$page->cache;
    $cache->set(MVC\Page::$page->getPageID() . ($init ? '_init_vs' : session_id() . '_vs'), $vs, $init ? $cache->getVaultLifeTime() : ini_get('session.gc_maxlifetime'), '--controls');
  }
  
  public static function vsMerge(array $vs)
  {
    if ($vs['timestamp'] <= self::$vsTimestamp) return;
    foreach ($vs['vs'] as $uniqueID => $cvs)
    {
      if (empty(self::$vs[$uniqueID])) continue;
      $params = self::$vs[$uniqueID]['parameters'];
      foreach ($cvs as $k => $v)
      {
        if (array_key_exists($k, $params[0])) $params[0][$k] = $v;
        else if (array_key_exists($k, $params[2])) $params[2][$k] = $v;
      }
      self::$vs[$uniqueID]['parameters'] = $params;
       //  $value = $this->fv[$this->uniqueID][$vs['parameters'][0]['uniqueID']];
       //  if ($value === null || !$vs['extra']['assign']) continue;
       //  $this[$vs['parameters'][0]['uniqueID']] = foo(new $vs['parameters'][1]['ctrlClass']($vs['parameters'][1]['id']))->setParameters($vs)->assign($value);
    }
    //print_r(self::$vs);
  }
  
  public static function vsCompare()
  {
    $ajax = Web\Ajax::getInstance();
    $actions = $ajax->getActions();
    $ajax->setActions(array());
    foreach (self::$controls as $uniqueID => $ctrl)
    {
      if (($diff = $ctrl->compare(self::$vs[$uniqueID])) !== false) 
      {
        $ctrl->refresh($diff);
        self::$vs[$uniqueID] = $ctrl->getVS();
      }
    }
    $ajax->setActions(array_merge($ajax->getActions(), $actions));
  }
  
  public static function vsExpired($init = false)
  {
    return MVC\Page::$page->cache->isExpired(MVC\Page::$page->getPageID() . ($init ? '_init_vs' : session_id() . '_vs'));
  }
  
  public static function getByUniqueID($uniqueID)
  {
    return self::vsGet($uniqueID, true, true);
  }
  
  protected $a = null;
  protected $ajax = null;
  protected $doRefresh = false;
  protected $doRemove = false;

  public function __construct($ctrl, $id)
  {
    if (!preg_match(self::ID_REG_EXP, $id)) throw new Core\Exception($this, 'ERR_CTRL_1', get_class($this), $id);
    $this->attributes['id'] = uniqid($id);
    $this->attributes['data-id'] = $id;
    $this->attributes['data-ctrl'] = $ctrl;
    $this->properties['parentUniqueID'] = null;
    $this->properties['visible'] = true;
    $this->a = \Aleph::getInstance();
    $this->ajax = Web\Ajax::getInstance();
  }
  
  public function getVS()
  {
    return array('parameters' => array($this->attributes, $this->properties, $this->events),
                 'class' => get_class($this),
                 'methods' => array('init' => method_exists($this, 'init'),
                                    'load' => method_exists($this, 'load'),
                                    'unload' => method_exists($this, 'unload'),
                                    'assign' => method_exists($this, 'assign'),
                                    'clean' => method_exists($this, 'clean'),
                                    'validate' => method_exists($this, 'validate')));
  }

  public function setVS(array $vs)
  {
    list ($this->attributes, $this->properties, $this->events) = $vs['parameters'];
    return $this;
  }
  
  public function __get($param)
  {
    if ($param == 'uniqueID') return $this->attributes['id'];
    if ($param == 'id') return $this->attributes['data-id'];
    return parent::__get($param);
  }
  
  public function __set($param, $value)
  {
    if ($param == 'uniqueID') throw new Core\Exception($this, 'ERR_CTRL_2', get_class($this), $this->getFullID());
    if ($param == 'parentUniqueID') throw new Core\Exception($this, 'ERR_CTRL_4', get_class($this), $this->getFullID());
    if ($param == 'id')
    {
      if ($this->attributes['data-id'] != $value)
      {
        if (!preg_match(self::ID_REG_EXP, $value)) throw new Core\Exception($this, 'ERR_CTRL_1', get_class($this), $value);
        if ($this->properties['parentUniqueID'] != '')
        {
          /*$pvs = self::getActualVS($this->properties['parentUniqueID']);
          foreach ($pvs['controls'] as $uniqueID => $v)
          {
            $vs = self::getActualVS($uniqueID);
            if ($vs['parameters'][1]['id'] != $this->properties['id'] && $vs['parameters'][1]['id'] == $value)
            throw new Core\Exception($this, 'ERR_CTRL_5', get_class($this), $this->properties['fullID'], self::restoreFromVS($uniqueID)->fullID);
          }*/
        }
      }
      return;
    }
    parent::__set($param, $value);
  }

  public function __toString()
  {
    try
    {
      return $this->render();
    }
    catch (\Exception $e)
    {
      \Aleph::exception($e);
    }
  }
  
  public function update($time = true)
  {
    if ($time !== false) $time = ($time === true) ? 0 : (int)$time;
    $this->doRefresh = $time;
    return $this;
  }
  
  public function delete($time = 0)
  {
    $parent = self::getByUniqueID($this->properties['parentUniqueID']);
    if (!$parent) throw new Core\Exception($this, 'ERR_CTRL_3', $this->getFullID());
    // delete from panel
    $this->doRemove = (int)$time;
    return $this;
  }
  
  public function compare(array $vs, array $exclusions = null)
  {
    if (!$this->properties['parentUniqueID']) return false;
    $new = array($this->attributes, $this->properties, $this->events);
    if ($this->doRefresh !== false) return $new;
    $diff = array(array(), array(), array());
    for ($i = 0; $i < 3; $i++)
    {
      foreach ($vs['parameters'][$i] as $k => $v) 
      {
        if ($v != $new[$i][$k]) $diff[$i][$k] = $new[$i][$k];
      }
    }
    return ($diff[0] || $diff[1] || $diff[2]) ? $diff : false;
  }
  
  protected function refresh(array $diff = null, $selector = null)
  {
    $selector = $selector ?: '#' . $this->attributes['id'];
    if (!$diff || $diff[1] || $diff[2]) $this->ajax->replace($selector, $this->render(), $this->doRefresh);
    else
    {
      foreach ($diff[0] as $k => &$v) $v = $k . ': \'' . addslashes($v) . '\'';
      $this->ajax->script('aleph.dom.attr(\'' . $selector . '\', {' . implode(',', $diff[0]) . '})');
    }
    return $this;
  }
  
  /*public function setParent(IPanel $parent, $id = null, $mode = 'top')
  {
    return $this->setParentByUniqueID($parent->uniqueID, $id, $mode);
  }

  public function setParentByUniqueID($uniqueID, $id = null, $mode = 'top')
  {
    $parent = self::getByUniqueID($uniqueID);
    if (!$parent) throw new \Exception('ERR_CTRL_7', $uniqueID);
    //$this->remove();
    //$parent->inject($this, $id, $mode);
    return $this;
  }*/
  
  protected function invisible()
  {
    return '<span id="' . htmlspecialchars($this->attributes['id']) . '" style="display:none;"></span>';
  }
}