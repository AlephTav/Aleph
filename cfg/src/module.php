<?php

namespace Aleph\Configurator;

abstract class Module
{
  public function init(){}

  abstract public function process($command, array $args = null);
  
  abstract public function getData();
  
  abstract public function getCommandHelp();
  
  protected static function normalizePath($path)
  {
    if (strlen($path) == 0) return false;
    if ($path[0] != '/') $path = '/' . $path;
    return $_SERVER['DOCUMENT_ROOT'] . $path;
  }

  protected static function isPHPFile($file)
  {
    return strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'php';
  }
  
  protected static function removeFiles($dir, $isRecursive = true, $removeDir = true)
  {
    if (!is_dir($dir)) return false;
    $hasDir = false;
    foreach (scandir($dir) as $item)
    {
      if ($item == '..' || $item == '.') continue;
      $file = $dir . '/' . $item;
      if (is_dir($file))
      {
        if ($isRecursive) self::removeFiles($file, true, true);
        else $hasDir = true;
      }
      else unlink($file);   
    }
    if ($removeDir && !$hasDir) rmdir($dir);
  }
  
  protected static function render($file, array $vars)
  {
    ${'(_._)'} = $file;
    extract($vars);
    ob_start();
    require(${'(_._)'});
    return ob_get_clean();
  }
  
  protected static function error($message)
  {
    if (Configurator::isCLI()) echo PHP_EOL . 'ERROR: ' . $message . PHP_EOL;
    else throw new \Exception($message);
  }
}