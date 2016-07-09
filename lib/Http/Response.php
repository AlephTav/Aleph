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

use Aleph,
    Aleph\Data,
    Aleph\Data\Converters,
    Aleph\Utils;
    

/**
 * Response Class provides easier interaction with variables of the current HTTP response and to build a new response.
 * This class is clone of Symfony Response class. See https://github.com/symfony/HttpFoundation/blob/master/Response.php
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.1.1
 * @package aleph.http
 */
class Response
{
    /**
     * Error message templates throwing by Response class.
     */
    const ERR_RESPONSE_1 = 'Invalid HTTP version. Must be only 1.0 or 1.1';
    const ERR_RESPONSE_2 = 'Cannot set response status. Status code "%s" is not a valid HTTP response code.';
    const ERR_RESPONSE_3 = 'The response body must be a scalar or object implementing __toString(), "%s" given.';
    const ERR_RESPONSE_4 = 'Cannot redirect to an empty URL.';
  
    /**
     * The instance of this class.
     * 
     * @var \Aleph\Http\Response
     */           
    private static $instance = null;
  
    /**
     * Determines whether the response was sent.
     *
     * @var bool
     */
    private $isSent = false;

    /**
     * The HTTP status codes.
     *
     * @var array
     */
    protected static $codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Checkpoint',
        122 => 'Request-URI too long',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Resume Incomplete',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        420 => 'Enhance Your Calm',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'No Response',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        499 => 'Client Closed Request',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        598 => 'Network read timeout error',
        599 => 'Network connect timeout error'
    ];

    /**
     * The HTTP headers of the request.
     *
     * @var \Aleph\Http\HeaderBag
     */
    public $headers = null;
  
    /**
     * Version of the HTTP protocol.
     *
     * @var string
     */
    protected $version = '1.1';
  
    /**
     * Status code of the HTTP response.
     *
     * @var int
     */
    protected $statusCode = 200;
    
    /**
     * Status text of the HTTP response.
     *
     * @var string
     */
    protected $statusText = '';
  
    /**
     * The HTTP response raw body.
     *
     * @var mixed
     */
    protected $body = null;
    
    /**
     * Used for singleton version of the response.
     *
     * @var static
     */
    private static $response = null;
    
    /**
     * Returns an HTTP status message by its code. 
     * If such message doesn't exist the method returns $default.
     * 
     * @param int $status The HTTP status code.
     * @param mixed $default The default value of the HTTP status text.
     * @return mixed
     */
    public static function getStatusText(int $status, $default = null)
    {
        return isset(static::$codes[$status]) ? static::$codes[$status] : $default;
    }
    
    /**
     * Creates a new response with values from PHP's super globals.
     *
     * @param bool $asSingleton Determines whether the response instance should be stored as singleton.
     * @return static
     */
    public static function createFromGlobals(bool $asSingleton = false)
    {
        if ($asSingleton && self::$response)
        {
            return self::$response;
        }
        $response = new static('', 200, HeaderBag::getResponseHeaders());
        if ($asSingleton)
        {
            self::$response = $response;
        }
        return $response;
    }
  
    /**
     * Constructor.
     *
     * @param mixed $body The response raw body.
     * @param int $status The response status code.
     * @param array $headers An array of the response headers.
     * @return void
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     */
    public function __construct($body = '', int $status = 200, $headers = [])
    {
        $this->headers = new HeaderBag($headers);
        $this->setStatusCode($status);
        $this->setRawBody($body);
    }

    /**
     * Clones the current response instance.
     *
     * @return void
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
    }
    
    /**
     * Returns the response as an HTTP string.
     *
     * @return string
     */
    public function __toString() : string
    {
        $this->prepareHeaders();
        return 'HTTP/' . $this->version . ' ' . $this->statusCode . ' ' . $this->statusText . "\r\n" . $this->headers . "\r\n" . $this->body;
    }
    
    /**
     * Sets the response raw body.
     * Valid types are scalars, null, resource and objects that implement a __toString() method.
     *
     * @param mixed $body
     * @return static
     * @throws \UnexpectedValueException
     */
    public function setRawBody($body)
    {
        if ($body !== null && !is_scalar($body) && !is_callable(array($body, '__toString')))
        {
            throw new \UnexpectedValueException(sprintf(static::ERR_RESPONSE_3, gettype($body)));
        }
        $this->body = $body;
        return $this;
    }
    
    /**
     * Sets the response body.
     * The body will be converted according to the response content type.
     *
     * @param mixed $body
     * @return static
     * @throws \UnexpectedValueException
     */
    public function setBody($body)
    {
        if ($body === null)
        {
            $this->body = null;
            return $this;
        }
        $output = [
            'application/json' => Converters\TextConverter::JSON_ENCODED
        ];
        $type = $this->headers->getContentType(true);
        $converter = new Converters\TextConverter();
        $converter->output = isset($output[$type['type']]) ? $output[$type['type']] : Converters\TextConverter::ANY;
        if ($type['charset'])
        {
            $converter->outputCharset = $type['charset'];
        }
        $this->body = (string)$converter->convert($body);
        return $this;
    }
    
    /**
     * Returns the response raw body.
     *
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * Sets the HTTP protocol version (1.0 or 1.1).
     *
     * @param string $version The HTTP protocol version.
     * @return static
     * @throws \UnexpectedValueException
     */
    public function setVersion(string $version)
    {
        if ($version != '1.0' && $version != '1.1')
        {
            throw new \UnexpectedValueException(static::ERR_RESPONSE_1);
        }
        $this->version = $version;
        return $this;
    }
    
    /**
     * Returns the HTTP protocol version.
     *
     * @return string The HTTP protocol version.
     */
    public function getVersion() : string
    {
        return $this->version;
    }
    
    /**
     * Sets the response status code and status text.
     * If the status text is null it will be automatically populated for the known
     * status codes and left empty otherwise.
     *
     * @param int $code The HTTP status code.
     * @param string|null $text The HTTP status text.
     * @return static
     * @throws \UnexpectedValueException
     */
    public function setStatusCode(int $code, string $text = null)
    {
        $this->statusCode = $code;
        if ($this->isInvalid())
        {
            throw new \UnexpectedValueException(sprintf(static::ERR_RESPONSE_2, $code));
        }
        if ($text === null)
        {
            $this->statusText = static::getStatusText($code, '');
            return $this;
        }
        $this->statusText = $text;
        return $this;
    }
    
    /**
     * Returns the status code for the current response.
     *
     * @return int
     */
    public function getStatusCode() : int
    {
        return $this->statusCode;
    }
    
    /**
     * Sets the response charset.
     *
     * @param string $charset The response charset.
     * @return static
     */
    public function setCharset(string $charset = 'UTF-8')
    {
        $this->headers->setCharset($charset);
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
        return $this->headers->getCharset();
    }
    
    /**
     * Sets content type header. You can use content type alias instead of some HTTP headers.
     *
     * @param string $type The content type or its alias.
     * @param string $charset The content charset.
     * @return static
     */
    public function setContentType(string $type, string $charset = '')
    {
        $this->headers->setContentType($type, $charset);
        return $this;
    }
    
    /**
     * Returns the content type and/or response charset.
     *
     * @param bool $withCharset If it is TRUE the method returns an array of the following structure ['type' => ..., 'charset' => ...], otherwise only content type will be returned.
     * @return string|array
     */
    public function getContentType(bool $withCharset = false)
    {
        return $this->headers->getContentType($withCharset);
    }
    
    /**
     * Sets the "Date" header value.
     *
     * @param string|\DateTimeInterface $date The "Date" header value.
     * @return static
     */
    public function setDate($date = 'now')
    {
        $this->headers['Date'] = new Utils\DT($date, 'UTC');
        return $this;
    }
    
    /**
     * Returns the "Date" header of the response as a Aleph\Utils\DT instance.
     * If the "Date" header is not set or not parseable the method returns a Aleph\Utils\DT object that represents the current time.
     *
     * @return \Aleph\Utils\DT
     */
    public function getDate() : Utils\DT
    {
        if (!isset($this->headers['Date']))
        {
            $this->setDate();
        }
        return $this->headers['Date'];
    }
    
    /**
     * Sets the "Expires" header value.
     * Passing null as value will remove the header.
     *
     * @param string|\DateTimeInterface $date The "Expires" header value.
     * @return static
     */
    public function setExpires($date = null)
    {
        if ($date === null)
        {
            unset($this->headers['Expires']);
        }
        else
        {
            $this->headers['Expires'] = $date;
        }
        return $this;
    }
    
    /**
     * Returns the value of the "Expires" header as a Aleph\Utils\DT instance.
     *
     * @return \Aleph\Utils\DT
     */
    public function getExpires() : Utils\DT
    {
        if ($date = $this->headers['Expires'])
        {
            return $date;
        }
        return Utils\DT::createFromFormat(DATE_RFC2822, 'Sun, 3 Jan 1982 21:30:00 +0000');
    }
    
    /**
     * Returns the response's time-to-live in seconds.
     *
     * @return int
     */
    public function getTTL() : int
    {
        return $this->getMaxAge() - $this->getAge();
    }
  
    /**
     * Sets the response's time-to-live for shared caches.
     * This method adjusts the Cache-Control s-maxage directive.
     *
     * @param int $value The number of seconds.
     * @return static
     */
    public function setTTL(int $value)
    {
        $this->setSharedMaxAge($this->getAge() + $value);
        return $this;
    }
    
    /**
     * Sets the response's time-to-live for private client caches.
     * This method adjusts the Cache-Control max-age directive.
     *
     * @param int $value The number of seconds.
     * @return static
     */
    public function setClientTTL(int $value)
    {
        $this->setMaxAge($this->getAge() + $value);
    }
    
    /**
     * Returns the number of seconds after the time specified in the response's Date
     * header when the response should no longer be considered fresh.
     *
     * @return int
     */
    public function getMaxAge() : int
    {
        if ($this->headers->hasCacheControlDirective('s-maxage'))
        {
            return (int)$this->headers->getCacheControlDirective('s-maxage');
        }
        if ($this->headers->hasCacheControlDirective('max-age'))
        {
            return (int)$this->headers->getCacheControlDirective('max-age');
        }
        return $this->getExpires()->format('U') - $this->getDate()->format('U');
    }
  
    /**
     * Sets the number of seconds after which the response should no longer be considered fresh.
     * This methods sets the Cache-Control max-age directive.
     *
     * @param int $value The number of seconds.
     * @return static
     */
    public function setMaxAge(int $value)
    {
        $this->headers->setCacheControlDirective('max-age', (int)$value);
        return $this;
    }
    
    /**
     * Returns amount of time (in seconds) since the response (or its revalidation) was generated at the origin server.
     *
     * @return int
     */
    public function getAge() : int
    {
        if (null !== $age = $this->headers['Age'])
        {
            return (int)$age;
        }
        return max(time() - $this->getDate()->format('U'), 0);
    }
    
    /**
     * Sets the number of seconds after which the response should no longer be considered fresh by shared caches.
     * This methods sets the Cache-Control s-maxage directive.
     *
     * @param int $value The number of seconds.
     * @return static
     */
    public function setSharedMaxAge(int $value)
    {
        $this->markAsPublic();
        $this->headers->setCacheControlDirective('s-maxage', (int)$value);
        return $this;
    }
    
    /**
     * Returns the Last-Modified HTTP header as a Aleph\Utils\DT instance.
     * The method returns NULL if the header does not exist.
     *
     * @return \Aleph\Utils\DT|null
     */
    public function getLastModified()
    {
        return $this->headers['Last-Modified'];
    }
  
    /**
     * Sets the Last-Modified HTTP header with a DateTime instance.
     * Passing null as value will remove the header.
     *
     * @param string|\DateTimeInterface $date A DateTimeInterface instance or NULL to remove the header.
     * @return static
     */
    public function setLastModified($date = null)
    {
        if ($date === null)
        {
            unset($this->headers['Last-Modified']);
        }
        else
        {
            $this->headers['Last-Modified'] = $date;
        }
        return $this;
    }
    
    /**
     * Sets the ETag value.
     *
     * @param string|null $etag The ETag unique identifier or NULL to remove the header.
     * @param bool $weak Determines whether you want a weak ETag or not.
     * @return static
     */
    public function setETag($etag = null, bool $weak = false)
    {
        if ($etag == null)
        {
            unset($this->headers['ETag']);
        }
        else
        {
            if (strlen($etag) == 0 || $etag[0] != '"')
            {
                $etag = '"' . $etag . '"';
            }
            $this->headers['ETag'] = ($weak ? 'W/' : '') . $etag;
        }
        return $this;
    }
    
    /**
     * Returns the literal value of the ETag HTTP header.
     *
     * @return string|null
     */
    public function getETag()
    {
        return $this->headers['ETag'];
    }
  
    /**
     * Returns cookie information.
     *
     * @param string $name The cookie name.
     * @return array
     */
    public function getCookie(string $name) : array
    {
        return $this->headers->getCookie($name);
    }
    
    /**
     * Sets a cookie.
     *
     * @param string $name The name of the cookie.
     * @param array|string The value of the cookie. It can be an array of all cookie parameters: value, expire, path and so on.
     * @param int $expire The time (Unix timestamp) the cookie expires.
     * @param string $path The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain.
     * @param string $domain The domain that the cookie is available to.
     * @param bool $secure Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client. When set to TRUE, the cookie will only be set if a secure connection exists.
     * @param bool $httpOnly When it is TRUE the cookie will be made accessible only through the HTTP protocol. This means that the cookie won't be accessible by scripting languages, such as JavaScript.
     * @return static
     */
    public function setCookie(string $name, $value = null, int $expire = 0, string $path = '', string $domain = '', bool $secure = false, bool $httpOnly = false)
    {
        $this->headers->setCookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
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
        $this->headers->removeCookie($name);
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
        $this->headers->clearCookie($name, $path, $domain, $secure, $httpOnly);
        return $this;
    }
    
    /**
     * Marks the response as "private".
     * It makes the response ineligible for serving other clients.
     *
     * @return static
     */
    public function markAsPrivate()
    {
        $this->headers->removeCacheControlDirective('public');
        $this->headers->setCacheControlDirective('private');
        return $this;
    }

    /**
     * Marks the response as "public".
     * It makes the response eligible for serving other clients.
     *
     * @return static
     */
    public function markAsPublic()
    {
        $this->headers->setCacheControlDirective('public');
        $this->headers->removeCacheControlDirective('private');
        return $this;
    }
    
    /**
     * Modifies the response so that it conforms to the rules defined for a 304 status code.
     * This sets the status, removes the body, and discards any headers that MUST NOT be included in 304 responses.
     *
     * @return static
     */
    public function markAsNotModified()
    {
        $this->status = 304;
        $this->body = null;
        foreach (['Allow', 'Content-Encoding', 'Content-Language', 'Content-Length', 'Content-MD5', 'Content-Type', 'Last-Modified'] as $header)
        {
            unset($this->headers[$header]);
        }
        return $this;
    }
    
    /**
     * Returns TRUE if the response must be revalidated by caches.
     * This method indicates that the response must not be served stale by a
     * cache in any circumstance without first revalidating with the origin.
     * When present, the TTL of the response should not be overridden to be
     * greater than the value provided by the origin.
     *
     * @return bool It is TRUE if the response must be revalidated by a cache, FALSE otherwise.
     */
    public function mustRevalidate() : bool
    {
        return $this->headers->hasCacheControlDirective('must-revalidate') || $this->headers->hasCacheControlDirective('proxy-revalidate');
    }
    
    /**
     * Marks the response stale by setting the Age header to be equal to the maximum age of the response.
     *
     * @return static
     */
    public function expire()
    {
        if ($this->isFresh())
        {
            $this->headers['Age'] = $this->getMaxAge();
        }
        return $this;
    }
    
    /**
     * Sets cache expire for a response.
     * If cache expire is FALSE or equals 0 then no cache will be set.
     * 
     * @param int $expires The new cache expire time in seconds.
     * @return static
     */
    public function cache(int $expires) 
    {
        if ($expires <= 0) 
        {
            $this->headers->merge([
                'Expires' => 'Sun, 3 Jan 1982 21:30:00 GMT',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0',
                'Pragma' => 'no-cache'
            ]);
        }
        else
        {
            $this->headers['Expires'] = gmdate('D, d M Y H:i:s', $expires) . ' GMT';
            $this->headers['Cache-Control'] = 'max-age=' . ($expires - time());
        }
        return $this;
    }
    
    /**
     * Returns TRUE if the response was sent and FALSE otherwise.
     *
     * @return bool
     */
    public function isSent() : bool
    {
        return $this->isSent || headers_sent();
    }
    
    /**
     * Returns true if the response is worth caching under any circumstance.
     * Responses marked "private" with an explicit Cache-Control directive are considered uncacheable.
     * Responses with neither a freshness lifetime (Expires, max-age) nor cache validator (Last-Modified, ETag) are considered uncacheable.
     *
     * @return bool It is TRUE if the response is worth caching, FALSE otherwise.
     */
    public function isCacheable() : bool
    {
        if (!in_array($this->statusCode, [200, 203, 300, 301, 302, 404, 410]))
        {
            return false;
        }
        if ($this->headers->hasCacheControlDirective('no-store') || $this->headers->getCacheControlDirective('private'))
        {
            return false;
        }
        return $this->isValidateable() || $this->isFresh();
    }
    
    /**
     * Returns TRUE if the response is "fresh".
     * Fresh responses may be served from cache without any interaction with the origin.
     * A response is considered fresh when it includes a Cache-Control/max-age
     * indicator or Expires header and the calculated age is less than the freshness lifetime.
     *
     * @return bool It is TRUE if the response is fresh, FALSE otherwise.
     */
    public function isFresh() : bool
    {
        return $this->getTTL() > 0;
    }
    
    /**
     * Determines if the response validators (ETag, Last-Modified) match a conditional value specified in the Request.
     * If the Response is not modified, it sets the status code to 304 and removes the actual content by calling the setNotModified() method.
     *
     * @param \Aleph\Http\Request $request The current request instance.
     * @return bool It is TRUE if the response validators match the request, FALSE otherwise.
     */
    public function isNotModified(Request $request) : bool
    {
        if (!$request->isMethodSafe())
        {
            return false;
        }
        $notModified = false;
        $lastModified = $this->headers['Last-Modified'];
        $modifiedSince = $request->headers['If-Modified-Since'];
        if ($etags = $request->getETags())
        {
            $notModified = in_array($this->getETag(), $etags) || in_array('*', $etags);
        }
        if ($modifiedSince && $lastModified)
        {
            $notModified = $modifiedSince->getTimestamp() >= $lastModified->getTimestamp() && (!$etags || $notModified);
        }
        if ($notModified)
        {
            $this->markAsNotModified();
        }
        return $notModified;
    }
    
    /**
     * Returns true if the response includes headers that can be used to validate
     * the response with the origin server using a conditional GET request.
     *
     * @return bool It is TRUE if the response is validateable, FALSE otherwise.
     */
    public function isValidateable() : bool
    {
        return isset($this->headers['Last-Modified']) || isset($this->headers['ETag']);
    }

    /**
     * Returns TRUE if the response status code is invalid and FALSE otherwise.
     *
     * @return bool
     */
    public function isInvalid() : bool
    {
        return $this->statusCode < 100 || $this->statusCode >= 600;
    }
    
    /**
     * Determines whether the response is informative.
     *
     * @return bool
     */
    public function isInformational() : bool
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * Determines whether the response is successful.
     *
     * @return bool
     */
    public function isSuccessful() : bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
    
    /**
     * Determines whether the response is a redirect.
     *
     * @return bool
     */
    public function isRedirection() : bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }
    
    /**
     * Determines whether there is a client error.
     *
     * @return bool
     */
    public function isClientError() : bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }
    
    /**
     * Determines whther there is a server side error.
     *
     * @return bool
     */
    public function isServerError() : bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }
    
    /**
     * Determines whther the response is a redirect of some form.
     *
     * @param string $location
     * @return bool
     */
    public function isRedirect(string $location = '') : bool
    {
        return in_array($this->statusCode, [201, 301, 302, 303, 307, 308]) && ($location === '' || $location == $this->headers['Location']);
    }

    /**
     * Determines whether the response is empty.
     *
     * @return bool
     */
    public function isEmpty() : bool
    {
        return in_array($this->statusCode, [204, 304]);
    }
    
    /**
     * Performs an HTTP redirect. This method halts the script execution. 
     *
     * @param string $url An URL to redirect.
     * @param int $status The redirect HTTP status code.
     * @param bool $stop Determines whether to stop the script execution.
     * @return static
     * @throws \InvalidArgumentException
     */
    public function redirect(string $url, int $status = 302, bool $stop = true)
    {
        if (empty($url))
        {
            throw new \InvalidArgumentException(static::ERR_RESPONSE_4);
        }
        $this->setStatusCode($status, $this->statusText);
        $this->headers['Location'] = $url;
        $this->send();
        if ($stop)
        {
            exit;
        }
        return $this;
    }
    
    /**
     * Downloads the given file.
     *
     * @param string $path The full path to the downloading file.
     * @param string $filename The name of the downloading file.
     * @param string $contentType The mime type of the downloading file.
     * @param bool $deleteAfterDownload Determines whether the file should be deleted after download.
     * @param bool $stop Determines whether to stop the script execution.
     * @return static
     */
    public function download(string $path, string $filename = '', string $contentType = '', bool $deleteAfterDownload = false, bool $stop = true)
    {
        if (!$this->getContentType())
        {
            $this->setContentType(mime_content_type($path) ?: 'application/octet-stream');
        }
        $this->body = null;
        $this->cache(false);
        $this->headers['Content-Disposition'] = 'attachment; filename="' . str_replace('"', '\\"', $filename === null ? basename($path) : $filename) . '"';
        $this->headers['Content-Transfer-Encoding'] = 'binary';
        $this->headers['Content-Length'] = filesize($path);
        $this->sendHeaders();
        $output = fopen('php://output', 'wb');
        $input = fopen($path, 'rb');
        stream_copy_to_stream($input, $output);
        fclose($output);
        fclose($input);
        if ($this->deleteAfterDownload)
        {
            unlink($path);
        }
        $this->isSent = true;
        if ($stop)
        {
            exit;
        }
        return $this;
    }
    
    /**
     * Stops the script execution with some message and HTTP status code.
     *
     * @param int $status The response status code.
     * @param mixed $message The response body.
     * @param bool $stop Determines whether to stop the script execution.
     * @return static
     */
    public function stop(int $status = 500, $message = '', bool $stop = true)
    {
        $this->setBody($message);
        $this->setStatusCode($status, $this->statusText);
        return $this->send($stop);
    }
    
    /**
     * Sends HTTP headers and content.
     *
     * @param bool $stop Determines whether to stop the script execution.
     * @return static
     */
    public function send(bool $stop = false)
    {
        $this->sendHeaders();
        $this->sendBody();
        $this->isSent = true;
        if ($stop)
        {
            exit;
        }
        return $this;
    }
    
    /**
     * Prepares the response headers before they are sent to the client.
     *
     * @return static
     */
    protected function prepareHeaders()
    {
        $this->getDate();
        if ($this->isInformational() || $this->isEmpty())
        {
            $this->setRawBody(null);
            unset($this->headers['Content-Type']);
            unset($this->headers['Content-Length']);
        }
        else
        {
            $contentType = $this->getContentType(true);
            if (!$contentType['type'])
            {
                $this->setContentType('text/html', 'UTF-8');
            }
            else if (!$contentType['charset'])
            {
                $this->setContentType($contentType['type'], 'UTF-8');
            }
            if (isset($this->headers['Transfer-Encoding']))
            {
                unset($this->headers['Content-Length']);
            }
        }
        $this->normalizeCacheControlHeader();
        if ($this->getVersion() == '1.0' && $this->hasCacheControlDirective('no-cache'))
        {
            $this->headers['Pragma'] = 'no-cache';
            $this->headers['Expires'] = 'Sun, 3 Jan 1982 21:30:00 GMT';
        }
        return $this;
    }
    
    /**
     * Sends HTTP headers.
     *
     * @return static
     */
    protected function sendHeaders()
    {
        if ($this->isSent())
        {
            return $this;
        }
        $this->prepareHeaders();
        header('HTTP/' . $this->version . ' ' . $this->statusCode . ' ' . $this->statusText, true, $this->statusCode);
        foreach ($this->headers->getComputedHeaders() as $name => $value)
        {
            header($name . ': ' . $value, false, $this->statusCode);
        }
        foreach ($this->headers->getCookies() as $name => $cookie)
        {
            setcookie($name, $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httpOnly']);
        }
        return $this;
    }
    
    /**
     * Sends body for the current web response.
     *
     * @return static
     */
    protected function sendBody()
    {
        Aleph::setOutput($this->body, false);
        return $this;
    }
    
    /**
     * Normalizes value of the Cache-Control header.
     *
     * @return static
     */
    protected function normalizeCacheControlHeader()
    {
        if (!$this->headers['Cache-Control'] && !$this->headers['ETag'] && !$this->headers['Last-Modified'] && !$this->headers['Expires'])
        {
            $value = ['no-cache' => ''];
        }
        else if (!$this->headers['Cache-Control'])
        {
            $value = ['private' => '', 'must-revalidate' => ''];
        }
        $value = $this->headers['Cache-Control'];
        if (!isset($value['public']) && !isset($value['private']))
        {
            if (!isset($value['s-maxage']))
            {
                $value['private'] = '';
            }
        }
        $this->headers['Cache-Control'] = $value;
        return $this;
    }
}