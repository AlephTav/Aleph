<?php
/**
 * Copyright (c) 2013 - 2016 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2016 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Core;

use Aleph\Core\Interfaces\ITemplate;
use Aleph\Cache\Cache;

/**
 * Implementation of the base template engine that supports render caching.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.1.0
 * @package aleph.core
 */
class CacheableTemplate extends Template
{
    /**
     * Error message templates.
     */
    const ERR_TEMPLATE_1 = 'The cache instance is not specified.';

    /**
     * An instance of Aleph\Cache\Cache class.
     *
     * @var \Aleph\Cache\Cache
     */
    private $cache = null;

    /**
     * Unique cache identifier of the template.
     *
     * @var mixed
     */
    private $cacheKey = null;

    /**
     * Cache expiration time of the template.
     *
     * @var int
     */
    private $cacheExpire = -1;

    /**
     * Cache tags.
     *
     * @var array
     */
    private $cacheTags = [];

    /**
     * Hash of the cache key.
     *
     * @var string
     */
    private $hash = '';

    /**
     * Constructor.
     *
     * @param string $template The template string or path to a template file.
     * @param \Aleph\Cache\Cache $cache The cache instance.
     * @param string $cacheKey The unique identifier of the template cache.
     * @param int $cacheExpire The template cache life time in seconds.
     * @param array $cacheTags Tags associated with the template cache.
     */
    public function __construct(string $template = '',
                                Cache $cache = null, $cacheKey = null, int $cacheExpire = -1, array $cacheTags = [])
    {
        parent::__construct($template);
        if ($cache) {
            $this->cache = $cache;
        }
        if ($cacheKey === null) {
            $this->setCacheKey(md5(microtime(true)));
        } else {
            $this->setCacheKey($cacheKey);
        }
        $this->cacheExpire = $cacheExpire;
        $this->cacheTags = $cacheTags;
    }

    /**
     * Returns an instance of the template cache.
     *
     * @return \Aleph\Cache\Cache|null
     */
    public function getCacheInstance()
    {
        return $this->cache;
    }

    /**
     * Sets an instance of the template cache.
     *
     * @param \Aleph\Cache\Cache $cache
     * @return void
     */
    public function setCacheInstance(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Returns the identifier of the template cache.
     *
     * @return mixed
     */
    public function getCacheKey()
    {
        return $this->cacheKey;
    }

    /**
     * Sets the unique identifier of the template cache.
     *
     * @param mixed $key
     * @return void
     */
    public function setCacheKey($key)
    {
        $this->cacheKey = $key;
        $this->hash = md5(serialize($key));
    }

    /**
     * Returns the expiration time of the template cache.
     *
     * @return int
     */
    public function getCacheExpire() : int
    {
        return $this->cacheExpire;
    }

    /**
     * Sets the expiration time of the template cache.
     *
     * @param int $expire
     * @return void
     */
    public function setCacheExpire(int $expire)
    {
        $this->cacheExpire = $expire;
    }

    /**
     * Returns the cache tags.
     *
     * @return array
     */
    public function getCacheTags() : array
    {
        return $this->cacheTags;
    }

    /**
     * Sets the cache tags.
     *
     * @param array $tags
     * @return void
     */
    public function setCacheTags(array $tags)
    {
        $this->cacheTags = $tags;
    }

    /**
     * Returns the rendered template.
     *
     * @return string
     */
    public function render() : string
    {
        if ($this->cacheExpire < 0) {
            return parent::render();
        }
        if (!$this->cache) {
            throw new \RuntimeException(static::ERR_TEMPLATE_1);
        }
        $parts = $this->cache->get($this->hash, $isExpired);
        if ($isExpired) {
            $tmp = [];
            foreach ($this->getVars() as $name => $value) {
                if ($value instanceof ITemplate) {
                    $tmp[$name] = $value;
                    $this->__set($name, $this->hash . '<?php $' . $name . ';?>' . $this->hash);
                }
            }
            $content = parent::render();
            $parts = [];
            foreach (explode($this->hash, $content) as $part) {
                $name = substr($part, 7, -3);
                if ($this->__isset($name) && $part == '<?php $' . $name . ';?>') {
                    $parts[] = [$name, true];
                } else {
                    $parts[] = [$part, false];
                }
            }
            $this->setVars($tmp, true);
            $this->cache->set($this->hash, $parts, $this->cacheExpire, $this->cacheTags);
        }
        $content = '';
        $tmp = [];
        foreach ($parts as $part) {
            if ($part[1] === false) {
                $content .= $part[0];
            } else {
                $part = $part[0];
                if (!isset($tmp[$part])) {
                    if ($this->__get($part) instanceof ITemplate) {
                        $tmp[$part] = $this->__get($part)->render();
                    } else {
                        $tmp[$part] = $part;
                    }
                }
                $content .= $tmp[$part];
            }
        }
        return $content;
    }
}