<?php

use Aleph\Core\Template;
use Aleph\Core\CacheableTemplate;
use Aleph\Cache\FileCache;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Aleph\Core\Template class.
 *
 * @group core
 */
class TemplateTest extends TestCase
{
    /**
     * @cover Template::render
     */
    public function testSimpleTemplateRender()
    {
        $tpl = new Template(__DIR__ . '/../_resources/template.tpl');
        $tpl->attr1 = 2;
        $tpl->attr2 = 1;
        $tpl->tpl = 'test';
        $this->assertEquals(3, count($tpl));
        $this->assertEquals('<template attr1="2" attr2="1">test</template>', $tpl->render());
    }

    /**
     * @cover Template::render
     * @depends testSimpleTemplateRender
     */
    public function testNestedTemplateRender()
    {
        $template = __DIR__ . '/../_resources/template.tpl';
        $tpl = new Template($template);
        $tpl->attr2 = 'a2';
        $tpl->attr1 = 'a1';
        $tpl->tpl = new Template($template);
        $tpl->tpl->attr1 = 1;
        $tpl->tpl->attr2 = 2;
        $tpl->tpl->tpl = 'test';
        $this->assertEquals(3, count($tpl));
        $renderedTemplate = '<template attr1="a1" attr2="a2"><template attr1="1" attr2="2">test</template></template>';
        $this->assertEquals($renderedTemplate, $tpl->render());
    }

    /**
     * @cover Template::render
     * @depends testNestedTemplateRender
     */
    public function testCacheTemplateRender()
    {
        $dir = __DIR__ . '/../_cache/' . uniqid('tpl', true);
        $cache = new FileCache();
        $cache->setDirectory($dir);
        $key1 = uniqid('key1', true);
        $key2 = uniqid('key2', true);
        $tpl1 = 'Template #1: 1 | Template #2: 2 | test';
        $tpl2 = 'Template #1: 1 | Template #2: 1 | test';
        $tpl3 = 'Template #1: 2 | Template #2: 1 | test';
        $tpl = new CacheableTemplate(__DIR__ . '/../_resources/cached_template.tpl', $cache, $key1, 4);
        $tpl->number = 1;
        $tpl->var = 1;
        $tpl->tpl = new CacheableTemplate(__DIR__ . '/../_resources/cached_template.tpl', $cache, $key2, 2);
        $tpl->tpl->number = 2;
        $tpl->tpl->var = 2;
        $tpl->tpl->tpl = 'test';
        $flag = false;
        if ($tpl->render() === $tpl1) {
            $tpl->var = 2;
            $tpl->tpl->var = 1;
            sleep(1);
            if ($tpl->render() === $tpl1) {
                sleep(2);
                if ($tpl->render() === $tpl2) {
                    sleep(2);
                    if ($tpl->render() === $tpl3) {
                        $flag = true;
                    }
                }
            }
        }
        $this->assertTrue($flag);
        $cache->clean();
        rmdir($dir);
    }
}