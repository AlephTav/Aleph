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

namespace Aleph\Net;

use Aleph\Core;

/**
 * ValidatorXML compares the given xml with another xml or checks whether the given xml has the specified structure.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.net
 */
class ValidatorXML extends Validator
{
  /**
   * XML string or path to the XML file to be compared with.
   *
   * @var string $xml
   * @access public
   */
  public $xml = null;
  
  /**
   * The XML schema which the validating XML should correspond to. 
   *
   * @var string $schema
   * @access public
   */
  public $schema = null;

  /**
   * Determines whether the value can be null or empty.
   * If $allowEmpty is TRUE then validating empty value will be considered valid.
   *
   * @var boolean $allowEmpty
   * @access public
   */
  public $allowEmpty = false;

  /**
   * Validate an XML string.
   *
   * @param string $entity - the XML for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->allowEmpty && $this->isEmpty($entity)) return true;
    $dom = new \DOMDocument('1.0', 'utf-8');
    $dom->formatOutput = true;
    $dom->preserveWhiteSpace = false;
    if (is_file($entity)) $dom->load($entity);
    else $dom->loadXML($entity);
    if ($this->schema)
    {
      if (is_file($this->schema)) 
      {
        if (!$dom->schemaValidate($this->schema)) return false;
      }
      else if (!$dom->schemaValidateSource($this->schema)) return false;
    }
    if (!$this->xml) return true;
    $xml = $dom->saveXML();
    $dom = new \DOMDocument('1.0', 'utf-8');
    $dom->formatOutput = true;
    $dom->preserveWhiteSpace = false;
    if (is_file($this->xml)) $dom->load($this->xml);
    else $dom->loadXML($this->xml);
    return $xml === $dom->saveXML();
  }  
}