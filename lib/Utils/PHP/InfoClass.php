<?php
/**
 * Copyright (c) 2014 Aleph Tav
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
 * @copyright Copyright &copy; 2014 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Utils\PHP;

/**
 * This class is designed to work with the source code of a class (or interface), receive information about the class.
 * Using this class you can also change code and structure of any user defined class or define new non-existent class.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.utils.php
 */
class InfoClass implements \ArrayAccess
{
  /**
   * Number of spaces of indentation.
   *
   * @var integer $tab
   * @access public
   */
  public $tab = null;
  
  /**
   * Permissions for newly created file.
   *
   * @var integer $fileMode
   * @access public
   */
  public $fileMode = 0666;
  
  /**
   * Information about the class.
   *
   * @var array $info
   * @access protected
   */
  protected $info = [];

  /**
   * Constructor.
   *
   * @param mixed $class - class name or class object.
   * @param integer $tab - the number of spaces of indentation.
   * @access public
   */
  public function __construct($class, $tab = 2, $fileMode = 0666)
  {
    $this->tab = (int)$tab;
    $this->fileMode = (int)$fileMode;
    $this->extraction($class);
  }
  
  /**
   * Returns full information about the class.
   *
   * @return array
   * @access public
   */
  public function getInfo()
  {
    return $this->info;
  }
  
  /**
   * Returns code definition of the constant.
   * If the constant doesn't exist the method returns FALSE.
   *
   * @param string $constant
   * @return string
   * @access public
   */
  public function getCodeConstant($constant)
  {
    if (empty($this->info['constants'][$constant])) return false;
    return str_repeat(' ', $this->tab) . 'const ' . $constant . ' = ' . Tools::php2str($this->info['constants'][$constant], false) . ';';
  }
  
  /**
   * Returns code definition of the property.
   * If the property doesn't exist the method returns FALSE.
   *
   * @param string $property
   * @return string
   * @access public
   */
  public function getCodeProperty($property)
  {
    if (empty($this->info['properties'][$property])) return false;
    $code = [];
    $prop = $this->info['properties'][$property];
    if ($prop['isPublic']) $code[] = 'public';
    if ($prop['isProtected']) $code[] = 'protected';
    if ($prop['isPrivate']) $code[] = 'private';
    if ($prop['isStatic']) $code[] = 'static';
    $code[] = '$' . $property;
    if ($prop['isDefault']) $code[] = '= ' . Tools::php2str($prop['defaultValue'], true, 2 * $this->tab, $this->tab);
    $space = str_repeat(' ', $this->tab);
    return ($prop['comment'] ? $space . $prop['comment'] . PHP_EOL : '') . $space . implode(' ', $code) . ';';
  }
  
  /**
   * Returns code definition of the class method.
   * If such class method doesn't exist the method will return FALSE.
   *
   * @param string $method
   * @return string
   * @access public
   */
  public function getCodeMethod($method)
  {
    if (empty($this->info['methods'][$method])) return false;
    $code = $parameters = [];
    $met = $this->info['methods'][$method];
    foreach ($met['arguments'] as $parameter)
    {
      $param = '';
      if (isset($parameter['class']['name']))
      {
        if ($this->info['namespace'] == $parameter['class']['namespace']) $param .= $parameter['class']['shortName'] . ' ';
        else $param .= '\\' . $parameter['class']['name'] . ' ';
      }
      if ($parameter['isArray']) $param .= 'array ';
      $param .= ($parameter['isPassedByReference'] ? '&' : '') . '$' . $parameter['name'];
      if ($parameter['isDefaultValueAvailable']) $param .= ' = ' . Tools::php2str($parameter['defaultValue'], false);
      else if ($parameter['allowsNull'] && $parameter['isOptional']) $param .= ' = null';
      $parameters[] = $param;
    }
    if ($met['isFinal']) $code[] = 'final';
    if ($met['isAbstract']) $code[] = 'abstract';
    if ($met['isPublic']) $code[] = 'public';
    if ($met['isProtected']) $code[] = 'protected';
    if ($met['isPrivate']) $code[] = 'private';
    if ($met['isStatic']) $code[] = 'static';
    $space = str_repeat(' ', $this->tab);
    $code[] = 'function ' . ($met['returnsReference'] ? '&' : '') . $method . '(' . implode(', ', $parameters) . ')';
    $code = $space . implode(' ', $code);
    if ($met['isAbstract']) $code .= ';';
    else $code .= PHP_EOL . $space . '{' . $met['code'] . '}';
    return ($met['comment'] ? $space . $met['comment'] . PHP_EOL : '') . $code;
  }
  
  /**
   * Returns code definition of the class.
   *
   * @return string
   * @access public
   */
  public function getCodeClass()
  {
    $info = $this->info;
    $code = $header = $interfaces = $constants = $properties = $methods = [];
    if ($info['comment']) $code[] = $info['comment'];
    if ($info['isFinal']) $header[] = 'final';
    if ($info['isAbstract']) $header[] = 'abstract';
    $header[] = $info['isInterface'] ? 'interface' : 'class';
    $header[] = $info['shortName'];
    if ($info['parentName'])
    {
      if ($info['parentNamespace'] == $info['namespace']) $header[] = 'extends ' . $info['parentShortName'];
      else $header[] = 'extends \\' . $info['parentName'];
    }
    if ($info['interfaces']) foreach ($info['interfaces'] as $interface)
    {
      if ($interface['namespace'] == $info['namespace']) $interfaces[] = $interface['shortName'];
      else $interfaces[] = '\\' . $interface['name'];
    }
    if ($interfaces)  $header[] = 'implements ' . implode(', ', $interfaces);
    $code[] = implode(' ', $header);
    $code[] = '{';
    if ($info['constants']) foreach ($info['constants'] as $constant => $value) $constants[] = $this->getCodeConstant($constant);
    if ($constants) $code[] = implode(PHP_EOL, $constants) . PHP_EOL;
    if ($info['properties']) foreach ($info['properties'] as $property => $value) $properties[] = $this->getCodeProperty($property);
    if ($properties) $code[] = implode(PHP_EOL . PHP_EOL, $properties) . PHP_EOL;
    if ($info['methods']) foreach ($info['methods'] as $method => $value) $methods[] = $this->getCodeMethod($method);
    if ($methods) $code[] = implode(PHP_EOL . PHP_EOL, $methods);
    return implode(PHP_EOL, $code) . '}';
  }
  
  /**
   * Saves code definition of the class to file where it is defined or to new file.
   * The method returns FALSE if impossible to rewrite code and the number of bytes that were written to the file otherwise.
   *
   * @param string $file - the file to save the class definition.
   * @return boolean|integer
   * @access public
   */
  public function save($file = null)
  {
    if ($file !== null) 
    {
      $code = '<?php' . PHP_EOL . PHP_EOL;
      if ($this->info['inNamespace']) $code .= 'namespace ' . $this->info['namespace'] . ';' . PHP_EOL . PHP_EOL;
      $code .= $this->getCodeClass() . PHP_EOL . PHP_EOL . '?>';
      return $this->setFileContent($file, $code);
    }
    if ($this->info['isInternal']) return false;
    $lines = $this->getFileContent($this->info['file']);
    $code = implode(PHP_EOL, array_slice($lines, 0, $this->info['startLine'] - 1));
    $code .= PHP_EOL . $this->getCodeClass();
    $code .= implode(PHP_EOL, array_slice($lines, $this->info['endLine']));
    return $this->setFileContent($this->info['file'], $code);
  }
  
  /**
   * Sets new parameter of the class information.
   *
   * @param mixed $var - the parameter name.
   * @param mixed $value - value of the parameter.
   * @access public
   */
  public function offsetSet($var, $value)
  {
    $this->info[$var] = $value;
  }

  /**
   * Checks whether the class parameter exists.
   *
   * @param mixed $var - the parameter name.
   * @return boolean
   * @access public   
   */
  public function offsetExists($var)
  {
    return isset($this->info[$var]);
  }

  /**
   * Removes the requested class parameter.
   *
   * @param mixed $var - the parameter name.
   * @access public
   */
  public function offsetUnset($var)
  {
    unset($this->info[$var]);
  }

  /**
   * Returns value of the class parameter.
   *
   * @param mixed $var - the parameter name.
   * @return mixed
   * @access public
   */
  public function &offsetGet($var)
  {
    if (!isset($this->info[$var])) $this->info[$var] = null;
    return $this->info[$var];
  }

  /**
   * Extracts full information about the class and place into $info property.
   *
   * @param mixed $class - class name or class object.
   * @access protected
   */
  protected function extraction($class)
  {
    $info = [];
    $class = new \ReflectionClass($class);
    $parent = $class->getParentClass();
    $info['name'] = $class->getName();
    $info['shortName'] = $class->getShortName();
    $info['namespace'] = $class->getNamespaceName();
    $info['comment'] = $class->getDocComment();
    $info['file'] = $class->getFileName();
    $info['startLine'] = $class->getStartLine();
    $info['endLine'] = $class->getEndLine();
    $info['isAbstract'] = $class->isAbstract();
    $info['isFinal'] = $class->isFinal();
    $info['isInstantiable'] = $class->isInstantiable();
    $info['isInterface'] = $class->isInterface();
    $info['isInternal'] = $class->isInternal();
    $info['isIterateable'] = $class->isIterateable();
    $info['isUserDefined'] = $class->isUserDefined();
    if (method_exists($class, 'isCloneable')) $info['isCloneable'] = $class->isCloneable();
    if (method_exists($class, 'isTrait')) $info['isTrait'] = $class->isTrait();
    $info['inNamespace'] = $class->inNamespace();
    $info['extension'] = $class->getExtensionName();
    if ($parent instanceof \ReflectionClass)
    {
      $info['parentName'] = $parent->getName();
      $info['parentShortName'] = $parent->getShortName();
      $info['parentNamespace'] = $parent->getNamespaceName();
    }
    else
    {
      $info['parentName'] = '';
      $info['parentShortName'] = '';
      $info['parentNamespace'] = '';
    }
    $lines = $this->getFileContent($info['file']);
    $tokens = token_get_all('<?php ' . implode(PHP_EOL, array_slice($lines, $info['startLine'] - 1, $info['endLine'] - $info['startLine'] + 1)) . ' ?>');
    $info['interfaces'] = [];
    foreach ($class->getInterfaces() as $name => $interface)
    {
      if (!$this->interfaceInClass($tokens, $interface, $class)) continue;
      $tmp = [];
      $tmp['name'] = $interface->getName();
      $tmp['shortName'] = $interface->getShortName();
      $tmp['namespace'] = $interface->getNamespaceName();
      $info['interfaces'][$name] = $tmp;
    }
    $info['constants'] = [];
    foreach ($class->getConstants() as $name => $constant)
    {
      if (!$this->constantInClass($tokens, $name)) continue;
      $info['constants'][$name] = $constant;
    }
    $info['properties'] = [];
    $defaults = $class->getDefaultProperties();
    foreach ($class->getProperties() as $property)
    {
      if ($property->getDeclaringClass()->getName() != $info['name']) continue;
      $name = $property->getName();
      $tmp = [];
      $tmp['isDefault'] = $property->isDefault();
      $tmp['isPrivate'] = $property->isPrivate();
      $tmp['isProtected'] = $property->isProtected();
      $tmp['isPublic'] = $property->isPublic();
      $tmp['isStatic'] = $property->isStatic();
      $tmp['defaultValue'] = $tmp['isStatic'] ? $this->getStaticPropertyValue($tokens, $name) : $defaults[$name];
      $tmp['comment'] = $property->getDocComment();
      $info['properties'][$name] = $tmp;
    }
    $info['methods'] = [];
    foreach ($class->getMethods() as $method)
    {
      if ($method->getDeclaringClass()->getName() != $info['name']) continue;
      $name = $method->getName();
      $tmp = [];
      $tmp['isAbstract'] = $method->isAbstract();
      $tmp['isConstructor'] = $method->isConstructor();
      $tmp['isDestructor'] = $method->isDestructor();
      $tmp['isFinal'] = $method->isFinal();
      $tmp['isPrivate'] = $method->isPrivate();
      $tmp['isProtected'] = $method->isProtected();
      $tmp['isPublic'] = $method->isPublic();
      $tmp['isStatic'] = $method->isStatic();
      $tmp['returnsReference'] = $method->returnsReference();
      $tmp['comment'] = $method->getDocComment();
      $tmp['startLine'] = $method->getStartLine();
      $tmp['endLine'] = $method->getEndLine();
      $tmp['numberOfParameters'] = $method->getNumberOfParameters();
      $tmp['numberOfRequiredParameters'] = $method->getNumberOfRequiredParameters();
      $tmp['arguments'] = [];
      foreach ($method->getParameters() as $parameter)
      {
        $class = $parameter->getClass();
        if ($class instanceof \ReflectionClass)
        {
           $cls = [];
           $cls['name'] = $class->getName();
           $cls['shortName'] = $class->getShortName();
           $cls['namespace'] = $class->getNamespaceName();
           $class = $cls;
        }
        $arg = [];
        $arg['name'] = $parameter->getName();
        $arg['class'] = $class ?: '';
        $arg['position'] = $parameter->getPosition();
        $arg['isDefaultValueAvailable'] = $parameter->isDefaultValueAvailable();
        $arg['isArray'] = $parameter->isArray();
        $arg['isOptional'] = $parameter->isOptional();
        $arg['isPassedByReference'] = $parameter->isPassedByReference();
        $arg['allowsNull'] = $parameter->allowsNull();
        $arg['defaultValue'] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
        if (method_exists($parameter, 'canBePassedByValue')) $arg['canBePassedByValue'] = $parameter->canBePassedByValue();
        $tmp['arguments'][$arg['name']] = $arg;
      }
      $tmp['code'] = $this->getMethodBody(implode(PHP_EOL, array_slice($lines, $tmp['startLine'] - 1, $tmp['endLine'] - $tmp['startLine'] + 1)));
      $info['methods'][$name] = $tmp;
    }
    if ($info['comment'] != '') $info['startLine'] -= count(explode("\n", str_replace("\r", '', $info['comment'])));
    $this->info = $info;
  }
  
  /**
   * Checks whether the constant is declared in the class.
   * 
   * @param array $tokens - tokens of the class code.
   * @param string $constant - the constant name to check.
   * @return boolean
   * @access private
   */
  private function constantInClass(array $tokens, $constant)
  {
    if (count($tokens) <= 3) return true;
    $const = 0;
    foreach ($tokens as $token)
    {
      if (is_array($token))
      {
        if ($token[0] == T_CONST) $const = 1;
        else if ($const == 1 && $token[0] == T_STRING)
        {
          if ($token[1] == $constant) return true;
          else $const = 0;
        }        
      }
    }
  }
  
  /**
   * Checks whether the interface is declared in the class.
   * 
   * @param array $tokens - tokens of the class code.
   * @param \ReflectionClass $interface - the interface to check.
   * @param \ReflectionClass $class - the class implementing the required interface.
   * @return boolean
   * @access private
   */
  private function interfaceInClass(array $tokens, \ReflectionClass $interface, \ReflectionClass $class)
  {
    if (count($tokens) <= 3) return true;
    foreach ($tokens as $i => $token)
    {
      if ($token == '{') return false;
      if (is_array($token) && $token[1] == $interface->getShortName())
      {
        $ns = '';
        for ($n = $i - 1; $n > 0; $n--)
        {
          $token = $tokens[$n];
          if ($token == ',' || is_array($token) && $token[0] == T_WHITESPACE) break;
          $ns = (is_array($token) ? $token[1] : $token) . $ns;
        }
        $ns = ltrim($ns, '\\');
        if ($ns == '' && $class->getNamespaceName() == $interface->getNamespaceName() || strpos($interface->getNamespaceName(), rtrim($ns, '\\')) !== false) return true;
      }
    }
    return false;
  }
  
  /**
   * Returns default value of the static property.
   *
   * @param array $tokens - tokens of the class code.
   * @param string $name - static property name.
   * @return mixed
   * @access private
   */
  private function getStaticPropertyValue(array $tokens, $name)
  {
    $brace = $static = 0;
    $code = '';
    foreach ($tokens as $i => $token)
    {
      if ($token == '{') $brace++;
      else if ($token == '}') $brace--;
      else if ($brace == 1 && is_array($token) && $token[0] == T_STATIC) $static = 1;
      else if ($static && is_array($token) && $token[0] == T_VARIABLE) 
      {
        if ('$' . $name != $token[1]) $static = 0;
        else
        {
          for ($n = $i + 1, $m = count($tokens) - 1; $n <= $m; $n++)
          {
            $token = $tokens[$n];
            if ($token == ';') break;
            if (is_array($token) && $token[0] == T_WHITESPACE || $token == '=') continue;
            $code .= is_array($token) ? $token[1] : $token;
          }
          break;
        }
      }
    }
    if ($code != '')
    {
      eval('$tmp = ' . $code . ';');
      return $tmp;
    }
  }
  
  /**
   * Returns PHP code of the method body.
   *
   * @param string $code - PHP code of the method.
   * @return string
   * @access private
   */
  private function getMethodBody($code)
  {
    if ($code == '') return '';
    $tokens = token_get_all('<?php ' . $code . ' ?>');
    $code = '';
    $max = count($tokens) - 3;
    for ($i = 1; $i < $max; $i++)
    {
      if ($tokens[$i] == '{')
      {
        for ($i++; $i < $max; $i++)
        {
          $value = $tokens[$i];
          $code .= is_array($value) ? $value[1] : $value;
        }
        break;
      }
    }
    return $code;
  }
  
  /**
   * Returns array of code lines of the PHP script file.
   *
   * @param string $file
   * @return array
   * @access private
   */
  private function getFileContent($file)
  {
    if (!is_file($file)) return [];
    return explode("\n", str_replace("\r", '', file_get_contents($file)));
  }
  
  /**
   * Writes a file content.
   * It returns the number of bytes that were written to the file, or FALSE on failure.
   *
   * @param string $file - a file to write.
   * @param string $content - new file content.
   * @return boolean|integer
   * @access private
   */
  private function setFileContent($file, $content)
  {
    $res = file_put_contents($file, $content, LOCK_EX);
    chmod($file, $this->fileMode);
    return $res;
  }
}