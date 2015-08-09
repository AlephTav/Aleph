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
 * @version 1.0.1
 * @package aleph.net
 */
class Headers
{
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
     * HTTP headers of the server's request or response.
     *
     * @var array $headers
     * @access protected
     */
    protected $headers = [];

    /**
     * Directives of the Cache Control header.
     *
     * @var array $cacheControl
     * @access protected
     */
    protected $cacheControl = [];

    /**
     * Request instance of this class.
     * 
     * @var array $requestInstance
     * @access private
     */           
    private static $requestInstance = null;
    
    /**
     * Response instance of this class.
     * 
     * @var array $responseInstance
     * @access private
     */           
    private static $responseInstance = null;

    /**
     * Clones an object of this class. The private method '__clone' doesn't allow to clone an instance of the class.
     * 
     * @access private
     */
    private function __clone(){}
  
    /**
     * Constructor.
     *
     * @param array $headers - headers of the response or request.
     * @access private
     */
    private function __construct(array $headers)
    {
        $this->headers = $headers;
    }
  
    /**
     * Returns HTTP headers of the current HTTP request.
     *
     * @return self
     * @access public
     * @static
     */
    public static function getRequestHeaders()
    {
        if (self::$requestInstance === null)
        {
            if (function_exists('apache_request_headers'))
            {
                $headers = apache_request_headers();
            }
            else
            {
                $headers = [];
                foreach ($_SERVER as $key => $value) 
                {
                    if (strpos($key, 'HTTP_') === 0) 
                    {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
                    }
                }
            }
            self::$requestInstance = new self($headers);
        }
        return self::$requestInstance;
    }
  
    /**
     * Returns HTTP headers of the server response.
     *
     * @return self
     * @access public
     * @static
     */
    public static function getResponseHeaders()
    {
        if (self::$responseInstance === null)
        {
            $headers = [];
             if (function_exists('apache_response_headers'))
             {
                 $headers = apache_response_headers();
             }
            else
            {
                foreach (headers_list() as $header) 
                {
                    $header = explode(':', $header);
                    $headers[array_shift($header)] = trim(implode(':', $header));
                }
            } 
            self::$responseInstance = new self($headers);
        }
        return self::$responseInstance;
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
     * Returns array of all HTTP headers.
     *
     * @return array
     * @access public
     */
    public function getHeaders()
    {
        return $this->headers;
    }
  
    /**
     * Sets array of all HTTP headers.
     *
     * @param array $headers - new HTTP headers.
     * @access public
     */
    public function setHeaders(array $headers)
    {
        $this->headers = [];
        $this->merge($headers);
    }
  
    /**
     * Adds and merges array of new headers.
     *
     * @param array $headers
     * @access public
     */
    public function merge(array $headers)
    {
        foreach ($headers as $name => $value)
        {
            $this->set($name, $value);
        }
    }
  
    /**
     * Removes all headers.
     *
     * @access public
     */
    public function clean()
    {
        $this->headers = [];
    }
  
    /**
     * Returns TRUE if the HTTP header is defined and FALSE otherwise.
     *
     * @param string $name - the header name.
     * @access public
     */
    public function has($name)
    {
        return isset($this->headers[static::normalizeHeaderName($name)]);
    }
  
    /**
     * Returns value of an HTTP header.
     *
     * @param string $name - the header name.
     * @param mixed $default - the default header value.
     * @return mixed
     * @access public
     */
    public function get($name, $default = null)
    {
        $name = static::normalizeHeaderName($name);
        return isset($this->headers[$name]) ? $this->headers[$name] : $default;
    }
  
    /**
     * Sets an HTTP header.
     *
     * @param string $name - the header name.
     * @param mixed $value - new header value.
     * @access public
     */
    public function set($name, $value)
    {
        $name = static::normalizeHeaderName($name);
        $this->headers[$name] = $value;
        if ($name === 'Cache-Control')
        {
            $this->cacheControl = [];
            preg_match_all('#([a-zA-Z][a-zA-Z_-]*)\s*(?:=(?:"([^"]*)"|([^ \t",;]*)))?#', $value, $matches, PREG_SET_ORDER);
            foreach ($matches as $match)
            {
                $this->cacheControl[strtolower($match[1])] = isset($match[3]) ? $match[3] : (isset($match[2]) ? $match[2] : true);
            }
        }
    }
  
    /**
     * Removes an HTTP header by its name.
     *
     * @param string $name - the header name.
     * @access public
     */
    public function remove($name)
    {
        $name = static::normalizeHeaderName($name);
        unset($this->headers[$name]);
        if ($name === 'Cache-Control')
        {
            $this->cacheControl = [];
        }
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
        @list($type, $charset) = explode(';', $this->get('Content-Type'));
        $type = trim($type);
        if ($withCharset)
        {
            $charset = explode('=', $charset);
            return [
                'type' => $type,
                'charset' => isset($charset[1]) ? trim($charset[1]) : ''
            ];
        }
        return $type;
    }
  
    /**
     * Sets content type header. You can use content type alias instead of some HTTP headers (that are determined by $contentTypeMap property).
     *
     * @param string $type - content type or its alias.
     * @param string $charset - the content charset.
     * @access public
     */
    public function setContentType($type, $charset = null)
    {
        $type = isset(static::$contentTypeMap[$type]) ? static::$contentTypeMap[$type] : $type;
        $this->headers['Content-Type'] = $type . ($charset !== null ? '; charset=' . $charset : '');
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
     * @access public
     */
    public function setCharset($charset = 'UTF-8')
    {
        $this->setContentType($this->getContentType() ?: 'text/html', $charset);
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
        return preg_split('/\s*(?:,*("[^"]+"),*|,*(\'[^\']+\'),*|,+)\s*/', $this->get('Accept'), 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    }
  
    /**
     * Returns the given date header as a DateTime instance.
     * If the date header is not set or not parseable the method returns FALSE.
     *
     * @param string $name - the date header name.
     * @return Aleph\Utils\DT|boolean
     * @access public
     */
    public function getDate($name)
    {
        $name = static::normalizeHeaderName($name);
        if (isset($this->headers[$name]))
        {
            return Utils\DT::createFromFormat(\DateTime::DATE_RFC2822, $this->headers[$name]);
        }
        return false;
    }
  
    /**
     * Sets value of the given date header.
     *
     * @param string $name - the date header name.
     * @param string|\DateTimeInterface $date - the date header value.
     * @access public
     */
    public function setDate($name, $date = 'now')
    {
        $date = new Utils\DT($date);
        $date->setTimezone('UTC');
        $date = $date->format(\DateTime::DATE_RFC2822);
        $this->headers[static::normalizeHeaderName($name)] = $date;
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
        return array_key_exists($directive, $this->cacheControl);
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
        return $this->hasCacheControlDirective($directive) ? $this->cacheControl[$directive] : null;
    }
  
    /**
     * Sets the value of the given cache control directive.
     *
     * @param string $directive - the directive name.
     * @param mixed $value - the directive value.
     * @access public
     */
    public function setCacheControlDirective($directive, $value = true)
    {
        $this->cacheControl[$directive] = $value;
        $this->headers['Cache-Control'] = $this->formCacheControlHeader();
    }
  
    /**
     * Removes the given cache control directive.
     *
     * @access public
     */
    public function removeCacheControlDirective($directive)
    {
        unset($this->cacheControl[$directive]);
        $this->headers['Cache-Control'] = $this->formCacheControlHeader();
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
        foreach ($this->headers as $name => $value)
        {
            $headers .= $name . ': ' . $value . "\r\n";
        }
        return $headers;
    }
  
    /**
     * Forms new Cache-Control header value.
     *
     * @return string
     * @access protected
     */
    protected function formCacheControlHeader()
    {
        $tmp = [];
        foreach ($this->cacheControl as $directive => $value)
        {
            if ($value === true)
            {
                $tmp[] = $directive;
            }
            else 
            {
                if (preg_match('#[^a-zA-Z0-9._-]#', $value))
                {
                    $value = '"' . $value . '"';
                }
                $tmp[] = $directive . '=' . $value;
            }
        }
        return implode(', ', $tmp);
    }
}