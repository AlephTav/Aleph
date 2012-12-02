<?php

namespace ClickBlocks\Web\UI\POM;

use ClickBlocks\Core;

class Validators implements \IteratorAggregate
{
   private static $instance = null;

   protected $reg = null;

   private function __clone(){}

   private function __construct()
   {
      $this->reg = Core\Register::getInstance();
   }

   public static function getInstance()
   {
      if (self::$instance === null) self::$instance = new Validators();
      return self::$instance;
   }

   public function getIterator()
   {
      return new POMIterator($this->getValidators());
   }

   public function getValidators($uniqueID = null)
   {
     if (!$uniqueID) return $this->reg->page->getValidators();
     $vals = array();
     foreach ($this->reg->page->getValidators() as $uid)
     {
        $validator = $this->reg->page->getByUniqueID($uid);
        if (in_array($uniqueID, $validator->controls)) $vals[] = $uid;
     }
     return $vals;
   }

   public function clean($group = 'default', $update = false)
   {
      $validators = $this->getValidators();
      uasort($validators, array($this, 'sortValidators'));
      $iterator = new POMIterator($validators);
      foreach ($iterator as $validator)
      {
         if (in_array($group, $validator->groups) || $group == '')
         {
            $validator->isValid = true;
            if ($update) $validator->update();
         }
      }
      return $this;
   }

   public function isValid($group = 'default', $isAll = true)
   {
      $validators = $this->getValidators();
      $groups = explode(',', $group);
      uasort($validators, array($this, 'sortValidators'));
      $iterator = new POMIterator($validators);
      if (!$isAll)
      {
         foreach ($groups as $group)
         {
            foreach ($iterator as $validator)
            {
               if ((in_array($group, $validator->groups) || $group == '') && !$validator->validate()->isValid)
                 return false;
            }
         }
         return true;
      }
      $flag = true;
      foreach ($groups as $group)
      {
         foreach ($iterator as $validator)
         {
            if ((in_array($group, $validator->groups) || $group == '') && !$validator->validate()->isValid)
              $flag = false;
         }
      }
      return $flag;
   }

   private function sortValidators($uniqueID1, $uniqueID2)
   {
      $vs1 = $this->reg->page->getActualVS($uniqueID1);
      $vs2 = $this->reg->page->getActualVS($uniqueID2);
      $order1 = $vs1['parameters'][1]['order'];
      $order2 = $vs2['parameters'][1]['order'];
      if ($order1 == $order2) return 0;
      return ($order1 < $order2) ? -1 : 1;
   }
}

?>
