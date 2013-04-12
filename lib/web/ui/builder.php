<?php

namespace Aleph\Web\UI;

class Builder
{
  protected $dirs = array();
  protected $mask = '/.+\.(html|xhtml)$/i';
  protected $framework = 'jquery';
  protected $outputFileName = 'elements';
  
  public function __construct()
  {
    $this->dirs = array('tpl' => array(\Aleph::dir('elements') => true), 
                        'js' => \Aleph::dir('js'),
                        'css' => \Aleph::dir('css'));
  }
  
  public function addInputDirectory($path, $includeNestedDirectories = true)
  {
    $this->dirs['tpl'][$path] = $includeNestedDirectories;
  }
  
  public function getInputDirectories()
  {
    return $this->dirs['tpl'];
  }
  
  public function setOutputDirectoryForJS($path)
  {
    $this->dirs['js'] = $path;
  }
  
  public function getOutputDirectoryForJS()
  {
    return $this->dirs['js'];
  }
  
  public function setOutputDirectoryForCSS($path)
  {
    $this->dirs['css'] = $path;
  }
  
  public function getOutputDirectoryForCSS()
  {
    return $this->dirs['css'];
  }
  
  public function setOutputFileName($filename)
  {
    $this->outputFileName = $filename;
  }
  
  public function getOutputFileName()
  {
    return $this->outputFileName;
  }
  
  public function setFramework($framework)
  {
    $this->framework = $framework;
  }
  
  public function getFramework($framework)
  {
    return $this->framework;
  }
  
  public function setMask($mask)
  {
    $this->mask = $mask;
  }
  
  public function getMask()
  {
    return $this->mask;
  }
  
  public function build()
  {
    $files = $data = array();
    foreach ($this->dirs['tpl'] as $dir => $recursively)
    {
      if (!$recursively) $iterator = new \DirectoryIterator($dir);
      else $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
      $iterator = new \RegexIterator($iterator, $this->mask, \RecursiveRegexIterator::GET_MATCH);
      foreach ($iterator as $match) $files[$match[0]] = null;
    }
    $parser = new Parser();
    foreach ($files as $file => $foo)
    {
      $parser->parse($file);
      $data[$parser->getElementName()] = $parser->getData();
    }
    /*$compiler = new Compiler();
    $compiler->setFramework($this->framework);
    $compiler->setOutputDirectoryForJS($this->dirs['js']);
    $compiler->setOutputDirectoryForCSS($this->dirs['css']);
    $compiler->setFileName($this->outputFileName);
    $compiler->compile($data);*/
  }
}