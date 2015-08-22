<?php
/**
 * Copyright (c) 2013 - 2015 Aleph Tav
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
 * @copyright Copyright &copy; 2013 - 2015 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Net;

use Aleph\Utils;

/**
 * Class provides access to HTTP headers of the server's request or response.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.1.0
 * @package aleph.net
 */
class HeaderBag extends Utils\Bag
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
     * @var array $contentTypeMap
     * @access public
     * @static
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
     * @var array $cacheControlDirectives
     * @access protected
     * @static
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
     * @var array $cookies
     * @access protected
     */
    protected $cookies = [];
  
    /**
     * Returns HTTP headers of the current HTTP request.
     *
     * @return array
     * @access public
     * @static
     */
    public static function getRequestHeaders()
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
     * @access public
     * @static
     */
    public static function getResponseHeaders()
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
     * @access public
     * @static
     */
    public static function normalizeHeaderName($name)
    {
        $name = strtr(trim($name), ['_' => ' ', '-' => ' ']);
        $name = ucwords(strtolower($name));
        return str_replace(' ', '-', $name);
    }
    
    /**
     * Returns the parsed HTTP headers.
     *
     * @param array $headers - the HTTP headers.
     * @return array
     * @throws InvalidArgumentException
     * @access public
     * @static
     */
    public static function parseHeaders(array $headers)
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
     * @param array $headers - the parsed HTTP headers.
     * @return array
     * @throws InvalidArgumentException
     * @access public
     * @static
     */
    public static function computeHeaders(array $headers)
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
     * @param string $name - the normalized header name.
     * @param mixed $value - the header value.
     * @return mixed
     * @throws InvalidArgumentException
     * @access public
     * @static
     */
    public static function parseHeaderValue($name, $value)
    {
        return static::transformHeaderValue($name, $value, 'parse');
    }
    
    /**
     * Returns the computed header value.
     *
     * @param string $name - the normalized header name.
     * @param mixed $value - the parsed header value.
     * @return mixed
     * @throws InvalidArgumentException
     * @access public
     * @static
     */
    public static function computeHeaderValue($name, $value)
    {
        return static::transformHeaderValue($name, $value, 'compute');
    }
    
    
    /**
     * Parses or computes the header value.
     *
     * @param string $name - the normalized header name.
     * @param mixed $value - the header value.
     * @param string $type - the type of transformation. The valid values are "parse" and "compute".
     * @return mixed
     * @throws InvalidArgumentException
     * @access protected
     * @static
     */
    protected static function transformHeaderValue($name, $value, $type)
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
     * throws InvalidArgumentException
     * @access protected
     * @static
     */
    protected static function parseContentType($value)
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
     * @access protected
     * @static
     */
    protected static function computeContentType(array $value)
    {
        return (isset($value['type']) ? $value['type'] : 'text/html') . ($value['charset'] ? '; charset=' . $value['charset'] : '');
    }
    
    /**
     * Parses the Accept header value.
     *
     * @param string|array $value
     * @return array
     * @throws InvalidArgumentException
     * @access protected
     * @static
     */
    protected static function parseAccept($value)
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
     * @param array $value - the parsed Accept header value.
     * @return string
     * @access protected
     * @static
     */
    protected static function computeAccept(array $value)
    {
        return implode(',', $value);
    }
    
    /**
     * Parses the Accept-Encoding header value.
     *
     * @param string|array $value
     * @return array
     * @throws InvalidArgumentException
     * @access protected
     * @static
     */
    protected static function parseAcceptEncoding($value)
    {
        return static::parseAccept($value);
    }
    
    /**
     * Computes the Accept-Encoding header value.
     *
     * @param array $value - the parsed Accept-Encoding header value.
     * @return string
     * @access protected
     * @static
     */
    protected static function computeAcceptEncoding(array $value)
    {
        return static::computeAccept($value);
    }
    
    /**
     * Parses the Accept-Charset header value.
     *
     * @param string|array $value
     * @return array
     * @throws InvalidArgumentException
     * @access protected
     * @static
     */
    protected static function parseAcceptCharset($value)
    {
        return static::parseAccept($value);
    }
    
    /**
     * Computes the Accept-Charset header value.
     *
     * @param array $value - the parsed Accept-Charset header value.
     * @return string
     * @access protected
     * @static
     */
    protected static function computeAcceptCharset(array $value)
    {
        return static::computeAccept($value);
    }
    
    /**
     * Parses the Date header value.
     *
     * @param string|DateTimeInterface $value
     * @return Aleph\Utils\DT|null
     * @throws InvalidArgumentException
     * @access protected
     * @static
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
        return new Utils\DT($value, 'UTC');
    }
    
    /**
     * Computes the Date header value.
     *
     * @param Aleph\Utils\DT $value
     * @return string
     * @access protected
     * @static
     */
    protected static function computeDate(Utils\DT $value)
    {
        return $value->format('D, d M Y H:i:s') . ' GMT';
    }
    
    /**
     * Parses the Expires header value.
     *
     * @param string|DateTimeInterface $value
     * @return Aleph\Utils\DT|null
     * @throws InvalidArgumentException
     * @access protected
     * @static
     */
    protected static function parseExpires($value)
    {
        return static::parseDate($value);
    }
    
    /**
     * Computes the Expires header value.
     *
     * @param Aleph\Utils\DT $value
     * @return string
     * @access protected
     * @static
     */
    protected static function computeExpires(Utils\DT $value)
    {
        return static::computeDate($value);
    }
    
    /**
     * Parses the Last-Modified header value.
     *
     * @param string|DateTimeInterface $value
     * @return Aleph\Utils\DT|null
     * @throws InvalidArgumentException
     * @access protected
     * @static
     */
    protected static function parseLastModified($value)
    {
        return static::parseDate($value);
    }
    
    /**
     * Computes the Last-Modified header value.
     *
     * @param Aleph\Utils\DT $value
     * @return string
     * @access protected
     * @static
     */
    protected static function computeLastModified(Utils\DT $value)
    {
        return static::computeDate($value);
    }
    
    /**
     * Parses the If-Modified-Since header value.
     *
     * @param string|DateTimeInterface $value
     * @return Aleph\Utils\DT|null
     * @throws InvalidArgumentException
     * @access protected
     * @static
     */
    protected static function parseIfModifiedSince($value)
    {
        return static::parseDate($value);
    }
    
    /**
     * Computes the If-Modified-Since header value.
     *
     * @param Aleph\Utils\DT $value
     * @return string
     * @access protected
     * @static
     */
    protected static function computeIfModifiedSince(Utils\DT $value)
    {
        return static::computeDate($value);
    }
    
    /**
     * Parses the Cache-Control header value.
     *
     * @param string|array $value
     * @return array
     * @throws InvalidArgumentException
     * @access protected
     * @static
     */
    protected static function parseCacheControl($value)
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
     * @param array $value - the parsed Cache-Control header value.
     * @return string
     * @access protected
     * @static
     */
    protected static function computeCacheControl(array $value)
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
     * The most of the code of this method is taken from the Symfony framework (see Symfony\Component\HttpFoundation\ServerBag::getHeaders()).
     *
     * @param array $arr - an array of key/value pairs.
     * @param string $delimiter - the default key delimiter in composite keys.
     * @access public
     */
    public function __construct(array $arr = [], $delimiter = Utils\Arr::DEFAULT_KEY_DELIMITER)
    {
        parent::__construct(static::parseHeaders($arr), $delimiter);
    }
    
    /**
     * Replaces the current header set by a new one.
     *
     * @param array $headers
     * @return static
     * @access public
     */
    public function replace(array $headers = [])
    {
        return parent::replace(static::parseHeaders($headers));
    }
    
    /**
     * Adds new headers to the current set.
     *
     * @param array $headers
     * @return static
     * @access public
     */
    public function add(array $headers = [])
    {
        return parent::add(static::parseHeaders($headers));
    }
    
    /**
     * Merge existing headers with new set.
     *
     * @param array $headers
     * @return static
     * @access public
     */
    public function merge(array $headers = [])
    {
        return parent::merge(static::parseHeaders($headers));
    }
  
    /**
     * Returns TRUE if the HTTP header is defined and FALSE otherwise.
     *
     * @param string $name - the header name.
     * @param boolean $compositeKey - determines whether the key is compound key.
     * @param string $delimiter - the key delimiter in composite keys.
     * @access public
     */
    public function has($name, $compositeKey = false, $delimiter = null)
    {
        return parent::has(static::normalizeHeaderName($name), $compositeKey, $delimiter);
    }
  
    /**
     * Returns value of an HTTP header.
     *
     * @param string $name - the header name.
     * @param mixed $default - the default header value.
     * @param boolean $compositeKey - determines whether the key is compound key.
     * @param string $delimiter - the key delimiter in composite keys.
     * @return mixed
     * @access public
     */
    public function get($name, $default = null, $compositeKey = false, $delimiter = null)
    {
        return parent::get(static::normalizeHeaderName($name), $default, $compositeKey, $delimiter);
    }
  
    /**
     * Sets an HTTP header.
     *
     * @param string $name - the header name.
     * @param mixed $value - new header value.
     * @param boolean $merge - determines whether the old element value should be merged with new one.
     * @param boolean $compositeKey - determines whether the key is compound key.
     * @param string $delimiter - the key delimiter in composite keys.
     * @return static
     * @access public
     */
    public function set($name, $value, $merge = false, $compositeKey = false, $delimiter = null)
    {
        $name = static::normalizeHeaderName($name);
        return parent::set($name, static::parseHeaderValue($name, $value), $merge, $compositeKey, $delimiter);
    }
  
    /**
     * Removes an HTTP header by its name.
     *
     * @param string $name - the header name.
     * @param boolean $compositeKey - determines whether the key is compound key.
     * @param string $delimiter - the key delimiter in composite keys.
     * @param boolean $removeEmptyParent - determines whether the parent element should be removed if it no longer contains elements after removing the given one.
     * @return static
     * @access public
     */
    public function remove($name, $compositeKey = false, $delimiter = null, $removeEmptyParent = false)
    {
        return parent::remove(static::normalizeHeaderName($name), $compositeKey, $delimiter, $removeEmptyParent);
    }
    
    /**
     * Returns all computed HTTP headers.
     *
     * @return array
     * @access public
     */
    public function getComputedHeaders()
    {
        return static::computeHeaders($this->arr);
    }
  
    /**
     * Returns the content type and/or response charset.
     *
     * @param boolean $withCharset - if TRUE the method returns an array of the following structure ['type' => ..., 'charset' => ...], otherwise only content type will be returned.
     * @return array|string
     * @access public
     */
    public function getContentType($withCharset = false)
    {
        $type = $this['Content-Type'] ?: ['type' => '', 'charset' => ''];
        return $withCharset ? $type : $type['type'];
    }
  
    /**
     * Sets content type header. You can use content type alias instead of some HTTP headers (that are determined by $contentTypeMap property).
     *
     * @param string $type - content type or its alias.
     * @param string $charset - the content charset.
     * @return static
     * @access public
     */
    public function setContentType($type, $charset = null)
    {
        $type = isset(static::$contentTypeMap[$type]) ? static::$contentTypeMap[$type] : $type;
        $this['Content-Type'] = ['type' => $type, 'charset' => $charset === null ? $this->getCharset() : (string)$charset];
        return $this;
    }
  
    /**
     * Returns the response charset.
     * If the Content-Type header with charset is not set the method returns NULL.
     *
     * @return string
     * @access public
     */
    public function getCharset()
    {
        return $this->getContentType(true)['charset'];
    }
  
    /**
     * Sets the response charset.
     *
     * @param string $charset - the response charset.
     * @return static
     * @access public
     */
    public function setCharset($charset = 'UTF-8')
    {
        return $this->setContentType($this->getContentType() ?: 'text/html', $charset);
    }
  
    /**
     * Returns a list of content types acceptable by the client browser.
     * If header "Accept" is not set the methods returns empty array.
     *
     * @return array
     * @access public
     */
    public function getAcceptableContentTypes()
    {
        return $this['Accept'] ?: [];
    }
    
    /**
     * Sets the content types acceptable by the client browser.
     *
     * @param string|array $types - the aceptable content types.
     * @return static
     * @access public
     */
    public function setAcceptableContentTypes($types)
    {
        $this['Accept'] = $types;
        return $this;
    }
  
    /**
     * Checks whether the given cache control directive is set.
     *
     * @param string $directive - the directive name.
     * @return boolean
     * @access public
     */
    public function hasCacheControlDirective($directive)
    {
        return $this->has('Cache-Control.' . $directive);
    }

    /**
     * Returns the value of the given cache control directive.
     * If the directive is not set the method returns NULL.
     *
     * @param string $directive - the directive name.
     * @return mixed
     * @access public
     */
    public function getCacheControlDirective($directive)
    {
        return $this->get('Cache-Control.' . $directive);
    }
  
    /**
     * Sets the value of the given cache control directive.
     *
     * @param string $directive - the directive name.
     * @param mixed $value - the directive value.
     * @return static
     * @access public
     */
    public function setCacheControlDirective($directive, $value = '')
    {
        return $this->set('Cache-Control.' . $directive, $value);
    }
  
    /**
     * Removes the given cache control directive.
     *
     * @param string $directive - the directive name.
     * @return static
     * @access public
     */
    public function removeCacheControlDirective($directive)
    {
        return $this->remove('Cache-Control.' . $directive);
    }
    
    /**
     * Returns cookies.
     *
     * @return array
     * @access public
     */
    public function getCookies()
    {
        return $this->cookies;
    }
    
    /**
     * Returns cookie information.
     *
     * @param string $name - the cookie name.
     * @return array|null
     * @access public
     */
    public function getCookie($name)
    {
        return isset($this->cookies[$name]) ? $this->cookies[$name] : null;
    }
    
    /**
     * Sets a cookie.
     *
     * @param string $name - the name of the cookie.
     * @param array|string - the value of the cookie. It can be an array of all cookie parameters: value, expire, path and so on.
     * @param integer $expire - the time (Unix timestamp) the cookie expires.
     * @param string $path - the path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
     * @param string $domain - the domain that the cookie is available to.
     * @param boolean $secure - indicates that the cookie should only be transmitted over a secure HTTPS connection from the client. When set to TRUE, the cookie will only be set if a secure connection exists.
     * @param boolean $httpOnly - when TRUE the cookie will be made accessible only through the HTTP protocol. This means that the cookie won't be accessible by scripting languages, such as JavaScript.
     * @return static
     * @access public
     */
    public function setCookie($name, $value = null, $expire = 0, $path = null, $domain = null, $secure = false, $httpOnly = false)
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
     * @param string $name - the cookie name.
     * @return static
     * @access public
     */
    public function removeCookie($name)
    {
        unset($this->cookies[$name]);
        return $this;
    }
  
    /**
     * Clears a cookie in the browser.
     *
     * @param string $name - the cookie name.
     * @param string $path - the path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
     * @param string $domain - the domain that the cookie is available to.
     * @param boolean $secure - indicates that the cookie should only be transmitted over a secure HTTPS connection from the client. When set to TRUE, the cookie will only be set if a secure connection exists.
     * @param boolean $httpOnly - when TRUE the cookie will be made accessible only through the HTTP protocol. This means that the cookie won't be accessible by scripting languages, such as JavaScript.
     * @return static
     * @access public
     */
    public function clearCookie($name, $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        return $this->setCookie($name, null, 1, $path, $domain, $secure, $httpOnly);
    }
    
    /**
     * Returns computed cookie values.
     *
     * @return array
     * @access public
     */
    public function getComputedCookies()
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
                if ((int)$data['expire'] !== 0)
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
     * @access public
     */
    public function __toString()
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
        catch (\Exception $e)
        {
            \Aleph::exception($e);
        }
        return $headers;
    }
}