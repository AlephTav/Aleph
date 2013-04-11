<?php

namespace Aleph\Utils;

class Tokenizer
{
  protected $source = null;
  
  protected $length = null;
  
  protected $line = 1;
  
  protected $seek = 0;
  
  private $tokens = [];

  public function __construct($source)
  {
    $this->source = is_file($source) ? file_get_contents($source) : $source;
    $this->length = strlen($this->source);
  }
  
  public function reset()
  {
    $this->seek = 0;
    $this->line = 1;
    $this->tokens = [];
  }
  
  public function token()
  {
    if ($this->seek >= $this->length) return false;
    if (count($this->tokens)) $token = array_shift($this->tokens);
    else
    {
      $token = $this->read();
      if ($token == '"') $this->extractDoubleQuotedString();
      else if (is_array($token) && $token[0] == T_START_HEREDOC) $this->extractHeredocString($token);
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
    return $token;
  }
  
  private function extractHeredocString($token)
  {
    $doc = rtrim(substr($token[1], 3));
    if (preg_match('/\s{1}' . preg_quote($doc, '/') . ';?\s{1}/', $this->source, $matches, PREG_OFFSET_CAPTURE, $this->seek + strlen($token[1])))
    {
      $portion = substr($this->source, $this->seek, $matches[0][1] + strlen($matches[0][0]) - $this->seek);
    }
    else
    {
      $portion = substr($this->source, $this->seek);
    }
    $this->tokens = @token_get_all('<?php ' . $portion);
    array_shift($this->tokens);
    array_shift($this->tokens);
    array_pop($this->tokens);
  }
  
  private function extractDoubleQuotedString()
  {
    $p = $this->seek + 1;
    do
    {
      $n = strpos($this->source, '"', $p);
      if ($n === false) break;
      $t = $n; $p = $n + 1;
      while ($this->source[--$t] == '\\');
    }
    while ((($n - $t) & 1) == 0);
    $this->tokens = @token_get_all('<?php ' . substr($this->source, $this->seek, $p - $this->seek));
    array_shift($this->tokens);
    array_shift($this->tokens);
  }
  
  private function read()
  {
    $token = ''; $n = 0;
    do
    {
      $n += 16;
      $old = $token;
      $tokens = @token_get_all('<?php ' . substr($this->source, $this->seek, $n));
      array_shift($tokens);
      $token = $tokens[0];
    }
    while ($old != $token);
    return $token;
  }
}