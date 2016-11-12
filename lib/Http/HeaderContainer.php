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

namespace Aleph\Http;

use Aleph;
use Aleph\Data\Structures\Container;
use Aleph\Utils\{Arr, DT};

/**
 * Class provides access to HTTP headers of the server's request or response.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.1.1
 * @package aleph.http
 */
class HeaderContainer extends Container
{
    /**
     * Error message templates.
     */
    const ERR_HEADERBAG_1 = 'Invalid value of header "%s". It must be a scalar or object implementing __toString(), "%s" given.';
    const ERR_HEADERBAG_2 = 'Values of all "accept" headers must be a scalar, array or object implementing __toString(), "%s" given.';
    const ERR_HEADERBAG_3 = 'Content-Type header value must be an array of the structure [\'type\' => string, \'charset\' => string], a scalar or object implementing __toString(), "%s" given.';
    const ERR_HEADERBAG_4 = 'Values of all date headers must be a scalar, an object implementing DateTimeInterface or object implementing __toString(), "%s" given.';
    const ERR_HEADERBAG_5 = 'Value of Cache-Control header must be a scalar, array containing Cache-Control directives or object implementing __toString(), "%s" given.';
    
    /**
     * Array of content type aliases.
     *
     * @var array
     */
    public static $contentTypeMap = [
        'text' => 'text/plain',
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml'
    ];
    
    /**
     * Cache-Control header's directives.
     *
     * @var array
     */
    protected static $cacheControlDirectives = [
        'no-cache',
        'no-store',
        'public',
        'private',
        'max-age',
        'max-stale',
        'min-fresh',
        'no-transform',
        'must-revalidate',
        'proxy-revalidate',
        'max-age',
        's-maxage',
        'only-if-cached',
        'cache-extension',
        'post-check',
        'pre-check'        
    ];
    
    /**
     * The Set-Cookie header values.
     *
     * @var array
     */
    protected $cookies = [];
  
    /**
     * Returns HTTP headers of the current HTTP request.
     *
     * @return array
     */
    public static function getRequestHeaders() : array
    {
        if (function_exists('apache_request_headers'))
        {
            return apache_request_headers();
        }
        $headers = [];
        foreach ($_SERVER as $key => $value) 
        {
            if (strpos($key, 'HTTP_') === 0) 
            {
                $headers[substr($key, 5)] = $value;
            }
        }
        return $headers;
    }
  
    /**
     * Returns HTTP headers of the server response.
     *
     * @return array
     */
    public static function getResponseHeaders() : array
    {
        if (function_exists('apache_response_headers'))
        {
            return apache_response_headers();
        }
        $headers = [];
        foreach (headers_list() as $header) 
        {
            $header = explode(':', $header);
            $headers[array_shift($header)] = trim(implode(':', $header));
        }
        return $headers;
    }
    
    /**
     * Returns normalized header name.
     *
     * @param string $name
     * @return string
     */
    public static function normalizeHeaderName(string $name) : string
    {
        $name = strtr(trim($name), ['_' => ' ', '-' => ' ']);
        $name = ucwords(strtolower($name));
        return str_replace(' ', '-', $name);
    }
    
    /**
     * Returns the parsed HTTP headers.
     *
     * @param array $headers The HTTP headers.
     * @return array
     * @throws \InvalidArgumentException
     */
    public static function parseHeaders(array $headers) : array
    {
        $tmp = [];
        foreach ($headers as $name => $value)
        {
            $name = static::normalizeHeaderName($name);
            $tmp[$name] = static::parseHeaderValue($name, $value);
        }
        return $tmp;
    }
    
    /**
     * Returns the computed HTTP headers.
     *
     * @param array $headers The parsed HTTP headers.
     * @return array
     * @throws \InvalidArgumentException
     */
    public static function computeHeaders(array $headers) : array
    {
        $tmp = [];
        foreach ($headers as $name => $value)
        {
            $tmp[$name] = static::computeHeaderValue($name, $value);
        }
        return $tmp;
    }    
    
    /**
     * Parses the given header value.
     *
     * @param string $name The normalized header name.
     * @param mixed $value The header value.
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function parseHeaderValue(string $name, $value)
    {
        return static::transformHeaderValue($name, $value, 'parse');
    }
    
    /**
     * Returns the computed header value.
     *
     * @param string $name The normalized header name.
     * @param mixed $value The parsed header value.
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function computeHeaderValue(string $name, $value)
    {
        return static::transformHeaderValue($name, $value, 'compute');
    }
    
    
    /**
     * Parses or computes the header value.
     *
     * @param string $name The normalized header name.
     * @param mixed $value The header value.
     * @param string $type The type of transformation. The valid values are "parse" and "compute".
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected static function transformHeaderValue(string $name, $value, string $type)
    {
        $method = $type . str_replace('-', '', $name);
        if (method_exists(get_called_class(), $method))
        {
            return static::$method($value);
        }
        if (!is_scalar($value) && !is_callable([$value, '__toString']))
        {
            throw new \InvalidArgumentException(sprintf(static::ERR_HEADERBAG_1, $name, gettype($value))); 
        }
        return (string)$value;
    }
    
    /**
     * Parses the Content-Type header.
     *
     * @param string|array $value
     * @return array
     * throws \InvalidArgumentException
     */
    protected static function parseContentType($value) : array
    {
        if (is_scalar($value))
        {
            @list($type, $charset) = explode(';', $value, 2);
            $charset = explode('=', $charset);
            return [
                'type' => trim($type),
                'charset' => isset($charset[1]) ? trim($charset[1]) : ''
            ];
        }
        if (is_array($value))
        {
            return [
                'type' => isset($value['type']) ? trim($value['type']) : '',
                'charset' => isset($value['charset']) ? trim($value['charset']) : ''
            ];
        }
        throw new \InvalidArgumentException(sprintf(static::ERR_HEADERBAG_3, gettype($value))); 
    }
    
    /**
     * Computes the Content-Type header value.
     *
     * @param array $value - the parsed Content-Type header value.
     * @return string
     */
    protected static function computeContentType(array $value) : string
    {
        return ($value['type'] ?? 'text/html') . ($value['charset'] ? '; charset=' . $value['charset'] : '');
    }
    
    /**
     * Parses the Accept header value.
     *
     * @param string|array $value
     * @return array
     * @throws \InvalidArgumentException
     */
    protected static function parseAccept($value) : array
    {
        if (is_scalar($value) || is_callable([$value, '__toString']))
        {
            $value = preg_split('/\s*(?:,*("[^"]+"),*|,*(\'[^\']+\'),*|,+)\s*/', $value, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        }
        if (is_array($value))
        {
            return array_map('trim', $value);
        }
        throw new \InvalidArgumentException(sprintf(static::ERR_HEADERBAG_2, gettype($value))); 
    }
    
    /**
     * Computes the Accept header value.
     *
     * @param array $value The parsed Accept header value.
     * @return string
     */
    protected static function computeAccept(array $value) : string
    {
        return implode(',', $value);
    }
    
    /**
     * Parses the Accept-Encoding header value.
     *
     * @param string|array $value
     * @return array
     * @throws \InvalidArgumentException
     */
    protected static function parseAcceptEncoding($value) : array
    {
        return static::parseAccept($value);
    }
    
    /**
     * Computes the Accept-Encoding header value.
     *
     * @param array $value The parsed Accept-Encoding header value.
     * @return string
     */
    protected static function computeAcceptEncoding(array $value) : string
    {
        return static::computeAccept($value);
    }
    
    /**
     * Parses the Accept-Charset header value.
     *
     * @param string|array $value
     * @return array
     * @throws \InvalidArgumentException
     */
    protected static function parseAcceptCharset($value) : array
    {
        return static::parseAccept($value);
    }
    
    /**
     * Computes the Accept-Charset header value.
     *
     * @param array $value The parsed Accept-Charset header value.
     * @return string
     */
    protected static function computeAcceptCharset(array $value) : string
    {
        return static::computeAccept($value);
    }
    
    /**
     * Parses the Date header value.
     *
     * @param string|\DateTimeInterface $value
     * @return \Aleph\Utils\DT|null
     * @throws \InvalidArgumentException
     */
    protected static function parseDate($value)
    {
        if (!is_scalar($value) && !($value instanceof \DateTimeInterface) && is_callable([$value, '__toString']))
        {
            throw new \InvalidArgumentException(sprintf(static::ERR_HEADERBAG_4, gettype($value))); 
        }
        if (strlen($value) == 0)
        {
            return null;
        }
        return new DT($value, 'UTC');
    }
    
    /**
     * Computes the Date header value.
     *
     * @param \Aleph\Utils\DT $value
     * @return string
     */
    protected static function computeDate(DT $value) : string
    {
        return $value->format('D, d M Y H:i:s') . ' GMT';
    }
    
    /**
     * Parses the Expires header value.
     *
     * @param string|\DateTimeInterface $value
     * @return \Aleph\Utils\DT|null
     * @throws \InvalidArgumentException
     */
    protected static function parseExpires($value)
    {
        return static::parseDate($value);
    }
    
    /**
     * Computes the Expires header value.
     *
     * @param \Aleph\Utils\DT $value
     * @return string
     */
    protected static function computeExpires(DT $value) : string
    {
        return static::computeDate($value);
    }
    
    /**
     * Parses the Last-Modified header value.
     *
     * @param string|\DateTimeInterface $value
     * @return \Aleph\Utils\DT|null
     * @throws \InvalidArgumentException
     */
    protected static function parseLastModified($value)
    {
        return static::parseDate($value);
    }
    
    /**
     * Computes the Last-Modified header value.
     *
     * @param \Aleph\Utils\DT $value
     * @return string
     */
    protected static function computeLastModified(DT $value) : string
    {
        return static::computeDate($value);
    }
    
    /**
     * Parses the If-Modified-Since header value.
     *
     * @param string|\DateTimeInterface $value
     * @return \Aleph\Utils\DT|null
     * @throws \InvalidArgumentException
     */
    protected static function parseIfModifiedSince($value)
    {
        return static::parseDate($value);
    }
    
    /**
     * Computes the If-Modified-Since header value.
     *
     * @param \Aleph\Utils\DT $value
     * @return string
     */
    protected static function computeIfModifiedSince(DT $value) : string
    {
        return static::computeDate($value);
    }
    
    /**
     * Parses the Cache-Control header value.
     *
     * @param string|array $value
     * @return array
     * @throws \InvalidArgumentException
     */
    protected static function parseCacheControl($value) : array
    {
        if (is_array($value))
        {
            $tmp = [];
            foreach (static::$cacheControlDirectives as $directive)
            {
                if (array_key_exists($directive, $value))
                {
                    $tmp[$directive] = $value[$directive];
                }
            }
            return $tmp;
        }
        if (!is_scalar($value) && is_callable([$value, '__toString']))
        {
            throw new \InvalidArgumentException(sprintf(static::ERR_HEADERBAG_5, gettype($value))); 
        }
        $tmp = [];
        preg_match_all('#([a-zA-Z][a-zA-Z_-]*)\s*(?:=(?:"([^"]*)"|([^ \t",;]*)))?#', $value, $matches, PREG_SET_ORDER);
        foreach ($matches as $match)
        {
            $directive = strtolower($match[1]);
            $tmp[$directive] = isset($match[3]) ? $match[3] : (isset($match[2]) ? $match[2] : '');
        }
        return $tmp;
    }
    
    /**
     * Computes the Cache-Control header value.
     *
     * @param array $value The parsed Cache-Control header value.
     * @return string
     */
    protected static function computeCacheControl(array $value) : string
    {
        $tmp = [];
        foreach (static::$cacheControlDirectives as $directive)
        {
            if (!array_key_exists($directive, $value))
            {
                continue;
            }
            $val = trim($value[$directive]);
            if ($val === '')
            {
                $tmp[] = $directive;
            }
            else 
            {
                if (preg_match('#[^a-zA-Z0-9._-]#', $val))
                {
                    $value = '"' . $val . '"';
                }
                $tmp[] = $directive . '=' . $val;
            }
        }
        return implode(', ', $tmp);
    }
    
    /**
     * Constructor.
     *
     * @param array $headers An array of key/value pairs.
     * @param string $delimiter The default key delimiter in composite keys.
     */
    public function __construct(array $headers = [], string $delimiter = Arr::DEFAULT_KEY_DELIMITER)
    {
        parent::__construct(static::parseHeaders($headers), $delimiter);
    }
    
    /**
     * Replaces the current header set by a new one.
     *
     * @param array $headers
     * @return \Aleph\Data\Structures\Container
     */
    public function replace(array $headers = []) : Container
    {
        return parent::replace(static::parseHeaders($headers));
    }
    
    /**
     * Adds new headers to the current set.
     *
     * @param array $headers
     * @return \Aleph\Data\Structures\Container
     */
    public function add(array $headers = []) : Container
    {
        return parent::add(static::parseHeaders($headers));
    }
    
    /**
     * Merge existing headers with new set.
     *
     * @param array $headers
     * @return \Aleph\Data\Structures\Container
     */
    public function merge(array $headers = []) : Container
    {
        return parent::merge(static::parseHeaders($headers));
    }
  
    /**
     * Returns TRUE if the HTTP header is defined and FALSE otherwise.
     *
     * @param string $name The header name.
     * @param bool $compositeKey Determines whether the key is compound key.
     * @param string $delimiter The key delimiter in composite keys.
     * @return bool
     */
    public function has($name, bool $compositeKey = false, string $delimiter = '') : bool
    {
        return parent::has(static::normalizeHeaderName($name), $compositeKey, $delimiter);
    }
  
    /**
     * Returns value of an HTTP header.
     *
     * @param string $name The header name.
     * @param mixed $default The default header value.
     * @param bool $compositeKey Determines whether the key is compound key.
     * @param string $delimiter The key delimiter in composite keys.
     * @return mixed
     */
    public function get($name, $default = null, bool $compositeKey = false, string $delimiter = '')
    {
        return parent::get(static::normalizeHeaderName($name), $default, $compositeKey, $delimiter);
    }
  
    /**
     * Sets an HTTP header.
     *
     * @param string $name The header name.
     * @param mixed $value The new header value.
     * @param bool $merge Determines whether the old element value should be merged with new one.
     * @param bool $compositeKey Determines whether the key is compound key.
     * @param string $delimiter The key delimiter in composite keys.
     * @return \Aleph\Data\Structures\Container
     */
    public function set($name, $value, bool $merge = false,
                        bool $compositeKey = false, string $delimiter = '') : Container
    {
        $name = static::normalizeHeaderName($name);
        return parent::set($name, static::parseHeaderValue($name, $value), $merge, $compositeKey, $delimiter);
    }
  
    /**
     * Removes an HTTP header by its name.
     *
     * @param string $name The header name.
     * @param bool $compositeKey Determines whether the key is compound key.
     * @param string $delimiter The key delimiter in composite keys.
     * @param bool $removeEmptyParent Determines whether the parent element should be removed if it no longer contains elements after removing the given one.
     * @return \Aleph\Data\Structures\Container
     */
    public function remove($name, bool $compositeKey = false,
                           string $delimiter = '', bool $removeEmptyParent = false) : Container
    {
        return parent::remove(static::normalizeHeaderName($name), $compositeKey, $delimiter, $removeEmptyParent);
    }
    
    /**
     * Returns all computed HTTP headers.
     *
     * @return array
     */
    public function getComputedHeaders() : array
    {
        return static::computeHeaders($this->items);
    }
  
    /**
     * Returns the content type and/or response charset.
     *
     * @param bool $withCharset If TRUE the method returns an array of the following structure ['type' => ..., 'charset' => ...], otherwise only content type will be returned.
     * @return array|string
     */
    public function getContentType(bool $withCharset = false)
    {
        $type = $this['Content-Type'] ?: ['type' => '', 'charset' => ''];
        return $withCharset ? $type : $type['type'];
    }
  
    /**
     * Sets content type header. You can use content type alias instead of some HTTP headers (that are determined by $contentTypeMap property).
     *
     * @param string $type The content type or its alias.
     * @param string $charset The content charset.
     * @return static
     */
    public function setContentType(string $type, string $charset = '')
    {
        $type = isset(static::$contentTypeMap[$type]) ? static::$contentTypeMap[$type] : $type;
        $this['Content-Type'] = ['type' => $type, 'charset' => $charset === '' ? $this->getCharset() : $charset];
        return $this;
    }
  
    /**
     * Returns the response charset.
     * If the Content-Type header with charset is not set the method returns NULL.
     *
     * @return string
     */
    public function getCharset() : string
    {
        return $this->getContentType(true)['charset'];
    }
  
    /**
     * Sets the response charset.
     *
     * @param string $charset The response charset.
     * @return static
     */
    public function setCharset(string $charset = 'UTF-8')
    {
        return $this->setContentType($this->getContentType() ?: 'text/html', $charset);
    }
  
    /**
     * Returns a list of content types acceptable by the client browser.
     * If header "Accept" is not set the methods returns empty array.
     *
     * @return array
     */
    public function getAcceptableContentTypes() : array
    {
        return $this['Accept'] ?: [];
    }
    
    /**
     * Sets the content types acceptable by the client browser.
     *
     * @param string|array $types The aceptable content types.
     * @return static
     */
    public function setAcceptableContentTypes($types)
    {
        $this['Accept'] = $types;
        return $this;
    }
  
    /**
     * Checks whether the given cache control directive is set.
     *
     * @param string $directive The directive name.
     * @return bool
     */
    public function hasCacheControlDirective(string $directive)
    {
        return $this->has('Cache-Control.' . $directive);
    }

    /**
     * Returns the value of the given cache control directive.
     * If the directive is not set the method returns NULL.
     *
     * @param string $directive The directive name.
     * @return mixed
     */
    public function getCacheControlDirective(string $directive)
    {
        return $this->get('Cache-Control.' . $directive);
    }
  
    /**
     * Sets the value of the given cache control directive.
     *
     * @param string $directive The directive name.
     * @param mixed $value The directive value.
     * @return \Aleph\Data\Structures\Container
     */
    public function setCacheControlDirective(string $directive, $value = '') : Container
    {
        return $this->set('Cache-Control.' . $directive, $value);
    }
  
    /**
     * Removes the given cache control directive.
     *
     * @param string $directive The directive name.
     * @return \Aleph\Data\Structures\Container
     */
    public function removeCacheControlDirective(string $directive) : Container
    {
        return $this->remove('Cache-Control.' . $directive);
    }
    
    /**
     * Returns cookies.
     *
     * @return array
     */
    public function getCookies() : array
    {
        return $this->cookies;
    }
    
    /**
     * Returns cookie information.
     *
     * @param string $name The cookie name.
     * @return array|null
     */
    public function getCookie(string $name)
    {
        return $this->cookies[$name] ?? null;
    }
    
    /**
     * Sets a cookie.
     *
     * @param string $name The name of the cookie.
     * @param array|string $value The value of the cookie. It can be an array of all cookie parameters: value, expire, path and so on.
     * @param int $expire The time (Unix timestamp) the cookie expires.
     * @param string $path The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
     * @param string $domain The domain that the cookie is available to.
     * @param bool $secure Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client. When set to TRUE, the cookie will only be set if a secure connection exists.
     * @param bool $httpOnly When it is TRUE the cookie will be made accessible only through the HTTP protocol. This means that the cookie won't be accessible by scripting languages, such as JavaScript.
     * @return static
     */
    public function setCookie(string $name, $value = '', int $expire = 0, string $path = '', string $domain = '', bool $secure = false, bool $httpOnly = false)
    {
        if (!is_array($value))
        {
            $value = [
                'value' => $value,
                'expire' => $expire,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httpOnly' => $httpOnly
            ];
        }
        $this->cookies[$name] = array_replace($this->getCookie($name) ?: [], $value);
        return $this;
    }
    
    /**
     * Clears a cookie in the browser.
     *
     * @param string $name The cookie name.
     * @return static
     */
    public function removeCookie(string $name)
    {
        unset($this->cookies[$name]);
        return $this;
    }
  
    /**
     * Clears a cookie in the browser.
     *
     * @param string $name The cookie name.
     * @param string $path The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
     * @param string $domain The domain that the cookie is available to.
     * @param bool $secure Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client. When set to TRUE, the cookie will only be set if a secure connection exists.
     * @param bool $httpOnly When it is TRUE the cookie will be made accessible only through the HTTP protocol. This means that the cookie won't be accessible by scripting languages, such as JavaScript.
     * @return static
     */
    public function clearCookie(string $name, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true)
    {
        return $this->setCookie($name, '', 1, $path, $domain, $secure, $httpOnly);
    }
    
    /**
     * Returns computed cookie values.
     *
     * @return array
     */
    public function getComputedCookies() : array
    {
        $tmp = [];
        foreach ($this->cookies as $name => $data)
        {
            $cookie = urlencode($name) . '=';
            if ((string)$data['value'] === '')
            {
                $cookie .= 'deleted; expires=' . gmdate('D, d-M-Y H:i:s T', time() - 31536001);
            }
            else
            {
                $cookie .= urlencode($data['value']);
                if ($data['expire'] !== 0)
                {
                    $cookie .= '; expires=' . gmdate('D, d-M-Y H:i:s T', $data['expire']);
                }
            }
            if ($data['path'])
            {
                $cookie .= '; path=' . $data['path'];
            }
            if ($data['domain'])
            {
                $cookie .= '; domain=' . $data['domain'];
            }
            if ($data['secure'])
            {
                $cookie .= '; secure';
            }
            if ($data['httpOnly'])
            {
                $cookie .= '; httponly';
            }
            $tmp[] = $cookie;
        }
        return $tmp;
    }
  
    /**
     * Returns the headers as a string.
     *
     * @return string
     */
    public function __toString() : string
    {
        $headers = '';
        try
        {
            foreach ($this->getComputedHeaders() as $name => $value)
            {
                $headers .= $name . ': ' . $value . "\r\n";
            }
            foreach ($this->getComputedCookies() as $cookie)
            {
                $headers .= 'Set-Cookie: ' . $cookie . "\r\n";
            }
        }
        catch (\Throwable $e)
        {
            Aleph::exception($e);
        }
        return $headers;
    }
}