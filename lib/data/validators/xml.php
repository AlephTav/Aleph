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

namespace Aleph\Data\Validators;

use Aleph\Core;

/**
 * This validator compares the given XML with another XML or checks whether the given XML has the specified structure.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.data.validators
 */
class XML extends Validator
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
   * Validates an XML string.
   *
   * @param string $entity - the XML for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->empty && $this->isEmpty($entity)) return $this->reason = true;
    $dom = new \DOMDocument('1.0', 'utf-8');
    $dom->formatOutput = true;
    $dom->preserveWhiteSpace = false;
    if (is_file($entity)) $dom->load($entity);
    else $dom->loadXML($entity);
    if ($this->schema)
    {
      libxml_clear_errors();
      if (is_file($this->schema)) 
      {
        if (!$dom->schemaValidate($this->schema)) 
        {
          $this->reason = ['code' => 0, 'reason' => 'invalid schema', 'details' => libxml_get_errors()];
          return false;
        }
      }
      else if (!$dom->schemaValidateSource($this->schema)) 
      {
        $this->reason = ['code' => 0, 'reason' => 'invalid schema', 'details' => libxml_get_errors()];
        return false;
      }
    }
    if (!$this->xml) return $this->reason = true;
    $xml = $dom->saveXML();
    $dom = new \DOMDocument('1.0', 'utf-8');
    $dom->formatOutput = true;
    $dom->preserveWhiteSpace = false;
    if (is_file($this->xml)) $dom->load($this->xml);
    else $dom->loadXML($this->xml);
    if ($xml === $dom->saveXML()) return $this->reason = true;
    $this->reason = ['code' => 1, 'reason' => 'XML are not equal'];
    return false;
  }  
}