<?php

use Aleph\Utils\PHP;

require_once(__DIR__ . '/../utils/php/tokenizer.php');

/**
 * Test for Aleph\Utils\PHP\Tokenizer
 */
function test_tokenizer()
{
  $source = __DIR__ . '/_resources/php1.bin';
  $tokenizer = new PHP\Tokenizer($source);

  $tokens = [];
  foreach ($tokenizer as $n => $token) $tokens[$n] = $token;

  $tokenizer->reset();
  $tokens = [];
  while (($token = $tokenizer->token()) !== false) $tokens[] = $token;

  $tokens = [];
  foreach ($tokenizer as $n => $token) $tokens[$n] = $token;
  
  $tokenizer->reset();
  $tokens = [];
  while (($token = $tokenizer->token()) !== false) $tokens[] = $token;

  $original = token_get_all(file_get_contents($source));
  
  /*echo '<table><tr>';
  echo '<td valign="top"><pre>' . htmlspecialchars(print_r($original, true)) . '</pre></td>';
  echo '<td valign="top"><pre>' . htmlspecialchars(print_r($tokens, true)) . '</pre></td>';
  echo '</tr></table>';*/

  return $original == $tokens;
}

return test_tokenizer();