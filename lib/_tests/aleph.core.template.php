<?php

use Aleph\Core,
    Aleph\Cache;

require_once(__DIR__ . '/../core/template.php');

/**
 * Test for Aleph\Core\Template
 */
function test_template()
{
  $file = __DIR__ . '/_resources/template.tpl';
  // Checks processing of simple templates.
  Core\Template::setGlobals(['attr1' => 1, 'attr2' => 2, 'tpl' => 'test']);
  $tpl = new Core\Template($file);
  $tpl->attr1 = 2;
  $tpl['attr2'] = 1;
  if ($tpl->render() !== '<template attr1="2" attr2="1">test</template>') return 'Processing of simple templates doesn\'t work.';
  // Checks processing of nested templates.
  $tpl->tpl = new Core\Template($file);
  $tpl->cacheGroup = null;
  if ($tpl->render() !== '<template attr1="2" attr2="1"><template attr1="1" attr2="1">test</template></template>') return 'Processing of nested templates doesn\'t work.';
  // Checks caching of templates.
  $cache = new Cache\File();
  $cache->setDirectory(__DIR__ . '/_cache');
  $key1 = uniqid('key1', true);
  $key2 = uniqid('key2', true);
  $tpl1 = 'Template #1: 1 | Template #2: 2 | test';
  $tpl2 = 'Template #1: 1 | Template #2: 1 | test';
  $tpl3 = 'Template #1: 2 | Template #2: 1 | test';
  Core\Template::setGlobals([]);
  $tpl = new Core\Template(__DIR__ . '/_resources/cached_template.tpl', 4, $key1, $cache);
  $tpl->cacheGroup = null;
  $tpl->number = 1;
  $tpl->var = 1;
  $tpl->tpl = new Core\Template(__DIR__ . '/_resources/cached_template.tpl', 2, $key2, $cache);
  $tpl->tpl->cacheGroup = null;
  $tpl->tpl->number = 2;
  $tpl->tpl->var = 2;
  $tpl->tpl->tpl = 'test';
  $flag = false;
  if ($tpl->render() === $tpl1)
  {
    $tpl->var = 2;
    $tpl->tpl->var = 1;
    sleep(1);
    if ($tpl->render() === $tpl1)
    {
      sleep(2);
      if ($tpl->render() === $tpl2)
      {
        sleep(2);
        if ($tpl->render() === $tpl3) $flag = true;
      }
    }
  }
  if (!$flag) return 'Template caching doesn\'t work.';
  // Removes cache
  $cache = $tpl->getCache();
  $cache->remove($key1);
  $cache->remove($key2);
  return true;
}

return test_template();