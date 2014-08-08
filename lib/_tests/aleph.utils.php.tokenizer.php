<?php

use Aleph\Utils\PHP;

require_once(__DIR__ . '/../Utils/PHP/Tokenizer.php');

/**
 * Test for Aleph\Utils\PHP\Tokenizer
 */
function test_tokenizer()
{
  $source = __DIR__ . '/_resources/php1.bin';
  $original = token_get_all(file_get_contents($source));
  $tokenizer = new PHP\Tokenizer($source);
  $tokens = [];
  foreach ($tokenizer as $n => $token) $tokens[$n] = $token;
  if ($original != $tokens) return false;
  $tokenizer->reset();
  $tokens = [];
  while (($token = $tokenizer->token()) !== false) $tokens[] = $token;
  if ($original != $tokens) return false;
  $tokens = [];
  foreach ($tokenizer as $n => $token) $tokens[$n] = $token;
  if ($original != $tokens) return false;
  $tokenizer->reset();
  $tokens = [];
  while (($token = $tokenizer->token()) !== false) $tokens[] = $token;
  if ($original != $tokens) return false;
  return true;
}

return test_tokenizer();