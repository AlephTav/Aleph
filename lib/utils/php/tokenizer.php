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

namespace Aleph\Utils\PHP;

/**
 * The class allows to iterate tokens of php-code.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.3
 * @package aleph.utils.php
 */
class Tokenizer implements \Iterator
{
  /**
   * Source php-code.
   *
   * @var string $source
   * @access protected
   */
  protected $source = null;
  
  /**
   * Length of the source code.
   *
   * @var integer $length
   * @access protected
   */
  protected $length = null;
  
  /**
   * The current token.
   *
   * @var mixed $token
   * @access protected
   */
  protected $token = null;
  
  /**
   * Position of the current token.
   *
   * @var integer $pos
   * @access protected
   */
  protected $pos = -1;
  
  /**
   * Array of tokens representing heredoc string or double quoted string.
   *
   * @var array $tokens
   * @access private
   */
  private $tokens = [];
  
  /**
   * The number of processed code lines.
   *
   * @var integer $line
   * @access private
   */
  private $line = 1;
  
  /**
   * Position in source code string.
   *
   * @var integer $seek
   * @access private
   */
  private $seek = 0;
  
  /**
   * Parses string into tokens. 
   * Returns an array of token identifiers.
   *
   * @param string $code - php-code string.
   * @return array
   * @access public
   * @static
   */
  public static function parse($code)
  {
    if (strtolower(substr($code, 0, 5)) == '<?php' || substr($code, 0, 2) == '<?') 
    {
      $tokens = @token_get_all($code);
    }
    else
    {
      $tokens = @token_get_all('<?php ' . $code);
      array_shift($tokens);
    }
    return $tokens;
  }

  /**
   * Constructor. Sets source php code to tokenize.
   *
   * @param string $source - php code string or file path.
   * @access public
   */
  public function __construct($source)
  {
    $this->source = is_file($source) ? file_get_contents($source) : $source;
    $this->length = strlen($this->source);
  }
  
  /**
   * Sets internal pointer to the first character of source code.
   *
   * @access public
   */
  public function reset()
  {
    $this->pos = -1;
    $this->seek = 0;
    $this->line = 1;
    $this->tokens = [];
  }
  
  /**
   * Returns the current token and move forward to next one.
   *
   * @return mixed
   * @access public
   */
  public function token()
  {
    $this->next();
    return $this->token;
  }
  
  /**
   * Returns the current token.
   *
   * @return mixed
   * @access public
   */
  public function current()
  {
    return $this->token;
  }
  
  /**
   * Return the position of the current token.
   * Returns integer on success, or NULL on failure.
   *
   * @return integer
   * @access public
   */
  public function key()
  {
    return $this->pos;
  }
  
  /**
   * Checks if current position is valid. 
   * Returns TRUE on success or FALSE on failure.
   *
   * @return boolean
   * @access public
   */
  public function valid()
  {
    return $this->pos > -1;
  }
  
  /**
   * Rewinds the Tokenizer to the first token.
   *
   * @access public
   */
  public function rewind()
  {
    $this->reset();
    $this->next();
  }
  
  /**
   * Move forward to next token.
   *
   * @access public
   */
  public function next()
  {
    if ($this->seek >= $this->length)
    {
      $this->token = false;
      $this->pos = -1;
      return;
    }
    if (count($this->tokens)) $token = array_shift($this->tokens);
    else
    {
      $token = $this->read();
      if ($token == '"') $token = $this->extractDoubleQuotedString();
      else if (is_array($token) && $token[0] == T_START_HEREDOC) $token = $this->extractHeredocString($token);
    }
    if (is_array($token)) 
    {
      $this->seek += strlen($token[1]);
      $token[2] = $this->line;
      $this->line += substr_count($token[1], "\n");
    }
    else 
    {
      $this->seek += strlen($token);
    }
    $this->token = $token;
    $this->pos++;
  }
  
  /**
   * Extracts and parses heredoc string.
   *
   * @param array $token
   * @access private
   */
  private function extractHeredocString(array $token)
  {
    $doc = trim(substr($token[1], 3), "\" \t\n\r\0\x0B");
    if (preg_match('/\s{1}"?' . preg_quote($doc, '/') . '"?;?\s{1}/', $this->source, $matches, PREG_OFFSET_CAPTURE, $this->seek + strlen($token[1])))
    {
      $portion = substr($this->source, $this->seek, $matches[0][1] + strlen($matches[0][0]) - $this->seek);
    }
    else
    {
      $portion = substr($this->source, $this->seek);
    }
    $this->tokens = self::parse($portion);
    array_pop($this->tokens);
    return array_shift($this->tokens);
  }
  
  /**
   * Extracts ans parses double quoted string.
   *
   * @access private
   */
  private function extractDoubleQuotedString()
  {
    if (preg_match('/"(?:.*?[^\\\]{1}(?:\\\\\\\\)*?)?"/s', $this->source, $matches, PREG_OFFSET_CAPTURE, $this->seek))
    {
      $portion = substr($this->source, $this->seek, $matches[0][1] + strlen($matches[0][0]) - $this->seek);
    }
    else
    {
      $portion = substr($this->source, $this->seek);
    }
    $this->tokens = self::parse($portion);
    return array_shift($this->tokens);
  }
  
  /**
   * Rteurns next token from source code.
   *
   * @return mixed
   * @access private   
   */
  private function read()
  {
    $token = ''; $n = 0;
    do
    {
      $n += 8;
      $old = $token;
      $tokens = self::parse(substr($this->source, $this->seek, $n));
      $token = $tokens[0];
    }
    while ($old != $token);
    return $token;
  }
}